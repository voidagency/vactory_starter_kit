<?php

namespace Drupal\vactory_dynamic_field;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Entity\EntityMalformedException;
use Drupal\Core\StreamWrapper\StreamWrapperManager;
use Drupal\Core\Url;
use Drupal\file\Entity\File;
use Drupal\image\Entity\ImageStyle;
use Drupal\media\Entity\Media;
use Drupal\node\NodeInterface;
use Drupal\views\Views;

/**
 * Simplifies the process of generating an API version of a view.
 *
 * @api
 */
class ViewsToApi {

  const RESERVED_FIELDS = ['url', 'id'];

  protected $siteConfig;

  protected $dateFormatter;

  protected $dateFormats;

  /**
   * {@inheritdoc}
   */
  public function __construct() {
    $this->siteConfig = \Drupal::config('system.site');
    $this->dateFormatter = \Drupal::service('date.formatter');
    $this->dateFormats = \Drupal::entityTypeManager()
      ->getStorage('date_format')
      ->loadMultiple();
  }

  /**
   * Return the requested entity as an structured array.
   *
   * @param array $config
   *   The Config settings; see FormElement("dynamic_views")
   *
   * @return array
   *   The JSON structure of the requested resource.
   *
   */
  public function normalize(array $config) {
    $nodes = [];
    $views_name = $config['views_id'];
    $views_display = $config['views_display_id'];
    $views_items_per_page = $config['limit'];
    $views_args = $config['args'];
    $exposed_vocabularies = $config['vocabularies'];
    $entity_queue = $config['entity_queue'] ?? [];
    $entityRepository = \Drupal::service('entity.repository');

    // Override args using filters.
    if (
      isset($config['filters']) &&
      !empty($config['filters']) &&
      is_array($config['filters'])
    ) {
      $args_filters = [];
      $is_filters_null = empty(array_filter($config['filters'], function ($a) {
        return $a !== NULL;
      }));

      if (!$is_filters_null) {
        foreach ($config['filters'] as $key => $filter) {
          if (is_array($filter)) {
            $entity_ids = array_map(function (array $item) {
              return $item['target_id'];
            }, $filter);
            // @todo: Saisir plusieurs valeurs sous la forme 1+2+3 (pour OR) ou 1,2,3 (pour AND).
            // @todo: need OR & AND between two filters or more. > https://www.drupal.org/project/views_contextual_filters_or
            $condition = "+"; // OR by default.
            //            if (
            //              isset($config['condition_filters'][$key]) &&
            //              !empty($config['condition_filters'][$key])
            //            ) {
            //              $condition = $config['condition_filters'][$key] === 'AND' ? ',' : '+';
            //            }

            $rule = join($condition, $entity_ids);
            array_push($args_filters, $rule);
          }
          else {
            if (!$filter) {
              $filter = 'all';
            }
            array_push($args_filters, $filter);
          }
        }

        $views_args = $args_filters;
      }
    }

    $view = Views::getView($views_name);
    $view->get_total_rows = TRUE;

    if (!$view || !$view->access($views_display)) {
      return $nodes;
    }

    $view->setDisplay($views_display);

    // Ignore arguments if we have an entity queue.
    if (is_array($views_args) && !empty($views_args) && empty($entity_queue)) {
      $view->setArguments($views_args);
    }

    if (!empty($views_items_per_page)) {
      $view->setItemsPerPage($views_items_per_page);
    }

    if (is_string($views_display)) {
      $view->setDisplay($views_display);
    }
    else {
      $view->initDisplay();
    }

    // Override entity queue relationships.
    if (!empty($entity_queue)) {
      $views_relationships = $view->getDisplay()->getOption('relationships');
      $relationships = [];
      foreach ($views_relationships as $id => $relationship) {
        if ($relationship['plugin_id'] === 'entity_queue') {
          $relationship['required'] = TRUE;
          $relationship['limit_queue'] = $entity_queue;
        }

        $relationships[$id] = $relationship;
      }
      $view->getDisplay()->overrideOption('relationships', $relationships);
    }

    $view->preExecute();
    $view->execute();

    $result = $view->result;

    foreach ($result as $row) {
      /** @var NodeInterface $node */
      $node = $row->_entity;
      $node = $entityRepository
        ->getTranslationFromContext($node);
      try {
        $normalized_node = $this->normalizeNode($node, $config);
        array_push($nodes, $normalized_node);
      } catch (EntityMalformedException $e) {
      }
    }

    return [
      'nodes' => $nodes,
      'count' => $view->total_rows,
      'exposed' => $this->getExposedTerms($exposed_vocabularies),
    ];
  }

