<?php

namespace Drupal\readmehelp\Controller;

use Drupal\help\Controller\HelpController;
use Drupal\readmehelp\ReadmeHelpMarkdownConverter;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\help\HelpSectionManager;
use Drupal\Core\Extension\ModuleExtensionList;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Overrides HelpController class.
 */
class ReadmeHelpController extends HelpController {

  /**
   * The markdown converter service.
   *
   * @var \Drupal\readmehelp\ReadmeHelpMarkdownConverter
   */
  protected $markdownConverter;

  /**
   * Creates a new HelpController.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   * @param \Drupal\help\HelpSectionManager $help_manager
   *   The help section manager.
   * @param \Drupal\Core\Extension\ModuleExtensionList $module_extension_list
   *   The module extension list.
   * @param \Drupal\readmehelp\ReadmeHelpMarkdownConverter $markdown_converter
   *   The markdown converter.
   */
  public function __construct(RouteMatchInterface $route_match, HelpSectionManager $help_manager, ModuleExtensionList $module_extension_list, ReadmeHelpMarkdownConverter $markdown_converter) {
    $this->markdownConverter = $markdown_converter;
    parent::__construct($route_match, $help_manager, $module_extension_list);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new self(
      $container->get('current_route_match'),
      $container->get('plugin.manager.help_section'),
      $container->get('extension.list.module'),
      $container->get('readmehelp.markdown_converter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function helpPage($name) {
    $build = [];

    if (in_array($name, $this->config('readmehelp.settings')->get('readmehelp_modules'))) {
      $build['top'] = [
        '#attached' => [
          'library' => ['readmehelp/page'],
        ],
        '#markup' => $this->markdownConverter->convertMarkdownFile($name),
      ];

      // Only print list of administration pages if the module in question has
      // any such pages associated with it.
      $info = $this->moduleExtensionList->getExtensionInfo($name);
      $admin_tasks = system_get_module_admin_tasks($name, $info);
      if (!empty($admin_tasks)) {
        $module_name = $this->moduleHandler()->getName($name);
        $links = [];
        foreach ($admin_tasks as $task) {
          $link['url'] = $task['url'];
          $link['title'] = $task['title'];
          $links[] = $link;
        }
        $build['links'] = [
          '#theme' => 'links__help',
          '#heading' => [
            'level' => 'h3',
            'text' => $this->t('@module administration pages', ['@module' => $module_name]),
          ],
          '#links' => $links,
        ];
      }

      return $build;
    }
    else {
      return parent::helpPage($name);
    }
  }

}
