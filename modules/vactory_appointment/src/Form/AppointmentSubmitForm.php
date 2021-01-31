<?php

namespace Drupal\vactory_appointment\Form;

use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\taxonomy\Entity\Term;
use Drupal\user\Entity\User;
use Drupal\vactory_appointment\Entity\AppointmentEntity;
use Drupal\vactory_locator\Entity\LocatorEntity;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Provide form to submit new appointments.
 */
class AppointmentSubmitForm extends FormBase {
  /**
   * Form page number.
   *
   * @var int
   */
  const PAGE_COUNT = 3;

  /**
   * Confirmation page index.
   *
   * @var int
   */
  const CONFIRMATION_PAGE_INDEX = 4;

  /**
   * ID Agence.
   *
   * @var int
   */
  private $appointmentAgencyID;

  /**
   * Nom agence.
   *
   * @var string
   */
  private $appointmentAgencyName;

  /**
   * Id type rendez-vous.
   *
   * @var int
   */
  private $appointmentMotifID;

  /**
   * Nom type rendez-vous.
   *
   * @var string
   */
  private $appointmentMotifName;

  /**
   * Description du type rendez-vous.
   *
   * @var string
   */
  private $appointmentMotifDescription;

  /**
   * Path of current agency.
   *
   * @var string
   */
  private $appointmentAgencyPath;

