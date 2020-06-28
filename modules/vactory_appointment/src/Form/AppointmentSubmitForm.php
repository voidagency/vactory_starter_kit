<?php

namespace Drupal\vactory_appointment\Form;

use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\taxonomy\Entity\Term;
use Drupal\user\Entity\User;
use Drupal\vactory_appointment\Entity\AppointmentEntity;
use Symfony\Component\HttpFoundation\RedirectResponse;

class AppointmentSubmitForm extends FormBase {
  const PAGE_COUNT = 3;
  const CONFIRMATION_PAGE_INDEX = 4;
  private $appointmentAgencyID;
  private $appointmentAgencyName;
  private $appointmentMotifID;
  private $appointmentMotifName;
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
    return 'vactory_appointment_submission';
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
    if (!$this->isSetAgencyAndMotif()) {
      // If no agency either no motif has been specified in the path then
      // redirect to 404 page.
      redirect_to_notfound();
    }

    $params = \Drupal::request()->query->all();
    if (isset($params['aid'])) {
      if (!empty($params['aid'])) {
        $aid = decrypt($params['aid']);
        $appointment_object = AppointmentEntity::load($aid);
        if ($appointment_object) {
          $existing_values = [];
          $existing_values[0]['adviser'] = $appointment_object->getAdviser()->id();
          $existing_values[0]['appointment_motif'] = $this->appointmentMotifID;
          $existing_values[0]['appointment_agency'] = $this->appointmentAgencyID;
          $existing_values[1]['appointment_day'] = $appointment_object->getAppointmentDay()->format('Y-m-d');
          $existing_values[1]['appointment_hour'] = $appointment_object->getAppointmenthour();
          $existing_values[2]['last_name'] = $appointment_object->getAppointmentLastName();
          $existing_values[2]['first_name'] = $appointment_object->getAppointmentFirstName();
          $existing_values[2]['phone'] = $appointment_object->getAppointmentPhone();
          $existing_values[2]['email'] = $appointment_object->getAppointmentEmail();
          if (empty($form_state->get('values_storage'))) {
            $form_state->set('values_storage', $existing_values);
          }
          $current_page = $form_state->get('current_page');
          if (!isset($current_page)) {
            // Initialize page index.
            $form_state->set('current_page', 3);
          }
          $existing_values['id'] = $appointment_object->id();
          $form_state->set('existing_values', $existing_values);
          $form_state->set('is_existing_appointment', TRUE);
          $form_state->setRebuild(TRUE);
        }
        else {
          redirect_to_notfound();
        }
      }
    }

