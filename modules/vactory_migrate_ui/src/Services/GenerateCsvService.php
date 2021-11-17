<?php

namespace Drupal\vactory_migrate_ui\Services;

/**
 * Generate csv service class.
 */
class GenerateCsvService {

  /**
   * Function qui permet de générer fichier csv.
   */
  function generateCsv($header, $data, $delimiter, $file_name, $file_path) {
    if (!file_exists($file_path)) {
      mkdir($file_path, 0777, TRUE);
    }
    $file = fopen($file_path . '/' . $file_name . '.csv', 'w');
    fputcsv($file, $header, $delimiter);
    $items = [];
    if (is_array($data) || is_object($data)) {
      foreach ($data as $key => $value) {
        $item = [];
        foreach ($header as $h) {
          if (array_key_exists($h, $value)) {
            array_push($item, trim($value[$h]));
          }
        }
        array_push($items, $item);
      }
    }
    if (is_array($items) && !empty($items)) {
      foreach ($items as $row) {
        fputcsv($file, $row, $delimiter);
      }
    }
    fclose($file);
  }

}
