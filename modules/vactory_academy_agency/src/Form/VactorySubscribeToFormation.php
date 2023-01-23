<?php

namespace Drupal\vactory_academy_agency\Form;

use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;
use Drupal\user\Entity\User;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Provide subscribe to formation form.
 *
 * @package Drupal\vactory_academy_agency\Form
 */
class VactorySubscribeToFormation extends FormBase {

  /**
   * Form Page index count (Confirmation page is excluded).
   *
   * @var int
   */
  const PAGE_COUNT = 2;

  /**
   * Form confirmation page index.
   *
   * @var int
   */
  const CONFIRMATION_PAGE_INDEX = 3;

  /**
   * Current agency ID.
   *
   * @var int
   */
  private $agencyID;

  /**
   * Current agency name.
   *
   * @var string
   */
  private $agencyName;

  /**
   * Current agency url parameter.
   *
   * @var string
   */
  private $agencyPath;

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
    return 'vactory_academy_agency_subscriber';
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
    if (!$this->isSetAgency()) {
      // If no agency has been specified in the path then redirect to 404 page.
      throw new NotFoundHttpException();
    }
    if (empty($form_state->get('current_page'))) {
      // Initialize page index.
      $form_state->set('current_page', 0);
      $form_state->set('values_storage', []);
      $form_state->setRebuild(TRUE);
      $this->damFormationSettings = [];
    }
    // Get current page index.
    $current_page = $form_state->get('current_page');
    // Get current page storage.
    $values_storage = $form_state->get('values_storage');
    switch ($current_page) {
      case 0:
        // First page case.
        $this->getFormationsTypes($form, $form_state, $current_page, $values_storage);
        break;

      case 1:
        // Second page case.
        $this->getSelectFormationsPage($form, $form_state, $current_page, $values_storage);
        break;

      case 2:
        // Third page case.
        $this->getStudentInfoPage($form, $form_state, $current_page, $values_storage);
        break;

      case 3:
        // Confirm registration page case.
        $this->getConfirmationPage($form, $form_state, $current_page, $values_storage);
        break;

      default:
        break;
    }

