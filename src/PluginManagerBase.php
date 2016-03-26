<?php
/**
 * @file
 * Contains \Drupal\ultimate_cron\PluginManagerBase.
 */

namespace Drupal\ultimate_cron;

use Drupal\Core\Plugin\DefaultPluginManager;

abstract class PluginManagerBase extends DefaultPluginManager{

  /**
   * {@inheritdoc}
   *
   * Override to add cron_plugin_type.
   */
  public function getDefinitions() {
    $definitions = parent::getDefinitions();
    foreach ($definitions as &$definition) {
      $definition['cron_plugin_type'] = $this->pluginType();
    }
    return $definitions;
  }

  /**
   * {@inheritdoc}
   *
   * Override to add cron_plugin_type.
   */
  public function getDefinition($plugin_id, $exception_on_invalid = TRUE) {
    $definition =  parent::getDefinition($plugin_id, $exception_on_invalid);
    $definition['cron_plugin_type'] = $this->pluginType();
    return $definition;
  }

  /**
   * Gets the cron plugin type
   * @return string
   */
  abstract protected function pluginType();

}
