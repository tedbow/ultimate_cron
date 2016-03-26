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
 * Base form for all plugin general settings form
 *
 * @todo Change logger scheduler and launcher forms to extend this
 *
 * @todo Do we actually need separate form classes???
 *  or can we just have different routes with {plugin_type}
 *
 *
 */
abstract class PluginSettingsFormBase extends ConfigFormBase{


  /**
   * The type of plugin this form should handle.
   *
   * @var string
   */
  const PLUGIN_TYPE = '';

  /**
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $pluginManager;

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
      $container->get('plugin.manager.ultimate_cron.' . static::PLUGIN_TYPE)
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
    $plugins_settings = $config->get(static::PLUGIN_TYPE);
    foreach ($definitions as  $id => $definition) {
      $plugins[$id] = $this->pluginManager->createInstance($id, $plugins_settings[$id]);
    }
    return $plugins;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $plugins = $this->getPlugins();
    $form['settings_tabs'] = array(
      '#type' => 'vertical_tabs',
    );

    foreach ($plugins as $id => $plugin) {
      // @todo Add vertical tab for each plugin
      $definition = $plugin->getPluginDefinition();
      // Settings for crontab.
      $form[$id] = [
        '#type' => 'details',
        '#title' => $definition['title'],
        '#group' => 'settings_tabs',
        '#tree' => TRUE,
      ];
      $form[$id] += $plugin->buildConfigurationForm([], $form_state);
      $form[$id]['id'] = [
        '#type' => 'value',
        '#value' => $id,
      ];
    }
    return parent::buildForm($form, $form_state);
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->cleanValues();
    $config = $this->config('ultimate_cron.settings');
    $config->set(static::PLUGIN_TYPE,$form_state->getValues());
    $config->save();
    parent::submitForm($form, $form_state);
  }


  /**
   * {@inheritdoc}
   *
   * @todo will this be the same for all plugin types forms?
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
    foreach ($plugins as $plugin) {
      $plugin->validateConfigurationForm($form, $form_state);
    }
  }


}
