<?php
/**
 * @file
 * Contains \Drupal\ultimate_cron\Form\LoggerSettingsForm.
 */

namespace Drupal\ultimate_cron\Form;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form for logger settings.
 */
class LoggerSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ultimate_cron_logger_settings';
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
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('ultimate_cron.settings');
    $plugins = ultimate_cron_plugin_load_all('logger');

    $form['logger_default'] = [
      '#type' => 'select',
      '#title' => t('Default Logger'),
      '#description' => t('Select which logger to use.'),
      '#default_value' => $config->get('default'),
    ];

    foreach ($plugins as $name => $plugin) {
      $form = $plugin->buildConfigurationForm($form, $form_state);
      $form['#submit'][] = $plugin->getPluginDefinition()['class'] . '::submitConfigurationForm';
    }
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('ultimate_cron.settings')
      ->set('default', $form_state->getValue('logger_default'))
      ->save('');

    parent::submitForm($form, $form_state);
  }

}