  /**
   * @param NodeInterface $node
   *    The Node.
   * @param array $config
   *    Views form element config.
   *
   * @return array
   *    Normalized data.
   *
   * @throws EntityMalformedException
   */
  protected function normalizeNode(NodeInterface $node, array $config = []) {
    $fields = $config['fields'];
    $imageStyles = $config['image_styles'];

    $result = [];
    $image_app_base_url = Url::fromUserInput('/app-image/')
      ->setAbsolute()->toString();
    $lqipImageStyle = ImageStyle::load('lqip');

    $appliedImageStyle = [];
    if (!empty($imageStyles)) {
      $appliedImageStyle = ImageStyle::loadMultiple($imageStyles);
    }

    foreach ($fields as $field_name => $output_field_name) {
      if (!$node->hasField($field_name) && !in_array($field_name, self::RESERVED_FIELDS)) {
        \Drupal::logger('vactory_dynamic_field')
          ->warning('Could not find %field_name field in content type %content_type in form element dynamic_views. You may have entered wrong fields names in the DF.', [
            '%field_name' => $field_name,
            '%content_type' => $node->bundle(),
          ]);
        continue;
      }

      // Url.
      if ($field_name === 'url') {
        $result[$output_field_name] = $node->toUrl()->toString();
        continue;
      }

      // ID.
      if ($field_name === 'id') {
        $result[$output_field_name] = $node->id();
        continue;
      }

      $definition = $node->getFieldDefinition($field_name);
      $field_storage_definition = $definition->getFieldStorageDefinition();
      $field_type = $definition->getType();

      if ($field_type === 'text_long') {
        $result[$output_field_name] = NULL;
        $text_field_data = $node->get($field_name)->getValue();
        if ($text_field_data && isset($text_field_data[0]['value'])) {
          $build = [
            '#type' => 'processed_text',
            '#text' => $text_field_data[0]['value'],
            '#format' => $text_field_data[0]['format'],
          ];

          $result[$output_field_name] = (string) \Drupal::service('renderer')
            ->renderPlain($build);
        }
        continue;
      }

      if ($field_type === 'entity_reference' && $field_storage_definition->getSettings()['target_type'] == 'media') {
        $mid = $node->get($field_name)->getString();
        $result[$output_field_name] = NULL;

        if (!empty($mid)) {
          $mid = (int) $mid;
          $media = Media::load($mid);

          if (
            $media &&
            $media->bundle() === 'image' &&
            isset($media->get('field_media_image')->getValue()[0]['target_id'])
          ) {
            $fid = $media->get('field_media_image')->getValue()[0]['target_id'];
            $file = File::load($fid);
            $uri = $file->getFileUri();
            $fileResult = [];
            $fileResult['_default'] = \Drupal::service('file_url_generator')->generateAbsoluteString($uri);
            $fileResult['_lqip'] = $lqipImageStyle->buildUrl($uri);
            $fileResult['uri'] = StreamWrapperManager::getTarget($uri);
            $fileResult['fid'] = $fid;
            $fileResult['file_name'] = $media->label();
            $fileResult['base_url'] = $image_app_base_url;
            $fileResult['meta'] = $media->get('field_media_image')
              ->first()
              ->getValue();

            foreach ($appliedImageStyle as $imageStyle) {
              $fileResult[$imageStyle->id()] = $imageStyle->buildUrl($uri);
            }

            $result[$output_field_name] = $fileResult;
          }

          if (
            $media &&
            $media->bundle() === 'document' &&
            isset($media->get('field_media_document')
                ->getValue()[0]['target_id'])
          ) {
            $fid = $media->get('field_media_document')
              ->getValue()[0]['target_id'];
            $file = File::load($fid);
            $uri = $file->getFileUri();

            $fileResult = [
              '_default' => \Drupal::service('file_url_generator')->generateAbsoluteString($uri),
              'uri' => StreamWrapperManager::getTarget($uri),
              'fid' => $fid,
              'file_name' => $media->label(),
            ];

            $result[$output_field_name] = $fileResult;
          }

        }
        continue;
      }

      if ($field_type === 'entity_reference' && $field_storage_definition->getSettings()['target_type'] == 'taxonomy_term') {
        $result[$output_field_name] = NULL;
        if (!empty($node->get($field_name)->getString()) && $node->get($field_name)->entity ) {
          $result[$output_field_name] = [
            'id' => $node->get($field_name)->entity->id(),
            'label' => $node->get($field_name)->entity->label(),
          ];
        }
        continue;
      }

      if ($field_type === 'link') {
        $result[$output_field_name] = NULL;
        $link_value = $node->get($field_name)->getValue();
        if (isset($link_value[0]['uri']) && !empty($link_value[0]['uri'])) {
          if (UrlHelper::isExternal($link_value[0]['uri'])) {
            $result[$output_field_name]['url'] = $link_value[0]['uri'];
          }
          else {
            $front_uri = $this->siteConfig->get('page.front');
            if ($front_uri === $link_value[0]['uri']) {
              $result[$output_field_name]['url'] = Url::fromRoute('<front>')
                ->toString();
            }
            else {
              $result[$output_field_name]['url'] = Url::fromUri($link_value[0]['uri'])
                ->toString();
            }
            $result[$output_field_name]['url'] = str_replace('/backend', '', $result[$output_field_name]['url']);
          }

          $result[$output_field_name]['title'] = $link_value[0]['title'] ?? '';
          $result[$output_field_name]['options'] = $link_value[0]['options'] ?? [];
        }

        continue;
      }


      if ($field_type === 'datetime') {
        $result[$output_field_name] = NULL;
        if (isset($node->get($field_name)->getValue()[0]['value'])) {
          $timestamp = $node->get($field_name)->date->getTimestamp();
          $date_formats = array_keys($this->dateFormats);
          $result[$output_field_name] = [];
          $result[$output_field_name]['timestamp'] = $timestamp;

          foreach ($date_formats as $key => $date_format) {
            $result[$output_field_name][$date_format] = $this->dateFormatter->format($timestamp, $date_format);
          }

        }
        continue;
      }

      if ($field_type === 'created') {
        $date_formats = array_keys($this->dateFormats);
        $timestamp = intval($node->get($field_name)->getString());
        $result[$output_field_name] = [];
        $result[$output_field_name]['timestamp'] = $timestamp;

        foreach ($date_formats as $key => $date_format) {
          $result[$output_field_name][$date_format] = $this->dateFormatter->format($timestamp, $date_format);
        }

        continue;
      }

      if ($field_type === 'daterange') {
        $result[$output_field_name] = [
          'date_start' => NULL,
          'date_end' => NULL,
        ];

        if (isset($node->get($field_name)->getValue()[0]['value'])) {
          $result[$output_field_name]['date_start'] = $node->get($field_name)
            ->getValue()[0]['value'];
        }

        if (isset($node->get($field_name)->getValue()[0]['end_value'])) {
          $result[$output_field_name]['date_end'] = $node->get($field_name)
            ->getValue()[0]['end_value'];
        }

        continue;
      }

      if ($field_type === 'faqfield') {
        $result[$output_field_name] = $node->get($field_name)->getValue();
        continue;
      }

      $result[$output_field_name] = $node->get($field_name)->getString();
    }


    return $result;
  }

  protected function getExposedTerms(array $vocabularies) {
    $result = [];

    $entityTypeManager = \Drupal::service('entity_type.manager');
    $taxonomyTermStorage = $entityTypeManager->getStorage('taxonomy_term');
    $slugManager = \Drupal::service('vactory_core.slug_manager');
    $entityRepository = \Drupal::service('entity.repository');
    $bundles = (array) $vocabularies;
    $bundles = array_filter($bundles, function ($value) {
      return $value != '0';
    });
    $bundles = array_keys($bundles);

    foreach ($bundles as $vid) {
      $terms = $taxonomyTermStorage->loadTree($vid, 0, NULL, TRUE);
      $result[$vid] = [];
      foreach ($terms as $term) {
        $term = $entityRepository
          ->getTranslationFromContext($term);
        array_push($result[$vid], [
          'id' => $term->id(),
          'uuid' => $term->uuid(),
          'slug' => $slugManager->taxonomy2Slug($term),
          'label' => $term->label(),
        ]);
      }

    }

    return $result;
  }

}
