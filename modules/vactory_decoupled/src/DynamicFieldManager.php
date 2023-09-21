<?php

namespace Drupal\vactory_decoupled;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Url;
use Drupal\file\Entity\File;
use Drupal\image\Entity\ImageStyle;
use Drupal\media\MediaInterface;
use Drupal\vactory_core\SlugManager;
use Drupal\vactory_dynamic_field\ViewsToApi;
use Shaper\Util\Context;
use Drupal\Component\Utility\NestedArray;
use Drupal\Component\Utility\UrlHelper;
use Drupal\taxonomy\Entity\Term;
use Drupal\Core\Cache\Cache;
use Drupal\serialization\Normalizer\CacheableNormalizerInterface;

/**
 * Manages Dynamic Field Transformation.
 */
class DynamicFieldManager {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity repository service.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

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

  /**
   * The decoupled media files manager service.
   *
   * @var \Drupal\vactory_decoupled\MediaFilesManager
   */
  protected $mediaFilesManager;

  /**
   * Site config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $siteConfig;

  /**
   * Cacheability.
   *
   * @var \Drupal\Core\Cache\CacheableMetadata
   */
  protected $cacheability;

  /**
   * JsonAPI generator service.
   *
   * @var \Drupal\vactory_decoupled\JsonApiGenerator
   */
  protected $jsonApiGenerator;

  /**
   * Slug manager service.
   *
   * @var \Drupal\vactory_core\SlugManager
   */
  protected $slugManager;

  /**
   * Module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Language manager service.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Vactory views to api service.
   *
   * @var \Drupal\vactory_dynamic_field\ViewsToApi
   */
  protected $viewsToApi;

  /**
   * Config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Media storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $mediaStorage;

  /**
   * Term Result Count Storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $termResultCount;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, $plateform_provider, MediaFilesManager $mediaFilesManager, EntityRepositoryInterface $entityRepository, JsonApiGenerator $jsonApiGenerator, SlugManager $slugManager, ModuleHandlerInterface $moduleHandler, LanguageManagerInterface $languageManager, ViewsToApi $viewsToApi, ConfigFactoryInterface $configFactory) {
    $this->entityTypeManager = $entity_type_manager;
    $this->language = $languageManager->getCurrentLanguage()->getId();
    $this->platformProvider = $plateform_provider;
    $this->imageStyles = ImageStyle::loadMultiple();
    $this->siteConfig = $configFactory->get('system.site');
    $this->mediaFilesManager = $mediaFilesManager;
    $this->entityRepository = $entityRepository;
    $this->jsonApiGenerator = $jsonApiGenerator;
    $this->slugManager = $slugManager;
    $this->moduleHandler = $moduleHandler;
    $this->languageManager = $languageManager;
    $this->viewsToApi = $viewsToApi;
    $this->mediaStorage = $this->entityTypeManager->getStorage('media');
    $this->termResultCount = $this->moduleHandler->moduleExists('vactory_taxonomy_results') ? $this->entityTypeManager->getStorage('term_result_count') : NULL;
  }

  /**
   * Transform.
   */
  public function transform($data, Context &$context) {
    $cacheability = (object) $context[CacheableNormalizerInterface::SERIALIZATION_CONTEXT_CACHEABILITY];
    $res = $this->process($data, $cacheability);
    $context[CacheableNormalizerInterface::SERIALIZATION_CONTEXT_CACHEABILITY] = $res['cacheability'];
    return $res['data'];
  }

  /**
   * Process.
   */
  public function process($data, $cacheability = NULL) {
    $this->cacheability = $cacheability;

    if (is_null($cacheability)) {
      $this->cacheability = CacheableMetadata::createFromRenderArray([]);
    }

    if (isset($data['widget_data']) && !empty($data['widget_data'])) {
      $widget_id = $data['widget_id'];
      $widget_data = json_decode($data['widget_data'], TRUE);
      $settings = $this->platformProvider->loadSettings($widget_id) ?? [];
      $content = [];

      // Add auto populate info.
      $content['auto_populate'] = $widget_data['auto-populate'] ?? FALSE;
      unset($widget_data['auto-populate']);

      // Handle extra fields.
      if (isset($widget_data['extra_field'])) {
        $content['extra_field'] = $widget_data['extra_field'];
        unset($widget_data['extra_field']);
      }

      if (isset($widget_data['pending_content'])) {
        $content['pending_content'] = array_map(fn($el) => !str_starts_with($el, 'extra_field') ? "components.{$el}" : $el, $widget_data['pending_content']);
        unset($widget_data['pending_content']);
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
        return (int) ($item1['_weight'] <=> $item2['_weight']);
      });

      // $image_app_base_url = Url::fromUserInput('/app-image/')
      // ->setAbsolute()->toString();
      foreach ($widget_data as &$component) {
        // $this->applyFormatters(['fields'], $settings, $component);
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
      $this->moduleHandler->alter('df_jsonapi_output', $content);
      $data['widget_data'] = json_encode($content);
    }

    // Restore cache.
    $this->cacheability->addCacheContexts(['url.query_args:q']);

    return [
      'data'         => $data,
      'cacheability' => $this->cacheability,
    ];
  }