  /**
   * Vactory appointment module config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  private $config;

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

    $user_has_access = \Drupal::service('vactory_appointment.appointments.manage')->isCurrentUserCanSubmitAppointment();
    if (!$user_has_access) {
      $url = Url::fromRoute('vactory_appointment.site_agency_select')->toString();
      $response = new RedirectResponse($url);
      $response->send();
    }

    $params = \Drupal::request()->query->all();
    if (isset($params['aid']) && empty($form_state->get('existing_values'))) {
      if (!empty($params['aid'])) {
        $aid = decrypt($params['aid']);
        $appointment_object = AppointmentEntity::load($aid);
        if ($appointment_object) {
          $existing_values = [];
          $existing_values[0]['adviser'] = $appointment_object->getAdviser()->id();
          $existing_values[0]['appointment_motif'] = $this->appointmentMotifID;
          $existing_values[0]['appointment_agency'] = $this->appointmentAgencyID;
          $existing_values[1]['appointment_date'] = $appointment_object->getAppointmentDate();
          $existing_values[2]['last_name'] = $appointment_object->getAppointmentLastName();
          $existing_values[2]['first_name'] = $appointment_object->getAppointmentFirstName();
          $existing_values[2]['phone'] = $appointment_object->getAppointmentPhone();
          $existing_values[2]['email'] = $appointment_object->getAppointmentEmail();
          if (empty($form_state->get('values_storage'))) {
            $form_state->set('values_storage', $existing_values);
          }
          $current_page = $form_state->get('current_page');
          if (empty($current_page)) {
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
    $existing_values = $form_state->get('existing_values');
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

    // Submit buttons wrapper opner.
    $form['submit_wrapper_opner'] = [
      '#type' => 'markup',
      '#markup' => '<div class="submit-wrapper d-flex justify-content-between">',
    ];
    if ($current_page > 0 && $current_page < self::PAGE_COUNT && empty($existing_values)) {
      // Form previous button.
      $form['previous'][$current_page] = [
        '#type' => 'submit',
        '#value' => $this->t('Retour'),
        '#submit' => [[static::class, 'updatePageContext']],
        '#limit_validation_errors' => [],
        '#attributes' => [
          'class' => [
            'mt-2',
            'mr-auto',
            'prefix-icon-chevrons-left',
          ],
        ],
      ];
    }
    if ($current_page < self::PAGE_COUNT) {
      if (!$form_state->get('no_adviser_founded')) {
        // Form next button.
        $form['next'][$current_page] = [
          '#type' => 'submit',
          '#value' => empty($existing_values) ? $this->t('Suivant') : $this->t('Valider'),
          '#submit' => [[static::class, 'updatePageContext']],
          '#attributes' => [
            'class' => [
              'mt-2',
              'ml-auto',
              'suffix-icon-chevrons-right',
            ],
          ],
        ];
      }
    }
    // Submit button wrapper closer.
    $form['submit_wrapper_closer'] = [
      '#type' => 'markup',
      '#markup' => '</div>',
    ];

    if (isset($values_storage[0]['adviser']) && $current_page == 1) {
      $date = new \DateTime('now');
      $form['#attached']['library'][] = 'vactory_appointment/submit_appointment.calendar';
      $adviser = User::load($values_storage[0]['adviser']);
      $adviser_appointments = $adviser->get('field_adviser_appointments')->value;
      $adviser_holidays = $adviser->get('field_adviser_holiday')->value;
      $is_edit_appointment = FALSE;
      $appointment_id = NULL;
      if ($form_state->get('is_existing_appointment')) {
        $is_edit_appointment = TRUE;
        $existing_values = $form_state->get('existing_values');
        $appointment_id = $existing_values['id'];
      }
      $choosed_date = NULL;
      $mobile_choosed_date = NULL;
      if (isset($values_storage[1]['appointment_date'])) {
        if (!empty($existing_values)) {
          $choosed_date = $values_storage[1]['appointment_date'] != $existing_values[1]['appointment_date'] ? $values_storage[1]['appointment_date'] : NULL;
        }
        else {
          $choosed_date = $values_storage[1]['appointment_date'];
        }
      }
      if ($choosed_date) {
        $mobile_choosed_date = new \DateTime($choosed_date);
      }
      else {
        if (isset($existing_values[1]['appointment_date'])) {
          $mobile_choosed_date = new \DateTime($existing_values[1]['appointment_date']);
        }
      }
      $langcode = \Drupal::languageManager()
        ->getCurrentLanguage()
        ->getId();
      $form['#attached']['drupalSettings']['vactory_appointment'] = [
        'adviser_appointments' => $adviser_appointments,
        'adviser_holidays' => $adviser_holidays,
        'appointment_id' => $appointment_id,
        'is_edit_appointment' => $is_edit_appointment,
        'current_date' => $date->format('Y-m-d'),
        'choosed_date' => $choosed_date,
        'lang_code' => $langcode,
        'server_timezone_ofsset' => $date->format('P'),
        'mobile_choosed_day' => $mobile_choosed_date ? $mobile_choosed_date->format('d/m/Y') : '',
        'mobile_choosed_time' => $mobile_choosed_date ? $mobile_choosed_date->format('H:i') : '',
      ];
    }
    $form['#cache'] = ['max-age' => 0];
    return $form;
  }

  /**
   * Form validation.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    $appointment_manager = \Drupal::service('vactory_appointment.appointments.manage');
    // Get the triggering submit button.
    $triggering_element = $form_state->getTriggeringElement();
    $triggering_element_parents = $triggering_element['#array_parents'];
    // Submit button type next or previous.
    $triggering_element_type = $triggering_element_parents[0];
    // Submit button page index.
    $elemnts_types = [
      'next',
      'previous',
      'submit',
    ];
    $triggering_element_page = in_array($triggering_element_type, $elemnts_types) ? $triggering_element_parents[1] : NULL;
    $values = $form_state->getValues();
    $values_storage = $form_state->get('values_storage');
    if (in_array($triggering_element_type, ['next', 'submit'])) {
      if ($triggering_element_page === 0) {
        if (empty($values['adviser'])) {
          $form_state->setErrorByName('adviser', t("Veuillez choisir un conseiller"));
        }
      }
      if ($triggering_element_page > 0) {
        if ($triggering_element_page === 1 && empty($values['appointment_date'])) {
          $form_state->setErrorByName('appointment_date', 'Veuillez choisir la date de votre rendez-vous');
        }
        else {
          $appointment_date = !empty($values['appointment_date']) ? $values['appointment_date'] : $values_storage[1]['appointment_date'];
          $date = new \DateTime($appointment_date);
          $appointment_id = $form_state->get('existing_values')['id'];
          $appointment = isset($appointment_id) ? AppointmentEntity::load($appointment_id) : NULL;
          $adviser_id = $values_storage[0]['adviser'];
          $adviser = User::load($adviser_id);
          $is_valid_date = TRUE;
          if (isset($appointment)) {
            if (!$appointment_manager->isAdviserAvailable($adviser, $date->getTimestamp(), TRUE, $appointment->id())) {
              $is_valid_date = FALSE;
            }
          }
          else {
            if (!$appointment_manager->isAdviserAvailable($adviser, $date->getTimestamp(), FALSE)) {
              $is_valid_date = FALSE;
            }
          }
          if (!$is_valid_date) {
            $form_state->setError($form, t("La date que vous avez choisi n'est plus disponible, merci de réessayez avec autre jour, heure et/ou conseiller."));
          }
        }
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
    $langcode = \Drupal::languageManager()
      ->getCurrentLanguage()
      ->getId();
    $values_storage = $form_state->get('values_storage');
    // Get adviser page values.
    $adviser_id = $values_storage[0]['adviser'];
    $agency = $values_storage[0]['appointment_agency'];
    $motif = $values_storage[0]['appointment_motif'];
    // Get appointment date page values.
    $date_string = $values_storage[1]['appointment_date'];
    $date = new \DateTime($date_string);
    // Get profile infos page values.
    $last_name = $values_storage[2]['last_name'];
    $first_name = $values_storage[2]['first_name'];
    $phone = $values_storage[2]['phone'];
    $email = $values_storage[2]['email'];
    $formated_day = $date->format('d/m/Y');
    $formated_hour = $date->format('H:i');
    $title = $this->t('Rendez-vous le @day à @hour', [
      '@day' => $formated_day,
      '@hour' => $formated_hour,
    ]);
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
        'appointment_date' => $date->format('Y-m-d\TH:i:s'),
      ]);
      try {
        $appointment->save();
        $params = [
          'adviser_id' => $adviser_id,
          'date' => $date,
          'agency' => $agency,
          'appointment_type' => $motif,
          'appointment_first_name' => $first_name,
          'appointment_last_name' => $last_name,
          'account_infos' => [
            'email' => encrypt($email),
            'phone' => encrypt($phone),
            'first_name' => $first_name,
            'last_name' => $last_name,
          ],
        ];
        $form_state->set('current_page', self::CONFIRMATION_PAGE_INDEX);
        $form_state->setRebuild(TRUE);
        // Send notification to client if notification feature is enabled.
        $notifications_enabled = \Drupal::config('vactory_appointment.settings')->get('enable_email_notifications');
        if ($notifications_enabled) {
          \Drupal::service('plugin.manager.mail')
            ->mail('vactory_appointment', 'create_appointment', $email, $langcode, $params);
        }
      }
      catch (EntityStorageException $e) {
        \Drupal::messenger()->addWarning(t('Une erreur est survenue lors de la création de votre rendez-vous, Veuillez réessayer plus tard.'));
        \Drupal::logger('vactory_appointment')->warning($e->getMessage());
      }
    }
    else {
      // Update existing appointment case.
      $aid = $form_state->get('existing_values')['id'];
      $appointment = AppointmentEntity::load($aid);
      $new_adviser = User::load($adviser_id);
      $motif = Term::load($motif);
      $agency = LocatorEntity::load($agency);
      try {
        $appointment->setTitle($title)
          ->setAdviser($new_adviser)
          ->setAppointmentType($motif)
          ->setAgency($agency)
          ->setAppointmentFirstName($first_name)
          ->setAppointmentLastName($last_name)
          ->setAppointmentPhone($phone)
          ->setAppointmentEmail($email)
          ->setAppointmentDate($date)
          ->save();
        $form_state->set('current_page', self::CONFIRMATION_PAGE_INDEX);
        $form_state->setRebuild(TRUE);
      }
      catch (EntityStorageException $e) {
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
        $existing_values = $form_state->get('existing_values');
        $new_page_index = empty($existing_values) ? ++$triggering_element_page : static::PAGE_COUNT;
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
   * Check if we are in an agency context.
   *
   * @return bool
   *   True when the agency adn the motif both assigned correctly in the path.
   */
  public function isSetAgencyAndMotif() {
    $langcode = \Drupal::languageManager()->getCurrentLanguage()->getId();
    $appointment_type = \Drupal::routeMatch()->getParameter('appointment_type');
    $agency = \Drupal::routeMatch()->getParameter('agency');
    $agency_properties = [
      'type' => 'vactory_locator',
      'field_agency_path' => $agency,
      'status' => 1,
    ];
    $agency_entities = \Drupal::entityTypeManager()
      ->getStorage('locator_entity')
      ->loadByProperties($agency_properties);
    $motif_properties = [
      'vid' => 'vactory_appointment_motifs',
      'field_path_motif_name' => $appointment_type,
    ];
    $motif_term = \Drupal::entityTypeManager()
      ->getStorage('taxonomy_term')
      ->loadByProperties($motif_properties);
    if (!empty($agency_entities) && !empty($motif_term)) {
      $agency_entity = array_values($agency_entities)[0];
      $is_appointment_enabled = $agency_entity->get('field_is_appointment_enabled')->value;
      if (!$is_appointment_enabled) {
        return FALSE;
      }

      $this->appointmentAgencyID = $agency_entity->id();
      $this->appointmentMotifID = array_keys($motif_term)[0];
      $this->appointmentAgencyPath = $agency;
      $translated_agency = \Drupal::service('entity.repository')
        ->getTranslationFromContext($agency_entity, $langcode);
      $translated_motif_term = \Drupal::service('entity.repository')
        ->getTranslationFromContext($motif_term[$this->appointmentMotifID], $langcode);
      $this->appointmentAgencyName = $translated_agency->get('name')->value;
      $this->appointmentMotifName = $translated_motif_term->getName();
      $this->appointmentMotifDescription = strip_tags($translated_motif_term->get('description')->value);
    }

    $this->config = \Drupal::config('vactory_appointment.settings');

    return !empty($agency_entities) && !empty($motif_term);
  }

