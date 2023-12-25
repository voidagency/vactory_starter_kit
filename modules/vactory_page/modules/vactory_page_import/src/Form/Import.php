<?php

namespace Drupal\vactory_page_import\Form;

use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Serialization\Yaml;
use Drupal\file\Entity\File;
use Drupal\file\FileInterface;
use Drupal\node\Entity\Node;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\vactory_page_import\PageImportConstants;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Exception;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Imports (create/update) page from excel.
 */
class Import extends FormBase {

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
    return 'vactory_page_import.import';
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

    $form['excel'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Excel file'),
      '#name' => 'csv',
      '#upload_location' => 'private://page-import',
      '#upload_validators' => [
        'file_validate_extensions' => ['xlsx'],
      ],
      '#description' => t("Load the Excel file to import.<br>"),
      '#required' => TRUE,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t("Start process"),
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
    $file_id = $form_state->getValue('excel');
    $file_id = (int) reset($file_id);
    $file = File::load($file_id);
    $file_path = \Drupal::service('file_system')
      ->realpath($file->getFileUri());
    $data = $this->readExcelToArray($file_path);
    foreach ($data as $key => $page) {
      // Check if page alrady exists (by node_id).
      $query = \Drupal::entityTypeManager()->getStorage('node')->getQuery();
      $query->accessCheck(FALSE);
      $query->condition('type', 'vactory_page');
      $query->condition('node_id', $key);
      $ids = $query->execute();
      if (empty($ids)) {
        $this->createDynamicFields($page['original']);
        $this->createNode($key, $page);
      }
      elseif (count($ids) == 1) {
        // Load the page (node) and update it.
        $this->createDynamicFields($page['original']);
        $nid = reset($ids);
        $node = Node::load($nid);
        $this->updatePage($node, $page);
      }
    }
  }

  /**
   * Transform input excel to array.
   */
  private function readExcelToArray($filePath) {
    // Load the spreadsheet file.
    $spreadsheet = IOFactory::load($filePath);

    $data = [];

    // Process sheets.
    foreach ($spreadsheet->getSheetNames() as $sheetName) {
      $data[$sheetName] = $this->readSheetToArray($spreadsheet->getSheetByName($sheetName));
    }

    return $data;
  }

  /**
   * Transform input excel (per sheet) to array.
   */
  private function readSheetToArray($sheet) {
    // Get the highest row and column indices.
    $highestRow = $sheet->getHighestRow();
    $highestColumn = $sheet->getHighestColumn();

    // Convert the column letter to a numerical index.
    try {
      $highestColumnIndex = Coordinate::columnIndexFromString($highestColumn);
    }
    catch (Exception $e) {
    }

    // Initialize an array to store the data.
    $data = [];
    $current_paragraph = NULL;
    for ($col = 2; $col <= $highestColumnIndex; ++$col) {
      $language = $sheet->getCell([$col, 1])->getValue();
      if (is_null($language)) {
        break;
      }
      for ($row = 1; $row <= $highestRow; ++$row) {
        $key = $sheet->getCell([1, $row])->getValue();
        if (is_null($key)) {
          break;
        }
        $value = $sheet->getCell([$col, $row])->getValue();
        if (!str_starts_with($key, 'paragraph') && !is_null($value) && is_null($current_paragraph)) {
          $data[$language][$key] = $value;
        }
        if (str_starts_with($key, 'paragraph') && is_null($value)) {
          $current_paragraph = $key;
        }
        elseif (!is_null($current_paragraph)) {
          $data[$language][$current_paragraph][$key] = $value;
        }
      }
      $current_paragraph = NULL;
    }
    return $data;
  }

  /**
   * Creates DF setting.
   */
  private function createDynamicFields(array $data) {
    foreach ($data as $key => $value) {
      if (str_starts_with($key, 'paragraph')) {
        $config = [];
        $split = explode('|', $key);
        $df_key = end($split);
        $config['name'] = $this->snakeToHuman($df_key);
        $config['enabled'] = TRUE;
        $config['multiple'] = $this->isDfMultiple(array_keys($value));
        $config['category'] = 'Imported content';

        foreach ($value as $field => $field_value) {
          $split = explode('|', $field);
          // @todo check if 2nd part is an available DF type.
          if (count($split) == 3 && is_numeric($split[1])) {
            $this->generateDfField($config, $split, 'fields', $field_value);
          }
          elseif (count($split) == 3) {
            $section = $config['multiple'] ? 'extra_fields' : 'fields';
            $this->generateDfField($config, $split, $section, $field_value, reset($split));
          }
          elseif (count($split) == 2) {
            $section = $config['multiple'] ? 'extra_fields' : 'fields';
            $this->generateDfField($config, $split, $section, $field_value);
          }
          elseif (count($split) == 4 && str_starts_with($field, 'g_')) {
            $this->generateDfField($config, $split, 'fields', $field_value, reset($split));
          }
        }
        $yaml_config = Yaml::encode($config);
        $this->writeDfFile($yaml_config, $df_key);
      }
    }
  }

