<?php
/**
 * * Contains \Drupal\ultimate_cron\Form\LauncherSettingsForm.
 */

namespace Drupal\ultimate_cron\Form;

/**
 * Form for launcher settings.
 */
class LauncherSettingsForm extends PluginSettingsFormBase {

  /**
   * {@inheritdoc}
   */
  const CRON_PLUGIN_TYPE = 'launcher';
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ultimate_cron_launcher_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['ultimate_cron.settings'];
  }

}