  /**
   * Get readable date function.
   */
  public function getReadableDate($appointment_date) {
    $langcode = \Drupal::languageManager()->getCurrentLanguage()->getId();
    $date = new \DateTime($appointment_date);
    $end_date = clone $date;
    $timestamp = $date->getTimestamp();
    $appointment_day = \Drupal::service('date.formatter')
      ->format($timestamp, 'custom', 'l d F Y', NULL, $langcode);
    $appointment_start_hour = $date->format('H\hi');
    $appointment_end_hour = $end_date->add(new \DateInterval('PT' . 30 . 'M'))->format('H\hi');
    $appointment_date = [
      'day' => $appointment_day,
      'hour' => $appointment_start_hour . ' - ' . $appointment_end_hour,
    ];
    return $appointment_date;
  }

  /**
   * Get adviser page.
   */
  public function getAdviserPage(array &$form, FormStateInterface $form_state, $current_page, $values_storage) {
    $title = $this->appointmentMotifDescription;
    // Page title.
    $form['title'] = $this->setPageTitle($title);
    // Get all advisers.
    $ids = \Drupal::entityQuery('user')
      ->condition('status', 1)
      ->condition('roles', 'adviser')
      ->condition('field_agencies', $this->appointmentAgencyID)
      ->execute();
    $users = User::loadMultiple($ids);
    $options = [];
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
    if (!empty($options)) {
      // Advisers list form element.
      $form['adviser'] = [
        '#type' => 'radios',
        '#options' => $options,
        '#validated' => TRUE,
        '#default_value' => isset($values_storage[$current_page]['adviser']) ? $values_storage[$current_page]['adviser'] : $existing_adviser,
        '#attributes' => [
          'class' => ['select-adviser-radio', 'hidden-radio'],
        ],
        '#required' => TRUE,
        '#prefix' => '<div class="select-adviser-wrapper card-radio-wrapper">',
        '#suffix' => '</div>',
      ];
      $form_state->set('no_adviser_founded', FALSE);
    }
    else {
      $form['message'] = [
        '#type' => 'markup',
        '#markup' => '<div class="not-found-message pt-3 d-flex flex-column"><a href="#" class="js-trigger-hp close mb-2 ml-auto"><i class="icon-close-bold"></i></a><h3 class="adviser-not-found d-block text-white text-uppercase bg-primary rounded shadow p-2 py-md-3 px-md-6 m-0">' . $this->t('Aucun conseiller trouvé') . '</h3></div>',
      ];
      $form_state->set('no_adviser_founded', TRUE);
    }
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

  /**
   * Get date page function.
   */
  public function getDatePage(array &$form, FormStateInterface $form_state, $current_page, $values_storage) {
    // Page title.
    $form['title'] = $this->setPageTitle(t("Choisissez le jour et l'heure de votre rendez-vous"));
    $appointment_date = isset($values_storage[$current_page]['appointment_date']) ? $values_storage[$current_page]['appointment_date'] : NULL;
    $form['calendar'] = [
      '#type' => 'markup',
      '#markup' => '<div id="calendar" class=" py-2 px-1 px-md-2 bg-white border border-primary shadow rounded"></div>',
    ];
    $form['appointment_date'] = [
      '#type' => 'hidden',
      '#default_value' => $appointment_date,
    ];
  }

  /**
   * Get profile info page.
   */
  public function getProfileInfoPage(array &$form, FormStateInterface $form_state, $current_page, $values_storage) {
    // Page title.
    $form['title'] = $this->setPageTitle(t("Renseignez vos informations"));
    $previous_page_index = $current_page - 1;
    $appointment_date = $values_storage[$previous_page_index]['appointment_date'];
    $appointment_date = $this->getReadableDate($appointment_date);
    $form['appointment_date'] = [
      '#theme' => 'appointment_date',
      '#content' => [
        'day' => $appointment_date['day'],
        'hour' => $appointment_date['hour'],
      ],
      '#prefix' => '<div class="user-info-wrapper row"><div class="col-md-4 pb-2 pb-md-0">',
      '#suffix' => '</div>',
    ];
    $existing_values = $form_state->get('existing_values');
    $last_name = isset($existing_values[$current_page]) ? $existing_values[$current_page]['last_name'] : NULL;
    $first_name = isset($existing_values[$current_page]) ? $existing_values[$current_page]['first_name'] : NULL;
    $phone = isset($existing_values[$current_page]) ? $existing_values[$current_page]['phone'] : NULL;
    $email = isset($existing_values[$current_page]) ? $existing_values[$current_page]['email'] : NULL;

    $form['form_wrapper_opner'] = [
      '#type' => 'markup',
      '#markup' => '<div class="col-md-8 pb-2 pb-md-0"><div class="user-informations bg-white border border-primary rounded h-100">',
    ];
    $form['last_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Last name'),
      '#default_value' => isset($values_storage[$current_page]['last_name']) ? $values_storage[$current_page]['last_name'] : $last_name,
      '#required' => TRUE,
      '#attributes' => [
        'class' => [
          'prefix-icon-user',
        ],
        'placeholder' => $this->t('Nom'),
        'autocomplete' => 'off',
      ],
    ];
    $form['first_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('First name'),
      '#default_value' => isset($values_storage[$current_page]['first_name']) ? $values_storage[$current_page]['first_name'] : $first_name,
      '#required' => TRUE,
      '#attributes' => [
        'class' => [
          'prefix-icon-user',
        ],
        'placeholder' => $this->t('Prénom'),
        'autocomplete' => 'off',
      ],
    ];
    $form['phone'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Phone'),
      '#default_value' => isset($values_storage[$current_page]['phone']) ? $values_storage[$current_page]['phone'] : $phone,
      '#required' => TRUE,
      '#attributes' => [
        'class' => [
          'prefix-icon-mobile',
        ],
        'placeholder' => $this->t('Mobile'),
        'autocomplete' => 'off',
      ],
    ];
    $form['email'] = [
      '#type' => 'email',
      '#title' => $this->t('Email'),
      '#default_value' => isset($values_storage[$current_page]['email']) ? $values_storage[$current_page]['email'] : $email,
      '#required' => TRUE,
      '#attributes' => [
        'class' => [
          'prefix-icon-mail',
        ],
        'placeholder' => $this->t('Adresse e-mail'),
      ],
    ];
    $form['accept_conditions'] = [
      '#type' => 'checkbox',
      '#title' => t("En cochant cette case, j'accepte et je reconnais avoir pris connaissance des conditions générales d'utilisation."),
      '#required' => TRUE,
      '#attributes' => [
        'class' => ['conditions-general'],
      ],
      '#prefix' => '<div class="skinned-control">',
      '#suffix' => '</div>',
    ];
    $block_content = $this->getGeneralConditions();
    $form['conditions_generales'] = [
      '#markup' => \Drupal::service('renderer')->render($block_content),
    ];
    $form['form_wrapper_closer'] = [
      '#type' => 'markup',
      '#markup' => '</div></div></div>',
    ];
  }

