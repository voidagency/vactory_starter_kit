<?php

namespace Drupal\vactory_page_import\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;
use Drupal\media\MediaInterface;
use Drupal\node\Entity\Node;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\vactory_page_import\PageImportConstants;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

/**
 * Exports pages (with DFs as excel).
 */
class Export extends FormBase {

  /**
   * Returns a unique string identifying the form.
   *
   * The returned ID should be a unique string that can be a valid PHP function
   * name, since it's used in hook implementation names such as
   * hook_form_FORM_ID_alter().
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'vactory_page_import.export';
  }

  /**
   * Form constructor.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The form structure.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $sql = "SELECT nid, title from node_field_data ";
    $sql .= "WHERE  type = 'vactory_page' AND default_langcode = 1";

    $query = \Drupal::database()->query($sql);
    $result = $query->fetchAll(\PDO::FETCH_KEY_PAIR);

    $form['pages'] = [
      '#type' => 'checkboxes',
      '#title' => t('Concerned pages'),
      '#options' => $result,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t("Export pages"),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $pages = $form_state->getValue('pages');
    $pages = array_filter($pages, function ($item) {
      return $item !== 0;
    });
    $available_languages = \Drupal::languageManager()->getLanguages();
    $available_languages = array_filter($available_languages, function ($language) {
      return !$language->isDefault();
    });

    $pages_data = [];
    foreach ($pages as $page) {
      $node = Node::load($page);
      $node_id = $node->get('node_id')->value;
      $pages_data[$node_id]['original'] = $this->constructNodeArray($node, 'original');
      foreach (array_keys($available_languages) as $language) {
        if ($node->hasTranslation($language)) {
          $node_translation = $node->getTranslation($language);
          $pages_data[$node_id][$language] = $this->constructNodeArray($node_translation, $language);
        }
      }
    }
    $filapath = $this->createExcelFromArray($pages_data);
    $response = new BinaryFileResponse($filapath, 200, [], FALSE);
    $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, "pages.xlsx");
    $response->deleteFileAfterSend(TRUE);
    $response->send();
  }

  /**
   * Transform node to array (to be added to excel file).
   */
  private function constructNodeArray($node, $language) {
    $node_array = [
      'language' => $language,
      'title' => $node->get('title')->value,
      'node_summary' => $node->get('node_summary')->value,
      'status' => $node->get('status')->value,
    ];

    $node_id = $node->get('node_id')->value;
    $paragraphs = $node->get('field_vactory_paragraphs')->getValue();
    foreach ($paragraphs as $paragraph) {
      // Load paragraph entity.
      $paragraph_entity = Paragraph::load($paragraph['target_id']);
      if (!isset($paragraph_entity)) {
        continue;
      }
      if ($language !== 'original' && $paragraph_entity->hasTranslation($language)) {
        $paragraph_entity = $paragraph_entity->getTranslation($language);
      }
      // Get widget.
      $paragraph_widget = $paragraph_entity->get('field_vactory_component')->getValue();
      $paragraph_widget = reset($paragraph_widget);
      $widget_settings = \Drupal::service('vactory_dynamic_field.vactory_provider_manager')->loadSettings($paragraph_widget['widget_id']);
      $widget_data = json_decode($paragraph_widget['widget_data'], TRUE);

      // Get paragraph identifier.
      $paragraph_identifier = $paragraph_entity->get('paragraph_identifier')->value;
      if (!isset($paragraph_identifier)) {
        $paragraph_identifier = $paragraph_widget['widget_id'];
      }
      $paragraph_identifier = str_replace($node_id . '|', '', $paragraph_identifier);

      if ($widget_settings['multiple']) {
        foreach ($widget_data as $key => $value) {
          if ($key == 'extra_field') {
            foreach ($value as $field_key => $field_value) {
              if (str_starts_with($field_key, 'group_')) {
                foreach ($field_value as $sub_key => $sub_value) {
                  $field_settings = $widget_settings['extra_fields'][$field_key][$sub_key];
                  $normalized_value = $this->normalizeDfData($sub_value, $field_settings);
                  $group_key = $this->replaceGroup($field_key);
                  $node_array['paragraphs'][$paragraph_identifier][$group_key . '|' . $sub_key . '|' . $field_settings['type']] = $normalized_value;
                }
              }
              else {
                $field_settings = $widget_settings['extra_fields'][$field_key];
                $normalized_value = $this->normalizeDfData($field_value, $field_settings);
                $node_array['paragraphs'][$paragraph_identifier][$field_key . '|' . $field_settings['type']] = $normalized_value;
              }
            }
          }
          elseif (is_numeric($key) && $key !== 'pending_content') {
            foreach ($value as $field_key => $field_value) {
              if ($field_key !== '_weight' && $field_key !== 'remove') {
                if (str_starts_with($field_key, 'group_')) {
                  foreach ($field_value as $sub_key => $sub_value) {
                    $field_settings = $widget_settings['fields'][$field_key][$sub_key];
                    $normalized_value = $this->normalizeDfData($sub_value, $field_settings);
                    $group_key = $this->replaceGroup($field_key);
                    $node_array['paragraphs'][$paragraph_identifier][$group_key . '|' . $sub_key . '|' . $key . '|' . $field_settings['type']] = $normalized_value;
                  }
                }
                else {
                  $field_settings = $widget_settings['fields'][$field_key];
                  $normalized_value = $this->normalizeDfData($field_value, $field_settings);
                  $node_array['paragraphs'][$paragraph_identifier][$field_key . '|' . $key . '|' . $field_settings['type']] = $normalized_value;
                }
              }
            }
          }
        }
      }
      else {
        $widget_data = $widget_data[0];
        foreach ($widget_data as $key => $value) {
          if (str_starts_with($key, 'group_')) {
            foreach ($value as $sub_key => $sub_value) {
              $field_settings = $widget_settings['fields'][$key][$sub_key];
              $normalized_value = $this->normalizeDfData($sub_value, $field_settings);
              $group_key = $this->replaceGroup($key);
              $node_array['paragraphs'][$paragraph_identifier][$group_key . '|' . $sub_key . '|' . $field_settings['type']] = $normalized_value;
            }
          }
          else {
            $field_settings = $widget_settings['fields'][$key];
            $normalized_value = $this->normalizeDfData($value, $field_settings);
            $node_array['paragraphs'][$paragraph_identifier][$key . '|' . $field_settings['type']] = $normalized_value;
          }
        }
      }
    }

    return $node_array;
  }

