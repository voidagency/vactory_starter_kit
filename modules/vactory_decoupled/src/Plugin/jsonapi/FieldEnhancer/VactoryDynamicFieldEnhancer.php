<?php

namespace Drupal\vactory_decoupled\Plugin\jsonapi\FieldEnhancer;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManager;
use Drupal\Core\Url;
use Drupal\file\Entity\File;
use Drupal\image\Entity\ImageStyle;
use Drupal\jsonapi_extras\Plugin\ResourceFieldEnhancerBase;
use Drupal\media\Entity\Media;
use Drupal\media\MediaInterface;
use Shaper\Util\Context;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Component\Utility\NestedArray;
use Drupal\Component\Utility\UrlHelper;
use Drupal\taxonomy\Entity\Term;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\serialization\Normalizer\CacheableNormalizerInterface;

/**
 * Use for Dynamic Field field value.
 *
 * @ResourceFieldEnhancer(
 *   id = "vactory_dynamic_field",
 *   label = @Translation("Vactory Dynamic Field"),
 *   description = @Translation("Unserialize dynamic field data.")
 * )
 */
class VactoryDynamicFieldEnhancer extends ResourceFieldEnhancerBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Language Id.
   *
   * @var string
   */
  protected $language;

  /**
   * The DF manager.
   *
   * @var \Drupal\vactory_dynamic_field\WidgetsManager
   */
  protected $platformProvider;

  /**
   * The image style entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $imageStyles;

  protected $siteConfig;

  protected $cacheability;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, EntityTypeManagerInterface $entity_type_manager, $plateform_provider) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->language = \Drupal::languageManager()->getCurrentLanguage()->getId();
    $this->platformProvider = $plateform_provider;
    $this->imageStyles = ImageStyle::loadMultiple();
    $this->siteConfig = \Drupal::config('system.site');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('vactory_dynamic_field.vactory_provider_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function doUndoTransform($data, Context $context) {
    /** @var \Drupal\Core\Cache\CacheableMetadata $cacheability */
    $cacheability = (object) $context[CacheableNormalizerInterface::SERIALIZATION_CONTEXT_CACHEABILITY];
    $this->cacheability = $cacheability;

    if (isset($data['widget_data']) && !empty($data['widget_data'])) {
      $widget_id = $data['widget_id'];
      $widget_data = json_decode($data['widget_data'], TRUE);
      $settings = $this->platformProvider->loadSettings($widget_id);

      $content = [];

      // Handle extra fields.
      if (isset($widget_data['extra_field'])) {
        $content['extra_field'] = $widget_data['extra_field'];
        unset($widget_data['extra_field']);
      }

      // Fallback for existing templates.
      // Which don't have _weight field yet.
      $widget_weight = 1;
      foreach ($widget_data as &$component) {
        if (!isset($component['_weight'])) {
          $component['_weight'] = $widget_weight++;
        }
      }

      // Sort data.
      usort($widget_data, function ($item1, $item2) {
        return $item1['_weight'] <=> $item2['_weight'];
      });

      foreach ($widget_data as &$component) {
        $this->applyFormatters(['fields'], $settings, $component);
        $content['components'][] = $component;
      }

      if (array_key_exists('extra_field', $content) && is_array($content['extra_field'])) {
        $this->applyFormatters(['extra_fields'], $settings, $content['extra_field']);
      }

      $content['template'] = $widget_id;

      /*
       * Allow other modules to override components content.
       *
       * @code
       * Implements hook_df_jsonapi_output_alter().
       * function myModule_df_jsonapi_output_alter(&$content) {
       * }
       * @endcode
       */
      \Drupal::moduleHandler()->alter('df_jsonapi_output', $content);

      $data['widget_data'] = json_encode($content);
    }

    // Restore cache.
    $this->cacheability->addCacheContexts(['url.query_args:q']);
    $context[CacheableNormalizerInterface::SERIALIZATION_CONTEXT_CACHEABILITY] = $this->cacheability;

    return $data;
  }

  /**
   * Apply formatters such as processed_text, image & links.
   *
   * @param array $parent_keys
   *   Keys.
   * @param array $settings
   *   Settings.
   * @param array $component
   *   Component.
   */
  private function applyFormatters($parent_keys, $settings, &$component) {
    $image_app_base_url = Url::fromUserInput('/app-image/')
      ->setAbsolute()->toString();
    foreach ($component as $field_key => &$value) {
      $info = NestedArray::getValue($settings, array_merge((array) $parent_keys, [$field_key]));

      if ($info && isset($info['type'])) {
        // Manage external/internal links.
        if ($info['type'] === 'url_extended') {

          if (!empty($value['url']) && !UrlHelper::isExternal($value['url'])) {
            $front_uri = $this->siteConfig->get('page.front');
            if ($front_uri === $value['url']) {
              $value['url'] = Url::fromRoute('<front>')->toString();
            }
            else {
              $value['url'] = Url::fromUserInput($value['url'])
                ->toString();
            }
            $value['url'] = str_replace('/backend', '', $value['url']);
          }

          // URL Parts.
          if (isset($value['attributes']['path_terms']) && !empty($value['attributes']['path_terms'])) {
            $entityRepository = \Drupal::service('entity.repository');
            $slugManager = \Drupal::service('vactory_core.slug_manager');
            $path_terms = $value['attributes']['path_terms'];

            $value['url'] .= preg_replace_callback(
              '/(\d+)/i',
              function ($matches) use ($entityRepository, $slugManager)  {
                $term = Term::load(intval($matches[0]));
                if (!$term) {
                  return NULL;
                }
                $term = $entityRepository
                  ->getTranslationFromContext($term);
                return $slugManager->taxonomy2Slug($term);
              },
              $path_terms
            );
            unset($value['attributes']['path_terms']);
          }

          // Check for external links.
          $value['is_external'] = UrlHelper::isExternal($value['url']);
        }

        // Text Preprocessor.
        if ($info['type'] === 'text_format') {
          $format = $info['options']['#format'] ?? 'full_html';

          $build = [
            '#type'   => 'processed_text',
            '#text'   => $value['value'],
            '#format' => $format,
          ];

          $value = ['value' => $build];
        }

        // Image media.
        if ($info['type'] === 'image' && !empty($value)) {
          $key = array_keys($value)[0];
          $image_data = [];
          if (isset($value[$key]['selection'])) {
            foreach ($value[$key]['selection'] as $media) {
              $file = Media::load($media['target_id']);
              if ($file) {
                // Add cache.
                $cacheTags = Cache::mergeTags($this->cacheability->getCacheTags(), $file->getCacheTags());
                $this->cacheability->setCacheTags($cacheTags);
                $uri = $file->thumbnail->entity->getFileUri();
                $image_item['_default'] = \Drupal::service('file_url_generator')->generateAbsoluteString($uri);
                $image_item['_lqip'] = $this->imageStyles['lqip']->buildUrl($uri);
                $image_item['uri'] = StreamWrapperManager::getTarget($uri);
                $image_item['fid'] = $file->thumbnail->entity->fid->value;
                $image_item['file_name'] = $file->label();
                $image_item['base_url'] = $image_app_base_url;
                if (!empty($file->get('field_media_image')->getValue())) {
                  $image_item['meta'] = $file->get('field_media_image')->first()->getValue();
                }
              }
              else {
                $image_item['_error'] = 'Media file ID: ' . $media['target_id'] . ' Not Found';
              }

              $image_data[] = $image_item;
            }
          }
          $value = $image_data;
        }

        // Document media.
        if ($info['type'] === 'file' && !empty($value)) {
          $key = array_keys($value)[0];
          $file_data = [];
          if (isset($value[$key]['selection'])) {
            foreach ($value[$key]['selection'] as $media) {
              $file = Media::load($media['target_id']);
              if ($file) {
                $cacheTags = Cache::mergeTags($this->cacheability->getCacheTags(), $file->getCacheTags());
                $this->cacheability->setCacheTags($cacheTags);
                $fid = (int) $file->get('field_media_file')->getString();
                $document = File::load($fid);
                if ($document) {
                  // Add cache.
                  $cacheTags = Cache::mergeTags($this->cacheability->getCacheTags(), $document->getCacheTags());
                  $this->cacheability->setCacheTags($cacheTags);
                  $uri = $document->getFileUri();
                  $file_data[] = [
                    '_default' => \Drupal::service('file_url_generator')->generateAbsoluteString($uri),
                    'uri' => StreamWrapperManager::getTarget($uri),
                    'fid' => $file->id(),
                    'file_name' => $file->label(),
                  ];
                }
              } else {
                $file_data['_error'] = 'Media file ID: ' . $media['target_id'] . ' Not Found';
              }
            }
          }
          $value = $file_data;
        }

        // Views.
        if ($info['type'] === 'dynamic_views' && !empty($value)) {
          $value = array_merge($value, $info['options']['#default_value']);
          $value['data'] = \Drupal::service('vactory.views.to_api')->normalize($value);
        }

        // Collection.
        if ($info['type'] === 'json_api_collection' && !empty($value)) {
          $value = array_merge($info['options']['#default_value'], $value);
          $response = \Drupal::service('vactory_decoupled.jsonapi.generator')->fetch($value);
          $cache = $response['cache'];
          unset($response['cache']);

          $cacheTags = Cache::mergeTags($this->cacheability->getCacheTags(), $cache['tags']);
          $this->cacheability->setCacheTags($cacheTags);
          $value = $response;
        }

        // Webform.
        if ($info['type'] === 'webform_decoupled' && !empty($value)) {
          $webform_id = $value['id'];
          $cacheTags = Cache::mergeTags($this->cacheability->getCacheTags(), ['webform_submission_list', 'config:webform_list']);
          $this->cacheability->setCacheTags($cacheTags);
          $value['elements'] = \Drupal::service('vactory.webform.normalizer')->normalize($webform_id);
        }

        if ($info['type'] === 'remote_video' && !empty($value)) {
          $value = reset($value);
          $mid = $value['selection'][0]['target_id'] ?? '';
          $media = !empty($mid) ? $this->entityTypeManager->getStorage('media')->load($mid) : NULL;
          if ($media instanceof MediaInterface) {
            $value = [
              'id' => $media->uuid(),
              'name' => $media->getName(),
              'url' => $media->get('field_media_oembed_video')->value,
            ];
          }
        }

        if ($info['type'] === 'node_queue' && !empty($value)) {
          $config['resource'] = $value['resource'] ?? '';
          $config['filters'] = is_string($value['filters']) ? explode("\n", $value['filters']) : $value['filters'];
          $config['vocabularies'] = [];
          $config['filters'][] = 'filter[df-node-nid][condition][path]=nid';
          $config['filters'][] = 'filter[df-node-nid][condition][operator]=IN';
          $i = 1;
          foreach ($value['nodes'] as $nid) {
            $config['filters'][] = "filter[df-node-nid][condition][value][$i]=". $nid['target_id'];
            $i++;
          }
          $response = \Drupal::service('vactory_decoupled.jsonapi.generator')->fetch($config);
          $cache = $response['cache'];
          unset($response['cache']);

          $cacheTags = Cache::mergeTags($this->cacheability->getCacheTags(), $cache['tags']);
          $this->cacheability->setCacheTags($cacheTags);
          $value = $response;
        }

        $cacheability = $this->cacheability;
        // Apply other modules formatters if exist on current component.
        \Drupal::moduleHandler()->alter('decoupled_df_format', $value, $info, $cacheability);
        $this->cacheability = $cacheability;
      }
      elseif (is_array($value)) {
        // Go deeper.
        $this->applyFormatters(array_merge((array) $parent_keys, [$field_key]), $settings, $value);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function doTransform($value, Context $context) {
    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public function getOutputJsonSchema() {
    return [
      'type' => 'object',
    ];
  }

}
