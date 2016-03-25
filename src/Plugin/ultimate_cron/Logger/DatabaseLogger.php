<?php
/**
 * @file
 * Contains \Drupal\ultimate_cron\Plugin\ultimate_cron\Logger\DatabaseLogger.
 * Database logger for Ultimate Cron.
 */

namespace Drupal\ultimate_cron\Plugin\ultimate_cron\Logger;

use Database;
use Drupal\Core\Form\FormStateInterface;
use Drupal\ultimate_cron\CronJobInterface;
use Drupal\ultimate_cron\Entity\CronJob;
use Drupal\ultimate_cron\Logger\DatabaseLogEntry;
use Drupal\ultimate_cron\Logger\LoggerBase;
use Drupal\ultimate_cron\PluginCleanupInterface;

/**
 * Database logger.
 *
 * @LoggerPlugin(
 *   id = "database",
 *   title = @Translation("Database"),
 *   description = @Translation("Stores logs in the database."),
 *   default = TRUE,
 * )
 */
class DatabaseLogger extends LoggerBase implements PluginCleanupInterface {
  public $options = array();
  public $logEntryClass = '\Drupal\ultimate_cron\Logger\DatabaseLogEntry';

  const CLEANUP_METHOD_DISABLED = 1;
  const CLEANUP_METHOD_EXPIRE = 2;
  const CLEANUP_METHOD_RETAIN = 3;

