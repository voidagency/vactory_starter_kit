<?php

namespace Drupal\vactory_dynamic_field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;
use Drupal\vactory_dynamic_field\Plugin\Field\FieldWidget\FormWidgetTrait;

/**
 * Auto populate manager.
 */
class AutoPopulateManager {

  use FormWidgetTrait;

  /**
   * Get dummy content checkbox element.
   */
  public function getDummyContentCheckbox($name, $element_label, $modal_form_object) {
    $result = $this->isPendingContent($name, $modal_form_object->widget, $modal_form_object->context);
    $dummy_checkbox = [
      '#type' => 'checkbox',
      '#title' => "🕒 Le contenu <strong>{$element_label}</strong> est en attente...",
      '#ajax' => [
        'callback' => [$modal_form_object, 'updateFormCallback'],
        'event'    => 'change',
        'wrapper'  => ModalEnum::FORM_WIDGET_AJAX_WRAPPER,
      ],
      '#default_value' => $result ? $result->pending : 0,
    ];
    return $dummy_checkbox;
  }

  /**
   * Get given element dummy content.
   */
  public function getDummyData($element, $field_name, $form, FormStateInterface $form_state) {
    switch ($element['#type']) {
      case 'textarea':
        return <<<TEXT
          Lorem ipsum dolor sit amet, consectetur adipiscing elit. Integer dictum, nisl quis condimentum bibendum, nisi dui viverra orci, at ornare ligula ante at elit. 
          Cras sed vehicula est. Aliquam eleifend tellus a dolor pharetra ornare. Aenean vitae venenatis lacus. Vivamus sapien turpis, blandit sit amet neque eu, accumsan vestibulum lorem. Sed id lorem eget dui tempus porttitor. Fusce efficitur leo a risus elementum, non condimentum massa tempor.
        TEXT;

      case 'textfield':
        return 'Vivamus posuere erat sit amet';

      case 'url_extended':
        $element['title']['#value'] = 'Praesent nisl eros';
        $element['url']['#value'] = '#';
        $element['attributes']['id']['#value'] = 'link-' . uniqid();
        $element['attributes']['target']['#value'] = '_self';
        return $element;

      case 'text_format':
        return <<<Text
        
          <strong>Donec pretium</strong> eros vel diam tristique egestas. Duis dictum id mi in congue. Vivamus eu semper dolor. Quisque blandit condimentum lectus, eget imperdiet diam sagittis nec. Class aptent taciti sociosqu ad litora torquent per conubia nostra, per inceptos himenaeos. Mauris congue elit eget tellus congue, ac dictum nisl commodo. Ut condimentum commodo purus a tempus. Nam dictum pharetra est, in efficitur ante tempor sit amet. Sed rutrum arcu metus, at gravida risus lobortis a. Proin tincidunt magna eget velit posuere, sit amet commodo odio egestas. Praesent eu arcu lacus. Praesent feugiat venenatis risus vel aliquet. In tempor, erat eget fermentum sagittis, libero nisi interdum nisi, quis fringilla diam urna vitae libero. Duis ultricies tellus erat, quis tempor ex volutpat eu.
          
          Aliquam erat volutpat. Vivamus gravida viverra risus non sodales. Nam rhoncus cursus erat non vestibulum. Nunc sed sapien ullamcorper, tincidunt odio a, elementum nunc. Sed in pharetra metus, in faucibus tortor. Pellentesque eros ipsum, aliquet sit amet odio a, tristique malesuada tortor. Quisque id tempor nibh.
        Text;

      case $element['#type'] === 'container' && isset($element['widget']['#target_bundles']['image']):
        $num = random_int(1, 3);
        $file_path = \Drupal::service('extension.list.module')->getPath('vactory_dynamic_field') . "/images/dummyimage{$num}.jpeg";
        // Create a file entity.
        $fid = $this->createFile($file_path);
        $media_id = $this->createMedia('image', 'field_media_image', $fid);
        $el = $this->getImageFieldForm($field_name, [
          'label' => $field_name,
          'default_value' => [$media_id],
          'required' => FALSE,
          /* 'required' => $element_defaults['#required'] ?? FALSE, */
          'cardinality' => 1,
        ], $form, $form_state);
        unset($el['widget']['selection'][0]['remove_button']);
        return $el;

      case $element['#type'] === 'container' && isset($element['widget']['#target_bundles']['file']):
        $file_path = \Drupal::service('extension.list.module')->getPath('vactory_dynamic_field') . "/files/dummyfile.pdf";
        // Create a file entity.
        $fid = $this->createFile($file_path);
        $media_id = $this->createMedia('file', 'field_media_file', $fid);
        $el = $this->getFileFieldForm($field_name, [
          'label' => $field_name,
          'default_value' => [$media_id],
          'required' => FALSE,
          /* 'required' => $element_defaults['#required'] ?? FALSE, */
          'cardinality' => 1,
        ], $form, $form_state);
        /* unset($el['widget']['selection'][0]['remove_button']); */
        return $el;

      default:
        return '';
    }
  }

