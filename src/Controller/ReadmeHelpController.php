<?php

namespace Drupal\readmehelp\Controller;

use Drupal\help\Controller\HelpController;

/**
 * Overrides HelpController class.
 */
class ReadmeHelpController extends HelpController {

  /**
   * {@inheritdoc}
   */
  public function helpPage($name) {
    $build = [];
    $self = $name == 'readmehelp';
    $dependencies = system_get_info('module', $name)['dependencies'];
    $depender = $self || in_array('readmehelp', $dependencies) || in_array('drupal:readmehelp', $dependencies);
    // Allow dependers to override default behaviour not displaying README
    // markdown file automatically and instead calling a regular hook_help() in
    // their .module files. For this to happen an empty hook_readmehelp() should
    // be implemented which is actually never will be called. Example:
    // @code
    // function MY_MODULE_readmehelp() {}
    // @endcode
    if ($depender && !$this->moduleHandler()->implementsHook($name, 'readmehelp')) {
      $converter = \Drupal::service('readmehelp.markdown_converter');
      $build['top'] = [
        '#attached' => [
          'library' => ['readmehelp/page'],
        ],
        '#markup' => $converter->convertMarkdownFile($name),
      ];
      return $build;
    }
    else {
      return parent::helpPage($name);
    }
  }

}
