<?php
/**
 * @file
 * Contains \Drupal\ultimate_cron\Form\PluginSettingsFormBase.
 */


namespace Drupal\ultimate_cron\Form;


use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\ultimate_cron\CronPlugin;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base form for all plugin general settings forms.
 */
abstract class PluginSettingsFormBase extends ConfigFormBase{


  /**
   * The type of plugin this form should handle.
   *
   * @var string
   */
  const CRON_PLUGIN_TYPE = '';

  /**
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $pluginManager;

  /**
   * PluginSettingsFormBase constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   * @param \Drupal\Component\Plugin\PluginManagerInterface $plugin_manager
   */
  public function __construct(ConfigFactoryInterface $config_factory, PluginManagerInterface $plugin_manager) {
    parent::__construct($config_factory);
    $this->pluginManager = $plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('plugin.manager.ultimate_cron.' . static::CRON_PLUGIN_TYPE)
    );
  }


  /**
   * Get all plugins for this form.
   *
   * @return \Drupal\ultimate_cron\CronPlugin[]
   */
  protected function getPlugins() {
    $definitions = $this->pluginManager->getDefinitions();
    /** @var \Drupal\ultimate_cron\CronPlugin[] $plugins */
    $plugins = [];
    $config = $this->config('ultimate_cron.settings');
    $plugins_settings = $config->get(static::CRON_PLUGIN_TYPE)? : [];
    foreach ($definitions as  $id => $definition) {
      $config = isset($plugins_settings[$id])? $plugins_settings[$id]: [];
      $plugins[$id] = $this->pluginManager->createInstance($id, $config);
    }
    return $plugins;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('ultimate_cron.settings');
    $plugins = $this->getPlugins();
    $form['settings_tabs'] = array(
      '#type' => 'vertical_tabs',
    );

    $default_options = [];
    $form['plugins'] = [
      '#tree' => TRUE,
    ];
    foreach ($plugins as $id => $plugin) {
      $definition = $plugin->getPluginDefinition();
      $default_options[$id] = $definition['title'];

      $form['plugins'][$id] = [
        '#type' => 'details',
        '#title' => $definition['title'],
        '#group' => 'settings_tabs',
        '#tree' => TRUE,
      ];
      $form['plugins'][$id] += $plugin->buildConfigurationForm([], $form_state);
      $form['plugins'][$id]['id'] = [
        '#type' => 'value',
        '#value' => $id,
      ];
    }
    $form['default_plugin'] = [
      '#type' => 'select',
      '#title' => $this->t('Default Plugin'),
      '#options' => $default_options,
      '#default_value' => $config->get('default_plugins.' . static::CRON_PLUGIN_TYPE),
      '#weight' => -100,
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->cleanValues();
    $config = $this->config('ultimate_cron.settings');
    $values = $form_state->getValues();
    // Set the default plugin for this type.
    $config->set('default_plugins.' . static::CRON_PLUGIN_TYPE, $values['default_plugin']);
    $config->set(static::CRON_PLUGIN_TYPE,$values['plugins']);
    $config->save();
    parent::submitForm($form, $form_state);
  }


  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['ultimate_cron.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    $plugins = $this->getPlugins();
    // Validate each plugin form.
    foreach ($plugins as $plugin) {
      $plugin->validateConfigurationForm($form, $form_state);
    }
  }

}
