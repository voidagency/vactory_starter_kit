<?php

namespace Drupal\vactory_dynamic_field\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\NestedArray;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Url;

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
   * Apply formatters such as processed_text, image & links.
   */
  private function applyFormatters($parent_keys, $settings, &$component) {
    foreach ($component as $field_key => &$value) {
      $info = NestedArray::getValue($settings, array_merge((array) $parent_keys, [$field_key]));

      if ($info && isset($info['type'])) {
        // Manage external/internal links.
        if ($info['type'] === 'url_extended') {
          if (!empty($value['url']) && !UrlHelper::isExternal($value['url'])) {
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

    // Get vactory_provider_manager to get the module name provide the plugin.
    /* @var \Drupal\vactory_dynamic_field\WidgetsManager $platformProvider */
    $platformProvider = \Drupal::service('vactory_dynamic_field.vactory_provider_manager');

    foreach ($items as $delta => $item) {
      $widget_id = $item->widget_id;
      $widget_data = json_decode($item->widget_data, TRUE);
      list($platform, $template_id) = explode(':', $widget_id);
      $settings = $platformProvider->loadSettings($widget_id);
      $widgets_path = $platformProvider->getWidgetsPath($widget_id);

      // Current template to find.
      $content['template'] = (int) $template_id;
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

      $elements[$delta] = [
        '#theme'        => 'vactory_dynamic_main',
        '#entity_delta' => $delta,
        '#item'         => $item,
        '#content'      => $content,
        '#platform'     => $platform,
        '#widgets_path' => $widgets_path,
        "#cache"        => [
          "max-age" => 0,
        ],
      ];
    }

    return $elements;
  }

}