    // Submit button wrapper opener.
    $form['submit_wrapper_opner'] = [
      '#type' => 'markup',
      '#markup' => '<div class="submit-wrapper d-flex justify-content-between">',
    ];
    if ($current_page < self::PAGE_COUNT) {
      if (!$form_state->get('no_formations_founded')) {
        // Form next button.
        $form['next'][$current_page] = [
          '#type' => 'submit',
          '#value' => $this->t('Suivant'),
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
    if ($current_page == self::PAGE_COUNT) {
      // Form next button.
      $form['next'][$current_page] = [
        '#type' => 'submit',
        '#value' => $this->t('Envoyer'),
        '#attributes' => [
          'class' => [
            'mt-2',
            'ml-auto',
            'suffix-icon-chevrons-right',
          ],
        ],
      ];
    }
    // Submit button wrapper closer.
    $form['submit_wrapper_closer'] = [
      '#type' => 'markup',
      '#markup' => '</div>',
    ];
    $form['#cache'] = ['max-age' => 0];

    return $form;
  }

  /**
   * Form validation.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $values_storage = $form_state->get('values_storage');
    if (isset($values['type_formation']) && empty($values['type_formation'])) {
      $form_state->setErrorByName('type_formation', $this->t("Veuillez choisir le type de formation auquel vous souhaitez vous inscrire"));
    }
    if (isset($values['agency_academies']) && empty($values['agency_academies'])) {
      $form_state->setErrorByName('agency_academies', $this->t("Veuillez choisir la formation à laquelle vous souhaitez vous inscrire"));
    }
    if (isset($values['agency_academies']) && !empty($values['agency_academies'])) {
      $formation = \Drupal::entityTypeManager()->getStorage('node')
        ->load($values['agency_academies']);
      $nb_places = $formation->get('field_nombre_places')->value;
      $available_nb_places = $this->getAvailableNbPlace($nb_places, $values_storage[0]['agency_id'], $values['agency_academies']);
      if ($available_nb_places === 0) {
        $form_state->setErrorByName('agency_academies', $this->t("Toutes les places pour la formation «@title» ont été réservées.", ['@title' => $formation->label()]));
      }
    }
    if (isset($values['phone']) && !empty($values['phone'])) {
      $course_id = $values_storage[1]['agency_academies'];
      $properties = [
        'vid' => 'academy_subscribers',
        'field_agence_formation' => $values_storage[0]['agency_id'],
        'field_subscriber_course' => (int) $course_id,
        'field_subscriber_telephone' => $values['phone'],
      ];
      $formation = \Drupal::entityTypeManager()->getStorage('node')
        ->load($course_id);
      $exiting_inscriptions = \Drupal::entityTypeManager()->getStorage('taxonomy_term')
        ->loadByProperties($properties);
      if (!empty($exiting_inscriptions)) {
        $form_state->setErrorByName('phone', $this->t("Vous êtes déjà inscrit dans la formation «@title».", ['@title' => $formation->label()]));
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
    $langcode = \Drupal::languageManager()->getCurrentLanguage()->getId();
    // Get form submitted values.
    $values_storage = $form_state->get('values_storage');
    $current_page_index = $form_state->get('current_page');
    $values_storage[$current_page_index] = $form_state->getValues();
    $course = $values_storage[1]['agency_academies'];
    $nom = $values_storage[2]['last_name'];
    $prenom = $values_storage[2]['first_name'];
    $phone = $values_storage[2]['phone'];
    $email = $values_storage[2]['email'];
    $customer_type = $values_storage[2]['type_client'];
    $formation = Node::load($course);
    $titre_formation = $formation->label();
    // Generate submission title.
    $inscription_title = ucfirst($prenom) . ' ' . strtoupper($nom) . " s'est inscrit à la formation «" . $titre_formation . '»';
    // Create new formation inscription..
    $term = Term::create([
      'vid' => 'academy_subscribers',
      'name' => $inscription_title,
      'field_agence_formation' => $values_storage[0]['agency_id'],
      'field_subscriber_course' => $course,
      'field_subscriber_last_name' => $nom,
      'field_subscriber_first_name' => $prenom,
      'field_subscriber_telephone' => $phone,
      'field_subscriber_mail' => $email,
      'field_customer_type' => $customer_type,
    ]);
    try {
      $term->save();
      $form_state->set('current_page', self::CONFIRMATION_PAGE_INDEX);
      $form_state->setRebuild(TRUE);
    }
    catch (EntityStorageException $e) {
      \Drupal::messenger()->addWarning($this->t('Une erreur est survenue lors de la sauvegarde de votre inscription, Veuillez réessayer plus tard.'));
      \Drupal::logger('vactory_academy_agency')->warning($e->getMessage());
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
    if ($triggering_element_type == 'next') {
      if (isset($submitted_page_index) && $submitted_page_index === 0) {
        $submitted_values = $form_state->getValues();
        if (isset($submitted_values['type_formation']) && $submitted_values['type_formation'] == 'online') {
          $new_page_index = 3;
        }
        else {
          $new_page_index = ++$triggering_element_page;
        }
      }
      else {
        $new_page_index = ++$triggering_element_page;
      }
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
   * Check if current agency  is set or not.
   */
  public function isSetAgency() {
    // Get current language code.
    $langcode = \Drupal::languageManager()->getCurrentLanguage()->getId();
    // Get current agency term.
    $agency = \Drupal::routeMatch()->getParameter('agency_path');
    $agency_properties = [
      'type' => 'vactory_locator',
      'field_agency_path' => $agency,
      'status' => 1,
    ];
    $agency_entities = \Drupal::entityTypeManager()
      ->getStorage('locator_entity')
      ->loadByProperties($agency_properties);
    if (!empty($agency_entities)) {
      // Check if the agency is enabled on borne.
      $agency_entity = array_values($agency_entities)[0];
      $is_agency_borne = $agency_entity->get('field_is_agence_borne')->value;
      if (!$is_agency_borne) {
        return FALSE;
      }
      // Store current agency path, ID and name.
      $this->agencyPath = $agency;
      $this->agencyID = $agency_entity->id();
      // Get translated agency term.
      $translated_agency_term = \Drupal::service('entity.repository')
        ->getTranslationFromContext($agency_entity, $langcode);
      $this->agencyName = $translated_agency_term->get('name')->value;
    }

    return !empty($agency_entities);
  }

  /**
   * Format date to a human readable date format.
   */
  public function getReadableDate($date) {
    $langcode = \Drupal::languageManager()->getCurrentLanguage()->getId();
    $date = str_replace('T', ' ', $date);
    $date = \DateTime::createFromFormat('Y-m-d H:i:s', $date)->getTimestamp();
    $formatted_date = \Drupal::service('date.formatter')
      ->format($date, 'custom', 'l d F Y - H:i', NULL, $langcode);
    return $formatted_date;
  }

  /**
   * Get existings formations types.
   */
  public function getFormationsTypes(array &$form, FormStateInterface $form_state, $current_page, $values_storage) {
    $content = [];
    $langcode = \Drupal::languageManager()->getCurrentLanguage()->getId();
    // Load existing formation types terms.
    $types_formation = \Drupal::entityTypeManager()->getStorage('taxonomy_term')
      ->loadByProperties(['vid' => 'academy_types_formation']);
    $options = [];
    $renderer = \Drupal::service('renderer');
    $entity_repository = \Drupal::service('entity.repository');
    $file_url_generator = \Drupal::service('file_url_generator');
    foreach ($types_formation as $type_formation) {
      // Get the term translation.
      $type_formation = $entity_repository->getTranslationFromContext($type_formation, $langcode);
      $type = $type_formation->get('field_type_formation')->value;
      $content['title'] = $type_formation->get('name')->value;
      $mid = $type_formation->get('field_type_formation_image')->target_id;
      $media = Media::load($mid);
      $fid = $media->field_media_image->target_id;
      $file = File::load($fid);
      if ($file) {
        $content['image_uri'] = $file_url_generator->generateAbsoluteString($file->get('uri')->value);
      }
      // Get formation preview.
      $formation_type_preview = [
        '#theme' => 'vactory_academy_agency_types',
        '#content' => $content,
      ];
      $options[$type] = $renderer->render($formation_type_preview);
    }
    // Page title.
    $form['title'] = $this->setPageTitle($this->t('Formations et Webinars'));

    $existing_values = $form_state->get('existing_values');
    $existing_adviser = isset($existing_values[$current_page]) ? $existing_values[$current_page]['adviser'] : '';
    // Formation type list form element.
    $form['type_formation'] = [
      '#type' => 'radios',
      '#options' => $options,
      '#validated' => TRUE,
      '#default_value' => isset($values_storage[$current_page]['adviser']) ? $values_storage[$current_page]['adviser'] : $existing_adviser,
      '#attributes' => [
        'class' => ['select-formation-radio', 'hidden-radio'],
      ],
      '#required' => TRUE,
      '#prefix' => '<div class="select-formation-wrapper card-radio-wrapper">',
      '#suffix' => '</div>',
    ];

    $form['agency_id'] = [
      '#type' => 'hidden',
      '#value' => $this->agencyID,
    ];
    $form['agency_path'] = [
      '#type' => 'hidden',
      '#value' => $this->agencyPath,
    ];
  }

  /**
   * Get select formation page content.
   */
  public function getSelectFormationsPage(array &$form, FormStateInterface $form_state, $current_page, $values_storage) {
    // Load current agency academies.
    $properties = [
      'type' => 'vactory_academy_agency',
      'field_academy_agence' => $this->agencyID,
      'status' => 1,
    ];
    $courses = \Drupal::entityTypeManager()->getStorage('node')
      ->loadByProperties($properties);
    $nb_blocked_course = 0;
    foreach ($courses as $course) {
      // Get academy places count.
      $nb_place = $course->get('field_nombre_places')->value;
      // Get available nb places for current course.
      $nb_place = $this->getAvailableNbPlace($nb_place, $this->agencyID, $course->id());
      if ($nb_place <= 0) {
        $nb_blocked_course++;
      }
    }
    if (!empty($courses) && count($courses) > $nb_blocked_course) {
      $options = $this->getFormationsAsOptions($courses, $this->agencyID);
      // Page title.
      $form['title'] = $this->setPageTitle(t("Formations en présentiel, dans le centre @agence - Dar Al Macharii", ['@agence' => $this->agencyName]));
      // Select agency academies radios element.
      $form['agency_academies'] = [
        '#type' => 'radios',
        '#options' => $options,
        '#default_value' => isset($values_storage[$current_page]['agency_academies']) ? $values_storage[$current_page]['agency_academies'] : '',
        '#validated' => TRUE,
        '#required' => TRUE,
        '#attributes' => [
          'class' => [
            'select-formation-radio',
            'hidden-radio',
          ],
        ],
        '#prefix' => '<div class="select-formation-wrapper card-radio-wrapper card-radio--inline">',
        '#suffix' => '</div>',
      ];
      $form_state->set('no_formations_founded', FALSE);
    }
    else {
      // Page title.
      $form['title'] = $this->setPageTitle(t("Découvrez toutes les formations de Dar Al Macharii chaque mercredi..."));
      // In case the current agency has no academy node.
      $message = $this->t("Un programme de formations vous sera proposé très prochainement.");
      $form['message'] = [
        '#type' => 'markup',
        '#markup' => '<div class="not-found-message pt-3 d-flex flex-column"><a href="#" class="js-trigger-hp close mb-2 ml-auto"><i class="icon-close-bold"></i></a><h3 class="formation-not-found d-block text-white text-uppercase bg-primary rounded shadow p-2 py-md-3 px-md-6 m-0">' . $message . '</h3></div>',
      ];
      $form_state->set('no_formations_founded', TRUE);
    }
  }

  /**
   * Get agency formations as radios options format.
   */
  public function getFormationsAsOptions($courses, $agency_id) {
    $langcode = \Drupal::languageManager()->getCurrentLanguage()->getId();
    $options = [];
    $renderer = \Drupal::service('renderer');
    $entity_repository = \Drupal::service('entity.repository');
    foreach ($courses as $nid => $course) {
      // Get academy node translation.
      $translated_course = $entity_repository->getTranslationFromContext($course, $langcode);
      // Get academy title.
      $title = $translated_course->get('title')->value;
      // Get academy places count.
      $nb_place = $translated_course->get('field_nombre_places')->value;
      // Get available nb places for current course.
      $nb_place = $this->getAvailableNbPlace($nb_place, $agency_id, $course->id());
      // Get academy Date.
      $date = $translated_course->get('field_academy_date')->value;
      // Format academy date to human readable date.
      $date = $this->getReadableDate($date);
      // Get academy instructor info.
      $enseignant_id = $translated_course->get('field_vactory_instructor')->target_id;
      $enseignant = $enseignant_id ? User::load($enseignant_id) : NULL;
      if ($enseignant) {
        $enseignant_first_name = $enseignant->get('field_first_name')->value;
        $enseignant_last_name = $enseignant->get('field_last_name')->value;
        $enseignant_full_name = $enseignant_first_name ? ucfirst($enseignant_first_name) : '';
        $enseignant_full_name .= !empty($enseignant_full_name) && $enseignant_last_name ? ' ' : '';
        $enseignant_full_name .= $enseignant_last_name ? strtoupper($enseignant_last_name) : '';
      }
      $module_id = $translated_course->get('field_formation_agency_module')->target_id;
      $module = $module_id ? \Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($module_id) : NULL;
      if ($module) {
        // Get academy module translation.
        $translated_module = $entity_repository->getTranslationFromContext($module, $langcode);
        $module_name = $translated_module->getName();
      }
      // Get academy description.
      $description = $translated_course->get('field_vactory_excerpt')->value;
      /* $description = substr(strip_tags($description), 0, 135) . '...'; */
      // Prepare academy content to be used in view template.
      $content = [
        'title' => $title,
        'nb_place' => $nb_place,
        'date' => $date,
        'enseignant_full_name' => isset($enseignant_full_name) ? $enseignant_full_name : NULL,
        'description' => $description,
        'node_id' => $nid,
        'module_name' => isset($module_name) ? $module_name : NULL,
      ];
      // Get the academy node preview.
      $course_preview = [
        '#theme' => 'formation_select_card',
        '#content' => $content,
      ];
      // Add academy node to user select options.
      $options[$nid] = $renderer->render($course_preview);
    }
    return $options;
  }

  /**
   * Get student infos form elements.
   */
  public function getStudentInfoPage(array &$form, FormStateInterface $form_state, $current_page, $values_storage) {
    // Page title.
    $form['title'] = $this->setPageTitle(t("Renseignez vos informations pour vous inscrire"));

    $form['form_wrapper_opner'] = [
      '#type' => 'markup',
      '#markup' => '<div class="user-info-wrapper"><div class="user-informations bg-white border border-primary rounded h-100">',
    ];
    $form['type_client'] = [
      '#type' => 'select',
      '#options' => $this->getCustomerTypes(),
      '#default_value' => $this->getDefaultCustomerType(),
      '#required' => TRUE,
    ];
    $form['last_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Nom'),
      '#default_value' => isset($values_storage[$current_page]['last_name']) ? $values_storage[$current_page]['last_name'] : '',
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
      '#title' => $this->t('Prénom'),
      '#default_value' => isset($values_storage[$current_page]['first_name']) ? $values_storage[$current_page]['first_name'] : '',
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
      '#title' => $this->t('Téléphone'),
      '#default_value' => isset($values_storage[$current_page]['phone']) ? $values_storage[$current_page]['phone'] : '',
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
      '#default_value' => isset($values_storage[$current_page]['email']) ? $values_storage[$current_page]['email'] : '',
      '#attributes' => [
        'class' => [
          'prefix-icon-mail',
        ],
        'placeholder' => $this->t('Adresse e-mail'),
      ],
    ];
    $form['accept_conditions'] = [
      '#type' => 'checkbox',
      '#title' => $this->t("En cochant cette case, j'accepte et je reconnais avoir pris connaissance des conditions générales d'utilisation."),
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
      '#markup' => '</div></div>',
    ];
  }

  /**
   * Return confirmation page content.
   */
  public function getConfirmationPage(array &$form, FormStateInterface $form_state, $current_page, $values_storage) {
    $content = [];
    $langcode = \Drupal::languageManager()->getCurrentLanguage()->getId();
    if ($values_storage[0]['type_formation'] == 'online') {
      $properties = [
        'vid' => 'academy_types_formation',
        'field_type_formation' => 'online',
      ];
      $types_formation = \Drupal::entityTypeManager()->getStorage('taxonomy_term')
        ->loadByProperties($properties);
      if (!empty($types_formation)) {
        $types_formation = \Drupal::service('entity.repository')
          ->getTranslationFromContext($types_formation[array_keys($types_formation)[0]], $langcode);
        $content['title'] = $types_formation->get('name')->value;
      }
      $content['type_confirmation'] = 'online';
    }
    else {
      $content['type_confirmation'] = 'local';
      $content['agency_path'] = $this->agencyPath;
    }

    $client_infos_fields = ['first_name', 'last_name', 'email', 'phone'];
    $datalayer_fields_infos = \Drupal::config('vactory_academy_agency.settings')->get('datalayer_concerned_fields');
    $datalayer_attributes = [];
    foreach ($datalayer_fields_infos as $key => $enabled) {
      if ($enabled) {
        if ($key === 'agency_academies') {
          $selected_academy = $values_storage[0][$key];
          $academy = \Drupal::service('entity_type.manager')->getStorage('node')
            ->load($selected_academy);
          if ($academy) {
            $academy_title = $academy->label();
            $datalayer_attributes['academy'] = $academy_title;
          }
        }
        elseif ($key === 'type_client') {
          $customer_type = \Drupal::entityTypeManager()->getStorage('taxonomy_term')
            ->load($values_storage[1][$key]);
          if ($customer_type) {
            $datalayer_attributes[$key] = $customer_type->label();
          }
        }
        elseif (in_array($key, $client_infos_fields)) {
          $client_submitted_data = $values_storage[1][$key];
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
      '#theme' => 'formation_confirmation_page',
      '#content' => $content,
    ];
  }

  /**
   * Set page title.
   */
  public function setPageTitle($title) {
    return [
      '#type' => 'markup',
      '#markup' => '<h2 class="form-title text-uppercase h4 mb-5">' . $title . '</h2>',
    ];
  }

  /**
   * Get available nb places for the given course.
   */
  public function getAvailableNbPlace($nb_place, $agency_id, $formation_id) {
    $properties = [
      'vid' => 'academy_subscribers',
      'field_agence_formation' => $agency_id,
      'field_subscriber_course' => (int) $formation_id,
    ];
    $exiting_inscriptions = \Drupal::entityTypeManager()->getStorage('taxonomy_term')
      ->loadByProperties($properties);
    return (int) $nb_place - count($exiting_inscriptions);
  }

  /**
   * Get general conditions block content.
   */
  public function getGeneralConditions() {
    $block = \Drupal::entityTypeManager()->getStorage('block_content')
      ->loadByProperties(['block_machine_name' => 'borne_conditions_generales']);
    if ($block) {
      $block = array_values($block)[0];
      $block_content = \Drupal::entityTypeManager()
        ->getViewBuilder('block_content')->view($block);
      return $block_content;
    }
    return '';
  }

  /**
   * Get customer types options.
   */
  public function getCustomerTypes() {
    $options = [];
    $customer_types = \Drupal::entityTypeManager()->getStorage('taxonomy_term')
      ->loadByProperties(['vid' => 'types_client']);
    if ($customer_types) {
      $options = array_map(function ($customer_type) {
        $langcode = \Drupal::languageManager()->getCurrentLanguage()->getId();
        $customer_type = \Drupal::service('entity.repository')
          ->getTranslationFromContext($customer_type, $langcode);
        return $customer_type->getName();
      }, $customer_types);
    }
    return $options;
  }

  /**
   * Get default customer type.
   */
  public function getDefaultCustomerType() {
    $customer_types = \Drupal::entityTypeManager()->getStorage('taxonomy_term')
      ->loadByProperties(['vid' => 'types_client']);
    foreach ($customer_types as $tid => $customer_type) {
      $position = strpos(strtolower($customer_type->getName()), 'nouveau');
      if ($position > 0 || $position === 0) {
        return $tid;
      }
    }
    return NULL;
  }

}
