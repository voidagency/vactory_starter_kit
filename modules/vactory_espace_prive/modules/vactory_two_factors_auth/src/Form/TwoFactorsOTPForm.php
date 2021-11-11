<?php

namespace Drupal\vactory_two_factors_auth\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\vactory_otp\Services\VactoryOtpService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class TwoFactorsOTPForm.
 *
 * @package Drupal\vactory_two_factors_auth\Form
 */
class TwoFactorsOTPForm extends FormBase {

  /**
   * The entityTypeManager service injection.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The current user object.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $currentUserObject;

  /**
   * The session variables manager.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStoreFactory
   */
  protected $privateTempStoreFactory;

  /**
   * Vactory OTP service injection.
   *
   * @var \Drupal\vactory_otp\Services\VactoryOtpService
   */
  protected $vactoryOTPManager;

  /**
   * Route match service injection.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $currentRouteMatch;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, PrivateTempStoreFactory $privateTempStoreFactory, VactoryOtpService $vactoryOTPManager, CurrentRouteMatch $currentRouteMatch) {
    $this->entityTypeManager = $entityTypeManager;
    $this->privateTempStoreFactory = $privateTempStoreFactory;
    $this->vactoryOTPManager = $vactoryOTPManager;
    $this->currentRouteMatch = $currentRouteMatch;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Instantiates this form class.
    return new static(
    // Load the service required to construct this class.
      $container->get('entity_type.manager'),
      $container->get('tempstore.private'),
      $container->get('vactory_otp.send_otp'),
      $container->get('current_route_match')
    );
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
    return 'vactory_two_factors_auth';
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
    $uid = vactory_two_factors_decrypt($this->currentRouteMatch->getParameter('uid'));
    if ($uid) {
      if ($this->currentUser()->isAuthenticated()) {
        throw new NotFoundHttpException();
      }

      $this->currentUserObject = $this->entityTypeManager->getStorage('user')
        ->load($uid);
      if (empty($form_state->get('current_step'))) {
        $form_state->set('current_step', 0);
      }

      $form['wrapper'] = [
        '#type' => 'container',
        '#prefix' => '<div id="vactory-two-factors-otp-wrapper">',
        '#suffix' => '</div>',
      ];

      switch ($form_state->get('current_step')) {
        case 0:
          $this->chooseOtpMethodStep($form, $form_state);
          break;

        case 1:
          $this->enterRecievedOtpCodeStep($form, $form_state);
          break;
      }

      return $form;
    }
    throw new NotFoundHttpException();
  }

  /**
   * Step 1 form builder.
   */
  private function chooseOtpMethodStep(array &$form, FormStateInterface $form_state) {
    $user_email = $this->currentUserObject->get('mail')->value;
    $user_phone = $this->currentUserObject->get('field_telephone')->value;
    $otp_method_options = [];
    $email_pieces = explode('@', $user_email);
    $email_username = substr_replace($email_pieces[0], str_repeat('•', strlen($email_pieces[0]) - 2), 2);
    $email_domaine = substr_replace($email_pieces[1], str_repeat('•', strlen($email_pieces[1]) - 4), 1, -3);
    $otp_method_options['email'] = $email_username . '@' . $email_domaine;
    if (isset($user_phone) && !empty($user_phone)) {
      $otp_method_options['phone'] = substr_replace($user_phone, str_repeat('•', strlen($user_phone) - 5), 3, -2);
    }
    $form['wrapper']['otp_method'] = [
      '#type' => 'radios',
      '#title' => $this->t("Pour vérifier votre identité, sélectionnez un contact auprès duquel vous pouvez recevoir un code de vérification"),
      '#options' => $otp_method_options,
      '#required' => TRUE,
    ];
    $form['wrapper']['next'] = [
      '#type' => 'submit',
      '#value' => $this->t('Envoyer le code'),
      '#submit' => ['::nextStep'],
      '#ajax' => [
        'callback' => [$this, 'nextStepCallback'],
        'wrapper' => 'vactory-two-factors-otp-wrapper',
        'method' => 'replace',
      ],
    ];
  }