  /**
   * Create file entity.
   */
  protected function createFile($file_path) {
    $file = File::create([
      'uri' => $file_path,
    ]);
    // Save the file entity.
    $file->save();
    // Optionally, set additional properties for the file entity.
    $file->setPermanent();
    // Save the file entity again to update any additional properties.
    $file->save();
    return $file->id();
  }

  /**
   * Create media entity.
   */
  protected function createMedia($bundle, $media_field_name, $fid = NULL) {
    $media_values = [
      'bundle' => $bundle,
      'uid' => \Drupal::currentUser()->id(),
      $media_field_name => [
        'target_id' => $fid,
      ],
    ];
    $media = Media::create($media_values);
    $media->save();
    return $media->id();
  }

  /**
   * Clear given element content.
   */
  public function clearDummyData($element, $field_name, $form, FormStateInterface $form_state) {
    switch ($element['#type']) {
      /*case 'url_extended':
      $element['title']['#value'] = '';
      $element['url']['#value'] = '';
      $element['attributes']['id']['#value'] = 'link-' . uniqid();
      $element['attributes']['target']['#value'] = '_self';
      return $element;*/
      case $element['#type'] === 'container' && isset($element['widget']['#target_bundles']['image']):
        $el = $this->getImageFieldForm($field_name, [
          'label' => 'test',
          'default_value' => [],
          'required' => FALSE,
          /* 'required' => $element_defaults['#required'] ?? FALSE, */
          'cardinality' => 1,
        ], $form, $form_state);
        return $el;

      case $element['#type'] === 'container' && isset($element['widget']['#target_bundles']['file']):
        $el = $this->getFileFieldForm($field_name, [
          'label' => 'test',
          'default_value' => [],
          'required' => FALSE,
          /* 'required' => $element_defaults['#required'] ?? FALSE, */
          'cardinality' => 1,
        ], $form, $form_state);
        return $el;

      default:
        return '';
    }
  }

  /**
   * Check if given field type is dummiable.
   */
  public function isFieldTypeDummiable($type, $isPendingContentEnabled = FALSE, $context = []) {
    $allowed_types = [
      'text',
      'textarea',
      'text_format',
      'url_extended',
      'image',
      'file',
      'remote_video',
      'video',
    ];
    return $isPendingContentEnabled && in_array($type, $allowed_types) && !empty($context);
  }

  /**
   * Set field content in pending.
   */
  public function setFieldInPending($field_name, $widget_id, $context, $field_label, $widget_name, $screenshot) {
    $langcode = \Drupal::languageManager()->getCurrentLanguage()->getId();
    $id = $this->getPendingContentId($field_name, $widget_id, $context);
    if (!isset($id)) {
      // New entry.
      \Drupal::database()->insert('content_progress')
        ->fields([
          'entity_id' => $context['entity_id'],
          'entity_type' => $context['entity_type'],
          'paragraph_id' => $context['paragraph_id'],
          'widget_id' => $widget_id,
          'field_name' => $field_name,
          'field_label' => $field_label,
          'widget_name' => $widget_name,
          'langcode' => $langcode,
          'widget_screen' => $screenshot,
          'pending' => 1,
        ])
        ->execute();
    }
    else {
      // Update existing entry.
      \Drupal::database()->update('content_progress')
        ->fields([
          'entity_id' => $context['entity_id'],
          'entity_type' => $context['entity_type'],
          'paragraph_id' => $context['paragraph_id'],
          'widget_id' => $widget_id,
          'field_name' => $field_name,
          'field_label' => $field_label,
          'widget_name' => $widget_name,
          'langcode' => $langcode,
          'widget_screen' => $screenshot,
          'pending' => 1,
        ])
        ->condition('id', $id)
        ->execute();
    }
  }

