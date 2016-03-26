<?php
/**
 * @file
 * Simple cron job scheduler for Ultimate Cron.
 */

namespace Drupal\ultimate_cron_plugin_test\Plugin\ultimate_cron\Scheduler;
use Drupal\Core\Form\FormStateInterface;
use Drupal\ultimate_cron\Entity\CronJob;
use Drupal\ultimate_cron\Plugin\ultimate_cron\Scheduler\Simple;

/**
 * Simple scheduler.
 *
 * @SchedulerPlugin(
 *   id = "simpler",
 *   title = @Translation("Simpler"),
 *   description = @Translation("Provides a set of predefined intervals for scheduling."),
 * )
 */
class Simpler extends Simple {

  public $presets = array(
    '* * * * *' => 60,
    '*/5+@ * * * *' => 300,
  );

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array(
      'rules' => array('* * * * *'),
    ) + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function formatLabel(CronJob $job) {
    return t('Simpler Every @interval', array(
      '@interval' => \Drupal::service('date.formatter')->formatInterval($this->presets[$this->configuration['rules'][0]]),
    ));
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $date_formatter = \Drupal::service('date.formatter');
    $intervals = array_map(array($date_formatter, 'formatInterval'), $this->presets);

    $form['rules'][0] = array(
      '#type' => 'select',
      '#title' => t('Simpler Run cron every'),
      '#default_value' => $this->configuration['rules'][0],
      '#description' => t('Select the interval you wish cron to run on.'),
      '#options' => $intervals,
      '#fallback' => TRUE,
      '#required' => TRUE,
    );

    return $form;
  }
}
