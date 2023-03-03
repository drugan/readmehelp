<?php

namespace Drupal\readmehelp\Plugin\HelpSection;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Link;
use Drupal\help\Plugin\HelpSection\HookHelpSection;
use Drupal\readmehelp\ReadmeHelpInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;

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
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

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
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ModuleHandlerInterface $module_handler, ConfigFactoryInterface $config_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $module_handler);
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new self(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('module_handler'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function listTopics() {
    $dirs = $this->moduleHandler->getModuleDirectories();
    $readmehelp_modules = $this->configFactory
      ->get('readmehelp.settings')
      ->get('readmehelp_modules');

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
      if (in_array($name, $readmehelp_modules)) {
        foreach (static::READMEHELP_FILES as $readme) {
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
