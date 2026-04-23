<?php

namespace Drupal\vactory_checklist;

use Drupal\Core\Config\ConfigFactoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Runs checklist plugins during cron and notifies Slack when checks fail.
 */
class ChecklistSlackCron {

  /**
   * The checklist runner.
   *
   * @var \Drupal\vactory_checklist\ChecklistRunner
   */
  protected $runner;

  /**
   * The Slack notifier.
   *
   * @var \Drupal\vactory_checklist\SlackNotifier
   */
  protected $slackNotifier;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Constructs a ChecklistSlackCron.
   *
   * @param \Drupal\vactory_checklist\ChecklistRunner $runner
   *   The checklist runner.
   * @param \Drupal\vactory_checklist\SlackNotifier $slack_notifier
   *   The Slack notifier.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger.
   */
  public function __construct(ChecklistRunner $runner, SlackNotifier $slack_notifier, ConfigFactoryInterface $config_factory, LoggerInterface $logger) {
    $this->runner = $runner;
    $this->slackNotifier = $slack_notifier;
    $this->configFactory = $config_factory;
    $this->logger = $logger;
  }

  /**
   * Executes checks and posts to Slack when there are failures or warnings.
   */
  public function execute(): void {
    $webhook = trim((string) $this->configFactory->get('vactory_checklist.slack_notification')->get('webhook_url'));
    $rows = $this->runner->runAll();
    $lines = [];

    foreach ($rows as $row) {
      $result = $row['result'];
      if (!empty($result['status'])) {
        continue;
      }
      $level = !empty($result['is_warning']) ? 'WARNING' : 'FAILURE';
      $message = isset($result['message']) ? (string) $result['message'] : '';
      $lines[] = sprintf('[%s] %s: %s', $level, $row['label'], $message);
    }

    if ($lines === []) {
      return;
    }

    if ($webhook === '') {
      $this->logger->notice('Checklist cron found @count issue(s) but Slack is disabled: configure the Incoming Webhook URL in the Slack notification settings.', [
        '@count' => (string) count($lines),
      ]);
      return;
    }

    $site_name = (string) $this->configFactory->get('system.site')->get('name');
    $text = '*' . $site_name . "* — Vactory checklist (cron)\n\n" . implode("\n", $lines);
    $this->slackNotifier->send($webhook, $text);
  }

}
