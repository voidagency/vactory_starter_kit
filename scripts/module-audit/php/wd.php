<?php

/**
 * @file
 * Module-audit: prints watchdog errors newer than wid $extra[0], as JSON.
 *
 * Severity error or worse; run with drush php:script.
 */

$since = (int) ($extra[0] ?? 0);
$rows = [];
if (\Drupal::database()->schema()->tableExists('watchdog')) {
  $q = \Drupal::database()->query('SELECT wid, type, severity, message, variables, location FROM {watchdog} WHERE wid > :w AND severity <= 3 ORDER BY wid LIMIT 200', [':w' => $since]);
  foreach ($q as $r) {
    $vars = @unserialize($r->variables, ['allowed_classes' => FALSE]);
    $msg = $r->message;
    if (is_array($vars)) {
      foreach ($vars as $k => $v) {
        if (is_scalar($v) && $k !== '@backtrace_string') {
          $msg = str_replace($k, (string) $v, $msg);
        }
      }
    }
    $rows[] = [
      'wid' => (int) $r->wid,
      'type' => $r->type,
      'severity' => (int) $r->severity,
      'message' => mb_substr(strip_tags($msg), 0, 400),
      'location' => mb_substr((string) $r->location, 0, 150),
    ];
  }
}
echo json_encode($rows, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
