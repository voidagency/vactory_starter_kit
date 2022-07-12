<?php

namespace Drupal\vactory_image_sitemap\Services;

use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Url;

/**
 * Class ImageSitemapGenerator.
 *
 * @package Drupal\vactory_image_sitemap\Services
 */
class ImageSitemapGenerator {

  /**
   * Generate image sitemap process.
   */
  public function process() {
    // Query for used images.
    $query = \Drupal::service('database')->select('file_managed', 'fm');
    $query->join('file_usage', 'fu', 'fu.fid = fm.fid');
    $query->fields('fm', ['fid', 'filename', 'uri']);
    $query->fields('fu', ['id']);
    $query->condition('fm.filemime', 'image%', 'LIKE');
    $query->condition('fm.uri', 'private://%', 'NOT LIKE');
    $used_images = $query->execute()->fetchAll();
    if (!empty($used_images)) {
      // Load all available node types.
      $content_types = \Drupal::entityTypeManager()->getStorage('node_type')
        ->loadMultiple();
      if ($content_types) {
        $content_types = array_keys($content_types);
        // This will store all existing node media image fields infos.
        $image_fields = [];
        $excluded_content_types = \Drupal::config('vactory_image_sitemap.settings')->get('excluded_content_types');
        foreach ($content_types as $type) {
          if (in_array($type, $excluded_content_types, TRUE)) {
            continue;
          }
          $field_definitions = \Drupal::service('entity_field.manager')->getFieldDefinitions('node', $type);
          foreach ($field_definitions as $field_name => $field_definition) {
            if ($field_definition->getType() === 'entity_reference') {
              $fd_settings = $field_definition->getSettings();
              $handler = $fd_settings['handler'];
              if ($handler === 'default:media' && in_array('image', $fd_settings['handler_settings']['target_bundles'])) {
                $image_fields[] = [
                  'field_name' => $field_name,
                  'translatable' => $field_definition->isTranslatable(),
                ];
              }
            }
          }
        }
        $languages = \Drupal::languageManager()->getLanguages();
        $operations = [];
        // Prepare batch operations.
        foreach ($languages as $language) {
          $operations[] = [
            static::class . '::imageSitemapNormalizerBatchProcess',
            [
              $language,
              $image_fields,
              $used_images,
            ],
          ];
        }
        if (!empty($operations)) {
          $operations[] = [static::class . '::imageSitemapGenerateBatchProcess', []];
          $batch = [
            'title' => 'Generate Image XML Sitemap...',
            'operations' => $operations,
            'finished' => static::class . '::imageSitemapGenerateBatchFinish',
          ];
          // process batch.
          batch_set($batch);
          if (php_sapi_name() === 'cli') {
            // Running from drush context case.
            drush_backend_batch_process();
          }
        }
      }
    }
  }

  /**
   * Normalize existing media image operation process.
   */
  public static function  imageSitemapNormalizerBatchProcess(
    LanguageInterface $language,
    $image_fields,
    $used_images,
    &$context
  ) {
    $default_langcode = \Drupal::languageManager()->getDefaultLanguage();
    $langcode = $language->getId();
    $image_sitemap = isset($context['results']['image_sitemap']) ? $context['results']['image_sitemap'] : [];
    foreach ($image_fields as $image_field_info) {
      if (!$image_field_info['translatable']) {
        $langcode = $default_langcode;
      }
      $node_image_table = 'node__' . $image_field_info['field_name'];
      $query = \Drupal::service('database')->select($node_image_table, 'nit');
      $query->fields('nit', ['entity_id']);
      $query->condition('nit.langcode', $langcode);
      $nids = $query->execute()->fetchAll();
      $nids = array_map(function ($object) {
        return $object->entity_id;
      }, $nids);
      $nodes = \Drupal::entityTypeManager()->getStorage('node')
        ->loadMultiple($nids);
      if ($nodes) {
        foreach ($nodes as $nid => $node) {
          if ($node->hasTranslation($langcode)) {
            $node = $node->getTranslation($langcode);
          }
          foreach ($used_images as $used_image) {
            $mid = $used_image->id;
            $node_image_ids = $node->get($image_field_info['field_name'])->getValue();
            $node_image_ids = array_map(function ($value) {
              return $value['target_id'];
            }, $node_image_ids);
            if (in_array($mid, $node_image_ids)) {
              $node_url = Url::fromRoute('entity.node.canonical', ['node' => $node->id()], ['language' => $language, 'absolute' => TRUE])->toString();
              $image_sitemap[$langcode][$nid]['node'] = $node_url;
              $image_sitemap[$langcode][$nid]['images'][] = [
                'image_loc' => \Drupal::service('file_url_generator')->generateAbsoluteString($used_image->uri),
                'image_title' => $used_image->filename,
              ];
            }
          }
        }
      }
    }
    if (empty($image_fields)) {
      $image_sitemap[$langcode] = [];
    }
    $context['results']['image_sitemap'] = $image_sitemap;
    $context['message'] = t('Fetch and normalize existing used media image...');
  }

  /**
   * Generate image sitemap xml file operation process.
   */
  public static function  imageSitemapGenerateBatchProcess(&$context) {
    $image_sitemap = isset($context['results']['image_sitemap']) ? $context['results']['image_sitemap'] : [];
    foreach ($image_sitemap as $langcode => $sitemap_infos) {
      $total_urls = 0;
      $output = '<?xml version="1.0" encoding="UTF-8"?>';
      $output .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
          xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">';
      foreach ($sitemap_infos as $sitemap_info) {
        $output .= '<url><loc>' . $sitemap_info['node'] . '</loc>';
        foreach ($sitemap_info['images'] as $image) {
          $output .= '<image:image><image:loc>' . $image['image_loc'] . '</image:loc><image:title>' . $image['image_title'] . '</image:title></image:image>';
          $total_urls++;
        }
        $output .= '</url>';
      }
      $output .= '</urlset>';
      // File build path.
      $path = \Drupal::service('file_url_generator')->generateAbsoluteString(\Drupal::service('file_system')->realpath("public://image_sitemap"));
      if (!is_dir($path)) {
        \Drupal::service('file_system')->mkdir($path);
      }
      $time = time();
      $filename = $langcode . '_image_sitemap.xml';
      if ($file = \Drupal::service('file_system')->saveData($output, $path . '/' . $filename, \Drupal\Core\File\FileSystemInterface::EXISTS_REPLACE)) {
        \Drupal::configFactory()->getEditable('vactory_image_sitemap.settings')
          ->set('created', $time)
          ->set($langcode . '_number_of_urls', $total_urls)
          ->save();
      }
    }
    $context['message'] = t('Generate final media xml sitemap file for each enabled language...');
  }

  /**
   * Image sitemap batch finished callback.
   */
  public static function imageSitemapGenerateBatchFinish($success, $results, $operations) {
    if ($success) {
      $message = t('Image sitemap has been successfully generated!');
      \Drupal::messenger()->addStatus($message);
    }
    else {
      $message = t('Finished with an error.');
      \Drupal::messenger()->addError($message);
    }
  }

}
