<?php

namespace Drupal\vactory_notifications\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\vactory_notifications\Entity\NotificationsEntity;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Provide delete all notification confirmation form.
 *
 * @package Drupal\vactory_notifications\Form
 */
class NotificationsDeleteAll extends FormBase {

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
    return 'notifications_delete_all_form';
  }

  /**
   * Form constructor.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The form structure.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $message = t('This action cannot be undone.');
    $form['message'] = [
      '#type' => 'markup',
      '#markup' => '<p>' . $message . '</p>',
    ];
    $form['confirmed'] = [
      '#type' => 'submit',
      '#button_type' => 'primary',
      '#value' => t('Delete all existing notifications'),
      '#submit' => [[$this, 'deleteAllNotifications']],
    ];
    $form['cancel'] = [
      '#type' => 'submit',
      '#value' => t('Cancel'),
      '#submit' => [[$this, 'cancelDeleteForm']],
    ];
    return $form;
  }

  /**
   * Delete all notifications submit function.
   *
   * @param array $form
   *   Form object.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state object.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function deleteAllNotifications(array &$form, FormStateInterface $form_state) {
    $notifications = NotificationsEntity::loadMultiple();
    $languages = \Drupal::languageManager()->getLanguages();
    foreach ($notifications as $notification) {
      // Delete notification translations if exist.
      foreach ($languages as $language) {
        try {
          $notification->removeTranslation($language->getId());
        }
        catch (\InvalidArgumentException $e) {
        }
      }
      // Delete the notification entity.
      $notification->delete();
    }
    $url = Url::fromRoute('entity.notifications_entity.collection');
    $form_state->setRedirectUrl($url);
    \Drupal::service('messenger')->addMessage(t('All notifications have been successfully deleted.'));
  }

  /**
   * Cancel deleting all notification action.
   *
   * @param array $form
   *   Form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   */
  public function cancelDeleteForm(array &$form, FormStateInterface $form_state) {
    $url = Url::fromRoute('entity.notifications_entity.collection');
    $redirect = new RedirectResponse($url->toString());
    $redirect->send();
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

}
