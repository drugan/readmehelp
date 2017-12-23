<?php

namespace Drupal\readmehelp\Plugin\HelpSection;

use Drupal\Core\Link;
use Drupal\help\Plugin\HelpSection\HookHelpSection;
use Drupal\readmehelp\ReadmeHelpInterface;

/**
 * Provides the module topics list section for the help page.
 *
 * @HelpSection(
 *   id = "hook_help",
 *   title = @Translation("Module overviews"),
 *   description = @Translation("Module overviews are provided by modules. Overviews available for your installed modules:"),
 * )
 */
class ReadmeHookHelpSection extends HookHelpSection implements ReadmeHelpInterface {

  /**
   * {@inheritdoc}
   */
  public function listTopics() {
    $dirs = $this->moduleHandler->getModuleDirectories();
    $hook_help = $this->moduleHandler->getImplementations('help');
    $topics = [];

    foreach ($this->moduleHandler->getModuleList() as $name => $module) {
      $file = FALSE;
      $self = $name == 'readmehelp';
      $dependencies = system_get_info('module', $name)['dependencies'];
      if (in_array('readmehelp', $dependencies) || in_array('drupal:readmehelp', $dependencies) || $self) {
        foreach (explode(', ', static::READMEHELP_FILES) as $readme) {
          if ($file = file_exists("$dirs[$name]/$readme")) {
            break;
          }
        }
      }
      if ($file || in_array($name, $hook_help)) {
        $title = $this->moduleHandler->getName($name);
        $topics[$title] = Link::createFromRoute($title, 'help.page', ['name' => $name]);
      }
    }
    // Sort topics by title, which is the array key above.
    ksort($topics);
    return $topics;
  }

}
