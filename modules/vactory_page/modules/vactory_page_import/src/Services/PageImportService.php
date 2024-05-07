<?php

namespace Drupal\vactory_page_import\Services;

use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\file\FileInterface;
use Drupal\vactory_page_import\PageImportConstants;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Exception;
use Drupal\Core\Serialization\Yaml;
use Drupal\node\Entity\Node;
use Drupal\paragraphs\Entity\Paragraph;

/**
 * Contains page import services.
 *
 * Class PageImportService.
 */
class PageImportService {

  /**
   * The logger channel factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $logger;

  /**
   * Constructs a new EventFormMailService.
   *
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger
   *   The logger channel factory.
   */
  public function __construct(LoggerChannelFactoryInterface $logger) {
    $this->logger = $logger;
  }

  /**
   * Prepare (normalize) data for DF settings creation.
   */
  public function prepareDfSettings(array $data) {
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
   * Transform input excel to array.
   */
  public function readExcelToArray($filePath) {
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
   * Check if DF is multiple.
   */
  public function isDfMultiple($config) {
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
  public function snakeToHuman($string) {
    $words = explode('_', $string);
    return ucfirst(implode(' ', $words));
  }

  /**
   * Creates DF setting.
   */
  public function createDynamicFields(array $settings) {
    foreach ($settings as $key => $value) {
      $config = [];
      $split = explode('|', $key);
      $df_name = explode(':', $key);
      $df_name = end($df_name);
      $config['name'] = $this->snakeToHuman($df_name);
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
      $this->writeDfFile($yaml_config, $df_name);
      $this->createDfTemplateHtml($config, $df_name);
    }
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
   * Create html template file.
   */
  private function createDfTemplateHtml($settings, $name) {
    $dest_uri = 'private://imported-pages-df';
    $dest_df_uri = $dest_uri . '/' . $name;
    if (!file_exists($dest_df_uri)) {
      mkdir($dest_df_uri, 0777, TRUE);
    }
    $filepath = \Drupal::service('file_system')->realpath($dest_df_uri . '/template.html.twig');
    $content = "<div>";
    if (!$settings['multiple']) {
      $content .= $this->constructDfTemplateHtml($settings['fields']);
    }
    else {
      $content .= $this->constructDfTemplateHtml($settings['extra_fields'], NULL, TRUE);
      $content .= "\n\t{% for key, item in content %}";
      $content .= $this->constructDfTemplateHtml($settings['fields'], NULL, FALSE, TRUE);
      $content .= "\n\t{% endfor %}";
    }
    $content .= "\n</div>";

    $printed = file_put_contents($filepath, $content);
    return (bool) $printed;
  }

  /**
   * Construct html template file content.
   */
  private function constructDfTemplateHtml($fields, $group = NULL, $is_extra_field = FALSE, $is_multiple = FALSE) {
    $content = '';
    foreach ($fields as $key => $field) {
      if (str_starts_with($key, 'group_')) {
        $exclude_title = array_filter($field, function ($key) {
          return !($key == 'g_title');
        }, ARRAY_FILTER_USE_KEY);
        $content .= $this->constructDfTemplateHtml($exclude_title, $key);
      }
      else {
        $item = $key;
        if (!is_null($group)) {
          $item = "{$group}.{$item}";
        }
        $index = 'content.0';
        if ($is_extra_field) {
          $index = 'extra_fields';
        }
        if ($is_multiple) {
          $index = 'item';
        }

        if ($field['type'] == 'text' || $field['type'] == 'textarea') {
          $content .= "\n\t<p>";
          $content .= "\n\t\t{{ {$index}.{$item} }}";
          $content .= "\n\t</p>";
        }
        elseif ($field['type'] == 'url_extended') {
          $content .= "\n\t<a href='{{ {$index}.{$item}.url }}'> {{ {$index}.{$item}.title }} </a>";
        }
        elseif ($field['type'] == 'text_format') {
          $content .= "\n\t<div> {{ {$index}.{$item}.value|raw }}</div>";
        }
        elseif ($field['type'] == 'image') {
          $content .= "\n\t{% set image_uri = ({$index}.{$item}.0 is defined) ? get_image({$index}.{$item}.0) : '' %}";
          $content .= "\n\t{% set image_src = file_url(image_uri) %}";
          $content .= "\n\t<img src='{{ image_src }}'>";
        }
      }
    }
    return $content;
  }

  /**
   * Finds a paragraph entity based on node and paragraph_key.
   */
  public function findParagraphByNodeAndKey(Node $node, $key) {
    $node_id = $node->get('node_id')->value;
    $node_paragraphs = $node->get('field_vactory_paragraphs')->getValue();
    $node_paragraph_ids = [];
    foreach ($node_paragraphs as $node_paragraph) {
      $node_paragraph_ids[] = $node_paragraph['target_id'];
    }
    if (empty($node_paragraph_ids)) {
      return [];
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
  public function findParagraphTabByParagraph(Paragraph $paragraph, $key, $node_id) {
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

  /**
   * Normalize widget data.
   */
  public function normalizeWidgetData($data) {
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

}
