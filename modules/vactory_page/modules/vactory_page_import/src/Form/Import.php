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
      $df_settings = $this->prepareDfSettings($page['original']);
      $this->createDynamicFields($df_settings);
      if (empty($ids)) {
        $this->createNode($key, $page);
      }
      elseif (count($ids) == 1) {
        // Load the page (node) and update it.
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
    $current_multiple = NULL;
    $current_tab = NULL;
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
        if ($value == 'IGNORE') {
          continue;
        }
        if (!str_starts_with($key, 'paragraph') && !is_null($value) && is_null($current_paragraph) && is_null($current_multiple)) {
          $data[$language][$key] = $value;
        }
        if (str_starts_with($key, 'multiple') && is_null($value)) {
          $current_multiple = $key;
          $current_paragraph = NULL;
        }
        if (str_starts_with($key, 'tab') && is_null($value) && !is_null($current_multiple)) {
          $current_tab = $key;
        }
        if (str_starts_with($key, 'paragraph') && is_null($value)) {
          $current_paragraph = $key;
          $current_multiple = NULL;
          $current_tab = NULL;
        }
        if (!str_starts_with($key, 'paragraph') && !str_starts_with($key, 'multiple') && !str_starts_with($key, 'tab')) {
          if (!is_null($current_paragraph)) {
            $data[$language][$current_paragraph][$key] = $value;
          }
          if (!is_null($current_multiple) && !is_null($current_tab)) {
            $data[$language][$current_multiple][$current_tab][$key] = $value;
          }
        }
      }
      $current_paragraph = NULL;
      $current_multiple = NULL;
      $current_tab = NULL;
    }
    return $data;
  }

  /**
   * Prepare (normalize) data for DF settings creation.
   */
  private function prepareDfSettings(array $data) {
    $dynamic_field_settings = [];
    foreach ($data as $key => $value) {
      if (str_starts_with($key, 'paragraph')) {
        $dynamic_field_id = explode('|', $key);
        $dynamic_field_id = end($dynamic_field_id);
        $dynamic_field_settings[$dynamic_field_id] = $value;
      }
      if (str_starts_with($key, 'multiple')) {
        foreach ($value as $tab => $tab_dfs) {
          foreach ($tab_dfs as $sub_key => $sub_value) {
            $split = explode('|', $sub_key);
            $dynamic_field_id = reset($split);
            $dynamic_field_field = substr($sub_key, strlen($dynamic_field_id) + 1);
            $dynamic_field_settings[$dynamic_field_id][$dynamic_field_field] = $sub_value;
          }
        }
      }
    }
    return $dynamic_field_settings;
  }

  /**
   * Creates DF setting.
   */
  private function createDynamicFields(array $settings) {
    foreach ($settings as $key => $value) {
      $config = [];
      $split = explode('|', $key);
      $config['name'] = $this->snakeToHuman($key);
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
      $this->writeDfFile($yaml_config, $key);
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
        $split = explode('|', $key);
        if (str_starts_with($key, 'paragraph')) {
          $widget_id = "vactory_page_import:" . end($split);
          $widget_data = $this->normalizeWidgetData($value);
          $paragraph = [
            "type" => "vactory_component",
            "paragraph_key" => $page_key . '|' . $key,
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
        elseif (str_starts_with($key, 'multiple')) {
          $multi_paragraph_type = end($split);
          $paragraph = [
            'type' => 'vactory_paragraph_multi_template',
            'paragraph_key' => $page_key . '|' . $key,
            'field_multi_paragraph_type' => $multi_paragraph_type,
            'field_vactory_paragraph_tab' => [],
          ];
          foreach ($value as $tab_key => $tab_values) {
            $split_key = explode('|', $tab_key);
            $tab_title = $this->snakeToHuman(end($split_key));
            $paragraph_tab_template = [
              "type" => "vactory_paragraph_tab",
              "paragraph_key" => $page_key . '|' . $key . '|' . $tab_key,
              "field_vactory_title" => $tab_title,
              "field_tab_templates" => [],
            ];
            $templates = [];
            foreach ($tab_values as $field_key => $field_value) {
              $split = explode('|', $field_key);
              $dynamic_field_id = reset($split);
              $dynamic_field_field = substr($field_key, strlen($dynamic_field_id) + 1);
              $templates[$dynamic_field_id][$dynamic_field_field] = $field_value;
            }
            foreach ($templates as $df_key => $data) {
              $widget_data = $this->normalizeWidgetData($data);
              $paragraph_tab_template['field_tab_templates'][] = [
                "widget_id" => 'vactory_page_import:' . $df_key,
                "widget_data" => $widget_data,
              ];
            }
            $paragraph_tab_template = Paragraph::create($paragraph_tab_template);
            $paragraph_tab_template->save();
            $paragraph['field_vactory_paragraph_tab'][] = [
              'target_id' => $paragraph_tab_template->id(),
              'target_revision_id' => \Drupal::entityTypeManager()
                ->getStorage('paragraph')
                ->getLatestRevisionId($paragraph_tab_template->id()),
            ];
          }
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
      if (empty($value)) {
        return $value;
      }
      $extracted = $this->extractTextWithParentheses($value);
      return $this->denormalizeDfMedia($extracted['out'], $field_type, $media_fields[$field_type], $extracted['in']);
    }

    if ($field_type == 'url_extended') {
      if (empty($value)) {
        return $value;
      }
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
          if (!str_starts_with($field, 'paragraph') && !str_starts_with($field, 'multiple')) {
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
            "paragraph_key" => $node_id . '|' . $key,
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
        if (str_starts_with($key, 'multiple')) {
          $paragraph_entity = $this->findParagraphByNodeAndKey($node, $key);
          if ($paragraph_entity) {
            foreach ($value as $tab_key => $tab_values) {
              $paragraph_identifier = $key . '|' . $tab_key;

              $templates = [];
              $tab_templates = [];
              foreach ($tab_values as $field_key => $field_value) {
                $split = explode('|', $field_key);
                $dynamic_field_id = reset($split);
                $dynamic_field_field = substr($field_key, strlen($dynamic_field_id) + 1);
                $templates[$dynamic_field_id][$dynamic_field_field] = $field_value;
              }
              foreach ($templates as $df_key => $data) {
                $widget_data = $this->normalizeWidgetData($data);
                $tab_templates[] = [
                  "widget_id" => 'vactory_page_import:' . $df_key,
                  "widget_data" => $widget_data,
                ];
              }

              $paragraph_tab_entity = $this->findParagraphTabByParagraph($paragraph_entity, $paragraph_identifier, $node_id);
              if ($paragraph_tab_entity) {
                $paragraph_tab_entity->field_tab_templates = $tab_templates;
                $paragraph_tab_entity->save();
              }
              else {
                $split_key = explode('|', $tab_key);
                $tab_title = $this->snakeToHuman(end($split_key));
                $paragraph_tab_template = [
                  "type" => "vactory_paragraph_tab",
                  "paragraph_key" => $node_id . '|' . $key . '|' . $tab_key,
                  "field_vactory_title" => $tab_title,
                  "field_tab_templates" => $tab_templates,
                ];
                $paragraph_tab_template = Paragraph::create($paragraph_tab_template);
                $paragraph_tab_template->save();

                $paragraph_entity->field_vactory_paragraph_tab[] = [
                  'target_id' => $paragraph_tab_template->id(),
                  'target_revision_id' => \Drupal::entityTypeManager()
                    ->getStorage('paragraph')
                    ->getLatestRevisionId($paragraph_tab_template->id()),
                ];
                $paragraph_entity->save();
              }
            }
          }
          else {
            $split = explode('|', $key);
            $multi_paragraph_type = end($split);
            $paragraph = [
              'type' => 'vactory_paragraph_multi_template',
              'paragraph_key' => $node_id . '|' . $key,
              'field_multi_paragraph_type' => $multi_paragraph_type,
              'field_vactory_paragraph_tab' => [],
            ];
            foreach ($value as $tab_key => $tab_values) {
              $split_key = explode('|', $tab_key);
              $tab_title = $this->snakeToHuman(end($split_key));
              $paragraph_tab_template = [
                "type" => "vactory_paragraph_tab",
                "paragraph_key" => $node_id . '|' . $key . '|' . $tab_key,
                "field_vactory_title" => $tab_title,
                "field_tab_templates" => [],
              ];
              $templates = [];
              foreach ($tab_values as $field_key => $field_value) {
                $split = explode('|', $field_key);
                $dynamic_field_id = reset($split);
                $dynamic_field_field = substr($field_key, strlen($dynamic_field_id) + 1);
                $templates[$dynamic_field_id][$dynamic_field_field] = $field_value;
              }
              foreach ($templates as $df_key => $data) {
                $widget_data = $this->normalizeWidgetData($data);
                $paragraph_tab_template['field_tab_templates'][] = [
                  "widget_id" => 'vactory_page_import:' . $df_key,
                  "widget_data" => $widget_data,
                ];
              }
              $paragraph_tab_template = Paragraph::create($paragraph_tab_template);
              $paragraph_tab_template->save();
              $paragraph['field_vactory_paragraph_tab'][] = [
                'target_id' => $paragraph_tab_template->id(),
                'target_revision_id' => \Drupal::entityTypeManager()
                  ->getStorage('paragraph')
                  ->getLatestRevisionId($paragraph_tab_template->id()),
              ];
            }
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
      }
    }
  }

  /**
   * Finds a paragraph entity based on node and paragraph_key.
   */
  private function findParagraphByNodeAndKey(Node $node, $key) {
    $node_id = $node->get('node_id')->value;
    $node_paragraphs = $node->get('field_vactory_paragraphs')->getValue();
    $node_paragraph_ids = [];
    foreach ($node_paragraphs as $node_paragraph) {
      $node_paragraph_ids[] = $node_paragraph['target_id'];
    }

    $query = \Drupal::entityTypeManager()->getStorage('paragraph')->getQuery();
    $query->accessCheck(FALSE);
    $query->condition('paragraph_key', $node_id . '|' . $key);
    $query->condition('parent_type', 'node');
    $query->condition('parent_id', $node->id());
    $query->condition('id', $node_paragraph_ids, 'IN');
    $ids = $query->execute();

    if (count($ids) == 1) {
      $id = reset($ids);
      return Paragraph::load($id);
    }
    return [];
  }

  /**
   * Finds a paragraph tab entity based on node and paragraph_key.
   */
  private function findParagraphTabByParagraph(Paragraph $paragraph, $key, $node_id) {
    $query = \Drupal::entityTypeManager()->getStorage('paragraph')->getQuery();
    $query->accessCheck(FALSE);
    $query->condition('paragraph_key', $node_id . '|' . $key);
    $query->condition('parent_type', 'paragraph');
    $query->condition('parent_id', $paragraph->id());
    $ids = $query->execute();

    if (count($ids) == 1) {
      $id = reset($ids);
      return Paragraph::load($id);
    }
    return [];
  }

}
