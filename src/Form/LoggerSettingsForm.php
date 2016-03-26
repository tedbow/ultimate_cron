<?php
/**
 * @file
 * Contains \Drupal\ultimate_cron\Form\LoggerSettingsForm.
 */

namespace Drupal\ultimate_cron\Form;

/**
 * Form for logger settings.
 */
class LoggerSettingsForm extends PluginSettingsFormBase {

  /**
   * {@inheritdoc}
   */
  const CRON_PLUGIN_TYPE = 'logger';
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

}