    if (empty($form_state->get('current_page'))) {
      // Initialize page index.
      $form_state->set('current_page', 0);
    }
    if (empty($form_state->get('values_storage'))) {
      // Initialize values storage.
      $form_state->set('values_storage', []);
    }
    // Get current page index.
    $current_page = $form_state->get('current_page');
    // Get current page storage.
    $values_storage = $form_state->get('values_storage');
    switch ($current_page) {
      case 0:
        // First page case.
        $this->getAdviserPage($form, $form_state, $current_page, $values_storage);
        break;
      case 1:
        // Second page case.
        $this->getDatePage($form, $form_state, $current_page, $values_storage);
        break;
      case 2:
        // Third page case.
        $this->getProfileInfoPage($form, $form_state, $current_page, $values_storage);
        break;
      case 3:
        // Preview page case.
        $this->getPreviewPage($form, $form_state, $current_page, $values_storage);
        break;
      case 4:
        // Confirm creation page case.
        $this->getConfirmationPage($form, $form_state, $current_page, $values_storage);
        break;
      default:
    }
    if ($current_page > 0 && $current_page < self::PAGE_COUNT) {
      // Form previous button.
      $form['previous'][$current_page] = [
        '#type' => 'submit',
        '#value' => t('Retour'),
        '#submit' => [[static::class, 'updatePageContext']],
        '#limit_validation_errors' => [],
      ];
    }
    if ($current_page < self::PAGE_COUNT) {
      // Form next button.
      $form['next'][$current_page] = [
        '#type' => 'submit',
        '#value' => t('Continue'),
        '#submit' => [[static::class, 'updatePageContext']],
      ];
      if ($current_page === 1) {
        $form['next']['#validate'] = [static::class, 'validateAppointmentDate'];
      }
    }
    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    $appoitnment_manager = \Drupal::service('vactory_appointment.appointments.manage');
    // Get the triggering submit button.
    $triggering_element = $form_state->getTriggeringElement();
    $triggering_element_parents = $triggering_element['#array_parents'];
    // Submit button type next or previous.
    $triggering_element_type = $triggering_element_parents[0];
    // Submit button page index.
    $triggering_element_page = in_array($triggering_element_type, ['next', 'previous']) ? $triggering_element_parents[1] : NULL;
    if ($triggering_element_type == 'next' && $triggering_element_page === 1) {
      $values = $form_state->getValues();
      $values_storage = $form_state->get('values_storage');
      $day = $values['appointment_day'];
      $day = \DateTime::createFromFormat('Y-m-d', $day);
      $hour = $values['appointment_hour'];
      $appointment_id = $form_state->get('existing_values')['id'];
      $appointment = isset($appointment_id) ? AppointmentEntity::load($appointment_id) : NULL;
      $adviser_id = $values_storage[0]['adviser'];
      $adviser = User::load($adviser_id);
      $is_valide_date = TRUE;
      if (isset($appointment)) {
        if (!$appoitnment_manager->isAdviserAvailable($adviser, $day, $hour) && !$appoitnment_manager->isAdviserHasAppointment($adviser, $appointment)) {
          $is_valide_date = FALSE;
        }
      }
      else {
        if (!$appoitnment_manager->isAdviserAvailable($adviser, $day, $hour)) {
          $is_valide_date = FALSE;
        }
      }
      if (!$is_valide_date) {
        $form_state->setError($form['appointment_day'], t("La date que vous avez choisi n'est plus disponible, merci de réessayez avec autre jour, heure et/ou conseiller."));
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
    $appoitnment_manager = \Drupal::service('vactory_appointment.appointments.manage');
    $values_storage = $form_state->get('values_storage');
    // Get adviser page values.
    $adviser_id = $values_storage[0]['adviser'];
    $agency = $values_storage[0]['appointment_agency'];
    $motif = $values_storage[0]['appointment_motif'];
    // Get appointment date page values.
    $day = $values_storage[1]['appointment_day'];
    $hour = $values_storage[1]['appointment_hour'];
    // Get profile infos page values.
    $last_name = $values_storage[2]['last_name'];
    $first_name = $values_storage[2]['first_name'];
    $phone = $values_storage[2]['phone'];
    $email = $values_storage[2]['email'];
    $formated_day = \DateTime::createFromFormat('Y-m-d', $day)->format('d/m/Y');
    $title = t('Rendez-vous le @day à @hour', ['@day' => $formated_day, '@hour' => $hour,]);
    if (empty($form_state->get('is_existing_appointment'))) {
      // Create new appointment case.
      $appointment = AppointmentEntity::create([
        'title' => $title,
        'adviser_id' => $adviser_id,
        'appointment_type' => $motif,
        'appointment_agency' => $agency,
        'appointment_first_name' => $first_name,
        'appointment_last_name' => $last_name,
        'appointment_phone' => $phone,
        'appointment_email' => $email,
        'appointment_day' => $day,
        'appointment_hour' => $hour,
      ]);
      try {
        $appointment->save();
        $adviser = User::load($adviser_id);
        $appoitnment_manager->updateAdviserAppointments($adviser, $appointment);
        $form_state->set('current_page', self::CONFIRMATION_PAGE_INDEX);
        $form_state->setRebuild(TRUE);
      } catch (EntityStorageException $e) {
        \Drupal::messenger()->addWarning(t('Une erreur est survenue lors de la création de votre rendez-vous, Veuillez réessayer plus tard.'));
        \Drupal::logger('vactory_appointment')->warning($e->getMessage());
      }
    }
    else {
      // Update existing appointment case.
      $aid = $form_state->get('existing_values')['id'];
      $appointment = AppointmentEntity::load($aid);
      $old_adviser = $appointment->getAdviser();
      $new_adviser = User::load($adviser_id);
      $motif = Term::load($motif);
      $agency = Term::load($agency);
      $day = \DateTime::createFromFormat('Y-m-d', $day);
      try {
        $appointment->setTitle($title)
          ->setAdviser($new_adviser)
          ->setAppointmentType($motif)
          ->setAgency($agency)
          ->setAppointmentFirstName($first_name)
          ->setAppointmentLastName($last_name)
          ->setAppointmentPhone($phone)
          ->setAppointmentEmail($email)
          ->setAppointmentDay($day)
          ->setAppointmentHour($hour)
          ->save();
        $appoitnment_manager->updateAdviserAppointments($new_adviser, $appointment);
        if ($old_adviser->id() !== $new_adviser->id()) {
          $appoitnment_manager->removeAdviserAppointmentIfExist($old_adviser, $appointment);
        }
        $form_state->set('current_page', self::CONFIRMATION_PAGE_INDEX);
        $form_state->setRebuild(TRUE);
      } catch (EntityStorageException $e) {
        \Drupal::messenger()->addWarning(t('Une erreur est survenue lors de la modification de votre rendez-vous, Veuillez réessayer plus tard.'));
        \Drupal::logger('vactory_appointment')->warning($e->getMessage());
      }
    }
  }

  /**
   * Form updte page counter.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public static function updatePageContext(array &$form, FormStateInterface $form_state) {
    // Get the triggering submit button.
    $triggering_element = $form_state->getTriggeringElement();
    $triggering_element_parents = $triggering_element['#array_parents'];
    // Submit button type next or previous.
    $triggering_element_type = $triggering_element_parents[0];
    // Submit button page index.
    $triggering_element_page = in_array($triggering_element_type, ['next', 'previous']) ? $triggering_element_parents[1] : NULL;
    $submitted_page_index = $triggering_element_page;
    // Update the page index.
    switch ($triggering_element_type) {
      case 'next':
        $new_page_index = ++$triggering_element_page;
        break;
      case 'previous':
        $new_page_index = --$triggering_element_page;
        break;
      case 'edit_profile':
        $new_page_index = 2;
        break;
      case 'edit_date':
        $new_page_index = 1;
        break;
      default:
        break;
    }
    // Update current page index.
    $form_state->set('current_page', $new_page_index);
    if (isset($submitted_page_index)) {
      // Update submitted page values.
      $values_storage = $form_state->get('values_storage');
      $values_storage[$submitted_page_index] = $form_state->getValues();
      $form_state->set('values_storage', $values_storage);
    }
    $form_state->setRebuild(TRUE);
  }

  /**
   * @return bool
   *   True when the agency adn the motif both assigned correctly in the path.
   */
  public function isSetAgencyAndMotif() {
    $langcode = \Drupal::languageManager()->getCurrentLanguage()->getId();
    $appointment_type = \Drupal::routeMatch()->getParameter('appointment_type');
    $agency = \Drupal::routeMatch()->getParameter('agency');
    $agency_properties = [
      'vid' => 'dam_agencies',
      'field_path_agency' => $agency,
    ];
    $agency_term = \Drupal::entityTypeManager()
      ->getStorage('taxonomy_term')
      ->loadByProperties($agency_properties);
    $motif_properties = [
      'vid' => 'dam_motifs',
      'field_path_motif_name' => $appointment_type,
    ];
    $motif_term = \Drupal::entityTypeManager()
      ->getStorage('taxonomy_term')
      ->loadByProperties($motif_properties);
    if (!empty($agency_term) && !empty($motif_term)) {
      $this->appointmentAgencyID = array_keys($agency_term)[0];
      $this->appointmentMotifID = array_keys($motif_term)[0];
      $translated_agency_term = \Drupal::service('entity.repository')
        ->getTranslationFromContext($agency_term[$this->appointmentAgencyID], $langcode);
      $translated_motif_term = \Drupal::service('entity.repository')
        ->getTranslationFromContext($motif_term[$this->appointmentMotifID], $langcode);
      $this->appointmentAgencyName = $translated_agency_term->getName();
      $this->appointmentMotifName = $translated_motif_term->getName();


    }

    return !empty($agency_term) && !empty($motif_term);
  }

  public function getReadableDate($appointment_day, $appointment_hour) {
    $langcode = \Drupal::languageManager()->getCurrentLanguage()->getId();
    $appointment_day = \DateTime::createFromFormat('Y-m-d', $appointment_day)->getTimestamp();
    $appointment_day = \Drupal::service('date.formatter')
      ->format($appointment_day, 'custom', 'l d F Y', NULL, $langcode);
    $appointment_date = t('<strong>Jour:</strong> :day <strong class="ml-2">Heure:</strong> :hour', [':day' => $appointment_day, ':hour' => $appointment_hour]);
    return $appointment_date;
  }

  public function getAdviserPage(array &$form, FormStateInterface $form_state, $current_page, $values_storage) {
    // Page title.
    $title = t('Choisissez votre conseiller');
    $form['title'] = [
      '#type' => 'markup',
      '#markup' => '<h1 class="mb-5">' . $title . '</h1>',
    ];
    // Get all advisers.
    $ids = \Drupal::entityQuery('user')
      ->condition('status', 1)
      ->condition('roles', 'dam_adviser')
      ->condition('field_adviser_agencies', $this->appointmentAgencyID)
      ->execute();
    $users = User::loadMultiple($ids);
    foreach ($users as $user) {
      $adviser_preview = [
        '#theme' => 'appointment_adviser_preview',
        '#user' => $user,
      ];
      $options[$user->id()] = \Drupal::service('renderer')
        ->render($adviser_preview);
    }
    $existing_values = $form_state->get('existing_values');
    $existing_adviser = isset($existing_values[$current_page]) ? $existing_values[$current_page]['adviser'] : '';
    // Advisers list form element.
    $form['adviser'] = [
      '#type' => 'radios',
      '#options' => $options,
      '#validated' => TRUE,
      '#default_value' => isset($values_storage[$current_page]['adviser']) ? $values_storage[$current_page]['adviser'] : $existing_adviser,
      '#prefix' => '<div class="select-adviser-wrapper">',
      '#suffix' => '</div>',
      '#attributes' => [
        'class' => ['select-adviser-radio'],
      ],
      '#required' => TRUE,
    ];

    // Hidden field to store current agency ID.
    $form['appointment_agency'] = [
      '#type' => 'hidden',
      '#value' => $this->appointmentAgencyID,
    ];
    // Hidden field to store current motif ID.
    $form['appointment_motif'] = [
      '#type' => 'hidden',
      '#value' => $this->appointmentMotifID,
    ];
  }

  public function getDatePage(array &$form, FormStateInterface $form_state, $current_page, $values_storage) {
    // Page title.
    $title = t("Choisissez le jour et l'heure");
    $form['title'] = [
      '#type' => 'markup',
      '#markup' => '<h1 class="mb-5">' . $title . '</h1>',
    ];
    $existing_values = $form_state->get('existing_values');
    $appointment_day = isset($existing_values[$current_page]) ? $existing_values[$current_page]['appointment_day'] : NULL;
    $appointment_hour = isset($existing_values[$current_page]) ? $existing_values[$current_page]['appointment_hour'] : NULL;
    // Appointment date field.
    $form['appointment_day'] = array(
      '#title' => t('Jour souhaité'),
      '#type' => 'date',
      '#default_value' => isset($values_storage[$current_page]['appointment_day']) ? $values_storage[$current_page]['appointment_day'] : $appointment_day,
      '#required' => TRUE,
    );
    // Get available hours from module settings.
    $appointment_hours = \Drupal::configFactory()->get('vactory_appointment.settings')
      ->get('appointment_hours');
    // Create available hours list.
    $form['appointment_hour'] = [
      '#type' => 'select',
      '#title' => t('Heure souhaitée'),
      '#options' => $appointment_hours,
      '#default_value' => isset($values_storage[$current_page]['appointment_hour']) ? $values_storage[$current_page]['appointment_hour'] : $appointment_hour,
      '#required' => TRUE,
    ];
  }

  public function getProfileInfoPage(array &$form, FormStateInterface $form_state, $current_page, $values_storage) {
    // Page title.
    $title = t("Renseignez vos informations");
    $form['title'] = [
      '#type' => 'markup',
      '#markup' => '<h1 class="mb-5">' . $title . '</h1>',
    ];
    $previous_page_index = $current_page-1;
    $appointment_day = $values_storage[$previous_page_index]['appointment_day'];
    $appointment_hour = $values_storage[$previous_page_index]['appointment_hour'];
    $form['appointment_date'] = [
      '#type' => 'markup',
      '#markup' => '<div class="text-center">' . $this->getReadableDate($appointment_day, $appointment_hour) . '</div>',
    ];
    $existing_values = $form_state->get('existing_values');
    $last_name = isset($existing_values[$current_page]) ? $existing_values[$current_page]['last_name'] : NULL;
    $first_name = isset($existing_values[$current_page]) ? $existing_values[$current_page]['first_name'] : NULL;
    $phone = isset($existing_values[$current_page]) ? $existing_values[$current_page]['phone'] : NULL;
    $email = isset($existing_values[$current_page]) ? $existing_values[$current_page]['email'] : NULL;
    $form['last_name'] = [
      '#type' => 'textfield',
      '#title' => t('Last name'),
      '#default_value' => isset($values_storage[$current_page]['last_name']) ? $values_storage[$current_page]['last_name'] : $last_name,
      '#required' => TRUE,
    ];
    $form['first_name'] = [
      '#type' => 'textfield',
      '#title' => t('First name'),
      '#default_value' => isset($values_storage[$current_page]['first_name']) ? $values_storage[$current_page]['first_name'] : $first_name,
      '#required' => TRUE,
    ];
    $form['phone'] = [
      '#type' => 'textfield',
      '#title' => t('Phone'),
      '#default_value' => isset($values_storage[$current_page]['phone']) ? $values_storage[$current_page]['phone'] : $phone,
      '#required' => TRUE,
    ];
    $form['email'] = [
      '#type' => 'email',
      '#title' => t('Email'),
      '#default_value' => isset($values_storage[$current_page]['email']) ? $values_storage[$current_page]['email'] : $email,
      '#required' => TRUE,
    ];
  }

  public function getPreviewPage(array &$form, FormStateInterface $form_state, $current_page, $values_storage) {
    $previous_page_index = $current_page-1;
    $first_name = $values_storage[$previous_page_index]['first_name'];
    $last_name = $values_storage[$previous_page_index]['last_name'];
    $phone = $values_storage[$previous_page_index]['phone'];
    $email = $values_storage[$previous_page_index]['email'];

    $title = t("Confirmer votre rendez-vous");
    $form['title'] = [
      '#type' => 'markup',
      '#markup' => '<h1 class="mb-5">' . $title . '</h1>',
    ];
    $form['appointment_type'] = [
      '#type' => 'markup',
      '#prefix' => '<div class="confirmation-wrapper mb-3"><div class="row">',
      '#markup' => '<div class="col-md-6"><strong>' . t('Type de rendez-vous') . '</strong></div>' . '<div class="col-md-6">' . $this->appointmentMotifName . '</div>',
    ];
    $form['appointment_agency'] = [
      '#type' => 'markup',
      '#markup' => '<div class="col-md-6"><strong>' . t('Agence de rendez-vous') . '</strong></div>' . '<div class="col-md-6">' . $this->appointmentAgencyName . '</div>',
      '#suffix' => '</div><hr class="mt-3 mb-3">'
    ];
    $form['appointment_profile'] = [
      '#type' => 'markup',
      '#markup' => '<h4>' . t("Profile de l'utilisateur") . '</h4>',
    ];
    $user_infos = '<div class="row">'
      . '<div class="col-md-6">' . t('Nom') . ':<strong class="ml-3">' . $last_name . '</strong></div>'
      . '<div class="col-md-6">' . t('Prénom') . ':<strong class="ml-3">' . $first_name . '</strong></div>'
      . '<div class="col-md-6">' . t('Téléphone') . ':<strong class="ml-3">' . $phone . '</strong></div>'
      . '<div class="col-md-6">' . t('Email') . ':<strong class="ml-3">' . $email . '</strong></div></div>';
    $form['appointment_user_infos'] = [
      '#type' => 'markup',
      '#markup' => $user_infos,
    ];
    $form['edit_profile'] = [
      '#type' => 'submit',
      '#value' => t('Modifier'),
      '#submit' => [[static::class, 'updatePageContext']],
      '#suffix' => '<hr class="mt-3 mb-3">'
    ];
    $date_page_index = $current_page-2;
    $appointment_day = $values_storage[$date_page_index]['appointment_day'];
    $appointment_hour = $values_storage[$date_page_index]['appointment_hour'];
    $form['date_title'] = [
      '#type' => 'markup',
      '#markup' => '<h4>' . t("Rendez-vous") . '</h4>',
    ];
    $form['date_info'] = [
      '#type' => 'markup',
      '#markup' => '<div class="text-center">' . $this->getReadableDate($appointment_day, $appointment_hour) . '</div>',
    ];
    $form['edit_date'] = [
      '#type' => 'submit',
      '#value' => t('Changer la date'),
      '#submit' => [[static::class, 'updatePageContext']],
      '#suffix' => '</div>',
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Confirmer'),
    ];
  }

  /**
   * Return confirmation page content.
   */
  public function getConfirmationPage(array &$form, FormStateInterface $form_state, $current_page, $values_storage) {
    $content = [];
    $content['type_confirmation'] = !empty($form_state->get('is_existing_appointment')) ? 'edit' : 'create';
    $form['confirm_creation_page'] = [
      '#theme' => 'appointment_confirmation_page',
      '#content' => $content,
    ];
  }

}