  /**
   * Constructor.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->options['method'] = array(
      static::CLEANUP_METHOD_DISABLED => t('Disabled'),
      static::CLEANUP_METHOD_EXPIRE => t('Remove logs older than a specified age'),
      static::CLEANUP_METHOD_RETAIN => t('Retain only a specific amount of log entries'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array(
      'method' => static::CLEANUP_METHOD_RETAIN,
      'expire' => 86400 * 14,
      'retain' => 1000,
    );
  }

  /**
   * {@inheritdoc}
   */
  public function cleanup() {
    $jobs = CronJob::loadMultiple();
    $current = 1;
    $max = 0;
    foreach ($jobs as $job) {
      if ($job->getLoggerId() === $this->getPluginId()) {
        $max++;
      }
    }
    foreach ($jobs as $job) {
      if ($job->getLoggerId() === $this->getPluginId()) {
        $this->cleanupJob($job);
        $class = \Drupal::entityTypeManager()->getDefinition('ultimate_cron_job')->getClass();
        if ($class::$currentJob) {
          $class::$currentJob->setProgress($current / $max);
          $current++;
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function cleanupJob(CronJobInterface $job) {
    switch ($this->configuration['method']) {
      case static::CLEANUP_METHOD_DISABLED:
        return;

      case static::CLEANUP_METHOD_EXPIRE:
        $expire = $this->configuration['expire'];
        // Let's not delete more than ONE BILLION log entries :-o.
        $max = 10000000000;
        $chunk = 100;
        break;

      case static::CLEANUP_METHOD_RETAIN:
        $expire = 0;
        $max = db_query("SELECT COUNT(lid) FROM {ultimate_cron_log} WHERE name = :name", array(
          ':name' => $job->id(),
        ))->fetchField();
        $max -= $this->configuration['retain'];
        if ($max <= 0) {
          return;
        }
        $chunk = min($max, 100);
        break;

      default:
        \Drupal::logger('ultimate_cron')->warning('Invalid cleanup method: @method', array(
          '@method' => $this->configuration['method'],
        ));
        return;
    }

    // Chunked delete.
    $count = 0;
    do {
      $lids = db_select('ultimate_cron_log', 'l')
        ->fields('l', array('lid'))
        ->condition('l.name', $job->id())
        ->condition('l.start_time', microtime(TRUE) - $expire, '<')
        ->range(0, $chunk)
        ->orderBy('l.start_time', 'ASC')
        ->orderBy('l.end_time', 'ASC')
        ->execute()
        ->fetchCol();
      if ($lids) {
        $count += count($lids);
        $max -= count($lids);
        $chunk = min($max, 100);
        db_delete('ultimate_cron_log')
          ->condition('lid', $lids, 'IN')
          ->execute();
      }
    } while ($lids && $max > 0);
    if ($count) {
      \Drupal::logger('database_logger')->info('@count log entries removed for job @name', array(
        '@count' => $count,
        '@name' => $job->id(),
      ));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function settingsLabel($name, $value) {
    switch ($name) {
      case 'method':
        return $this->options[$name][$value];
    }
    return parent::settingsLabel($name, $value);

  }

  /**
   * Settings form.
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $plugin_id = $this->pluginId;
    $defination = $this->getPluginDefinition();
    $config = \Drupal::service('config.factory')->get('ultimate_cron.settings');
    $form['logger_default']['#options'][$plugin_id] = $defination['title'];

    $form[$plugin_id] = [
      '#type' => 'fieldset',
      '#title' => $defination['title'],
      '#tree' => TRUE,
    ];

    $form[$plugin_id]['method'] = array(
      '#type' => 'select',
      '#title' => t('Log entry cleanup method'),
      '#description' => t('Select which method to use for cleaning up logs.'),
      '#options' => $this->options['method'],
      '#default_value' => $config->get('logger.database.method'),
    );

    $form[$plugin_id]['expire'] = array(
      '#type' => 'textfield',
      '#title' => t('Log entry expiration'),
      '#description' => t('Remove log entries older than X seconds.'),
      '#default_value' => $config->get('logger.database.expire'),
      '#fallback' => TRUE,
      '#states' => array(
        'visible' => array(
          ':input[name="logger[settings][method]"]' => array('value' => static::CLEANUP_METHOD_EXPIRE),
        ),
        'required' => array(
          ':input[name="logger[settings][method]"]' => array('value' => static::CLEANUP_METHOD_EXPIRE),
        ),
      ),
    );

    $form[$plugin_id]['retain'] = array(
      '#type' => 'textfield',
      '#title' => t('Retain logs'),
      '#description' => t('Retain X amount of log entries.'),
      '#default_value' => $config->get('logger.database.retain'),
      '#fallback' => TRUE,
      '#states' => array(
        'visible' => array(
          ':input[name="logger[settings][method]"]' => array('value' => static::CLEANUP_METHOD_RETAIN),
        ),
        'required' => array(
          ':input[name="logger[settings][method]"]' => array('value' => static::CLEANUP_METHOD_RETAIN),
        ),
      ),
    );
    return $form;
  }

  /**
   *
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $config = \Drupal::service('config.factory')->getEditable('ultimate_cron.settings');
    $config->set('logger.database', $form_state->getValue('database'))
      ->save();
  }

  /**
   * {@inheritdoc}
   */
  public function load($name, $lock_id = NULL, array $log_types = [ULTIMATE_CRON_LOG_TYPE_NORMAL]) {
    if ($lock_id) {
      $log_entry = db_select('ultimate_cron_log', 'l')
        ->fields('l')
        ->condition('l.lid', $lock_id)
        ->execute()
        ->fetchObject($this->logEntryClass, array($name, $this));
    }
    else {
      $log_entry = db_select('ultimate_cron_log', 'l')
        ->fields('l')
        ->condition('l.name', $name)
        ->condition('l.log_type', $log_types, 'IN')
        ->orderBy('l.start_time', 'DESC')
        ->orderBy('l.end_time', 'DESC')
        ->range(0, 1)
        ->execute()
        ->fetchObject($this->logEntryClass, array($name, $this));
    }
    if ($log_entry) {
      $log_entry->finished = TRUE;
    }
    else {
      $log_entry = new DatabaseLogEntry($name, $this);
    }
    return $log_entry;
  }

  /**
   * {@inheritdoc}
   */
  public function loadLatestLogEntries(array $jobs, array $log_types) {
    if (Database::getConnection()->databaseType() !== 'mysql') {
      return parent::loadLatestLogEntries($jobs, $log_types);
    }

    $result = db_query("SELECT l.*
    FROM {ultimate_cron_log} l
    JOIN (
      SELECT l3.name, (
        SELECT l4.lid
        FROM {ultimate_cron_log} l4
        WHERE l4.name = l3.name
        AND l4.log_type IN (:log_types)
        ORDER BY l4.name desc, l4.start_time DESC
        LIMIT 1
      ) AS lid FROM {ultimate_cron_log} l3
      GROUP BY l3.name
    ) l2 on l2.lid = l.lid", array(':log_types' => $log_types));

    $log_entries = array();
    while ($object = $result->fetchObject()) {
      if (isset($jobs[$object->name])) {
        $log_entries[$object->name] = new $this->logEntryClass($object->name, $this);
        $log_entries[$object->name]->setData((array) $object);
      }
    }
    foreach ($jobs as $name => $job) {
      if (!isset($log_entries[$name])) {
        $log_entries[$name] = new $this->logEntryClass($name, $this);
      }
    }

    return $log_entries;
  }

  /**
   * {@inheritdoc}
   */
  public function getLogEntries($name, array $log_types, $limit = 10) {
    $result = db_select('ultimate_cron_log', 'l')
      ->fields('l')
      ->extend('Drupal\Core\Database\Query\PagerSelectExtender')
      ->condition('l.name', $name)
      ->condition('l.log_type', $log_types, 'IN')
      ->limit($limit)
      ->orderBy('l.start_time', 'DESC')
      ->execute();

    $log_entries = array();
    while ($object = $result->fetchObject($this->logEntryClass, array(
      $name,
      $this,
    ))) {
      $log_entries[$object->lid] = $object;
    }

    return $log_entries;
  }

  /**
   * Settings form.
   */
  public function settingsForm(&$form, &$form_state) {
    $elements['bin'] = array(
      '#type' => 'textfield',
      '#title' => t('Cache bin'),
      '#description' => t('Select which cache bin to use for storing logs.'),
      '#fallback' => TRUE,
      '#required' => TRUE,
    );
    $elements['timeout'] = array(
      '#type' => 'textfield',
      '#title' => t('Cache timeout'),
      '#description' => t('Seconds before cache entry expires (0 = never, -1 = on next general cache wipe).'),
      '#fallback' => TRUE,
      '#required' => TRUE,
    );
    return $elements;
  }

}
