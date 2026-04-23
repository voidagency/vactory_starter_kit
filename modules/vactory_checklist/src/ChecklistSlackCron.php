<?php

namespace Drupal\vactory_checklist;

use Drupal\Core\Config\ConfigFactoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Runs checklist plugins during cron and notifies Slack according to settings.
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
   * Executes checks and posts to Slack for enabled severity levels.
   */
  public function execute(): void {
    $slack_config = $this->configFactory->get('vactory_checklist.slack_notification');

    $webhook = trim((string) $slack_config->get('webhook_url'));
    $rows = $this->runner->runAll();
    $sections = [
      'SUCCESS' => [],
      'WARNING' => [],
      'ERROR' => [],
    ];

    foreach ($rows as $row) {
      $level = $this->classifyResult($row['result']);
      $message = isset($row['result']['message']) ? (string) $row['result']['message'] : '';
      $sections[$level][] = [
        'label' => $row['label'],
        'message' => $message,
        'details' => isset($row['result']['details']) && is_array($row['result']['details'])
        ? $row['result']['details']
        : [],
      ];
    }

    $total = count($sections['SUCCESS']) + count($sections['WARNING']) + count($sections['ERROR']);
    if ($total === 0) {
      return;
    }

    if ($webhook === '') {
      $this->logger->notice('Checklist cron has @count Slack line(s) to send but Slack is disabled: configure the Incoming Webhook URL in the Slack notification settings.', [
        '@count' => (string) $total,
      ]);
      return;
    }

    $site_name = (string) $this->configFactory->get('system.site')->get('name');
    $text = $this->formatSlackReport($site_name, $sections);
    $this->slackNotifier->send($webhook, $text);
  }

  /**
   * Builds the Slack body: Succès / Avertissements / Erreurs with emojis.
   */
  protected function formatSlackReport(string $site_name, array $sections): string {
    $lines = [];
    $lines[] = '*' . $site_name . "* — Vactory checklist (cron)";
    $headers = [
      'SUCCESS' => '✅ *Succès*',
      'WARNING' => '⚠️ *Avertissements*',
      'ERROR' => '❌ *Erreurs*',
    ];
    foreach (['SUCCESS', 'WARNING', 'ERROR'] as $key) {
      if (empty($sections[$key])) {
        continue;
      }
      $lines[] = '';
      $lines[] = $headers[$key];
      foreach ($sections[$key] as $item) {
        $lines[] = '• *' . $this->escapeSlackMrkdwn($item['label']) . '*: ' . $this->escapeSlackMrkdwn($item['message']);
        if (!empty($item['details'])) {
          $lines = array_merge($lines, $this->formatDetailsLines($item['details']));
        }
      }
    }
    return implode("\n", $lines);
  }

  /**
   * Formats plugin "details" (list of associative rows) as extra Slack lines.
   */
  protected function formatDetailsLines(array $details): array {
    $lines = [];
    $lines[] = '  Détails :';
    foreach ($details as $row) {
      if (!is_array($row)) {
        $lines[] = '    – ' . $this->escapeSlackMrkdwn($this->stringifyDetailValue($row));
        continue;
      }
      $fragments = [];
      foreach ($row as $col_key => $col_val) {
        $fragments[] = (string) $col_key . ': ' . $this->stringifyDetailValue($col_val);
      }
      $lines[] = '    – ' . $this->escapeSlackMrkdwn(implode(' · ', $fragments));
    }
    return $lines;
  }

  /**
   * Turns a detail cell value into plain text for Slack.
   */
  protected function stringifyDetailValue(mixed $value): string {
    if ($value === NULL) {
      return '';
    }
    if (is_scalar($value)) {
      $s = (string) $value;
    }
    elseif (is_array($value)) {
      $encoded = json_encode($value, JSON_UNESCAPED_UNICODE);
      return trim($encoded !== FALSE ? $encoded : '[]');
    }
    else {
      $s = (string) $value;
    }
    $s = html_entity_decode(strip_tags($s), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    return trim(preg_replace('/\s+/u', ' ', $s));
  }

  /**
   * Escapes characters that break Slack mrkdwn in plain text payloads.
   */
  protected function escapeSlackMrkdwn(string $text): string {
    return str_replace(['&', '<'], ['&amp;', '&lt;'], $text);
  }

  /**
   * Maps a plugin result to success | warning | error.
   */
  protected function classifyResult(array $result): string {
    if ($result['status']) {
      return 'SUCCESS';
    }
    elseif (!empty($result['is_warning'])) {
      return 'WARNING';
    }
    return 'ERROR';
  }

}
