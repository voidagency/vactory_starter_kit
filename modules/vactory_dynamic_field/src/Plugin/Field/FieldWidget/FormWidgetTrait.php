<?php

namespace Drupal\vactory_dynamic_field\Plugin\Field\FieldWidget;

use Drupal\Component\Render\MarkupInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Language\LanguageInterface;
use Drupal\file\Entity\File;
use Drupal\node\Entity\Node;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\media\Entity\Media;

/**
 * Provides common functionality for testing stubbing.
 */
trait FormWidgetTrait {

  /**
   * Returns a Form API default array options.
   *
   * This will convert human-readable type to Form API type.
   * For example, it may convert text to textfield, image to managed_file and
   * so on.
   *
   * @param string $type
   *   Used to determine the type of form element.
   * @param array $options
   *   Specific form element options.
   *
   * @return array
   *   Modified form element options.
   */
  protected function getFormElementDefaults($type = '', array $options = []) {
    $default_options = [];

    if ($type === 'text') {
      $default_options = [
        '#type' => 'textfield',
      ];
    }

    if (in_array($type, ['image', 'file'])) {
      $default_options = [
        '#type' => 'managed_file',
      ];

      $element_info = \Drupal::service('element_info')->getInfo('managed_file');
      $default_options['#process'] = $element_info['#process'];
      $default_options['#process'][] = [get_class($this), 'processFile'];
    }

    if ($type === 'image') {
      $default_options =
        [
          '#upload_location'   => 'public://widgets/images',
          '#upload_validators' => [
            'file_validate_extensions' => ['jpg gif png jpeg svg'],
          ],
        ] + $default_options;
    }

    if ($type === 'file') {
      $default_options =
        [
          '#upload_location'   => 'public://widgets/files',
          '#upload_validators' => [
            'file_validate_extensions' => ['pdf doc docx txt'],
          ],
        ] + $default_options;
    }

    return array_merge($default_options, $options);
  }

  /**
   * Returns a Form API array that defines each of the form elements.
   *
   * @param string $type
   *   Used to determine the type of form element.
   * @param \Drupal\Component\Render\MarkupInterface $label
   *   A text to display as label.
   * @param string|array $default_value
   *   The value of the form element that will be displayed or selected
   *   initially if the form has not been submitted yet.
   * @param array $options
   *   Specific form element options.
   * @param array $form
   *   Form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   *
   * @return array
   *   The Form API renderable array.
   */
  // phpcs:disable
  protected function getFormElement($type, MarkupInterface $label, $default_value, array $options, array $form, FormStateInterface $form_state, $field_name) {
    // phpcs:enable
    $element = [
      '#type'          => $type,
      '#title'         => $label,
      '#default_value' => $default_value,
    ];

    // Text format default value.
    if ($type === 'text_format') {
      $default_value_string = is_string($default_value) ? $default_value : '';
      $element['#default_value'] = isset($default_value['value']) ? $default_value['value'] : $default_value_string;
    }

    // Entity autocomplete default value.
    if ($type === 'entity_autocomplete') {
      $default_value = !empty($default_value) ? Node::load($default_value) : NULL;
      $element['#default_value'] = $default_value;
    }

    $element_defaults = $this->getFormElementDefaults($type, $options);

    if ($type == 'image') {
      $image_default_value = [];
      if (!empty($default_value)) {
        if (!is_array($default_value)) {
          $image_default_value[] = $default_value;
        }
        else {
          $key = array_keys($default_value)[0];
          if (isset($default_value[$key]['selection'])) {
            foreach ($default_value[$key]['selection'] as $media) {
              $image_default_value[] = $media['target_id'];
            }
          }
        }
      }

      return $this->getImageFieldForm($field_name, [
        'label'         => $label,
        'default_value' => $image_default_value,
        'required'      => FALSE,
        'cardinality'   => 1,
      ], $form, $form_state);
    }

    return $element_defaults + $element;
  }

