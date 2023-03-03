<?php

namespace Drupal\readmehelp\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Configure README Help settings for this site.
 */
class ReadmeHelpSettingsForm extends ConfigFormBase {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a \Drupal\system\ConfigFormBase object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ModuleHandlerInterface $module_handler) {
    $this->moduleHandler = $module_handler;
    parent::setConfigFactory($config_factory);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new self(
      $container->get('config.factory'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'readmehelp_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['readmehelp.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $options = [];
    foreach ($this->moduleHandler->getModuleList() as $machine_name => $extension) {
      $options[$machine_name] = $this->moduleHandler->getName($machine_name);
    }

    $form['readmehelp_modules'] = [
      '#type' => 'select',
      '#title' => $this->t('Modules using README Help'),
      '#description' => $this->t('Modules having <strong>no</strong> <em>HOOK_help()</em> implementation are automatically selected in a module install process.<br>Selected modules should have a valid <em>README.md</em>, <em>README.txt</em> or <em>README</em> file in their root directory.<br><mark>TIP:</mark> Press <strong>CTRL</strong> key to select / unselect an option or press <strong>SHIFT</strong> key to select a range of options.'),
      '#options' => $options,
      '#default_value' => $this->config('readmehelp.settings')->get('readmehelp_modules'),
      '#sort_options' => TRUE,
      '#multiple' => TRUE,
      '#size' => count($options),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (!is_array($form_state->getValue('readmehelp_modules'))) {
      $form_state->setErrorByName('readmehelp_modules', $this->t('The value is not correct.'));
    }
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('readmehelp.settings')
      ->set('readmehelp_modules', array_values($form_state->getValue('readmehelp_modules')))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
