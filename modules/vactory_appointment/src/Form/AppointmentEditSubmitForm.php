<?php

namespace Drupal\vactory_appointment\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Provide form to edit/delete an appointment using phone number.
 */
class AppointmentEditSubmitForm extends FormBase {

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
    return 'vactory_appointment_submission_edit';
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
    $config = \Drupal::config('vactory_appointment.settings');
    if (!$config->get('user_can_edit_appointment')) {
      // If edit appointment is disabled from module settings then
      // redirect to 404 page.
      redirect_to_notfound();
    }
    $form['title'] = [
      '#type' => 'markup',
      '#markup' => '<h4 class="form-title mb-4 text-uppercase">' . $this->t('Renseignez votre numéro de téléphone pour modifier votre rendez-vous') . '</h4>',
    ];
    $form['form_wrapper_opner'] = [
      '#type' => 'markup',
      '#markup' => '<div class="user-info-wrapper change-rdv-form shadow"><div class="user-informations bg-white border border-primary rounded h-100">',
    ];
    $form['phone'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Phone'),
      '#required' => TRUE,
      '#attributes' => [
        'class' => [
          'prefix-icon-mobile',
        ],
        'placeholder' => $this->t('Mobile'),
        'autocomplete' => 'off',
      ],
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Valider'),
      '#attributes' => [
        'class' => [
          'ml-auto',
          'suffix-icon-chevrons-right',
        ],
      ],
      '#prefix' => '<div class="submit-wrapper d-flex">',
      '#suffix' => '</div>',
    ];
    $form['form_wrapper_closer'] = [
      '#type' => 'markup',
      '#markup' => '</div></div>',
    ];
    return $form;
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
    $phone = $form_state->getValue('phone');
    $phone = encrypt($phone);
    $url = Url::fromRoute('view.appointments_edit.my_appointments_listing', ['phone' => $phone]);
    $reponse = new RedirectResponse($url->toString());
    $reponse->send();
  }

}
