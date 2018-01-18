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
class ReadmeHelpMarkdownConverter implements ReadmeHelpInterface {

  use StringTranslationTrait;

  /**
   * The allowed tags.
   *
   * @var array
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
  public $moduleHandler;

  /**
   * The request context.
   *
   * @var \Drupal\Core\Routing\RequestContext
   */
  public $requestContext;

  /**
   * The app root.
   *
   * @var string
   */
  public $root;

  /**
   * The filter plugin manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $filterManager;

  /**
   * Constructs a new ReadmeHelpMarkdownConverter object.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Routing\RequestContext $request_context
   *   The request context.
   * @param \Drupal\Component\Plugin\PluginManagerInterface $filter_manager
   *   The filter manager.
   * @param string $app_root
   *   The app root.
   */
  public function __construct(ModuleHandlerInterface $module_handler, RequestContext $request_context, PluginManagerInterface $filter_manager, $app_root) {
    $this->moduleHandler = $module_handler;
    $this->requestContext = $request_context;
    $this->filterManager = $filter_manager;
    $this->root = $app_root;
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
    $root = $this->root;
    $files = static::READMEHELP_FILES;
    // Allow files from directories other than a module root folder.
    if (is_file($file)) {
      $files = ', ' . basename($file);
      $dir = dirname($file);
    }
    if (isset($dir) || (is_dir($file) && $dir = $file)) {
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
        ':href' => '/admin/help/readmehelp',
      ]);
    }

    $text = $this->convertMarkdownText($text, 'en', $path);
    // The snippets should be inserted the last because Xss::filter() strips
    // css style attribute which is inserted by PHP highlight_file() function.
    // Note that output of this function is safe to print on a page because any
    // HTML tags found in the file to highlight are escaped to HTML entities.
    $text = $this->insertPhpSnippets($text, $path);
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
   *   The path to a module's directory. Example: modules/contrib/my_module.
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
   * @param string $path
   *   (optional) The relative path to the module's folder.
   *
   * @return string
   *   The text with HTML markup.
   *
   * @see ::highlightPhp()
   */
  public function insertPhpSnippets($text, $path = '') {
    $root = $this->root;
    return preg_replace_callback('{([^\s])(@PHPFILE:).*?(:PHPFILE@)}s', function ($matches) use ($root, $path) {
      $pattern = '/(\ *@PHPFILE)|(PHPFILE@\ *)|(\ *LINE)|(\ *PADD)/';
      $match = explode(':', preg_replace($pattern, '', $matches[0]));
      $prefix = '';
      if (isset($match[1]) && $file = $orig = trim($match[1])) {
        $line = isset($match[2]) ? (int) trim($match[2]) : 1;
        $padding = isset($match[3]) ? (int) trim($match[3]) : 10;
        $prefix = $match[0];
        $file = $file[0] == '/' ? $file : "$root/$file";
        if (!is_file($file)) {
          $name = basename($orig);
          $file = "$root/$path/$name";
        }
      }

      return $prefix . $this->highlightPhp($file, $line, $padding);
    }, $text);
  }

  /**
   * Highlights PHP file.
   *
   * @param string $file
   *   The absolute path to the file.
   * @param int $line_number
   *   The line number to put in the middle of the snippet.
   * @param int $padding
   *   The number of lines to add before and after the $line_number.
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
      return "<span class=\"readmehelp-error\">CAN'T BE READ:</span> $file LINE: $line_number PADD: $padding";
    }

    // An example how to change css on the highlighted code.
    ini_set('highlight.comment', '#CCCCCC; font-style: oblique; color: #a48bad;');
    $highlighted = highlight_file($file, TRUE);

    $padding = is_int($padding) && $padding > 0 ? $padding : 10;
    $valid = is_int($line_number) && $line_number > 0;
    $line_number = $valid ? $line_number : 0;
    if (!$valid) {
      $start = 0;
      $end = 10000;
    }
    else {
      $start = $line_number - $padding;
      $start = $start > 0 ? $start : 1;
      $end = $line_number + $padding;
    }
    $highlighted = preg_replace('{(^<code>)|(</code>$)}s', '', $highlighted);
    $source = explode('<br />', $highlighted);
    $count = count($source);
    $code_line = NULL;
    $number = $line = [];

    foreach ($source as $key => $value) {
      $code_line = $key + 1;
      if (empty($value)) {
        $value = "<span></span>";
      }
      if ($code_line >= $start && $code_line <= $end && $code_line < $count) {
        $number[$code_line] = "<span class=\"line-number\">$code_line</span>";
        $line[$code_line] = $value;
        if ($code_line == $line_number) {
          $line[$code_line] = "<strong style=\"background-color:yellow\">$value</strong>";
        }
      }
    }

    $numbers = implode('<br>', $number);
    $lines = implode('<br>', $line);
    $snippet = "<table class=\"highlighted-snippet\"><tr><td>$numbers</td><td>$lines</td></tr></table>";

    return $markup ? Markup::create($snippet) : $snippet;
  }

}
