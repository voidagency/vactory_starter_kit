<?php

namespace Drupal\vactory_multivers\Form;

use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Multivers Config Form.
 */
class MultiversConfig extends ConfigFormBase {

  const EXCLUDED_CONTENT_TYPES = [
    'vactory_page',
    'vactory_multivers',
  ];

  const MULTIVERS_FIELD_NAME = 'field_multivers';

  /**
   * Function get Editable Config Names.
   */
  protected function getEditableConfigNames() {
    return [
      'vactory_multivers.settings',
    ];
  }

  /**
   * Function Get Form Id.
   */
  public function getFormId() {
    return 'vactory_multivers_settings';
  }

  /**
   * Function build Form.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $config = $this->config('vactory_multivers.settings');
    $node_types = \Drupal::entityTypeManager()
      ->getStorage('node_type')
      ->loadMultiple();

    $node_types = array_filter($node_types, function ($key) {
      return !in_array($key, self::EXCLUDED_CONTENT_TYPES);
    }, ARRAY_FILTER_USE_KEY);

    $node_types = array_map(function ($node_type) {
      return $node_type->label();
    }, $node_types);

    $form['node_types'] = [
      '#type' => 'checkboxes',
      '#options' => $node_types,
      '#default_value' => $config->get('node_types'),
      '#title' => $this->t('Content types'),
    ];

    return $form;
  }

  /**
   * Function Submittion Form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('vactory_multivers.settings');
    $node_types = $form_state->getValue('node_types');
    $config->set('node_types', $node_types);
    $config->save();

    foreach ($node_types as $node_type_key => $node_type) {
      $field = FieldConfig::loadByName('node', $node_type_key, self::MULTIVERS_FIELD_NAME);
      if ($node_type !== 0 && empty($field)) {
        $field_storage = FieldStorageConfig::loadByName('node', self::MULTIVERS_FIELD_NAME);
        if (empty($field_storage)) {
          $field_storage = FieldStorageConfig::create([
            'field_name' => self::MULTIVERS_FIELD_NAME,
            'entity_type' => 'node',
            'type' => 'entity_reference',
            'cardinality' => 1,
            'settings' => [
              'target_type' => 'taxonomy_term',
            ],
          ]);
          $field_storage->save();
        }
        $field = FieldConfig::create([
          'field_storage' => $field_storage,
          'bundle' => $node_type,
          'label' => t('Multivers'),
          'translatable' => FALSE,
          'settings' => [
            'handler' => 'default:vactory_term',
            'handler_settings' => [
              'target_bundles' => [
                'multivers' => 'multivers',
              ],
            ],
          ],
        ]);
        $field->save();

        $entity_form_display = \Drupal::entityTypeManager()
          ->getStorage('entity_form_display')
          ->load('node.' . $node_type . '.default');
        $entity_form_display->setComponent(self::MULTIVERS_FIELD_NAME, [
          'type' => 'options_select',
          'region' => 'content',
        ])->save();

      }
      if ($node_type === 0 && $field instanceof FieldConfig) {

        try {
          $field->delete();
        }
        catch (EntityStorageException $e) {
        }
      }
    }

    parent::submitForm($form, $form_state);
  }

}
