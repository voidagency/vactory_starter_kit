<?php

namespace Drupal\vactory_core\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class OpenGraphMetaDefaultImage.
 *
 * @package Drupal\vactory_core\Form
 */
class OpenGraphMetaDefaultImage extends ConfigFormBase {

  /**
   * Gets the configuration names that will be editable.
   *
   * @return array
   *   An array of configuration object names that are editable if called in
   *   conjunction with the trait's config() method.
   */
  protected function getEditableConfigNames() {
    return ['vactory_core.opengraph_default_image.settings'];
  }

  /**
   * Returns a unique string identifying the form.
   *
   * The returned ID should be a unique string that can be a valid PHP function
   * name, since it's used in hook implementation names such as
   * hook_form_FORM_ID_alter().
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'vactory_core_content_opengraph_images_form';
  }

  /**
   * {@inheritDoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $node_types = \Drupal::service('entity_type.manager')->getStorage('node_type')
      ->loadMultiple();
    // Add the token browser at the top.
    $form += \Drupal::service('metatag.token')->tokenBrowser(['node']);
    $form['#tree'] = TRUE;
    $form['content_types'] = [
      '#type' => 'fieldset',
      '#title' => t('Contenu'),
    ];
    $config = $this->config('vactory_core.opengraph_default_image.settings');
    $default_content_opengraph_images = $config->get('default_content_opengraph_images') ? $config->get('default_content_opengraph_images') : '';
    foreach ($node_types as $id => $data) {
      $content_type_fields = \Drupal::service('entity_field.manager')->getFieldDefinitions('node', $id);
      if (isset($content_type_fields['field_vactory_meta_tags'])) {
        $form['content_types'][$id] = [
          '#type' => 'details',
          '#title' => $data->get('name'),
          '#collapsed' => TRUE,
        ];
        $form['content_types'][$id]['default_opengraph_image'] = [
          '#type' => 'textfield',
          '#title' => t('Default OpenGraph image URL'),
          '#description' => t("The URL of an image which should represent the content. Note: les Jetons qui retournent plusieurs valeurs seront gérés automatiquement. Cela permettra d'extraire l'URL depuis un champ image."),
          '#default_value' => !empty($default_content_opengraph_images) ? $default_content_opengraph_images[$id]['default_opengraph_image'] : '',
        ];
      }
    }
    return $form;
  }

  /**
   * {@inheritDoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $content_types = $form_state->getValue('content_types');
    $this->config('vactory_core.opengraph_default_image.settings')
      ->set('default_content_opengraph_images', $content_types)
      ->save();
    parent::submitForm($form, $form_state);
  }

}
