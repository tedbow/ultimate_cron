<?php
/**
 * @file
 * Contains \Drupal\ultimate_cron\Form\PluginSettingsFormBase.
 */


namespace Drupal\ultimate_cron\Form;


use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\ultimate_cron\CronPlugin;

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
   * The type of plugin this form should handle
   * @var string
   */
  protected $plugin_type;

  /**
   * Get all plugins for this form.
   *
   * @return CronPlugin[]
   */
  protected function getPlugins() {

  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $plugins = $this->getPlugins();
    foreach ($plugins as $plugin) {
      // @todo Add vertical tab for each plugin
      $form[$plugin->getPluginId()] = $plugin->buildConfigurationForm([], $form_state);
    }
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   *
   * @todo will this be the same for all plugin types forms?
   */
  protected function getEditableConfigNames() {
    return ['ultimate_cron.settings'];
  }

  //@todo add validation of plugins


}
