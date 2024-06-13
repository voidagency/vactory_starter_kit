<?php

namespace Drupal\vactory_content_package\Form;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Form\FormStateInterface;
use Drupal\vactory_dynamic_field\Form\ModalForm;

/**
 * Configure Vactory content package settings for this site.
 */
class DynamicFieldJsonGenerator extends ModalForm {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'vactory_content_package_df_json_generator';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $widget_id = \Drupal::request()->query->get('widget_id');
    if (empty($widget_id)) {
      return [
        '#markup' => '<p>No template has been selected (empty widget_id)!</p>',
      ];
    }
    $values = [
      'widget_id' => $widget_id,
      'widget_data' => [],
    ];
    $widget_data = [];
    $settings = $this->widgetsManager->loadSettings($widget_id);
    $is_multiple = $settings['multiple'] ?? FALSE;
    if (isset($settings['extra_fields'])) {
      foreach ($settings['extra_fields'] as $field_name => $field) {
        $widget_data['extra_field'][$field_name] = $this->getFieldData($field);
      }
    }
    if (isset($settings['fields'])) {
      $n = $is_multiple ? 2 : 1;
      for ($i = 0; $i < $n; $i++) {
        $data = [];
        foreach ($settings['fields'] as $field_name => $field) {
          $data[$field_name] = $this->getFieldData($field);
        }
        $widget_data[$i] = $data;
      }
    }
    $values['widget_data'] = $widget_data;

    $form = [
      '#markup' => '<div><a data-clipboard-action="copy" data-clipboard-target="#json-display" class="copy-json-to-clipboard button">Copy</a><br><pre id="json-display"></pre></div>',
    ];
    $form['#attached']['drupalSettings']['vactory_content_package']['template_json'] = Json::encode($values);
    $form['#attached']['library'][] = 'vactory_content_package/scripts';
    return $form;
  }

  /**
   * Get given field data.
   */
  public function getFieldData($field) {
    $data = [];
    if (isset($field['g_title'])) {
      foreach ($field as $child_name => $child_field) {
        if ($child_name === 'g_title') {
          continue;
        }
        $data[$child_name] = $this->getFieldData($child_field);
      }
    }
    else {
      $data = $this->getDynamicFieldJsonFormatByType($field['type'], $field);
    }
    return $data;
  }

  /**
   * Get dummy data for given field.
   */
  public function getDynamicFieldJsonFormatByType($type, $field = NULL) {
    switch ($type) {
      case 'text_format':
        return [
          'value' => $this->getDummyData('textarea'),
        ];

      case 'url_extended':
        return [
          'title' => $this->getDummyData('text'),
          'url' => $this->getDummyData('url'),
          'attributes' => [
            'label' => '',
            'class' => '',
            'id' => 'link-' . uniqid(),
            'target' => '_self',
            'rel' => '',
          ],
        ];

      case 'hidden':
        $data = '';
        if ($field) {
          $data = $field['options']['#value'] ?? $data;
        }
        return $data;

      case 'entity_autocomplete':
        $data = '';
        if ($field) {
          $target_type = $field['options']['#target_type'] ?? NULL;
          if ($target_type && !empty($target_type)) {
            $data = !in_array($target_type, ['node', 'taxonomy_term']) ? 'targeted_entity_id_here' : $data;
            $data = $target_type === 'node' ? 'NODE_ID_HERE (not nid!)' : $data;
            $data = $target_type === 'taxonomy_term' ? 'TERM_ID_HERE (not tid!)' : $data;
          }
        }
        return $data;

      case 'radios':
        $data = '';
        if ($field) {
          $field_options = $field['options']['#options'] ?? NULL;
          if ($field_options && !empty($field_options)) {
            $options_keys = array_keys($field_options);
            $data = reset($options_keys);
          }
        }
        return $data;

      case 'select':
        $data = '';
        if ($field) {
          $field_options = $field['options']['#options'] ?? NULL;
          if ($field_options && !empty($field_options)) {
            if (is_array(reset($field_options))) {
              $new_options = [];
              foreach ($field_options as $option) {
                $new_options = [...$new_options, ...$option];
              }
              $field_options = $new_options;
            }
            $options_keys = array_keys($field_options);
            $data = reset($options_keys);
          }
        }
        return $data;

      case 'webform_decoupled':
        $data = [
          'id' => '',
          'style' => '',
          'buttons' => '',
        ];
        if ($field) {
          $field_value = $field['options']['#default_value'] ?? NULL;
          if ($field_value) {
            $data['id'] = $field_value['webform_id'] ?? '';
            $data['style'] = $field_value['style'] ?? '';
            $data['buttons'] = $field_value['buttons'] ?? '';
          }
        }
        return $data;

      case 'json_api_collection':
        $data = [];
        if ($field) {
          $field_value = $field['options']['#default_value'] ?? NULL;
          if ($field_value) {
            $data['resource'] = $field_value['resource'] ?? '';
            $data['id'] = $field_value['id'] ?? '';
            $data['filters'] = $field_value['filters'] ?? [];
            $data['vocabularies'] = $field_value['vocabularies'] ?? [];
            $data['entity_queue'] = $field_value['entity_queue'] ?? '';
            $data['entity_queue_field_id'] = $field_value['entity_queue_field_id'] ?? '';
          }
        }
        return $data;

      case 'node_queue':
        $data = [];
        if ($field) {
          $field_value = $field['options']['#default_value'] ?? NULL;
          if ($field_value) {
            $data['resource'] = $field_value['resource'] ?? '';
            $data['id'] = $field_value['id'] ?? '';
            $data['filters'] = $field_value['filters'] ?? [];
            $data['vocabularies'] = $field_value['vocabularies'] ?? [];
            $data['entity_queue'] = $field_value['entity_queue'] ?? '';
            $data['entity_queue_field_id'] = $field_value['entity_queue_field_id'] ?? '';
            $data['nodes'] = ['node_id_1', 'node_id_2'];
          }
        }
        return $data;

      default:
        return $this->getDummyData($type);

    }
  }

  /**
   * Get dummy data.
   */
  public function getDummyData($type) {
    switch ($type) {
      case in_array($type, ['text', 'textfield']):
        return 'Duis aute irure dolor';

      case 'checkbox':
        return (boolean) (rand(1, 9) % 2) === 0;

      case 'number':
        return rand(1, 200);

      case 'date':
        return "19/08/2024";

      case 'url':
        return 'https://www.example.com';

      case 'image':
        return 'https://placehold.co/500x300';

      case 'file':
        return 'https://www.w3.org/WAI/ER/tests/xhtml/testfiles/resources/pdf/dummy.pdf';

      case 'remote_video':
        return 'https://www.youtube.com/watch?v=9xwazD5SyVg';

      case 'textarea':
        return 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur.';

      default:
        return '';

    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

}
