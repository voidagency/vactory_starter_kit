<?php

namespace Drupal\vactory_otp\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class TestOtpForm.
 */
class TestOtpForm extends FormBase {

  /**
   * Function Get Form Id.
   */
  public function getFormId() {
    return 'vactory_otp_test_form';
  }

  /**
   * Function build Form.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = [];

    $form['email'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Adresse mail'),
    ];

    $form['phone'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Numéro de téléphone'),
      '#description' => $this->t('Le Numéro de téléphone doit être saisi au format international'),
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Envoyer les OTPs'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * Function Submittion Form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $otpService = \Drupal::service('vactory_otp.send_otp');

    if ($form_state->getValue('email')) {
      $mail_otp = $otpService->sendOtpByMail('Test OTP', $form_state->getValue('email'));
      if ($mail_otp) {
        \Drupal::messenger()->addMessage($this->t("OTP envoyé à l'adresse @adresse : @otp", [
          '@adresse' => $form_state->getValue('email'),
          '@otp' => $mail_otp,
        ]));
      }
    }

    if ($form_state->getValue('phone')) {
      $sms_otp = $otpService->sendOtpBySms($form_state->getValue('phone'));
      if ($sms_otp) {
        \Drupal::messenger()->addMessage($this->t("OTP envoyé au numéro @phone : @otp", [
          '@phone' => $form_state->getValue('phone'),
          '@otp' => $sms_otp,
        ]));
      }
    }

    $form_state->setRebuild(FALSE);
  }

}
