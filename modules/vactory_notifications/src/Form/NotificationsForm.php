<?php

namespace Drupal\vactory_notifications\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class NotificationsForm
 *
 * @package Drupal\vactory_notifications\Form
 */
class NotificationsForm extends ContentEntityForm {

  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $form['notification_message']['token_tree'] = [
      '#theme' => 'token_tree_link',
      '#token_types' => array(),
      '#show_restricted' => TRUE,
      '#weight' => 90,
    ];

    return $form;
  }

  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;
    $status = parent::save($form, $form_state);
    switch ($status) {
      case SAVED_NEW:
        \Drupal::messenger()->addMessage($this->t('Created the %label Notification Entity.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        \Drupal::messenger()->addMessage($this->t('Saved the %label Notifiction Entity.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.notifications_entity.collection');
  }

}