  /**
   * Get existing pending content ID.
   */
  public function getPendingContentId($field_name, $widget_id, $context) {
    $langcode = \Drupal::languageManager()->getCurrentLanguage()->getId();
    $query_string = "SELECT id FROM content_progress WHERE entity_id=:entity_id AND entity_type=:entity_type AND paragraph_id=:paragraph_id AND widget_id=:widget_id AND field_name=:field_name AND langcode=:langcode";
    $query_params = [
      'entity_id' => $context['entity_id'],
      'entity_type' => $context['entity_type'],
      'paragraph_id' => $context['paragraph_id'],
      'widget_id' => $widget_id,
      'langcode' => $langcode,
      'field_name' => $field_name,
    ];
    $result = \Drupal::database()->query($query_string, $query_params)
      ->fetchAll();
    if (!empty($result)) {
      $result = reset($result);
      return $result->id;
    }
    return NULL;
  }

  /**
   * Check if field content is pending.
   */
  public function isPendingContent($field_name, $widget_id, $context, $forward_to_default_langcode = TRUE) {
    $langcode = \Drupal::languageManager()->getCurrentLanguage()->getId();
    $default_lancode = \Drupal::languageManager()->getDefaultLanguage()->getId();
    $query_string = "SELECT id, pending FROM content_progress WHERE entity_id=:entity_id AND entity_type=:entity_type AND paragraph_id=:paragraph_id AND widget_id=:widget_id AND field_name=:field_name AND langcode=:langcode";
    $query_params = [
      'entity_id' => $context['entity_id'],
      'entity_type' => $context['entity_type'],
      'paragraph_id' => $context['paragraph_id'],
      'widget_id' => $widget_id,
      'langcode' => $langcode,
      'field_name' => $field_name,
    ];
    $result = \Drupal::database()->query($query_string, $query_params)
      ->fetch();
    if (empty($result) && $forward_to_default_langcode) {
      // Try with default langcode.
      $query_params['langcode'] = $default_lancode;
      $result = \Drupal::database()->query($query_string, $query_params)
        ->fetch();
    }
    if (!empty($result)) {
      return $result;
    }
    return NULL;
  }

  /**
   * Unset field in pending.
   */
  public function unsetFieldInPending($field_name, $widget_id, $context, $field_label, $widget_name, $screenshot) {
    $result = $this->isPendingContent($field_name, $widget_id, $context, FALSE);
    if (!empty($result)) {
      \Drupal::database()->update('content_progress')
        ->fields([
          'pending' => 0,
        ])
        ->condition('id', $result->id)
        ->execute();
    }
  }

  /**
   * Find in a nested array parent keys of key that start with given $search.
   */
  public function findParentKeysStartingWith(&$array, $search, $parentKeys = []) {
    $results = [];

    foreach ($array as $key => &$value) {
      // If the key starts with the search term.
      // Then add it to results with its parent keys.
      if (strpos($key, $search) === 0) {
        if ($array[$key] === 1) {
          $field_key = str_replace('dummy_', '', $key);
          $results[] = array_merge($parentKeys, [$field_key]);
        }
        unset($array[$key]);
      }

      // If the current element is an array, recursively search within it.
      if (is_array($value)) {
        $nestedResults = $this->findParentKeysStartingWith($value, $search, array_merge($parentKeys, [$key]));
        $results = array_merge($results, $nestedResults);
      }
    }

    return $results;
  }

}
