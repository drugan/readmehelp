<?php

namespace Drupal\readmehelp;

use Drupal\Core\Render\Markup;
use Drupal\Component\Utility\Xss;
use Drupal\Component\Utility\Html;
use Drupal\Core\Routing\RequestContext;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Default implementation of the ReadmeHelpMarkdownConverter.
 */
class ReadmeHelpMarkdownConverter {

  use StringTranslationTrait;

  /**
   * The allowed tags.
   */
  protected $tags = [
    'a',
    'em',
    'strong',
    'cite',
    'blockquote',
    'code',
    'ul',
    'ol',
    'li',
    'dl',
    'dt',
    'dd',
    'img',
    'h1',
    'h2',
    'h3',
    'h4',
    'h5',
    'h6',
    'p',
    'pre',
    'hr',
    'table',
    'tr',
    'td',
    'div',
    'span',
  ];

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The request context.
   *
   * @var \Drupal\Core\Routing\RequestContext
   */
  protected $requestContext;

    /**
   * The filter plugin manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $filterManager;

  /**
   * Constructs a new ReadmeHelpMarkdownConverter object.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface
   *   The module handler.
   * @param \Drupal\Core\Routing\RequestContext $request_context
   *   The request context.
   */
  public function __construct(ModuleHandlerInterface $module_handler, RequestContext $request_context, PluginManagerInterface $filter_manager) {
    $this->moduleHandler = $module_handler;
    $this->requestContext = $request_context;
    $this->filterManager = $filter_manager;
  }

  /**
   * Converts markdown into HTML markup in a file.
   *
   * If the second file argument is not passed then any of the README.md or
   * README.txt or README files will be looked for in a module's root directory.
   * The markdown is converted by the "Convert markdown into markup" filter and
   * then run through "Convert line breaks into HTML" and "Convert URLs into
   * links" filters. Finally, \Drupal\Component\Utility\Xss::filter() is
   * applyed using the class::tags property as the set of allowed tags.
   *
   * The second argument might be either an absolute path to a file or
   * a directory where README files could be found.
   *
   * @param string $module_name
   *   The name of the module where README file to look for.
   * @param string $file
   *   (optional) The alternative directory or file path.
   *
   * @return string|\Drupal\Component\Render\MarkupInterface
   *   A safe string.
   *
   * @see ::convertMarkdownText()
   * @see ::highlightPhp()
   * @see ::insertPhpSnippets()
   */
  public function convertMarkdownFile($module_name, $file = NULL) {
    $text = '';
    $host = $this->requestContext->getCompleteBaseUrl();
    $files = READMEHELP_FILES;
    // Allow files from directories other than a module root folder.
    if (is_file($file)) {
      $files = ', ' . basename($file);
      $dir = dirname($file);
    }
    if (isset($dir) || (is_dir($file) && $dir = $file)) {
      $root = \Drupal::root();
      $path = str_replace($root, '', $dir);
      // Seems that $dir passed is the relative to $root one.
      if ($path == $dir) {
        $path = trim($path, '/');
        $dir = "$root/$path";
      }
    }
    else {
      $path = $this->moduleHandler->getModule($module_name)->getPath();
      $dir = $this->moduleHandler->getModuleDirectories()[$module_name];
    }
    $path = trim($path, '/');

    foreach (explode(', ', $files) as $readme) {
      if (file_exists("$dir/$readme")) {
        if ($text = file_get_contents("$dir/$readme")) {
          break;
        }
      }
    }
    if (!$text) {
      return $this->t('None of the %files files is found in the %dir folder or their content is empty. Please, <a href=":href">README</a>.', [
        '%files' => $files,
        '%dir' => $dir,
        ':href' => \Drupal::url('help.page', ['name'=>'readmehelp']),
      ]);
    }

    $text = $this->convertMarkdownText($text, 'en', $path);
    // The snippets should be inserted the last because Xss::filter() strips
    // css style attribute which is inserted by PHP \highlight_file() function.
    // Note that output of this function is safe to print on a page because any
    // HTML tags found in the file to highlight are escaped to HTML entities.
    $text = $this->insertPhpSnippets($text);
    $name = Html::getClass($module_name);
    $readme = "<h3 class=\"readmenelp-heading\">$readme</h3>";
    $markup = "$readme<article class=\"markdown-body $name-readmehelp\">$text</article>";

    return Markup::create($markup);
  }