  /**
   * Get preview page.
   */
  public function getPreviewPage(array &$form, FormStateInterface $form_state, $current_page, $values_storage) {
    $previous_page_index = $current_page - 1;
    $first_name = $values_storage[$previous_page_index]['first_name'];
    $last_name = $values_storage[$previous_page_index]['last_name'];
    $phone = $values_storage[$previous_page_index]['phone'];
    $email = $values_storage[$previous_page_index]['email'];

    $title = t("Confirmer votre rendez-vous");

    $form['title'] = [
      '#type' => 'markup',
      '#markup' => '<div class="form-intro mb-3 mb-md-5"><h2 class="form-title h4 text-uppercase mb-xs">' . $title . '</h2>',
    ];
    $form['description'] = [
      '#type' => 'markup',
      '#markup' => '<p class="form-description mb-0">' . $this->appointmentMotifName . '<p></div>',
    ];
    $form['appointment_profile_push_opner'] = [
      '#type' => 'markup',
      '#markup' => '<div class="push-info-box px-1 py-2 px-sm-2 px-md-3 mb-16 bg-white border border-primary shadow rounded">',
    ];
    $form['appointment_profile'] = [
      '#type' => 'markup',
      '#markup' => '<h4 class="text-primary mb-0">' . t("Profil de l'utilisateur") . '</h4>',
      '#prefix' => '<div class="d-flex justify-content-between align-items-center">',
    ];
    $form['edit_profile'] = [
      '#type' => 'submit',
      '#value' => $this->t('Modifier'),
      '#submit' => [[static::class, 'updatePageContext']],
      '#attributes' => [
        'class' => [
          'btn-empty',
          'btn-edit',
          'ml-auto',
          'suffix-icon-pencil',
        ],
      ],
      '#suffix' => '</div>',
    ];
    $form['push_divider'] = [
      '#type' => 'markup',
      '#markup' => '<hr class="hr-primary">',
    ];
    $nom_label = $this->t('Nom');
    $prenom_label = $this->t('Prénom');
    $telephone_label = $this->t('Téléphone');
    $mail_label = $this->t('Email');
    $user_infos = '<div class="d-none d-md-flex mb-1"><div class="flex-fill w-50"><span class="mw-130 text-gray font-bold font-22">' . $nom_label . ':</span><strong class="ml-16 font-22">' . $last_name . '</strong></div>'
      . '<div class="flex-fill w-50"><span class="mw-130 text-gray font-bold font-22">' . $telephone_label . ':</span><strong class="ml-16 font-22">' . $phone . '</strong></div></div>'
      . '<div class="d-none d-md-flex"><div class="flex-fill w-50"><span class="mw-130 text-gray font-bold font-22">' . $prenom_label . ':</span><strong class="ml-16 font-22">' . $first_name . '</strong></div>'
      . '<div class="flex-fill w-50"><span class="mw-130 text-gray font-bold font-22">' . $mail_label . ':</span><strong class="ml-16 font-22">' . $email . '</strong></div></div>';

    $user_infos_mobile = '<div class="d-md-none"><div class="mb-xs d-flex justify-content-between"><span class="text-gray font-bold font-20">' . $nom_label . ':</span><strong class="ml-10 font-20">' . $last_name . '</strong></div>'
      . '<div class="mb-xs d-flex justify-content-between"><span class="text-gray font-bold font-20">' . $prenom_label . ':</span><strong class="ml-10 font-20">' . $first_name . '</strong></div>'
      . '<div class="mb-xs d-flex justify-content-between"><span class="text-gray font-bold font-20">' . $telephone_label . ':</span><strong class="ml-10 font-20">' . $phone . '</strong></div>'
      . '<div class="mb-xs d-flex justify-content-between"><span class="text-gray font-bold font-20">' . $mail_label . ':</span><strong class="ml-10 font-20">' . $email . '</strong></div></div>';

    $form['appointment_user_infos'] = [
      '#type' => 'markup',
      '#markup' => $user_infos,
    ];
    $form['appointment_user_infos_mobile'] = [
      '#type' => 'markup',
      '#markup' => $user_infos_mobile,
    ];
    $form['appointment_profile_push_closer'] = [
      '#type' => 'markup',
      '#markup' => '</div>',
    ];

    $date_page_index = $current_page - 2;
    $appointment_date = $values_storage[$date_page_index]['appointment_date'];
    $form['appointment_rdv_push_opner'] = [
      '#type' => 'markup',
      '#markup' => '<div class="push-info-box px-1 py-2 px-sm-2 px-md-3 mb-16 bg-white border border-primary shadow rounded">',
    ];
    $form['date_title'] = [
      '#type' => 'markup',
      '#markup' => '<h4 class="text-primary mb-0">' . t("Rendez-vous") . '</h4>',
      '#prefix' => '<div class="d-flex justify-content-between align-items-center">',
    ];
    $form['edit_date'] = [
      '#type' => 'submit',
      '#value' => $this->t('Changer la date'),
      '#submit' => [[static::class, 'updatePageContext']],
      '#attributes' => [
        'class' => [
          'btn-empty',
          'btn-edit',
          'ml-auto',
          'suffix-icon-pencil',
        ],
      ],
      '#suffix' => '</div>',
    ];
    $form['push_rdv_divider'] = [
      '#type' => 'markup',
      '#markup' => '<hr class="hr-primary">',
    ];

    // Get choosen date.
    $appointment_date = $this->getReadableDate($appointment_date);
    $day_label = $this->t('Jour');
    $hour_label = $this->t('Heure');
    $appointment_date_day = $appointment_date['day'];
    $appointment_date_hour = $appointment_date['hour'];
    $date = '<div class="d-none d-md-flex"><div class="flex-fill w-50"><span class="mw-130 text-gray font-bold font-22">' . $day_label . ':</span><strong class="ml-16 font-22">' . $appointment_date_day . '</strong></div>'
      . '<div class="flex-fill w-50"><span class="mw-130 text-gray font-bold font-22">' . $hour_label . ':</span><strong class="ml-16 font-22">' . $appointment_date_hour . '</strong></div></div>';

    $date_mobile = '<div class="d-md-none"><div class="mb-xs d-flex justify-content-between"><span class="text-gray font-bold font-20">' . $day_label . ':</span><strong class="ml-10 font-20">' . $appointment_date_day . '</strong></div>'
      . '<div class="mb-xs d-flex justify-content-between"><span class="text-gray font-bold font-20">' . $hour_label . ':</span><strong class="ml-10 font-20">' . $appointment_date_hour . '</strong></div></div>';

    $form['date_info'] = [
      '#type' => 'markup',
      '#markup' => $date,
    ];
    $form['date_info_mobile'] = [
      '#type' => 'markup',
      '#markup' => $date_mobile,
    ];
    $form['appointment_rdv_push_closer'] = [
      '#type' => 'markup',
      '#markup' => '</div>',
    ];
    $form['submit'][$current_page] = [
      '#type' => 'submit',
      '#value' => $this->t('Confirmer'),
      '#attributes' => [
        'class' => [
          'ml-auto',
          'suffix-icon-chevrons-right',
        ],
      ],
      '#prefix' => '<div class="submit-wrapper d-flex">',
      '#suffix' => '</div>',
    ];
  }

