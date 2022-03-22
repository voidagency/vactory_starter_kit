<?php

namespace Drupal\vactory_jsonapi\Plugin\jsonapi;

use Drupal\Core\StreamWrapper\StreamWrapperManager;
use Drupal\Core\Url;
use Drupal\file\Entity\File;
use Drupal\image\Entity\ImageStyle;
use Drupal\media\Entity\Media;
use Drupal\Component\Utility\NestedArray;
use Drupal\Component\Utility\UrlHelper;
use Drupal\taxonomy\Entity\Term;

class VactoryDynamicFieldServiceEnhancer
{
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

  /**
   * {@inheritdoc}
   */
  public function __construct()
  {
    $this->language = \Drupal::languageManager()->getCurrentLanguage()->getId();
    $this->platformProvider = \Drupal::service('vactory_dynamic_field.vactory_provider_manager');
    $this->imageStyles = ImageStyle::loadMultiple();
    $this->siteConfig = \Drupal::config('system.site');
  }

  /**
   * {@inheritdoc}
   */
  public function doUndoTransform($data)
  {
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
  private function applyFormatters($parent_keys, $settings, &$component)
  {
    $image_app_base_url = Url::fromUserInput('/app-image/')
      ->setAbsolute()->toString();
    foreach ($component as $field_key => &$value) {
      $info = NestedArray::getValue($settings, array_merge((array)$parent_keys, [$field_key]));

      if ($info && isset($info['type'])) {
        // Manage external/internal links.
        if ($info['type'] === 'url_extended') {

          if (!empty($value['url']) && !UrlHelper::isExternal($value['url'])) {
            $front_uri = $this->siteConfig->get('page.front');
            if ($front_uri === $value['url']) {
              $value['url'] = Url::fromRoute('<front>')->toString();
            } else {
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
            '#type' => 'processed_text',
            '#text' => $value['value'],
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
              $uri = $file->thumbnail->entity->getFileUri();
              $image_item['_default'] = \Drupal::service('file_url_generator')->generateAbsoluteString($uri);
              $image_item['_lqip'] = $this->imageStyles['lqip']->buildUrl($uri);
              $image_item['uri'] = \Drupal::service('file_url_generator')->generateAbsoluteString($uri);
              $image_item['fid'] = $file->thumbnail->entity->fid->value;
              $image_item['file_name'] = $file->label();
              $image_item['base_url'] = $image_app_base_url;
              if (!empty($file->get('field_media_image')->getValue())) {
                $image_item['meta'] = $file->get('field_media_image')->first()->getValue();
              }
              $image_data[] = $image_item;
            }
          }
          $value = $image_data;
        }

        // Document media.
        if ($info['type'] === 'file' && !empty($value)) {
          $media = Media::load($value);
          if ($media) {
            $fid = (int) $media->get('field_media_document')->getString();
            $file = File::load($fid);
            if ($file) {
              $uri = $file->getFileUri();
              $value = [
                '_default' => \Drupal::service('file_url_generator')->generateAbsoluteString($uri),
                'uri' => StreamWrapperManager::getTarget($uri),
                'fid' => $media->id(),
                'file_name' => $media->label(),
              ];
            }
          }
        }

        // Views.
        if ($info['type'] === 'dynamic_views' && !empty($value)) {
          $value = array_merge($value, $info['options']['#default_value']);
          $value['data'] = \Drupal::service('vactory.views.to_api')->normalize($value);
        }

      } elseif (is_array($value)) {
        // Go deeper.
        $this->applyFormatters(array_merge((array)$parent_keys, [$field_key]), $settings, $value);
      }
    }
  }

}
