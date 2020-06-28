<?php

namespace Drupal\vactory_locator\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class LocatorEntityTypeForm.
 */
class LocatorEntityTypeForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $locator_entity_type = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $locator_entity_type->label(),
      '#description' => $this->t("Label for the Locator Entity type."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $locator_entity_type->id(),
      '#machine_name' => [
        'exists' => '\Drupal\vactory_locator\Entity\LocatorEntityType::load',
      ],
      '#disabled' => !$locator_entity_type->isNew(),
    ];

    /* You will need additional form elements for your custom properties. */

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $locator_entity_type = $this->entity;
    $status = $locator_entity_type->save();

    switch ($status) {
      case SAVED_NEW:
        \Drupal::messenger()->addMessage($this->t('Created the %label Locator Entity type.', [
          '%label' => $locator_entity_type->label(),
        ]));
        break;

      default:
        \Drupal::messenger()->addMessage($this->t('Saved the %label Locator Entity type.', [
          '%label' => $locator_entity_type->label(),
        ]));
    }
    $form_state->setRedirectUrl($locator_entity_type->toUrl('collection'));
  }

}