  /**
   * Step 2 form builder.
   */
  private function enterRecievedOtpCodeStep(array &$form, FormStateInterface $form_state) {
    $form['wrapper']['otp_code'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Code de vérification'),
      '#required' => TRUE,
      '#size' => 5,
    ];
    $form['wrapper']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Terminer'),
    ];
  }

  /**
   * Next step submit button ajax callback.
   */
  public function nextStepCallback(array &$form, FormStateInterface $form_state) {
    return $form['wrapper'];
  }

  /**
   * Next step submit button submit function.
   */
  public function nextStep(array &$form, FormStateInterface $form_state) {
    $config = $this->config('vactory_two_factors_auth.settings');
    $otp_method = $form_state->getValue('otp_method');
    $result = NULL;
    $otp_method_label = '';
    $otp = rand(1000, 9999);
    $form_state->set('otp', $otp);
    if ($otp_method == 'email') {
      $mail_subject = !empty($config->get('mail_message_subject')) ? $config->get('mail_message_subject') : $this->t("Code de vérification d'identité");
      $mail_body = [];
      $mail_body['value'] = !empty($config->get('mail_message_body')) ? $config->get('mail_message_body') : '';
      $otp_method_label = $this->t('mail');
      $user_email = $this->currentUserObject->get('mail')->value;
      $result = $this->vactoryOTPManager->sendOtpByMail($mail_subject, $user_email, $mail_body, $otp);
    }
    if ($otp_method == 'phone') {
      $sms_message = !empty($config->get('sms_message_body')) ? $config->get('sms_message_body') : '';
      $otp_method_label = $this->t('SMS');
      $user_phone = $this->currentUserObject->get('field_telephone')->value;
      $result = $this->vactoryOTPManager->sendOtpBySms($user_phone, $sms_message, $otp);
    }
    if ($result) {
      $session_manager = $this->privateTempStoreFactory->get('vactory_two_factors_auth');
      $session_manager->set('otp', $otp);
      $session_manager->set('otp_generate_date', (new \DateTime())->getTimestamp());
      $message = $this->t("Le code de verification est envoyé, merci de vérifier votre boîte @otp_mmehtod_label et de saisir le code ci-dessous.", ['@otp_mmehtod_label' => $otp_method_label]);
      \Drupal::messenger()->addMessage($message);
      $form_state->set('current_step', 1);
      $form_state->setRebuild();
    }
    else {
      $form_state->set('current_step', 0);
      $form_state->setRebuild();
      $message = $this->t("Une erreur est survenue lors de génération/envoie de code de confirmation, merci de réessayer.");
      \Drupal::messenger()->addError($message);
    }
  }

  /**
   * {@inheritDoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    if ($form_state->get('current_step') === 1) {
      $config = $this->config('vactory_two_factors_auth.settings');
      $submitted_otp = $form_state->getValue('otp_code');
      $session_manager = $this->privateTempStoreFactory->get('vactory_two_factors_auth');
      $generated_otp = $session_manager->get('otp');
      if ($generated_otp != $submitted_otp) {
        $form_state->setErrorByName('otp_code', $this->t('Le code saisi est incorrecte'));
      }
      else {
        $otp_lifetime = $config->get('otp_lifetime');
        if ($otp_lifetime > 0) {
          $otp_generate_date = $session_manager->get('otp_generate_date');
          $current_date = (new \DateTime())->getTimestamp();
          if ($current_date - $otp_generate_date > $otp_lifetime) {
            $uid = vactory_two_factors_encrypt($this->currentUserObject->id());
            $link = Link::createFromRoute($this->t('Essayer avec un autre code'), 'vactory_two_factors_auth.otp_form', ['uid' => $uid]);
            $form_state->setErrorByName('otp_code', $this->t("Le code saisi n'est plus valide, @link", ['@link' => $link->toString()]));
          }
        }
        $session_manager->delete('otp');
      }
    }
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
    $message = $this->t("Félicitaion votre identité a bien été vérifiée.");
    \Drupal::messenger()->addMessage($message);
    $form_state->setRedirect('vactory_espace_prive.cleaned_profile');
    user_login_finalize($this->currentUserObject);
  }

}
