<?php

namespace Drupal\readmehelp\Plugin\HelpSection;

use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Link;
use Drupal\help\Plugin\HelpSection\HookHelpSection;
use Drupal\readmehelp\ReadmeHelpInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
   * The module extension list.
   *
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  protected $moduleExtensionList;

  /**
   * Constructs a HookHelpSection object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   * @param \Drupal\Core\Extension\ModuleExtensionList $module_extension_list
   *   The module extension list.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ModuleHandlerInterface $module_handler, ModuleExtensionList $module_extension_list) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $module_handler);
    $this->moduleExtensionList = $module_extension_list;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('module_handler'),
      $container->get('extension.list.module')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function listTopics() {
    $dirs = $this->moduleHandler->getModuleDirectories();

    $hook_help = [];
    $this->moduleHandler->invokeAllWith(
      'help',
      function (callable $hook, string $module) use (&$hook_help) {
        $hook_help[] = $module;
      }
    );

    $topics = [];

    foreach ($this->moduleHandler->getModuleList() as $name => $module) {
      $file = FALSE;
      $self = $name == 'readmehelp';
      $extension_info = $this->moduleExtensionList->getExtensionInfo($name);
      $dependencies = $extension_info['dependencies'];
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
