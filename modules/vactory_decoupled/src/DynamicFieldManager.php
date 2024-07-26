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
use Drupal\Core\Utility\Token;
use GuzzleHttp\Client;
use Drupal\webform\Entity\Webform;

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
   * Token.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * Http client.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, $plateform_provider, MediaFilesManager $mediaFilesManager, EntityRepositoryInterface $entityRepository, JsonApiGenerator $jsonApiGenerator, SlugManager $slugManager, ModuleHandlerInterface $moduleHandler, LanguageManagerInterface $languageManager, ViewsToApi $viewsToApi, ConfigFactoryInterface $configFactory, Token $token, Client $httpClient) {
    $this->entityTypeManager = $entity_type_manager;
    $this->platformProvider = $plateform_provider;
    $this->imageStyles = ImageStyle::loadMultiple();
    $this->siteConfig = $configFactory->get('system.site');
    $this->configFactory = $configFactory;
    $this->mediaFilesManager = $mediaFilesManager;
    $this->entityRepository = $entityRepository;
    $this->jsonApiGenerator = $jsonApiGenerator;
    $this->slugManager = $slugManager;
    $this->moduleHandler = $moduleHandler;
    $this->languageManager = $languageManager;
    $this->language = $languageManager->getCurrentLanguage()->getId();
    $this->viewsToApi = $viewsToApi;
    $this->token = $token;
    $this->httpClient = $httpClient;
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
      $this->moduleHandler->alter('df_jsonapi_output', $content, $cacheability);
      $this->cacheability = $cacheability;
      $data['widget_data'] = json_encode($content);
    }

    // Restore cache.
    if (isset($this->cacheability)) {
      $this->cacheability->addCacheContexts(['url.query_args:q']);
    }

    return [
      'data'         => $data,
      'cacheability' => $this->cacheability,
    ];
  }

  /**
   * Apply formatters such as processed_text, image & links.
   */
  public function applyFormatters($parent_keys, $settings, &$component) {
    $simple_text_types = [
      'text',
      'textarea',
    ];
    $contentService = NULL;
    if (\Drupal::moduleHandler()->moduleExists('vactory_content_sheets')) {
      $contentService = \Drupal::service('vactory_content_sheets.content_services');
    }
    foreach ($component as $field_key => &$value) {
      $info = NestedArray::getValue($settings, array_merge((array) $parent_keys, [$field_key]));
      if (is_array($info)) {
        $info['uuid'] = $settings['uuid'];
        if ($info && isset($info['type'])) {
          // Manage external/internal links.
          if ($info['type'] === 'url_extended') {
            $url = $value['url'];

            if (str_starts_with($url, 'cta:') && $contentService) {
              $this->cacheability->addCacheTags([$url]);
              $retrievedContent = $contentService->getContent($url);
              $retrievedContent = $contentService->extractCTA($retrievedContent);
              if ($retrievedContent !== NULL) {
                $value['url'] = $retrievedContent['url'];
                $value['title'] = $retrievedContent['label'];
              }
            }
            else {
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
              $path = $this->handleEditLiveModeFormat($parent_keys, $settings, $component, $field_key);
              if (!is_null($path)) {
                $path .= '.title';
                $value['title'] = "{LiveMode id=\"{$path}\"}{$value['title']}{/LiveMode}";
              }
            }

            // Check for external links.
            $value['is_external'] = UrlHelper::isExternal($value['url']);
          }

          // Text Preprocessor.
          if (in_array($info['type'], $simple_text_types)) {
            if (str_starts_with($value, 'tx:') && $contentService) {
              $this->cacheability->addCacheTags([$value]);
              $retrievedContent = $contentService->getContent($value);
              if ($retrievedContent) {
                $value = $retrievedContent;
              }
            }
            $path = $this->handleEditLiveModeFormat($parent_keys, $settings, $component, $field_key);
            if (!is_null($path)) {
              $value = "{LiveMode id=\"{$path}\"}{$value}{/LiveMode}";
            }
          }

          // Text_format Preprocessor.
          if ($info['type'] === 'text_format') {
            $text = $value['value'] ?? $value;
            if ((str_starts_with($text, 'tx:') || str_starts_with($text, '<p>tx:')) && $contentService) {
              $text = strip_tags($text);
              $this->cacheability->addCacheTags([$text]);
              $retrievedContent = $contentService->getContent($text);
              if ($retrievedContent) {
                $text = $retrievedContent;
              }
            }

            $format = $info['options']['#format'] ?? 'full_html';
            $build = [
              // '#type'   => 'processed_text',
              '#text' => (string) check_markup($text, $format),
              '#format' => $format,
            ];
            $path = $this->handleEditLiveModeFormat($parent_keys, $settings, $component, $field_key);
            if (!is_null($path)) {
              $val = (string) check_markup($text, $format);
              $build = [
                // '#type'   => 'processed_text',
                '#text' => "<p>{LiveMode id=\"{$path}\"}</p>{$val}<p>{/LiveMode}</p>",
                '#format' => $format,
              ];
            }

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
            $media_img = $value[$key]['media_google_sheet'] ?? NULL;
            $image_data = [];
            $file = NULL;
            if (isset($value[$key]['selection'])) {
              foreach ($value[$key]['selection'] as $media) {
                $file = $this->mediaStorage->load($media['target_id']);
                if ($file) {
                  // Add cache.
                  $cacheTags = Cache::mergeTags($this->cacheability->getCacheTags(), $file->getCacheTags());
                  $this->cacheability->setCacheTags($cacheTags);
                  $uri = $file->thumbnail->entity->getFileUri();
                  $image_item['_default'] = $this->mediaFilesManager->getMediaAbsoluteUrl($uri);
                  $image_item['file_name'] = $file->label();
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
            if (isset($media_img) && $contentService && str_starts_with($media_img, 'img:')) {
              $media_img = strip_tags($media_img);
              $this->cacheability->addCacheTags([$media_img]);
              $retrievedContent = $contentService->getContent($media_img);
              if (!empty($retrievedContent)) {
                $file = $this->mediaStorage->load($retrievedContent);
                if ($file) {
                  // Add cache.
                  $cacheTags = Cache::mergeTags($this->cacheability->getCacheTags(), $file->getCacheTags());
                  $this->cacheability->setCacheTags($cacheTags);
                  $uri = $file->thumbnail->entity->getFileUri();
                  $image_item['_default'] = $this->mediaFilesManager->getMediaAbsoluteUrl($uri);
                  $image_item['file_name'] = $file->label();
                  if (!empty($file->get('field_media_image')->getValue())) {
                    $image_item['meta'] = $file->get('field_media_image')
                      ->first()
                      ->getValue();
                  }
                  $image_data = [$image_item];
                }
              }
            }
            if ($file) {
              $this->moduleHandler->alter('df_manager_image', $image_data, $file->thumbnail->entity);
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
            $media_file = $value[$key]['media_google_sheet'] ?? NULL;
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
            if (isset($media_file) && $contentService && str_starts_with($media_file, 'file:')) {
              $media_file = strip_tags($media_file);
              $this->cacheability->addCacheTags([$media_file]);
              $retrievedContent = $contentService->getContent($media_file);
              if (!empty($retrievedContent)) {
                $file = $this->mediaStorage->load($retrievedContent);
                $fid = (int) $file->get('field_media_file')->getString();
                $document = File::load($fid);
                if ($document) {
                  // Add cache.
                  $cacheTags = Cache::mergeTags($this->cacheability->getCacheTags(), $document->getCacheTags());
                  $this->cacheability->setCacheTags($cacheTags);
                  $uri = $document->getFileUri();
                  $file_item['_default'] = $this->mediaFilesManager->getMediaAbsoluteUrl($uri);
                  $file_item['file_name'] = $file->label();
                  $file_item['fid'] = $document->id();
                  $file_data = [$file_item];
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

            $value['autosave'] = FALSE;
            // Check webform autosave module is enabled and add its settings.
            if (\Drupal::moduleHandler()->moduleExists('vactory_decoupled_webform_autosave')) {
              $webform = Webform::load($webform_id);
              if ($webform) {
                $autosave_settings = $webform->getThirdPartySetting('vactory_decoupled_webform_autosave', 'autosave_settings', []);
                if ($autosave_settings['autosave_enabled']) {
                  $value['autosave'] = $autosave_settings;
                }
              }
            }
          }

          if ($info['type'] === 'remote_video' && !empty($value)) {
            $value = reset($value);
            $media_ytb = $value['media_google_sheet'] ?? NULL;
            $mid = $value['selection'][0]['target_id'] ?? '';
            $media = !empty($mid) ? $this->mediaStorage->load($mid) : NULL;
            $video_url = '';
            $thumbnail = '';
            $thumbnail_maxres = '';
            if ($media instanceof MediaInterface) {
              $video_url = $media->get('field_media_oembed_video')->value;
              $thumbnail_maxres = $this->getYoutubeThumbnail($video_url);
              $thumbnail_uri = $this->getDefaultYoutubeThumbnail($media);
              $thumbnail = $this->mediaFilesManager->getMediaAbsoluteUrl($thumbnail_uri);
              $value = [
                'id'   => $media->uuid(),
                'name' => $media->getName(),
                'url'  => $video_url,
                'thumbnail' => [
                  'uri' => $thumbnail,
                  'maxres' => $thumbnail_maxres,
                  'height' => $media->get('thumbnail')->height,
                  'width' => $media->get('thumbnail')->width,
                ],
              ];
            }
            if (isset($media_ytb) && $contentService && str_starts_with($media_ytb, 'ytb:')) {
              $media_ytb = strip_tags($media_ytb);
              $this->cacheability->addCacheTags([$media_ytb]);
              $retrievedContent = $contentService->getContent($media_ytb);
              $media = !empty($retrievedContent) ? $this->mediaStorage->load($retrievedContent) : NULL;
              if ($media instanceof MediaInterface) {
                $video_url = $media->get('field_media_oembed_video')->value;
                $thumbnail_maxres = $this->getYoutubeThumbnail($video_url);
                $thumbnail_uri = $this->getDefaultYoutubeThumbnail($media);
                $thumbnail = $this->mediaFilesManager->getMediaAbsoluteUrl($thumbnail_uri);
                $value = [
                  'id'   => $media->uuid(),
                  'name' => $media->getName(),
                  'url'  => $video_url,
                  'thumbnail' => [
                    'uri' => $thumbnail,
                    'maxres' => $thumbnail_maxres,
                    'height' => $media->get('thumbnail')->height,
                    'width' => $media->get('thumbnail')->width,
                  ],
                ];
              }
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
          if ($info['type'] === 'dynamic_api_fetch' && !empty($value)) {

            $url = $this->token->replace($value['url']);
            $query_params_input = $this->token->replace($value['query_params']);
            $headers_input = $this->token->replace($value['headers']);
            $query_params = [];
            $headers = [];

            if (!empty($query_params_input)) {
              $query_params_as_rows = explode(PHP_EOL, $query_params_input);
              if (!empty($query_params_as_rows)) {
                foreach ($query_params_as_rows as $row) {
                  $param = explode('=', $row);
                  if (count($param) === 2) {
                    $query_params[trim($param[0])] = trim($param[1]);
                  }
                }
              }
            }

            if (!empty($headers_input)) {
              $headers_as_rows = explode(PHP_EOL, $headers_input);
              if (!empty($headers_as_rows)) {
                foreach ($headers_as_rows as $row) {
                  $header = explode('=', $row);
                  if (count($header) === 2) {
                    $headers[trim($header[0])] = trim($header[1]);
                  }
                }
              }
            }
            $api_config = [
              'url' => $url,
              'headers' => $headers,
              'query_params' => $query_params,
            ];
            $this->moduleHandler->alter('dynamic_api_fetch_config', $api_config, $info);

            if (is_array($api_config['query_params']) && count($api_config['query_params']) > 0) {
              $api_config['url'] = $api_config['url'] . '?' . http_build_query($api_config['query_params']);
            }

            try {
              $request = $this->httpClient->get($api_config['url'], [
                "headers" => $api_config['headers'],
              ]);

              $content = $request->getBody()->getContents();
              $result = json_decode($content, TRUE);
              $this->moduleHandler->alter('dynamic_api_fetch_result', $result, $info);
              $value = $result;

            }
            catch (\Exception $e) {
              \Drupal::logger('dynamic_api_fetch')->error($e->getMessage());
            }
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

  /**
   * Get youtube thumbnail by video id.
   */
  private function getYoutubeThumbnail($video_url) {
    $uri = '';
    $url_components = parse_url($video_url);
    $is_youtube_full_url = strpos($url_components['host'], 'youtube.com') !== FALSE;
    $is_youtube_embed_url = strpos($url_components['host'], 'youtu.be') !== FALSE;
    // Handle youtube thumbnail case.
    if ($is_youtube_full_url || $is_youtube_embed_url) {
      $video_id = '';
      if ($is_youtube_full_url && isset($url_components['query'])) {
        parse_str($url_components['query'], $params);
        if (isset($params['v'])) {
          $video_id = $params['v'];
        }
      }
      if ($is_youtube_embed_url && isset($url_components['path'])) {
        $path = trim($url_components['path'], '/');
        $path_args = explode('/', $path);
        if (!empty($path_args)) {
          $video_id = $path_args[0];
        }
      }
      if (!empty($video_id)) {
        $uri = 'https://img.youtube.com/vi/' . $video_id . '/maxres2.jpg';
      }
    }
    return $uri;
  }

  /**
   * Get drupal youtube.
   */
  private function getDefaultYoutubeThumbnail($media) {
    $fid = $media->get('thumbnail')->target_id;
    if (isset($fid) && !empty($fid)) {
      $uri = File::load($fid)->getFileUri();
      return $uri;
    }
    return '';
  }

  /**
   * Generate field path for edit live mode.
   */
  private function handleEditLiveModeFormat($parent_keys, $settings, $component, $field_key) {
    // Check permission.
    // Ensure the user has 'edit content live mode' permission.
    // To utilize the edit live mode feature.
    $user_id = \Drupal::currentUser()->id();
    $user = $this->entityTypeManager->getStorage('user')->load($user_id);
    $user_granted = $user->hasPermission('edit content live mode');

    // Check if the feature is enabled from vactory_dynamic_field setings.
    $decoupled_edit_live_mode = $this->configFactory->get('vactory_dynamic_field.settings')->get('decoupled_edit_live_mode');

    if ($user_granted && $decoupled_edit_live_mode) {
      $path = $parent_keys;
      // In the case of multiple fields.
      // Each component is indexed with its weight.
      if ($settings['multiple'] && $parent_keys[0] == 'fields') {
        $index = ((int) $component['_weight']) - 1;
        $path[] = "$index";
        // Remove 'fields' key from the path.
        // Since each component has its own index.
        if (($key = array_search('fields', $path)) !== FALSE) {
          unset($path[$key]);
        }
      }
      // Add field key to the path.
      $path[] = $field_key;

      // If DF is not multiple, only '0' key exists.
      // So we replace fields with '0'.
      if (($key = array_search('fields', $path)) !== FALSE) {
        $path[$key] = 0;
      }

      // extra_fields are stored in json with key extra_fields.
      // Remove the 's' to match more accurately.
      if (($key = array_search('extra_fields', $path)) !== FALSE) {
        $path[$key] = 'extra_field';
      }

      // Finally, join the constructed path parts to form the final path.
      $path = implode('.', $path);
      return $path;
    }
    return NULL;
  }

}
