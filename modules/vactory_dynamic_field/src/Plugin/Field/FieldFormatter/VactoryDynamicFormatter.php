<?php

namespace Drupal\vactory_dynamic_field\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\NestedArray;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Render\RenderContext;
use Drupal\Core\Site\Settings;
use Drupal\Core\Url;
use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;
use Drupal\media\MediaInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'field_wysiwyg_dynamic_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "field_wysiwyg_dynamic_formatter",
 *   module = "vactory_dynamic_field",
 *   label = @Translation("Dynamic Field formatter"),
 *   field_types = {
 *     "field_wysiwyg_dynamic"
 *   }
 * )
 */
class VactoryDynamicFormatter extends FormatterBase {

  /**
   * Renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Platform provider service.
   *
   * @var \Drupal\vactory_dynamic_field\WidgetsManager
   */
  protected $platformProvider;

  /**
   * Logger factory service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerChannelFactory;

  /**
   * File url generator service.
   *
   * @var \Drupal\Core\File\FileUrlGeneratorInterface
   */
  protected $fileUrlGenerator;

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->renderer = $container->get('renderer');
    $instance->platformProvider = $container->get('vactory_dynamic_field.vactory_provider_manager');
    $instance->loggerChannelFactory = $container->get('logger.factory');
    $instance->fileUrlGenerator = $container->get('file_url_generator');
    return $instance;
  }

  /**
   * Apply formatters such as processed_text, image & links.
   */
  private function applyFormatters($parent_keys, $settings, &$component) {
    $contentService = NULL;
    if (\Drupal::moduleHandler()->moduleExists('vactory_content_sheets')) {
      $contentService = \Drupal::service('vactory_content_sheets.content_services');
    }
    foreach ($component as $field_key => &$value) {
      $info = NestedArray::getValue($settings, array_merge((array) $parent_keys, [$field_key]));

      if ($info && isset($info['type'])) {
        // Manage external/internal links.
        if ($info['type'] === 'url_extended') {
          if (str_starts_with($value['url'] ?? '', 'cta:') && $contentService) {
            $retrievedContent = $contentService->getContent($value['url']);
            $retrievedContent = $contentService->extractCTA($retrievedContent);
            $value['url'] = $retrievedContent['url'];
            $value['title'] = $retrievedContent['label'];
          }
          elseif (!empty($value['url']) && !UrlHelper::isExternal($value['url'])) {
            $value['url'] = Url::fromUserInput($value['url'], ['absolute' => 'true'])
              ->toString();
          }
        }

        // Text Preprocessor.
        if ($info['type'] === 'text_format') {
          $format = $info['options']['#format'] ?? 'full_html';
          $build = [
            '#type'   => 'processed_text',
            '#text'   => isset($value['value']) ? $value['value'] : '',
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
              $image_data[] = $media['target_id'];
            }
          }
          $value = $image_data;
        }

        // Video media.
        if ($info['type'] === 'video' && !empty($value)) {
          if (is_array($value) && isset(array_values($value)[0]['selection'][0]['target_id'])) {
            $media_id = array_values($value)[0]['selection'][0]['target_id'];
            $media = Media::load($media_id);
            $uri = '';
            if (isset($media) && !empty($media)) {
              if ($media->hasField('field_media_video_file')) {
                $video_id = $media->field_media_video_file->target_id;
                if ($video_id) {
                  $video = File::load($video_id);
                  $url = $video->getFileUri();
                  $video_url = $this->fileUrlGenerator->generateAbsoluteString($url);
                  $fid = $media->get('thumbnail')->target_id;
                  $file = File::load($fid);
                  $uri = '';
                  if ($file) {
                    $uri = $file->getFileUri();
                  }
                  $content = [
                    'name' => $media->get('name')->value,
                    'video_url' => $video_url,
                    'thumbnail' => [
                      'uri' => $uri,
                      'height' => $media->get('thumbnail')->height,
                      'width' => $media->get('thumbnail')->width,
                    ],
                  ];
                  $value = $content;
                }
              }
            }
          }
        }
        // File media.
        if ($info['type'] === 'file' && !empty($value)) {
          $file_link = NULL;
          if (!is_array($value)) {
            $media = Media::load($value);
            if (isset($media) && !empty($media) && isset($media->field_media_file->target_id) && !empty($media->field_media_file->target_id)) {
              $fid = $media->field_media_file->target_id;
              $file = File::load($fid);
              if (isset($file) && !empty($file)) {
                $absolute_url = $this->fileUrlGenerator->generateAbsoluteString($file->getFileUri());
                $file_link = $this->fileUrlGenerator->transformRelative($absolute_url);
              }
            }
          }
          if (is_array($value)) {
            // PDF cloudinary case.
            $value = array_filter($value, function ($el) {
              return isset($el['selection'][0]);
            });
            $key = array_keys($value)[0] ?? '';
            if (isset($value[$key]['selection'])) {
              $file = reset($value[$key]['selection']);
              $mid = $file['target_id'];
              $media = Media::load($mid);
              if (isset($media) && $media instanceof MediaInterface) {
                $fid = (int) $media->get('field_media_file')->getString();
                $file = File::load($fid);
                if ($file) {
                  $absolute_url = $this->fileUrlGenerator->generateAbsoluteString($file->getFileUri());
                  $file_link = $this->fileUrlGenerator->transformRelative($absolute_url);
                }
              }
            }
          }

          $value = $file_link;
        }

        if ($info['type'] === 'remote_video' && !empty($value)) {
          if (is_array($value) && isset(array_values($value)[0]['selection'][0]['target_id'])) {
            $media_id = array_values($value)[0]['selection'][0]['target_id'];
            $media = Media::load($media_id);
            $uri = '';
            if (isset($media) && !empty($media)) {
              if ($media->hasField('field_media_oembed_video')) {
                $video_url = $media->get('field_media_oembed_video')->value;
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
              }
              if (empty($uri)) {
                $fid = $media->get('thumbnail')->target_id;
                if (isset($fid) && !empty($fid)) {
                  $uri = File::load($fid)->getFileUri();
                }
              }
              $content = [
                'titre' => $media->get('name')->value,
                'video_url' => $media->get('field_media_oembed_video')->value,
                'thumbnail' => [
                  'uri' => $uri,
                  'height' => $media->get('thumbnail')->height,
                  'width' => $media->get('thumbnail')->width,
                ],
              ];
              $value = $content;
            }
          }
        }

        // Views.
        if ($info['type'] === 'dynamic_views' && !empty($value)) {
          $value = array_merge($value, $info['options']['#default_value']);
          $value = \Drupal::service('vactory.views.to_api')->normalize($value);
        }

        // Collection.
        if ($info['type'] === 'json_api_collection' && !empty($value)) {
          $value = array_merge($value, $info['options']['#default_value']);
          $value = \Drupal::service('vactory_decoupled.jsonapi.generator')->fetch($value);
        }

        // Webform.
        if ($info['type'] === 'webform_decoupled' && !empty($value)) {
          $webform_id = $value['id'];
          $value['elements'] = \Drupal::service('vactory.webform.normalizer')->normalize($webform_id);
        }
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
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $request = \Drupal::request();
    $amp_context = \Drupal::service('router.amp_context');

    if ($request->isXmlHttpRequest()) {
      return [];
    }

    $elements = [];

    foreach ($items as $delta => $item) {
      $widget_id = $item->widget_id;
      $widget_data = json_decode($item->widget_data, TRUE);
      list($platform, $template_id) = explode(':', $widget_id);
      $settings = $this->platformProvider->loadSettings($widget_id);
      $widgets_path = $this->platformProvider->getWidgetsPath($widget_id);

      // Current template to find.
      $content['template'] = $template_id;
      // Placeholder Image.
      $content['image_placeholder'] = VACTORY_DYNAMIC_FIELD_V_IMAGE_PLACEHOLDER;
      // Is content auto populate.
      $content['is_dummy'] = FALSE;
      // Check if the static template exist.
      $content['has_static_tpl'] = FALSE;
      // Check if the amp template exist.
      $content['has_amp_tpl'] = file_exists($widgets_path . '/' . $content['template'] . '/template.amp.html.twig');
      $content['is_amp_page'] = $amp_context->isAmpRoute();
      // Add is dummy to check.
      if (isset($widget_data['auto-populate']) && $widget_data['auto-populate'] === TRUE) {
        $content['is_dummy'] = TRUE;
        $content['has_static_tpl'] = file_exists($widgets_path . '/' . $content['template'] . '/static.html.twig');
      }

      if (!$content['is_dummy']) {
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

        $content['components'] = [];

        // Format fields.
        foreach ($widget_data as &$component) {
          $this->applyFormatters(['fields'], $settings, $component);
          $content['components'][] = $component;
        }

        // Format extra fields.
        if (array_key_exists('extra_field', $content) && is_array($content['extra_field'])) {
          $this->applyFormatters(['extra_fields'], $settings, $content['extra_field']);
        }
      }

      /*
       * Allow other modules to override components content.
       *
       * @code
       * Implements hook_dynamic_field_content_alter().
       * function myModule_dynamic_field_content_alter(&$content) {
       * }
       * @endcode
       */
      \Drupal::moduleHandler()->alter('dynamic_field_content', $content);
      $cache = [
        "max-age" => Cache::PERMANENT,
      ];
      \Drupal::moduleHandler()->alter('dynamic_field_cache', $cache);
      $render = [
        '#theme' => 'vactory_dynamic_main',
        '#entity_delta' => $delta,
        '#item' => $item,
        '#content' => $content,
        '#platform' => $platform,
        '#widgets_path' => $widgets_path,
        "#cache" => is_array($cache) ? $cache : [],
      ];

      $renderer = $this->renderer;
      $logger = $this->loggerChannelFactory;
      $elements[$delta] = $this->renderer->executeInRenderContext(new RenderContext(), static function () use ($render, $widget_id, $renderer, $logger) {
        // Make sure the template render doesn't throw any exception.
        try {
          $artifact_render = $render;
          $renderer->render($render);
        }
        catch (\Exception $e) {
          $df_errors_policy = Settings::get('df_errors_policy');
          $artifact_render = [
            '#theme' => 'vactory_dynamic_errors',
            '#error' => [
              'template_id' => $widget_id,
              'message' => isset($df_errors_policy['show_message']) && $df_errors_policy['show_message'] ? $e->getMessage() : '',
              'trace' => isset($df_errors_policy['show_trace']) && $df_errors_policy['show_trace'] ? $e->getTraceAsString() : '',
              'concerned_file' => isset($df_errors_policy['show_source_file']) && $df_errors_policy['show_source_file'] ? $e->getFile() : '',
            ],
          ];
          $error_message = $widget_id . PHP_EOL;
          $error_message .= $e->getMessage() . PHP_EOL;
          $error_message .= 'In ' . $e->getFile() . PHP_EOL;
          $error_message .= $e->getTraceAsString();
          $logger->get('vactory_dynamic_field')->error($error_message);
        }
        return $artifact_render;
      });
    }

    return $elements;

  }

}
