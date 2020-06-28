<?php

namespace Drupal\vactory_dynamic_field\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\TraversableTypedDataInterface;
use Drupal\vactory_dynamic_field\WidgetsManagerInterface;

/**
 * Plugin implementation of the 'field_wysiwyg_dynamic' field type.
 *
 * @FieldType(
 *   id = "field_wysiwyg_dynamic",
 *   label = @Translation("Dynamic Field"),
 *   module = "vactory_dynamic_field",
 *   description = @Translation("Dynamic Field."),
 *   default_widget = "field_wysiwyg_dynamic_widget",
 *   default_formatter = "field_wysiwyg_dynamic_formatter"
 * )
 */
class VactoryDynamic extends FieldItemBase {

  /**
   * The embed provider plugin manager.
   *
   * @var \Drupal\vactory_dynamic_field\WidgetsManagerInterface
   */
  protected $providerManager;

  /**
   * {@inheritdoc}
   */
  public function __construct($definition, $name = NULL, TraversableTypedDataInterface $parent = NULL, WidgetsManagerInterface $provider_manager = NULL) {
    parent::__construct($definition, $name, $parent);
    $this->providerManager = $provider_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance($definition, $name = NULL, TraversableTypedDataInterface $parent = NULL) {
    $provider_manager = \Drupal::service('vactory_dynamic_field.vactory_provider_manager');
    return new static($definition, $name, $parent, $provider_manager);
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'widget_id'   => [
          'type'   => 'varchar',
          'length' => 255,
        ],
        'widget_data' => [
          'description' => 'The object item value.',
          'type'        => 'text',
          'size'        => 'big',
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->get('widget_id')->getValue();
    return $value === NULL || $value === '';
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['widget_id'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Widget ID'))
      ->setRequired(TRUE);

    $properties['widget_data'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Widget Data'))
      ->setRequired(TRUE);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function mainPropertyName() {
    return 'widget_id';
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    $form = [];
    $form['allowed_providers'] = [
      '#title'         => $this->t('Allowed Providers'),
      '#description'   => $this->t('Restrict users from entering information from the following providers. If none are selected any of the above providers can be used.'),
      '#type'          => 'checkboxes',
      '#default_value' => $this->getSetting('allowed_providers'),
      '#options'       => $this->providerManager->getProvidersOptionList(),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    return [
      'allowed_providers' => [],
    ];
  }

}
