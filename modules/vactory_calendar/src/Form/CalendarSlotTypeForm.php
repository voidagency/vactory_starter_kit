<?php

namespace Drupal\vactory_calendar\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class CalendarSlotTypeForm.
 */
class CalendarSlotTypeForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $calendar_slot_type = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $calendar_slot_type->label(),
      '#description' => $this->t("Label for the Calendar slot type."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $calendar_slot_type->id(),
      '#machine_name' => [
        'exists' => '\Drupal\vactory_calendar\Entity\CalendarSlotType::load',
      ],
      '#disabled' => !$calendar_slot_type->isNew(),
    ];

    /* You will need additional form elements for your custom properties. */

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $calendar_slot_type = $this->entity;
    $status = $calendar_slot_type->save();

    switch ($status) {
      case SAVED_NEW:
        $this->messenger()->addMessage($this->t('Created the %label Calendar slot type.', [
          '%label' => $calendar_slot_type->label(),
        ]));
        break;

      default:
        $this->messenger()->addMessage($this->t('Saved the %label Calendar slot type.', [
          '%label' => $calendar_slot_type->label(),
        ]));
    }
    $form_state->setRedirectUrl($calendar_slot_type->toUrl('collection'));
  }

}