  /**
   * Creates excel file from array (node data).
   */
  private function createExcelFromArray($sheetData) {
    // Create a new Spreadsheet object.
    $spreadsheet = new Spreadsheet();
    // Remove the default sheet created by PhpSpreadsheet.
    $spreadsheet->removeSheetByIndex(0);

    $style = [
      'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => [
          'rgb' => 'faf732',
        ],
      ],
    ];

    foreach ($sheetData as $sheetName => $data) {
      // Add a new sheet.
      $activeSheet = $spreadsheet->createSheet()->setTitle($sheetName);
      $spreadsheet->setActiveSheetIndex($spreadsheet->getIndex($activeSheet));

      // Set active sheet.
      $sheet = $spreadsheet->getActiveSheet();
      $sheet->getStyle($sheet->calculateWorksheetDimension())->getAlignment()->setWrapText(TRUE);

      // Add data to the sheet.
      $current_col = 2;
      foreach ($data as $rowData) {
        $current_row = 1;
        foreach ($rowData as $key => $value) {
          if ($key !== 'paragraphs') {
            $sheet->setCellValue([1, $current_row], $key);
            $sheet->setCellValue([$current_col, $current_row], $value);
            $current_row++;
          }
          else {
            foreach ($value as $paragraph_key => $paragraph_data) {
              $sheet->setCellValue([1, $current_row], $paragraph_key);
              $sheet->getStyle([1, $current_row, count($data) + 1, $current_row])->applyFromArray($style);
              $current_row++;
              foreach ($paragraph_data as $k => $v) {
                if (is_array($v)) {
                  $v = json_encode($v);
                }
                $sheet->setCellValue([1, $current_row], $k);
                $sheet->setCellValue([$current_col, $current_row], $v);
                $current_row++;
              }
            }
          }

        }
        $current_col++;
      }

      foreach ($sheet->getColumnIterator() as $column) {
        $sheet->getColumnDimension($column->getColumnIndex())->setAutoSize(TRUE);
      }

    }

    // Create a new Excel writer.
    $writer = new Xlsx($spreadsheet);

    // Save the Excel file.
    $output_uri = 'private://page-export';
    if (!file_exists($output_uri)) {
      mkdir($output_uri, 0777);
    }
    $time = time();
    $file_path = \Drupal::service('file_system')
      ->realpath($output_uri) . "/pages_{$time}.xlsx";
    $writer->save($file_path);
    return $file_path;

  }

  /**
   * Normalize Df Data.
   */
  private function normalizeDfData($value, $field_settings) {
    $type = $field_settings['type'];
    $media_types = PageImportConstants::MEDIA_FIELD_NAMES;
    if (in_array($type, array_keys($media_types))) {
      $value = reset($value);
      $media_field_name = PageImportConstants::MEDIA_FIELD_NAMES[$type];
      $mid = $value['selection'][0]['target_id'];
      $media = Media::load($mid);
      if ($media instanceof MediaInterface) {
        if ($type !== 'remote_video') {
          $fid = $media->get($media_field_name)->target_id;
          if ($fid) {
            $file = File::load($fid);
            if ($file) {
              $url = \Drupal::service('file_url_generator')->generateAbsoluteString($file->getFileUri());
              return "{$url} ({$mid})";
            }
          }
        }
        else {
          $url = $media->get($media_field_name)->value;
          return "{$url} ({$mid})";
        }
      }
    }
    if ($type === 'url_extended') {
      return "{$value['title']} ({$value['url']})";
    }
    if ($type == 'select') {
      $options = array_keys($field_settings['options']['#options']);
      $options = array_map(function ($option) use ($value) {
        if ($option == $value) {
          return "#{$option}";
        }
        return $option;
      }, $options);

      return implode(',', $options);
    }
    if ($type == 'text_format') {
      return $value['value'];
    }

    return $value;
  }

  /**
   * Replace "group_" by "g_".
   */
  private function replaceGroup($string) {
    $string = substr($string, 6);
    return 'g_' . $string;
  }

}
