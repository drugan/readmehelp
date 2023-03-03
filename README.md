# README Help module

A simple module allowing to display a *README.md*, *README.txt* or *README* file
on the **admin/help/your_module** page. Additionally, the module supports
inserting highlighted `PHP` file snippets into a *README* file and provides
a [markdown ↗](https://en.wikipedia.org/wiki/Markdown) text filter for
creating *Drupal* text formats.

The main difference with other similar modules is that it has no dependency on
any external library, package or contrib module.

> Tip: you can see this file in your browser by clicking
the [admin/help#](#0 "? Help") link at the right of the *Admin toolbar* and then
the [admin/help/readmehelp#](#0 "README Help") link in the list.

________________________________________________________________________________

Forget about implementing `hook_help()` in `your_module.module` file. Just
create a decent *README* file with all information required and see it looking
almost the same both on the **admin/help/your_module** page as well as in your
text editor. No more hardly to read (and to write!) markup intermingled with the
end user help text. All that you need is to enable the `readmehelp` module
and spend a couple of minutes learning basics of the markdown syntax:

- [admin/help/readmehelp#usage](#usage "Usage")
- [admin/help/readmehelp#markdown-special-symbols](#markdown-special-symbols
"Markdown special symbols")
- [admin/help/readmehelp#bold-and-italic-text](#bold-and-italic-text
"Bold and Italic Text")
- [admin/help/readmehelp#blockquote-and-cite](#blockquote-and-cite
"Blockquote and Cite")
- [admin/help/readmehelp#lists](#lists "Lists")
- [admin/help/readmehelp#anchor](#anchor "Anchor")
- [admin/help/readmehelp#image](#image "Image")
- [admin/help/readmehelp#horisontal-rule](#horisontal-rule "Horisontal Rule")
- [admin/help/readmehelp#headings](#headings "Headings")
- [admin/help/readmehelp#allowed-html-tags](#allowed-html-tags
"Allowed HTML tags")
- [admin/help/readmehelp#dynamic-php-snippets](#dynamic-php-snippets
"Dynamic PHP Snippets")
- [admin/help/readmehelp#markdown-text-filter](#markdown-text-filter
"Markdown Text Filter")
- [admin/help/readmehelp#advanced-readme-help](#advanced-readme-help
"Advanced README Help")
- [admin/help/readmehelp#module-author](#module-author "Module author")
- [README Help on drupal.org ↗](https://www.drupal.org/project/readmehelp)
- [README Help on github.com ↗](https://github.com/drugan/readmehelp)

## Usage

Enable the module and
visit [admin/config/system/readmehelp-settings#](#0 "README Help settings") page
to check a list of modules allowed to display their *README* file as a module
help.

By default all modules having no `hook_help()` implementation are selected.
Adding / removing additional modules will be done automatically in a module
install / uninstall process.

Changes could be done manually on this form if you want to display a
module's *README* file instead of the `hook_help()` content.

### Markdown special symbols

The *README Help* module's markdown syntax is a subset of the
[ Github Flavoured Markdown](
https://github.com/adam-p/markdown-here/wiki/Markdown-Cheatsheet) syntax.
Whenever possible the module attempts to replicate the look and feel of
a *Github* repository's *README* file. Still, you may notice differences in some
places of the [admin/help/readmehelp#](#0 "file displayed on a Drupal site") or
on the [module's *Github* repository](https://github.com/drugan/readmehelp
"README Help on Github.com").

The markdown "tag" special symbols:

- The asterisk: **\***
- The backtick: **\`**
- The dash: **-**
- The underscore: **\_**
- The line leading hash: **\#**
- The line leading greater than symbol: **>**
- The line leading two greater than symbols: **>>**
- The line leading three and more symbols set: **\_\_\_**
- The line leading three and more symbols set: **\*\*\***
- The line leading three and more symbols set: **---**
- The line leading three and more symbols set: **===**
- The markdown anchor: **\[]()**
- The markdown image: **!\[]()**
- The markdown unordered list item: **\- ITEM**
- The markdown ordered list item: **1. ITEM**

If it is required to display markdown symbol as a regular character then the
backslash should be prepended to a symbol: **\SYMBOL**.

#### Bold and Italic Text

\*\*Bold\*\*

`...converted into:`

**Bold**

\_\_Bold\_\_

`...converted into:`

__Bold__

\*Italic\*

`...converted into:`

*Italic*

\_Italic\_

`...converted into:`

_Italic_

#### Blockquote and Cite

\> My Blockquote

`...converted into:`

> My Blockquote

\>\> My Cite

`...converted into:`

>> My Cite

```
Note that every blockquote or cite can be referenced directly both locally and
externally by using the clickable greater than (>) sign at the left of a
block.
```

#### Lists

\- ITEM

`...converted into:`

- ITEM

1.\ ITEM

`...converted into:`

1. ITEM

#### Anchor

Relative path:

> Notice hash (**#**) signs in the url.

\[admin/reports/dblog#](#0 "Reports")

`...converted into:`

[admin/reports/dblog#](#0 "Reports")

> Note that on the [github.com](https://github.com/drugan/readmehelp#anchor) the
link above will work just as a dummy link.

Absolute path:

\[PHP](http://php.net "On Hover Title")

`...converted into:`

[PHP](http://php.net/ "On Hover Title")

```
Note that any raw text url, like http://php.net will be converted into:
```
http://php.net


#### Image

Relative Path:

!\[ALT Text]\(images/druplicon.png "On Hover Title")

`...converted into:`

![ALT Text](images/druplicon.png "On Hover Title")

Absolute Path:

!\[ALT Text](https://raw.githubusercontent.com/drugan/readmehelp/8.x-1.x/images/drupalcat.png
"On Hover Title")

`...converted into:`

![ALT Text](https://raw.githubusercontent.com/drugan/readmehelp/8.x-1.x/images/drupalcat.png
"On Hover Title")

> See origin of the above image: https://octodex.github.com/images/drupalcat.jpg


#### Horisontal Rule

Three or more undescores like:

\_\_\_

`...converted into:`
________________________________________________________________________________


Three or more asterisks like:

\*\*\*

`...converted into:`

********************************************************************************


Three or more dashes like:

\-\-\-

`...converted into:`

--------------------------------------------------------------------------------

#### Headings

> Every heading can be referenced directly both locally and externally by
using the clickable hash (**#**) sign at the left of a heading.


`# H1`

```
Alternative H1
==============
```

`## H2`

```
Alternative H2
------------ -
```

`### H3`

`#### H4`

`##### H5`

`###### H6`


> Note that `# H1` and `Alternative H1` syntax can be used just once at the
very beginning of the file. So, demoing this heading is not possible here.

`...converted into:`

## H2

Alternative H2
--------------

### H3

#### H4

##### H5

###### H6


#### Allowed HTML tags

Besides the markdown a restricted set of *HTML* tags can be used for
building *README Help* files:

- a
- em
- strong
- cite
- blockquote
- code
- ul
- ol
- li
- dl
- dt
- dd
- img
- h1
- h2
- h3
- h4
- h5
- h6
- p
- pre
- hr
- table
- tr
- td
- div
- span

Also, [HTML Entities](https://dev.w3.org/html5/html-author/charref)
and [HTML comments](https://developer.mozilla.org/en-US/docs/Learn/HTML/Introduction_to_HTML/Getting_started#html_comments)
are supported.

<!-- This text is not visible on the admin/help/your_module page -->

#### Dynamic PHP Snippets

To insert higlighted snippet of any existing on a Drupal site PHP file you need
to construct a token like the following:

```
@PHPFILE: modules/contrib/readmehelp/readmehelp.module LINE:26 PADD:7 :PHPFILE@
```

`...or just:`

```
@PHPFILE: readmehelp.module LINE:26 PADD:7 :PHPFILE@
```

> The absolute or relative path to a file might be followed by a **LINE** number
and **PADD** which is the number of lines to add before and after
the **LINE** (additionally highlighted with yellow color). Both arguments are
optional. If no **PADD** is passed then the default **10** lines will be added.
If no **LINE** is passed then an entire highlighted file will be displayed.
There
is a replacement of the token above (on
the [github.com](https://github.com/drugan/readmehelp#dynamic-php-snippets
"README Help Github repository") shown as a raw token):

********************************************************************************

@PHPFILE: readmehelp.module LINE:29 PADD:8 :PHPFILE@

********************************************************************************

## Markdown Text Filter

*README Help* module has its own [filter/tips#readmehelp-filter](#0
"markdown text filter") which could be used for creating
[/admin/config/content/formats/add#](#0 " Drupal text formats"). So, users can
implement easy to remember syntax for making their posts richer while not able
to harm a site, whether intentionally or not.

## Advanced README Help

There might be cases when you still need to implement `hook_help()` and at the
same time leverage the power of the *README Help* module. To restore the default
behaviour you need to remove your module from the list of supported modules and
implement `your_module_help()` hook using tricks as in the snippet below:


********************************************************************************

@PHPFILE: your_module.module.example LINE:22 PADD:30 :PHPFILE@

********************************************************************************

###### Module author:
```
  Vlad Proshin (drugan)
  [https://drupal.org/u/drugan](https://drupal.org/u/drugan)
```