  /**
   * Return confirmation page content.
   */
  public function getConfirmationPage(array &$form, FormStateInterface $form_state, $current_page, $values_storage) {
    $content = [];
    $content['agence'] = $this->appointmentAgencyPath;
    $content['type_confirmation'] = !empty($form_state->get('is_existing_appointment')) ? 'edit' : 'create';
    $content['user_can_edit_appointment'] = $this->config->get('user_can_edit_appointment');
    $datalayer_fields_infos = $this->config->get('datalayer_concerned_fields');
    $client_infos_fields = ['first_name', 'last_name', 'email', 'phone'];
    $datalayer_attributes = [];
    foreach ($datalayer_fields_infos as $key => $enabled) {
      if ($enabled) {
        if ($key === 'adviser') {
          $selected_adviser = $values_storage[0][$key];
          $adviser = \Drupal::service('entity_type.manager')->getStorage('user')
            ->load($selected_adviser);
          if ($adviser) {
            $adviser_mail = $adviser->get('mail')->value;
            $datalayer_attributes[$key] = $adviser_mail;
          }
        }
        elseif ($key === 'appointment_date') {
          $selected_date_infos = $values_storage[1][$key];
          $date_time = new \DateTime($selected_date_infos);
          $appointment_date = $date_time->format('d/m/Y H:i');
          $datalayer_attributes[$key] = $appointment_date;
        }
        elseif (in_array($key, $client_infos_fields)) {
          $client_submitted_data = $values_storage[2][$key];
          $datalayer_attributes[$key] = $client_submitted_data;
        }
      }
    }
    if (!empty($datalayer_attributes)) {
      $datalayer_snippet = '<script>dataLayer = [';
      $datalayer_snippet .= json_encode($datalayer_attributes);
      $datalayer_snippet .= '];</script>';
      $content['datalayer_snippet'] = $datalayer_snippet;
    }
    $form['confirm_creation_page'] = [
      '#theme' => 'appointment_confirmation_page',
      '#content' => $content,
    ];
  }

  /**
   * Set page title.
   */
  public function setPageTitle($title) {
    return [
      '#type' => 'markup',
      '#markup' => '<h4 class="form-title mb-4 text-uppercase">' . $title . '</h4>',
    ];
  }

  /**
   * Get general conditions block content.
   */
  public function getGeneralConditions() {
    $block = \Drupal::entityTypeManager()->getStorage('block_content')
      ->loadByProperties(['block_machine_name' => 'conditions_generales']);
    if ($block) {
      $block = array_values($block)[0];
      $block_content = \Drupal::entityTypeManager()
        ->getViewBuilder('block_content')->view($block);
      return $block_content;
    }
    return '';
  }

}