  /**
   * Form API callback: Processes file field elements.
   *
   * Adds the submit callback to each file field so it can be saved permanently.
   *
   * This method on is assigned as a #process
   * callback in getFormElementDefaults() method.
   */
  public static function processFile($element, FormStateInterface $form_state, $form) {
    $element['upload_button']['#submit'][] = [get_called_class(), 'submit'];
    if (isset($element['upload_button']['#ajax']['progress']['type'])) {
      $element['upload_button']['#ajax']['progress']['type'] = 'fullscreen';
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function submit($form, FormStateInterface $form_state) {
    $parents = $form_state->getTriggeringElement()['#array_parents'];
    $element = NestedArray::getValue($form, array_slice($parents, 0, -1));
    $fids = array_keys($element['#files']);

    // Permanently save uploaded files.
    foreach ($fids as $fid) {
      $file = File::load($fid);
      if (!empty($file)) {
        $file->setPermanent();
        $file->save();
      }
    }
  }

  /**
   * Get image field form.
   *
   * @param string $field_name
   *   Field name.
   * @param array $configuration
   *   Configuration.
   * @param array $form
   *   Form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   *
   * @return array
   *   Media field.
   */
  // phpcs:disable
  public function getImageFieldForm($field_name, array $configuration = [], array $form, FormStateInterface $form_state) {
    // phpcs:enable
    return $this->getMediaFieldForm('image', $field_name, $configuration, $form, $form_state);
  }

  /**
   * Get file field form.
   *
   * @param string $field_name
   *   Field name.
   * @param array $configuration
   *   Configuration.
   * @param array $form
   *   Form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   *
   * @return array
   *   Media field.
   */
  // phpcs:disable
  public function getFileFieldForm($field_name, array $configuration = [], array $form, FormStateInterface $form_state) {
    // phpcs:enable
    return $this->getMediaFieldForm('file', $field_name, $configuration, $form, $form_state);
  }

  /**
   * Get audio field form.
   *
   * @param string $field_name
   *   Field name.
   * @param array $configuration
   *   Configuration.
   * @param array $form
   *   Form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   *
   * @return array
   *   Media field.
   */
  // phpcs:disable
  public function getAudioFieldForm($field_name, array $configuration = [], array $form, FormStateInterface $form_state) {
    // phpcs:enable
    return $this->getMediaFieldForm('audio', $field_name, $configuration, $form, $form_state);
  }

  /**
   * Get video field form.
   *
   * @param string $field_name
   *   Field name.
   * @param array $configuration
   *   Configuration.
   * @param array $form
   *   Form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   *
   * @return array
   *   Media field.
   */
  // phpcs:disable
  public function getVideoFieldForm($field_name, array $configuration = [], array $form, FormStateInterface $form_state) {
    // phpcs:enable
    return $this->getMediaFieldForm('video', $field_name, $configuration, $form, $form_state);
  }

  /**
   * Get remote video field form.
   *
   * @param string $field_name
   *   Field name.
   * @param array $configuration
   *   Configuration.
   * @param array $form
   *   Form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   *
   * @return array
   *   Media field.
   */
  // phpcs:disable
  public function getRemoteVideoFieldForm($field_name, array $configuration = [], array $form, FormStateInterface $form_state) {
    // phpcs:enable
    return $this->getMediaFieldForm('remote_video', $field_name, $configuration, $form, $form_state);
  }

  /**
   * Get media field widget.
   *
   * @param string $media_type
   *   Media type.
   * @param string $field_name
   *   Field name.
   * @param array $configuration
   *   Configuration.
   * @param array $form
   *   Form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   *
   * @return array
   *   Media file field form.
   *
   * @throws \Exception
   */
  // phpcs:disable
  protected function getMediaFieldForm($media_type = 'image', $field_name, array $configuration = [], array $form, FormStateInterface $form_state) {
    // phpcs:enable
    $field_type = 'entity_reference';

    $configuration = array_merge(
      [
        'label'         => 'Image',
        'default_value' => [],
        'required'      => FALSE,
        'cardinality'   => 1,
      ],
      $configuration
    );

    try {
      $field_storage = FieldStorageConfig::create([
        'field_name'  => $field_name,
        'entity_type' => 'vactory_dynamic_field',
        'type'        => $field_type,
        'cardinality' => $configuration['cardinality'],
        'settings'    => ['target_type' => 'media'],
      ]);
      $field_storage->custom_storage = TRUE;
      $field_storage->save();
    }
    catch (EntityStorageException $e) {
    }

    // Update settings.
    $field_storage = FieldStorageConfig::loadByName('vactory_dynamic_field', $field_name);
    $field_storage->setCardinality($configuration['cardinality']);
    $field_storage->save();

    try {
      $field = FieldConfig::create([
        'field_storage' => $field_storage,
        'bundle'        => 'vactory_dynamic_field',
        'label'         => $configuration['label'],
        'required'      => $configuration['required'],
      ]);
      $field->save();
    }
    catch (EntityStorageException $e) {
    }

    // Update settings.
    $field = FieldConfig::loadByName('vactory_dynamic_field', 'vactory_dynamic_field', $field_name);
    $field->setLabel($configuration['label']);
    $field->setRequired($configuration['required']);
    $field->setSetting('handler_settings', ['target_bundles' => [$media_type => $media_type]]);
    $field->save();

    // Form display.
    $form_display = EntityFormDisplay::create([
      'targetEntityType' => 'vactory_dynamic_field',
      'bundle'           => 'vactory_dynamic_field',
      'mode'             => 'default',
    ]);

    // Add field to component.
    $form_display->setComponent($field_name, [
      'type' => 'media_library_widget',
    ]);

    /* @var \Drupal\media_library\Plugin\Field\FieldWidget\MediaLibraryWidget $widget */
    $widget = $form_display->getRenderer($field_name);

    /* @var \Drupal\vactory_dynamic_field\Entity\VactoryDynamicField $entity */
    $entity = \Drupal::service('entity_type.manager')
      ->getStorage('vactory_dynamic_field')
      ->create([
        'type'     => 'vactory_dynamic_field',
        'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
      ]);

    /* @var \Drupal\Core\Field\EntityReferenceFieldItemList $items */
    $items = $entity->get($field_name);

    // Default value.
    if (!empty($configuration['default_value'])) {
      $items->setValue($configuration['default_value']);
    }

    // Widget form.
    $form = $widget->form($items, $form, $form_state);

    return $form;
  }

  /**
   * Create a file entity from given dummy file path and return its id.
   *
   * @param string $platform_directory
   *   Path to the current widget folder.
   * @param string $template_name
   *   Template name.
   * @param string $fileName
   *   Dummy file name.
   * @param string $fieldType
   *   Media field type.
   *
   * @return string
   *   File id.
   */
  protected function getFileId($platform_directory, $template_name, $fileName, $fieldType) {
    $filePath = $platform_directory . '/' . $template_name . '/' . $fileName;
    // If the file doesn't exist, return an empty array.
    if (!file_exists($filePath)) {
      return "";
    }
    $fileName = $template_name . '__placeholder__vdf__' . $fileName;

    // Check if there is already a media with same name.
    // To prevent duplication.
    $mediaFile = \Drupal::entityQuery('media')
      ->condition('name', $fileName)
      ->execute();

    if (!empty($mediaFile)) {
      $id = $mediaFile[array_keys($mediaFile)[0]];
      return $id;
    }

    $fileStream = fopen($filePath, 'r');

    if (!file_exists("public://vdf_placeholder/")) {
      mkdir("public://vdf_placeholder/", 0770, TRUE);
    }

    // Create the file.
    $file = File::create([
      'uid'      => \Drupal::currentUser()->id(),
      'filename' => $fileName,
      'uri'      => 'public://vdf_placeholder/' . $fileName,
      'status'   => 1,
    ]);

    // Put contents in the file.
    file_put_contents($file->getFileUri(), $fileStream);
    $file->setPermanent();
    $file->save();
    fclose($fileStream);

    // Create media.
    $fieldMachineName = 'field_media_' . $fieldType;
    $media_image = Media::create([
      'bundle'          => $fieldType,
      'name'            => $fileName,
      $fieldMachineName => [
        'target_id' => $file->id(),
      ],
    ]);
    $media_image->save();

    return $media_image->id();
  }

}