  /**
   * Apply Formatters.
   */
  public function applyFormatters($parent_keys, $settings, &$component) {
    foreach ($component as $field_key => &$value) {
      $info = NestedArray::getValue($settings, array_merge((array) $parent_keys, [$field_key]));
      if (is_array($info)) {
        $info['uuid'] = $settings['uuid'];
        if ($info && isset($info['type'])) {
          // Manage external/internal links.
          if ($info['type'] === 'url_extended') {

            if (!empty($value['url']) && !UrlHelper::isExternal($value['url'])) {
              $front_uri = $this->siteConfig->get('page.front');
              if ($front_uri === $value['url']) {
                $value['url'] = Url::fromRoute('<front>')->toString();
              }
              else {
                $value['url'] = Url::fromUserInput($value['url'])->toString();
              }
              $value['url'] = str_replace('/backend', '', $value['url']);
            }

            // URL Parts.
            if (isset($value['attributes']['path_terms']) && !empty($value['attributes']['path_terms'])) {
              $entityRepository = $this->entityRepository;
              $slugManager = $this->slugManager;
              $path_terms = $value['attributes']['path_terms'];

              $value['url'] .= preg_replace_callback('/(\d+)/i', function ($matches) use ($entityRepository, $slugManager) {
                $term = Term::load(intval($matches[0]));
                if (!$term) {
                  return NULL;
                }
                $term = $entityRepository->getTranslationFromContext($term);
                return $slugManager->taxonomy2Slug($term);
              }, $path_terms);
              unset($value['attributes']['path_terms']);
            }

            // Check for external links.
            $value['is_external'] = UrlHelper::isExternal($value['url']);
          }

          // Text Preprocessor.
          if ($info['type'] === 'text_format') {
            // $format = $info['options']['#format'] ?? 'full_html';
            $build = [
              // '#type'   => 'processed_text',
              '#text' => $value['value'] ?? $value,
              // '#format' => $format,
            ];

            $value = ['value' => $build];
          }

          // Decoupled entity reference options.
          if ($info['type'] === 'decoupled_entity_reference') {
            $entity_repository = $this->entityRepository;
            $langcode = $this->languageManager->getCurrentLanguage()->getId();
            $entity_type_id = $value['entity_reference']['entity_type'] ?? '';
            $bundle = $value['entity_reference']['bundle'] ?? '';
            $value = [];
            if (!empty($entity_type_id) && !empty($bundle)) {
              $tags = [
                "{$entity_type_id}_list:{$bundle}",
              ];
              $this->cacheability->setCacheTags(array_merge($this->cacheability->getCacheTags(), $tags));
              $type_field = $entity_type_id === 'taxonomy_term' ? 'vid' : 'type';
              $entity_type_definition = $this->entityTypeManager->getDefinition($entity_type_id);
              $status = $entity_type_definition->getKey('status');
              $status = !$status ? $entity_type_definition->getKey('published') : $status;
              $properties = [
                $type_field => $bundle,
              ];
              if ($status) {
                $properties[$status] = 1;
              }
              $entities = $this->entityTypeManager->getStorage($entity_type_id)
                ->loadByProperties($properties);
              $entities = array_map(function ($entity) use ($entity_repository, $langcode) {
                return $entity_repository->getTranslationFromContext($entity, $langcode);
              }, $entities);

              if (!empty($entities) && $entity_type_id === 'taxonomy_term') {
                usort($entities, function ($a, $b) {
                  $weight_a = $a->get('weight')->value;
                  $weight_b = $b->get('weight')->value;
                  return ($weight_a <=> $weight_b);
                });
              }

              $info['is_options_locked'] = FALSE;
              $this->moduleHandler->alter('decoupled_entity_reference_options', $entities, $info, $this->cacheability);
              if (isset($info['is_options_locked']) && !$info['is_options_locked']) {
                // Format options here.
                $data = [];
                $tags = $this->cacheability->getCacheTags();
                foreach ($entities as $id => $entity) {
                  $data[$id] = [
                    'id'    => $entity->id(),
                    'uuid'  => $entity->uuid(),
                    'label' => $entity->label(),
                  ];
                  if ($entity->getEntityTypeId() === 'taxonomy_term' && $entity->hasField('results_count')) {
                    $this->injectTaxonomyResultsCount($entity, $data[$id], $tags);
                  }
                }
                $entities = array_values($data);
              }
            }
            $value = $entities;
          }

          // Image media.
          if ($info['type'] === 'image' && !empty($value)) {
            $key = array_keys($value)[0];
            $image_data = [];
            if (isset($value[$key]['selection'])) {
              foreach ($value[$key]['selection'] as $media) {
                $file = $this->mediaStorage->load($media['target_id']);
                if ($file) {
                  // Add cache.
                  $cacheTags = Cache::mergeTags($this->cacheability->getCacheTags(), $file->getCacheTags());
                  $this->cacheability->setCacheTags($cacheTags);
                  $uri = $file->thumbnail->entity->getFileUri();
                  $image_item['_default'] = $this->mediaFilesManager->getMediaAbsoluteUrl($uri);
                  // $image_item['_lqip'] = $this->mediaFilesManager->convertToMediaAbsoluteUrl($this->imageStyles['lqip']->buildUrl($uri));
                  // $image_item['uri'] = StreamWrapperManager::getTarget($uri);
                  // $image_item['fid'] = $file->thumbnail->entity->fid->value;
                  $image_item['file_name'] = $file->label();
                  // $image_item['base_url'] = $image_app_base_url;
                  if (!empty($file->get('field_media_image')->getValue())) {
                    $image_item['meta'] = $file->get('field_media_image')
                      ->first()
                      ->getValue();
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

          // Video media.
          if ($info['type'] === 'video' && !empty($value)) {
            $key = array_keys($value)[0];
            $video_data = [];
            if (isset($value[$key]['selection'])) {
              foreach ($value[$key]['selection'] as $media) {
                $file = $this->mediaStorage->load($media['target_id']);
                if ($file) {
                  // Add cache.
                  $cacheTags = Cache::mergeTags($this->cacheability->getCacheTags(), $file->getCacheTags());
                  $this->cacheability->setCacheTags($cacheTags);
                  $uri = $file->field_media_video_file->entity->getFileUri();
                  $video_item['_default'] = $this->mediaFilesManager->getMediaAbsoluteUrl($uri);
                  // $video_item['uri'] = StreamWrapperManager::getTarget($uri);
                  // $video_item['fid'] = $file->thumbnail->entity->fid->value;
                  $video_item['file_name'] = $file->label();
                  // $video_item['base_url'] = $image_app_base_url;
                  if (!empty($file->get('field_media_video_file')->getValue())) {
                    $video_item['meta'] = $file->get('field_media_video_file')
                      ->first()
                      ->getValue();
                  }
                }
                else {
                  $video_item['_error'] = 'Media file ID: ' . $media['target_id'] . ' Not Found';
                }

                $video_data[] = $video_item;
              }
            }
            $value = $video_data;
          }

          // Document media.
          if ($info['type'] === 'file' && !empty($value)) {
            $key = array_keys($value)[0];
            $file_data = [];
            if (isset($value[$key]['selection'])) {
              foreach ($value[$key]['selection'] as $media) {
                $file = $this->mediaStorage->load($media['target_id']);
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
                      '_default'  => $this->mediaFilesManager->getMediaAbsoluteUrl($uri),
                      // 'uri' => StreamWrapperManager::getTarget($uri),
                      'fid'       => $file->id(),
                      'file_name' => $file->label(),
                    ];
                  }
                }
                else {
                  $file_data['_error'] = 'Media file ID: ' . $media['target_id'] . ' Not Found';
                }
              }
            }
            $value = $file_data;
          }

          // Views.
          if ($info['type'] === 'dynamic_views' && !empty($value)) {
            $value = array_merge($value, $info['options']['#default_value']);
            $value['data'] = $this->viewsToApi->normalize($value);
          }

          // Collection.
          if ($info['type'] === 'json_api_collection' && !empty($value)) {
            $value = array_merge($info['options']['#default_value'], $value);
            $response = $this->jsonApiGenerator->fetch($value);
            $cache = $response['cache'];
            unset($response['cache']);

            $cacheTags = Cache::mergeTags($this->cacheability->getCacheTags(), $cache['tags']);
            $this->cacheability->setCacheTags($cacheTags);
            $cacheContexts = Cache::mergeContexts($this->cacheability->getCacheContexts(), $cache['contexts']);
            $this->cacheability->setCacheContexts($cacheContexts);
            $value = $response;
          }

          // Cross bundles.
          if ($info['type'] === 'json_api_cross_bundles' && !empty($value)) {
            $value = array_merge($info['options']['#default_value'], $value);
            $resource = $value['resource']['entity_type'];
            $bundles = $value['resource']['bundle'];
            $bundleEntityType = \Drupal::entityTypeManager()->getDefinition($resource)->getBundleEntityType();
            if (is_array($bundles)) {
              $value['filters'][] = "filter[bundles][condition][path]={$bundleEntityType}.meta.drupal_internal__target_id";
              $value['filters'][] = 'filter[bundles][condition][operator]=IN';
              foreach ($bundles as $key => $bundle) {
                $value['filters'][] = "filter[bundles][condition][value][{$key}]={$bundle}";
              }
            }
            $value['resource'] = $resource;
            $response = $this->jsonApiGenerator->fetch($value);
            $cache = $response['cache'];
            unset($response['cache']);

            $cacheTags = Cache::mergeTags($this->cacheability->getCacheTags(), $cache['tags']);
            $this->cacheability->setCacheTags($cacheTags);
            $cacheContexts = Cache::mergeContexts($this->cacheability->getCacheContexts(), $cache['contexts']);
            $this->cacheability->setCacheContexts($cacheContexts);
            $value = $response;
          }

          // Webform.
          if (
            $info['type'] === 'webform_decoupled' &&
            !empty($value) &&
            $this->moduleHandler->moduleExists('vactory_decoupled_webform')
          ) {
            $webform_id = $value['id'];
            // Cache tags.
            $cacheTags = Cache::mergeTags($this->cacheability->getCacheTags(), [
              'webform_submission_list',
              'config:webform_list',
            ]);
            $this->cacheability->setCacheTags($cacheTags);
            // Cache contexts.
            $cacheContexts = Cache::mergeContexts($this->cacheability->getCacheContexts(), ['user']);
            $this->cacheability->setCacheContexts($cacheContexts);
            // Service invoked statically because vactory_decoupled_webform.
            // Module depends on vactory_decoupled.
            $value['elements'] = \Drupal::service('vactory.webform.normalizer')->normalize($webform_id);
          }

          if ($info['type'] === 'remote_video' && !empty($value)) {
            $value = reset($value);
            $mid = $value['selection'][0]['target_id'] ?? '';
            $media = !empty($mid) ? $this->mediaStorage->load($mid) : NULL;
            if ($media instanceof MediaInterface) {
              $value = [
                'id'   => $media->uuid(),
                'name' => $media->getName(),
                'url'  => $media->get('field_media_oembed_video')->value,
              ];
            }
          }

          if ($info['type'] === 'node_queue' && !empty($value)) {
            $value = array_merge($info['options']['#default_value'], $value);
            $value['filters'][] = 'filter[df-node-nid][condition][path]=nid';
            $value['filters'][] = 'filter[df-node-nid][condition][operator]=IN';
            $i = 1;
            foreach ($value['nodes'] as $nid) {
              $value['filters'][] = "filter[df-node-nid][condition][value][$i]=" . $nid['target_id'];
              $i++;
            }
            $response = $this->jsonApiGenerator->fetch($value);
            $cache = $response['cache'];
            unset($response['cache']);

            $cacheTags = Cache::mergeTags($this->cacheability->getCacheTags(), $cache['tags']);
            $this->cacheability->setCacheTags($cacheTags);
            $cacheContexts = Cache::mergeContexts($this->cacheability->getCacheContexts(), $cache['contexts']);
            $this->cacheability->setCacheContexts($cacheContexts);
            $value = $response;
          }

          $cacheability = $this->cacheability;
          // Apply other modules formatters if exist on current component.
          $this->moduleHandler->alter('decoupled_df_format', $value, $info, $cacheability);
          $this->cacheability = $cacheability;
        }
        elseif (is_array($value)) {
          // Go deeper.
          $this->applyFormatters(array_merge((array) $parent_keys, [$field_key]), $settings, $value);
        }
      }
    }
  }

  /**
   * Inject Taxonomy Results Count.
   */
  public function injectTaxonomyResultsCount($term, &$term_data, $tags) {
    $result_count_ids = $term->get('results_count')->getValue();
    if (!empty($result_count_ids)) {
      $result_count_ids = array_map(function ($el) {
        return $el['target_id'];
      }, $result_count_ids);
      if (!empty($result_count_ids) && $this->termResultCount) {
        $results_count = $this->termResultCount->loadMultiple($result_count_ids);
        if (!empty($results_count)) {
          foreach ($results_count as $result_count) {
            $plugin = $result_count->get('plugin')->value;
            $entity_type = $result_count->get('entity_type')->value;
            $bundle = $result_count->get('bundle')->value;
            $count = $result_count->get('count')->value;
            if (!empty($plugin) && !empty($entity_type) && !empty($bundle) && !empty($count)) {
              $cacheTags = Cache::mergeTags($tags, $result_count->getCacheTags());
              $this->cacheability->setCacheTags($cacheTags);
              $term_data['results'][] = [
                'plugin'      => $plugin,
                'entity_type' => $entity_type,
                'bundle'      => $bundle,
                'count'       => $count,
              ];
            }
          }
        }
      }
    }
  }

}
