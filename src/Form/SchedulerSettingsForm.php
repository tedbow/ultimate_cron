<?php
/**
 * @file
 * Contains \Drupal\ultimate_cron\Form\SchedulerSettingsForm.
 */

namespace Drupal\ultimate_cron\Form;

/**
 * Form for scheduler settings.
 */
class SchedulerSettingsForm extends PluginSettingsFormBase {

  /**
   * {@inheritdoc}
   */
  const CRON_PLUGIN_TYPE = 'scheduler';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ultimate_cron_scheduler_settings';
  }

}
