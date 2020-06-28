<?php

namespace Drupal\vactory_appointment\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class AppointmentsForm
 *
 * @package Drupal\vactory_appointment\Form
 */
class AppointmentsForm extends ContentEntityForm {

  public function buildForm(array $form, FormStateInterface $form_state) {
    return parent::buildForm($form, $form_state);
  }

  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;
    $status = parent::save($form, $form_state);
    switch ($status) {
      case SAVED_NEW:
        \Drupal::messenger()->addMessage($this->t('Appointment %label has been created successfully.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        \Drupal::messenger()->addMessage($this->t('Appointment %label has been saved successfully.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.vactory_appointment.collection');
  }

}