  /**
   * Converts markdown to markup.
   *
   * @param string $text
   *   The text string to be filtered.
   * @param string $language
   *   The language code to use for filtering.
   * @param string $file_path
   *   The path to a module's directory. Example: modules/contrib/my_module
   *
   * @return string
   *   The text with HTML markup.
   *
   * @see \Drupal\readmehelp\Plugin\Filter\ReadmehelpMarkdown
   * @see \Drupal\filter\Plugin\Filter\FilterAutoP
   * @see \Drupal\filter\Plugin\Filter\FilterUrl
   * @see \Drupal\Component\Utility\Html::normalize()
   * @see \Drupal\Component\Utility\Xss::filter()
   */
  public function convertMarkdownText($text, $language = 'en', $file_path = NULL) {
    $text = $this->filterManager
      ->createInstance('readmehelp_markdown')
      ->process($text, $language, $file_path)
      ->getProcessedText();

    $text = $this->filterManager
      ->createInstance('filter_autop')
      ->process($text, $language)
      ->getProcessedText();

    $filter_url = $this->filterManager->createInstance('filter_url');
    $text = $filter_url->setConfiguration($filter_url->defaultConfiguration())
      ->process($text, $language)
      ->getProcessedText();

    // Does the same as the filter_htmlcorrector.
    $text = Html::normalize($text);
    $text = $text ? Xss::filter($this->t($text), $this->tags) : '';

    return $text;
  }

  /**
   * Replaces PHP file tokens with a snippet from the file.
   *
   * The LINE and PADD arguments in the token are optional. Use them to
   * highligth a particular LINE in the snippet having PADD lines added before
   * and after the line. If omitted then the whole PHP file is highlighted.
   * @code
   * @PHPFILE: /absolute/path/to/MY_FILE.php LINE:123 PADD:10 :PHPFILE@
   *
   * ## If PADD is omitted then by default 10 lines are added.
   * @PHPFILE: related/to/Drupal/root/path/MY_FILE.php LINE:123 :PHPFILE@
   *
   * @param string $text
   *   The text string to be filtered.
   *
   * @return string
   *   The text with HTML markup.
   *
   * @see ::highlightPhp()
   */
  public function insertPhpSnippets($text) {
    return preg_replace_callback('{([^\s])(@PHPFILE:).*?(:PHPFILE@)}s', function ($matches) {
      $pattern = '/(\ *@PHPFILE)|(PHPFILE@\ *)|(\ *LINE)|(\ *PADD)/';
      $match = explode(':', preg_replace($pattern, '', $matches[0]));
      $prefix = '';
      if (isset($match[1]) && $file = trim($match[1])) {
        $line = isset($match[2]) ? (int) trim($match[2]) : 1;
        $padding = isset($match[3]) ? (int) trim($match[3]) : 10;
        $file = $file[0] == '/' ? $file : \Drupal::root() . "/$file";
        $prefix = $match[0];
      }

      return $prefix . $this->highlightPhp($file, $line, $padding);
    }, $text);
  }

  /**
   * Highlights PHP file.
   *
   * @param string $file
   *   The absolute path to the file.
   *
   * @param int $line_number
   *   The line number to put in the middle of the snippet.
   *
   * @param int $padding
   *   The number of lines to add before and after the $line_number.
   *
   * @param bool $markup
   *   Whether to return the markup object instead of a string.
   *
   * @return string|\Drupal\Component\Render\MarkupInterface
   *   A safe highlighted snippet.
   *
   * @see http://php.net/manual/en/function.highlight-file.php
   * @see http://php.net/manual/en/function.highlight-string.php
   * @todo add css & js files support.
   */
  public function highlightPhp($file, $line_number, $padding, $markup = FALSE) {

    if (!$file || !is_readable($file)) {
      return "CAN'T BE READ: $file LINE: $line_number PADD: $padding";
    }

    // An example how to change css on the highlighted code.
    ini_set('highlight.comment', '#CCCCCC; font-style: oblique; color: #a48bad;');
    $source = explode( '<br />', highlight_file($file, TRUE));
    $index = 0;
    $padding = $padding && is_int($padding) > 0 ? $padding : 10;
    if ($line_number && is_int($line_number)) {
      $line_number = $line_number - 1;
      $index = $line_number < 0 ? 0 : $line_number;
    }
    else {
      $line_number = -1;
      $index = 0;
      // Just to ensure that the whole file is highlighted.
      $padding = 10000;
    }
    $lines_to_show = ($padding * 2) + 1;
    $lines_to_show = $lines_to_show > count($source) ? count($source) : $lines_to_show;
    $start = ($index - $padding) < 1 ? 1 : $index - $padding;
    $source[$index] = $line_number < 0 ? $source[$index] :
    "<strong style='background-color:yellow'>{$source[$index]}</strong>";
    $slice = array_slice($source, $start++, $lines_to_show);
    $digits = strlen($start + $lines_to_show);

    foreach ($slice as $row) {
      $code[] = '<div style="display:inline-block;color:black; width:' . $digits . 'ch; text-align:right;">' . ($start++) . '</div> ' . $row;
    }
    $php = '<div style="color: #007700">&lt;php?</div>';
    $snippet = '<div class="highlighted-snippet">' . $php . implode('<br>', $code) . '</div>';

    return $markup ? Markup::create($snippet) : $snippet;
  }

}