  /**
   * Check if DF is multiple.
   */
  private function isDfMultiple($config) {
    foreach ($config as $item) {
      $split = explode('|', $item);
      if (count($split) == 3 && is_numeric($split[1])) {
        return TRUE;
      }
      if (count($split) == 4 && str_starts_with($item, 'g_')) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Transforms string from snake case to human format.
   */
  private function snakeToHuman($string) {
    $words = explode('_', $string);
    return ucfirst(implode(' ', $words));
  }

  /**
   * Generates single DF field (setting.yml).
   */
  private function generateDfField(&$config, $pieces, $section, $value, $group = '') {
    $field_key = empty($group) ? reset($pieces) : $pieces[1];
    $field_type = end($pieces);
    if (!empty($group)) {
      $group_label = substr($group, 2);
      $group_key = 'group_' . $group_label;
      $config[$section][$group_key]['g_title'] = $this->snakeToHuman($group_label);
      $config[$section][$group_key][$field_key] = [
        'type' => $field_type,
        'label' => $this->snakeToHuman($field_key),
      ];
    }
    else {
      $config[$section][$field_key] = [
        'type' => $field_type,
        'label' => $this->snakeToHuman($field_key),
      ];
    }

    if ($field_type == 'select') {
      $options = [];
      $split_value = explode(',', $value);
      foreach ($split_value as $option) {
        $option = str_replace('#', '', $option);
        $options[$option] = ucfirst($option);
      }
      if (!empty($group)) {
        $config[$section][$group_key][$field_key]['options']['#options'] = $options;
      }
      else {
        $config[$section][$field_key]['options']['#options'] = $options;
      }

    }
  }

  /**
   * Create the YML file (DF).
   */
  private function writeDfFile($content, $name) {
    $dest_uri = 'private://imported-pages-df';
    $dest_df_uri = $dest_uri . '/' . $name;
    if (!file_exists($dest_df_uri)) {
      mkdir($dest_df_uri, 0777, TRUE);
    }
    $filepath = \Drupal::service('file_system')->realpath($dest_df_uri . '/settings.yml');

    $printed = file_put_contents($filepath, $content);
    return (bool) $printed;
  }

  /**
   * Created node (page).
   */
  private function createNode($page_key, $data) {
    // @todo Insure that original is the first item
    $node_entity = NULL;
    foreach ($data as $language => $language_value) {
      $node = [
        'type' => 'vactory_page',
        'status' => 1,
        'node_id' => $page_key,
      ];
      foreach ($language_value as $key => $value) {
        if (str_starts_with($key, 'paragraph')) {
          $split = explode('|', $key);
          $widget_id = "vactory_page_import:" . end($split);
          $widget_data = $this->normalizeWidgetData($value);
          $paragraph = [
            "type" => "vactory_component",
            "paragraph_identifier" => $page_key . '|' . $key,
            "field_vactory_title" => $this->snakeToHuman(end($split)),
            "field_vactory_component" => [
              "widget_id" => $widget_id,
              "widget_data" => $widget_data,
            ],
          ];
          if ($language == 'original') {
            $paragraph = Paragraph::create($paragraph);
            $paragraph->save();
            $node['field_vactory_paragraphs'][] = [
              'target_id' => $paragraph->id(),
              'target_revision_id' => \Drupal::entityTypeManager()
                ->getStorage('paragraph')
                ->getLatestRevisionId($paragraph->id()),
            ];
          }
          else {
            $concerned_paragraph = $this->findParagraphByNodeAndKey($node_entity, $key);
            $concerned_paragraph->addTranslation($language, $paragraph);
            $concerned_paragraph->save();
          }

        }
        else {
          $node[$key] = $value;
        }
      }
      if ($language == 'original') {
        $node_entity = Node::create($node);
        $node_entity->save();
      }
      else {
        $node_entity->addTranslation($language, $node);
        $node_entity->save();
      }
    }

  }

  /**
   * Normalize widget data.
   */
  private function normalizeWidgetData($data) {
    $result = [];
    $is_multiple = $this->isDfMultiple(array_keys($data));
    foreach ($data as $key => $value) {
      $is_group = str_starts_with($key, 'g_');
      $group_key = '';
      $split = explode('|', $key);
      if ($is_group) {
        $group_key = reset($split);
        $group_key = substr($group_key, 2);
        $group_key = 'group_' . $group_key;
      }
      $field_key = !$is_group ? reset($split) : $split[1];
      $field_type = end($split);
      $normalized_value = $this->normalizeDfValue($value, $field_type);
      if (count($split) == 2 && !$is_multiple) {
        $result['0'][$field_key] = $normalized_value;
      }
      if (count($split) == 2 && $is_multiple) {
        $result['extra_field'][$field_key] = $normalized_value;
      }
      if (count($split) == 3 && $is_multiple && is_numeric($split[1])) {
        $result[$split[1]][$field_key] = $normalized_value;
      }
      if (count($split) == 3 && $is_group && !$is_multiple) {
        $result[0][$group_key][$field_key] = $normalized_value;
      }

      if (count($split) == 3 && $is_group && $is_multiple) {
        $result['extra_field'][$group_key][$field_key] = $normalized_value;
      }

      if (count($split) == 4 && $is_group && $is_multiple) {
        $result[$split[2]][$group_key][$field_key] = $normalized_value;
      }

    }
    return json_encode($result);
  }

  /**
   * Normalize Df value.
   */
  private function normalizeDfValue($value, $field_type) {
    $media_fields = PageImportConstants::MEDIA_FIELD_NAMES;
    if (array_key_exists($field_type, $media_fields)) {
      $extracted = $this->extractTextWithParentheses($value);
      return $this->denormalizeDfMedia($extracted['out'], $field_type, $media_fields[$field_type], $extracted['in']);
    }

    if ($field_type == 'url_extended') {
      $extracted = $this->extractTextWithParentheses($value);
      $title = $extracted['out'];
      $url = $extracted['in'];
      if (str_starts_with($url, '#')) {
        $page_key = str_replace('#', '', $url);
        $query = \Drupal::entityTypeManager()->getStorage('node')->getQuery();
        $query->accessCheck(FALSE);
        $query->condition('type', 'vactory_page');
        $query->condition('node_id', $page_key);
        $ids = $query->execute();
        if (count($ids) == 1) {
          $nid = reset($ids);
          $url = "/node/{$nid}";
        }
      }
      return [
        "title" => $title,
        "url" => $url,
        "attributes" => [
          "class" => "",
          "id" => "",
          "target" => "",
          "rel" => "",
        ],
      ];
    }

    if ($field_type == 'select') {
      $split_value = explode(',', $value);
      foreach ($split_value as $option) {
        if (str_starts_with($option, '#')) {
          return str_replace('#', '', $option);
        }
      }
    }

    if ($field_type == 'checkbox') {
      return (bool) $value;
    }

    if ($field_type == 'text_format') {
      return [
        'value' => $value,
        'format' => 'full_html',
      ];
    }

    return $value;
  }

  /**
   * Extract text with parentheses.
   */
  private function extractTextWithParentheses($text) {
    // Define a regular expression pattern for text inside parentheses.
    $pattern = '/\((.*?)\)/';

    // Match all occurrences of text inside parentheses.
    preg_match_all($pattern, $text, $matches);

    // Get the last match (content inside the last parentheses).
    $lastMatch = end($matches[1]);

    // Extract the text outside parentheses.
    $textOutsideParentheses = trim(str_replace(end($matches[0]), '', $text));

    // Return an associative array with extracted text.
    return [
      'in' => $lastMatch ?? '',
      'out' => $textOutsideParentheses,
    ];
  }

  /**
   * Denormalize Df media.
   */
  private function denormalizeDfMedia($df_field_value, $media_type, $media_field, $image_alt = '') {
    if (is_numeric($image_alt)) {
      $df_field_value = [
        uniqid() => [
          'selection' => [
            [
              'target_id' => $image_alt,
            ],
          ],
        ],
      ];
    }
    elseif (!empty($df_field_value)) {
      $mid = $this->generateMediaFromUrl($df_field_value, $media_type, $media_field, $image_alt);
      $df_field_value = [];
      if ($mid) {
        $df_field_value = [
          uniqid() => [
            'selection' => [
              [
                'target_id' => $mid,
              ],
            ],
          ],
        ];
      }
    }
    return $df_field_value;
  }

  /**
   * Generates new media fromexternal url.
   */
  private function generateMediaFromUrl(string $url, string $type, string $field, string $image_alt): ?int {
    $media = NULL;
    switch ($type) {
      case 'remote_video':
        $media = \Drupal::entityTypeManager()->getStorage('media')->create([
          'bundle' => $type,
          'uid' => '1',
          $field => $url,
        ]);
        break;

      default:
        if (!file_exists('public://page-import-media')) {
          mkdir('public://page-import-media', 0777);
        }
        $filename = pathinfo($url);
        $filename = $filename['filename'];
        $filename = preg_replace("/-[^-]*$/", "", $filename);
        $filename = ucfirst(strtolower(str_replace('-', ' ', $filename)));
        $file = system_retrieve_file($url, 'public://page-import-media', TRUE, FileSystemInterface::EXISTS_RENAME);
        if ($file instanceof FileInterface) {
          $file->save();
          $media_data = [
            'bundle' => $type,
            'uid' => '1',
            $field => [
              'target_id' => $file->id(),
              'title' => $filename,
              'alt' => empty($image_alt) ? $filename : $image_alt,
            ],
          ];
          if ($type == 'image') {
            $file_metadata = $file->getAllMetadata() ?? [];
            if (!empty($file_metadata)) {
              $media_data[$field]['width'] = $file_metadata['width'];
              $media_data[$field]['height'] = $file_metadata['height'];
            }
          }
          $media = \Drupal::entityTypeManager()->getStorage('media')
            ->create($media_data);
        }
        break;
    }

    if (!isset($media)) {
      return NULL;
    }

    $media->setPublished(TRUE)->save();
    return $media->id();
  }

  /**
   * Updates the node (page).
   */
  private function updatePage(Node $node, $page) {
    $node_id = $node->get('node_id')->value;

    foreach ($page as $language => $data) {
      if ($language !== 'original' && !$node->hasTranslation($language)) {
        $node_translation = [];
        foreach ($data as $field => $value) {
          if (!str_starts_with($field, 'paragraph')) {
            $node_translation[$field] = $value;
          }
        }
        $node->addTranslation($language, $node_translation);
        $node->save();
      }
      foreach ($data as $key => $value) {
        if (str_starts_with($key, 'paragraph')) {
          $split = explode('|', $key);
          $widget_id = "vactory_page_import:" . end($split);
          $widget_data = $this->normalizeWidgetData($value);
          $field_vactory_component = [
            "widget_id" => $widget_id,
            "widget_data" => $widget_data,
          ];
          // Add new paragraph.
          $paragraph = [
            "type" => "vactory_component",
            "paragraph_identifier" => $node_id . '|' . $key,
            "field_vactory_title" => $this->snakeToHuman(end($split)),
            "field_vactory_component" => $field_vactory_component,
          ];
          if ($language == 'original') {
            // Search for paragraph with identifier [node_id]|$key.
            $paragraph_entity = $this->findParagraphByNodeAndKey($node, $key);
            if (!empty($paragraph_entity)) {
              // Update founded paragraph.
              $paragraph_entity->field_vactory_component = $field_vactory_component;
              $paragraph_entity->save();
            }
            else {
              $paragraph = Paragraph::create($paragraph);
              $paragraph->save();
              $node->field_vactory_paragraphs[] = [
                'target_id' => $paragraph->id(),
                'target_revision_id' => \Drupal::entityTypeManager()
                  ->getStorage('paragraph')
                  ->getLatestRevisionId($paragraph->id()),
              ];
              $node->save();
            }
          }
          else {
            $concerned_paragraph = $this->findParagraphByNodeAndKey($node, $key);
            if ($concerned_paragraph->hasTranslation($language)) {
              $concerned_paragraph_trans = $concerned_paragraph->getTranslation($language);
              $concerned_paragraph_trans->field_vactory_component = $field_vactory_component;
              $concerned_paragraph_trans->save();
            }
            else {
              $concerned_paragraph->addTranslation($language, $paragraph);
              $concerned_paragraph->save();
            }
          }
        }
      }
    }
  }

  /**
   * Finds a paragraph entity based on node and paragraph_identifier.
   */
  private function findParagraphByNodeAndKey(Node $node, $key) {
    $node_id = $node->get('node_id')->value;
    $paragraph_entity = \Drupal::entityTypeManager()->getStorage('paragraph')->loadByProperties([
      'paragraph_identifier' => $node_id . '|' . $key,
      'parent_type' => 'node',
      'parent_id' => $node->id(),
    ]);
    if (count($paragraph_entity) == 1) {
      return reset($paragraph_entity);
    }
    return [];
  }

}
