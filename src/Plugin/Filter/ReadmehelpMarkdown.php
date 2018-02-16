<?php

namespace Drupal\readmehelp\Plugin\Filter;

use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;
use Drupal\filter\Plugin\FilterBase;
use Drupal\filter\FilterProcessResult;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Unicode;

/**
 * Provides a filter for markdown.
 *
 * @Filter(
 *   id = "readmehelp_markdown",
 *   module = "readmehelp",
 *   title = @Translation("Convert markdown into markup"),
 *   description = @Translation("Converts basic markdown elements (like in README files) into HTML markup."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_MARKUP_LANGUAGE,
 *   settings = {
 *     "quick_tips" = "",
 *   },
 *   weight = -100
 * )
 */
class ReadmehelpMarkdown extends FilterBase {

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form['quick_tips'] = [
      '#type' => 'item',
      '#title' => $this->t('Quick tips'),
      '#description' => $this->t('You can use <a href=":readmehelp" name=":name">markdown syntax</a> like in README files to format and style the text. This syntax is a subset of the <a href=":github">Github Flavoured Markdown</a>. Note that this filter will always be kept at the top. After the filter it is recommended to place "Convert line breaks into HTML" and "Limit allowed HTML tags and correct faulty HTML" filters. The tags that should be allowed for proper working of the markdown filter are the following: &lt;a href hreflang&gt; &lt;em&gt; &lt;strong&gt; &lt;cite&gt; &lt;blockquote cite&gt; &lt;code&gt; &lt;ul type&gt; &lt;ol start type&gt; &lt;li&gt; &lt;h1 id&gt; &lt;h2 id&gt; &lt;h3 id&gt; &lt;h4 id&gt; &lt;h5 id&gt; &lt;h6 id&gt; &lt;p&gt; &lt;br&gt; &lt;pre&gt; &lt;hr&gt; &lt;img src alt data-entity-type data-entity-uuid&gt;. When using this filter with the CKEditor you need to press [Source] button on the editor while editing the markdown text. This is because markdown symbols are actually the source code similar to HTML tags. It is recommended to use this filter without any Rich Text Editor enabled on a text format.', [
        ':readmehelp' => Url::fromRoute('help.page', ['name' => $this->provider])->toString(),
        ':github' => 'https://github.com/adam-p/markdown-here/wiki/Markdown-Cheatsheet',
        ':name' => 'readmehelp-filter',
      ]),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    if (isset($configuration['status'])) {
      $this->status = (bool) $configuration['status'];
    }
    if (isset($configuration['weight'])) {
      // Always run this filter as the very first.
      $this->weight = -100;
    }
    if (isset($configuration['settings'])) {
      $this->settings = (array) $configuration['settings'];
    }

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function tips($long = FALSE) {
    return $this->t('You can use <a href=":readmehelp" name=":name">markdown syntax</a> like in README files to format and style the text. This syntax is a subset of the <a href=":github">Github Flavoured Markdown</a>.', [
      ':readmehelp' => Url::fromRoute('help.page', ['name' => $this->provider])->toString(),
      ':github' => 'https://github.com/adam-p/markdown-here/wiki/Markdown-Cheatsheet',
      ':name' => 'readmehelp-filter',
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode, $file_path = NULL) {
    if (!empty($text)) {
      $request = \Drupal::request();
      $path = $request->getPathInfo();
      $host = str_replace($path, '', $request->getSchemeAndHttpHost() . $request->getRequestUri());
      $path = $file_path ? trim($file_path, '/') : trim($path, '/');

      $text = $text . "\n";
      $text = preg_replace('/\r\n?/', '\n', $text);
      $text = preg_replace('/\t/', '  ', $text);
      $text = $this->tokenizeEscapedSpecialChars($text);
      $text = $this->convertLeadingDashSpace($text);
      $text = $this->convertLeadingNumberDotSpace($text);
      $text = $this->convertLeadingGreaterThanSign($text);
      $text = $this->convertLeadingHash($text);
      $text = $this->convertLeadingMultiDashAsteriskUnderscore($text);
      $text = $this->convertMultiEqualitySign($text);
      $text = $this->convertMultiDashSign($text);
      $text = $this->convertDoubleAsterisk($text);
      $text = $this->convertSingleAsterisk($text);
      $text = $this->convertDoubleUnderscore($text);
      $text = $this->convertSingleUnderscore($text);
      $text = $this->convertMarkdownImage($text, $host, $path);
      $text = $this->convertMarkdownLink($text, $host);
      $text = $this->convertTripleBacktick($text);
      $text = $this->convertSingleBacktick($text);
      $text = $this->detokenizeSpecialChars($text);
    }

    return new FilterProcessResult($text);
  }

  /**
   * Tokenizes escaped markdown special symbols.
   *
   * @param string $text
   *   The text string to be filtered.
   *
   * @return string
   *   The text with tokenized special symbols.
   */
  public function tokenizeEscapedSpecialChars($text) {
    return preg_replace_callback('/([\\\\])(.)/xs', function ($matches) {
      $match = '';
      if ($match = !empty($matches[2]) ? $matches[2] : '') {
        if ($match == '*') {
          $match = "@THEASTERISK@";
        }
        elseif ($match == '_') {
          $match = "@THEUNDERSCORE@";
        }
        elseif ($match == '`') {
          $match = "@THEBACKTICK@";
        }
        elseif ($match == '#') {
          $match = "@THEHASH@";
        }
        elseif ($match == "-") {
          $match = '@THEDASH@';
        }
        elseif ($match == '(') {
          $match = "@THELEFTPAREN@";
        }
        elseif ($match == ")") {
          $match = '@THERIGHTPAREN@';
        }
        elseif ($match == "[") {
          $match = '@THELEFTBRACKET@';
        }
        elseif ($match == "]") {
          $match = '@THERIGHTBRACKET@';
        }
        elseif ($match == " ") {
          $match = '@THESPACE@';
        }
        elseif ($match == ">") {
          $match = '@THEGREATERTHAN@';
        }
        else {
          $match = $matches[0];
        }
      }
      return $match;
    }, $text);
  }

  /**
   * Changes tokens for markdown special symbols.
   *
   * @param string $text
   *   The HTML string to be filtered.
   *
   * @return string
   *   The text with the symbols without special meaning.
   */
  public function detokenizeSpecialChars($text) {
    $pattern = '/(@THEASTERISK@)|(@THEUNDERSCORE@)|(@THEBACKTICK@)|(@THEHASH@)|(@THEDASH@)|(@THELEFTPAREN@)|(@THERIGHTPAREN@)|(@THELEFTBRACKET@)|(@THERIGHTBRACKET@)|(@THESPACE@)|(@THEGREATERTHAN@)/xs';

    return preg_replace_callback($pattern, function ($matches) {
      $match = '';
      if ($match = !empty($matches[0]) ? $matches[0] : '') {
        if ($match == '@THEASTERISK@') {
          $match = "*";
        }
        elseif ($match == '@THEUNDERSCORE@') {
          $match = "_";
        }
        elseif ($match == '@THEBACKTICK@') {
          $match = "`";
        }
        elseif ($match == '@THEHASH@') {
          $match = "#";
        }
        elseif ($match == '@THEDASH@') {
          $match = "-";
        }
        elseif ($match == '@THELEFTPAREN@') {
          $match = "(";
        }
        elseif ($match == '@THERIGHTPAREN@') {
          $match = ")";
        }
        elseif ($match == '@THELEFTBRACKET@') {
          $match = "[";
        }
        elseif ($match == '@THERIGHTBRACKET@') {
          $match = "]";
        }
        elseif ($match == '@THESPACE@') {
          $match = " ";
        }
        elseif ($match == '@THEGREATERTHAN@') {
          $match = ">";
        }
      }
      return $match;
    }, $text);
  }

  /**
   * Wraps leading "- " text lines into unordered list tag.
   *
   * @param string $text
   *   The string to be filtered.
   *
   * @return string
   *   The HTML source with unordered list(s).
   */
  public function convertLeadingDashSpace($text) {
    return preg_replace_callback('/(?(DEFINE)(?<emptyline>(\n\n)))((\n- )(.*?))(?&emptyline)/s',
    function ($matches) {
      $match = '';
      if ($match = !empty($matches[0]) ? $matches[0] : '') {
        $items = '';
        foreach (explode('- ', $match) as $item) {
          if ($item = trim($item)) {
             $items .= "<li>$item</li>";
          }
        }
        $match = $items ? "\n<ul class=\"ul\">$items</ul>\n" : $match;
      }

      return $match;
    }, $text);
  }

  /**
   * Wraps leading "NUMBER. " text lines into ordered list tag.
   *
   * @param string $text
   *   The string to be filtered.
   *
   * @return string
   *   The HTML source with ordered lists.
   */
  public function convertLeadingNumberDotSpace($text) {
    return preg_replace_callback('/(?(DEFINE)(?<emptyline>(\n\n)))(\n\d\.\ )(.*?)(?&emptyline)/s',
    function ($matches) {
      $match = '';
      if ($match = !empty($matches[0]) ? $matches[0] : '') {
        $match = preg_replace('/(^|\n)\d\.\ /s', '<LISTITEM>', $match);
        $items = '';
        foreach (explode('<LISTITEM>', $match) as $item) {
          if ($item = trim($item)) {
             $items .= "<li>$item</li>";
          }
        }
        $match = $items ? "\n<ol class=\"ol\">$items</ol>\n" : $match;
      }

      return $match;
    }, $text);
  }

  /**
   * Wraps leading "> " or ">> " text lines into blockquote or cite tag.
   *
   * Additionally a leading sign turns into anchor link, so the blockquote can
   * be used as a local or external link target. Multiple subsequent leading
   * "> " or ">> " text lines can be used to wrap them into one tag.
   *
   * @param string $text
   *   The string to be filtered.
   *
   * @return string
   *   The HTML source with blockquote or cite blocks.
   */
  public function convertLeadingGreaterThanSign($text) {
    return preg_replace_callback('/(?(DEFINE)(?<emptyline>(\n\n)))(\n>>? )(\w.*?)(?&emptyline)/s', function ($matches) {
      $match = '';
      if ($match = !empty($matches[4]) ? preg_replace('/\n>>? /', '', $matches[4]) : '') {
        $id = trim(Html::getUniqueId(Unicode::truncate($match, 32, TRUE)), '-');
        if (trim($matches[3]) == '>>') {
          $tag = 'cite';
          $sign = '>> ';
        }
        else {
          $tag = 'blockquote';
          $sign = '> ';
        }
        $match = "\n<$tag><a id=\"$id\" href=\"#$id\" class=\"anchor\">$sign</a>$match</$tag>\n";
      }
      return $match;
    }, $text);
  }

  /**
   * Wraps line followed with multi "=" sign line into h1 tag.
   *
   * Can be used just once at the very beginning of a text. Additionally "#"
   * sign turned into anchor link, so the heading can be used as a local or
   * externallink target.
   *
   * @param string $text
   *   The string to be filtered.
   *
   * @return string
   *   The HTML source with the heading.
   */
  public function convertMultiEqualitySign($text) {
    return preg_replace_callback('/^(\w.*?)\n=+\n/',
    function ($matches) {
      if ($match = !empty($matches[1]) ? rtrim($matches[1]) : '') {
        $id = trim(Html::getUniqueId($match), '-');
        $match = "<h1><a id=\"$id\" href=\"#$id\" class=\"anchor\"># </a>$match</h1>";
      }
      return $match;
    }, $text);
  }

  /**
   * Wraps line followed with multi "-" sign line into h2 tag.
   *
   * Additionally "#" sign turned into anchor link, so the heading can be used
   * as a local or external link target.
   *
   * @param string $text
   *   The string to be filtered.
   *
   * @return string
   *   The HTML source with the heading.
   */
  public function convertMultiDashSign($text) {
    return preg_replace_callback('/\n(\w.*?)\n\-+\n/',
    function ($matches) {
      if ($match = !empty($matches[1]) ? rtrim($matches[1]) : '') {
        $id = trim(Html::getUniqueId($match), '-');
        $match = "<h2 class=\"h-2\"><a id=\"$id\" href=\"#$id\" class=\"anchor\"># </a>$match</h2>";
      }
      return $match;
    }, $text);
  }

  /**
   * Wraps lines preceded with up to 6 "#" + " " text lines into h1-6 tag.
   *
   * The single "# " can be used just once at the very beginning of a text.
   * Other hash signs + space sets can be used multiple times in a text.
   * Additionally, "#" sign turns into anchor link, so the heading can be used
   * as a local or external link target.
   *
   * @param string $text
   *   The string to be filtered.
   *
   * @return string
   *   The HTML source with headings.
   */
  public function convertLeadingHash($text) {
    foreach (range(1, 6) as $i) {
      $hash = str_pad('#', $i, "#");
      $j = $i > 1 ? '\n' . $hash : '^' . $hash;

      $text = preg_replace_callback('/' . $j . '(\s+\w.*?)\n/',
      function ($matches) use ($i) {
        if ($match = !empty($matches[1]) ? rtrim($matches[1]) : '') {
          $id = trim(Html::getUniqueId($match), '-');
          $match = "<h$i class=\"h-$i\"><a id=\"$id\" href=\"#$id\" class=\"anchor\">#</a>$match</h$i>";
        }
        return $match;
      }, $text);
    }

    return $text;
  }

  /**
   * Replaces "*", "-" and "_" sign sequences by a ruler tag.
   *
   * At least three signs should placed on a line wrapped into two empty lines.
   * The "___" turns into a slim ruler tag, the "***" turns into middle one
   * and "---" into fat ruler tag.
   *
   * @param string $text
   *   The string to be filtered.
   *
   * @return string
   *   The HTML source with the rulers.
   */
  public function convertLeadingMultiDashAsteriskUnderscore($text) {
    $pattern = '/(\n\n)(\-\-\-+\n+)|(\*\*\*+\n+)|(\_\_\_+\n+)(\n)/xs';

    return preg_replace_callback($pattern, function ($matches) {
      $match = '';
      if ($match = !empty($matches[0]) ? $matches[0] : '') {
        if (strstr($match, '___')) {
          $class = 'underscore';
        }
        elseif (strstr($match, '***')) {
          $class = 'asterisk';
        }
        else {
          $class = 'dash';
        }
        $match = "\n\n<hr class=\"hr-$class\">\n\n";
      }
      return $match;
    }, $text);
  }

  /**
   * Replaces pairs of "*" signs by the em tag.
   *
   * @param string $text
   *   The string to be filtered.
   *
   * @return string
   *   The HTML source with emphasized text.
   */
  public function convertSingleAsterisk($text) {
    return preg_replace_callback('/(\*[^*]*?\*)/', function ($matches) {
      $match = '';
      if ($match = !empty($matches[0]) ? $matches[0] : '') {
        $match = "<em>" . str_replace('*', '', $match) . "</em>";
      }
      return $match;
    }, $text);
  }

  /**
   * Replaces pairs of "**" signs by the strong tag.
   *
   * @param string $text
   *   The string to be filtered.
   *
   * @return string
   *   The HTML source with emphasized text.
   */
  public function convertDoubleAsterisk($text) {
    return preg_replace_callback('/(\*\*[^*]*?\*\*)/', function ($matches) {
      $match = '';
      if ($match = !empty($matches[0]) ? $matches[0] : '') {
        $match = "<strong>" . str_replace('*', '', $match) . "</strong>";
      }
      return $match;
    }, $text);
  }

  /**
   * Replaces pairs of "_" signs by the em tag.
   *
   * @param string $text
   *   The string to be filtered.
   *
   * @return string
   *   The HTML source with emphasized text.
   */
  public function convertSingleUnderscore($text) {
    return preg_replace_callback('/(\s)(_[^_]*?_)/', function ($matches) {
      $match = '';
      if ($match = !empty($matches[2]) ? $matches[2] : '') {
        $char = isset($matches[3]) ? $matches[3] : '';
        $match = "$matches[1]<em>" . str_replace('_', '', $match) . "</em>$char";
      }
      return $match;
    }, $text);
  }

  /**
   * Replaces pairs of "__" signs by the strong tag.
   *
   * @param string $text
   *   The string to be filtered.
   *
   * @return string
   *   The HTML source with emphasized text.
   */
  public function convertDoubleUnderscore($text) {
    return preg_replace_callback('/(\s)(__[^_]*?__)/', function ($matches) {
      $match = '';
      if ($match = !empty($matches[2]) ? $matches[2] : '') {
        $char = isset($matches[3]) ? $matches[3] : '';
        $match = "$matches[1]<strong>" . str_replace('_', '', $match) . "</strong>$char";
      }
      return $match;
    }, $text);
  }

  /**
   * Replaces pairs of single "`" signs by the code tag.
   *
   * @param string $text
   *   The string to be filtered.
   *
   * @return string
   *   The HTML source with text wrapped into code tag.
   */
  public function convertSingleBacktick($text) {
    return preg_replace_callback('/`[^`]*?[^`]`/', function ($matches) {
      $match = '';
      if ($match = !empty($matches[0]) ? $matches[0] : '') {
        $match = "<code class=\"code--singleline\">" . str_replace('`', '', $match) . "</code>";
      }
      return $match;
    }, $text);
  }

  /**
   * Replaces pairs of triple "```" signs by the code tag.
   *
   * @param string $text
   *   The string to be filtered.
   *
   * @return string
   *   The HTML source with text wrapped into code tag which is wrapped into
   *   the pre tag.
   */
  public function convertTripleBacktick($text) {
    return preg_replace_callback('/(```\w*)[^`].*?[^`](```)/xs', function ($matches) {
      $match = '';
      if ($match = !empty($matches[2]) ? $matches[0] : '') {
        $match = "<pre><code class=\"code--multiline\">" . str_replace([$matches[1], $matches[2]], '', $match) . "</code></pre>";
      }
      return $match;
    }, $text);
  }

  /**
   * Converts markdown syntax image into HTML image.
   *
   * The markdown image should look like this:
   * @code
   * ## A relative to a module directory path. The "Title" is optional.
   * ![Alt](images/my-image.png "Title")
   *
   * ## An absolute path
   * ![Alt](
   * https://example.com/modules/contrib/my_module/images/my-image.png "Title")
   * @code
   *
   * Note that in the case of relative path the internal path to a module's
   * directory is taken from the current request. So, if the request looks like
   * https://example.com/modules/contrib/my_module/images then the markdown
   * image should be constructed like this:
   * @code
   * ![Alt](my-image.png "Title")
   * @code
   *
   * @param string $text
   *   The string to be filtered.
   * @param string $host
   *   (optional) The base URL of a site, like: http(s)://example.com.
   * @param string $path
   *   (optional) The path part of the URL, like: modules/contrib/my_module.
   *
   * @return string
   *   The HTML source with image tags.
   */
  public function convertMarkdownImage($text, $host, $path) {
    $parts = explode('?', $path);
    $path = isset($parts[0]) ? $parts[0] : 'NOT-A-PATH';
    $pattern = '/(!\[((?>[^\[\]]+|\[\])*)\]\s?\([ \n]*(?:<(\S*)>|((?>[^()\s]+|\((?>\)))*))[ \n]*(([\'"])(.*?)\6[ \n]*)?\))/xs';

    return preg_replace_callback($pattern, function ($matches) use ($host, $path) {
      $alt = $matches[2];
      $url = $matches[3] == '' ? $matches[4] : $matches[3];
      $title = empty($matches[7]) ? $alt : $matches[7];
      $src = "$host/$path/$url";
      if (preg_match('/^http/', $url)) {
        $src = $url;
      }
      return "<img src=\"$src\" alt=\"$alt\" title=\"$title\" class=\"markdown-image\" />";
    }, $text);
  }

  /**
   * Converts markdown syntax anchor into HTML anchor.
   *
   * The markdown anchor should look like this:
   * @code
   * > The Drupal site's internal page path. The "Title" is optional. The url is
   * taken from the link's textual part. The # part of the url is to render
   * README markdown file and have the same link to look similar both on the
   * Drupal site and extenal vcs systems like Github. The difference is that on
   * Github it works just as a dummy link but on the Drupal site it leads to a
   * really existing page.
   * [admin/reports/dblog](#admin-reports-dblog "Title")
   *
   * ## An absolute path.
   * [Recent log messages](https://example.com/admin/reports/dblog "Title")
   * @code
   *
   * @param string $text
   *   The string to be filtered.
   * @param string $host
   *   (optional) The base URL of a site, like: http(s)://example.com.
   *
   * @return string
   *   The HTML source with anchor tags.
   */
  public function convertMarkdownLink($text, $host) {
    $pattern = '/(\[((?>[^\[\]]+|\[\])*)\]\([ \n]*(?:<(.+?)>|((?>[^()\s]+|\((?>\)))*))[ \n]*(([\'"])(.*?)\6[ \n]*)?\))/xs';

    return preg_replace_callback($pattern, function ($matches) use ($host) {
      $text  = $matches[2];
      $parts = explode('?', $text);
      $text  = isset($parts[0]) ? $parts[0] : 'NOT-A-TEXT';
      $url   = $matches[3] == '' ? $matches[4] : $matches[3];
      $parts = explode('?', $url);
      $url   = isset($parts[0]) ? $parts[0] : 'NOT-A-URL';
      $title = empty($matches[7]) ? $text : $matches[7];
      if (preg_match('{^.*[#].*}', $text)) {
        $parts = explode('?', $text);
        $url = "$host/$parts[0]";
        $text = $title;
      }
      elseif (!preg_match('/^http/', $url)) {
        $url = "$host/$text";
      }
      return "<a href=\"$url\" title=\"$title\" class=\"markdown-link\">$text</a>";
    }, $text);
  }

}
