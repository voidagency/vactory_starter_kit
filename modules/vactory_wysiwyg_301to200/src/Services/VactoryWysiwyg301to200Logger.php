<?php

namespace Drupal\vactory_wysiwyg_301to200\Services;

use Drupal\Component\Serialization\Json;

/**
 * Vactory wysiwyg 301to200 service.
 */
class VactoryWysiwyg301to200Logger {

  protected $logFilePath;
  protected $logFileName;

  public function __construct() {
    $this->logFileName = 'redirects-log.json';
    $this->logFilePath = 'public://vactory_wysiwyg_301to200';
  }

  /**
   * Add link info to log.
   */
  public function addLinkInfo($page, $source_url, $final_url) {
    $uuid = base64_encode($page . $source_url);
    if (!file_exists($this->logFilePath)) {
      mkdir($this->logFilePath, 0777, TRUE);
    }
    $redirects = file_get_contents($this->logFilePath . '/' . $this->logFileName);
    if ($redirects) {
      $redirects = Json::decode($redirects);
    }
    else {
      $redirects = [];
    }

    $is_new = TRUE;
    if (!empty($redirects)) {
      $filtered_redirects = array_filter($redirects, function ($redirect) use ($uuid) {
        return isset($redirect['uuid']) && $redirect['uuid'] === $uuid;
      });
      if (count($filtered_redirects) > 0) {
        $is_new = FALSE;
      }
    }
    if ($is_new) {
      $redirects[] = [
        'uuid' => $uuid,
        'page' => $page,
        'source_url' => $source_url,
        'final_url' => $final_url,
      ];
      file_put_contents($this->logFilePath . '/' . $this->logFileName, Json::encode($redirects));
    }
  }

  public function clearLog() {
    if (!file_exists($this->logFilePath)) {
      mkdir($this->logFilePath, 0777, TRUE);
    }
    file_put_contents($this->logFilePath . '/' . $this->logFileName, '[]');
  }

}
