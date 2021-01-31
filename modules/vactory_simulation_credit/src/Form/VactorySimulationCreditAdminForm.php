<?php

namespace Drupal\vactory_simulation_credit\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class VactorySimulationCreditAdminForm.
 *
 * @package Drupal\vactory_simulation_credit\Form
 */
class VactorySimulationCreditAdminForm extends ConfigFormBase {

  /**
   * Property: Number of path items in form.
   *
   * @var int
   */
  protected $profileItemTotal = 1;

  /**
   * Item id to remove.
   *
   * @var int
   */
  protected $itemToRemove;
  /**
   * Temporary config, to be used by the Remove button.
   *
   * @var array
   */
  protected $tempProfilesConfig = [];

  /**
   * Simulateur credit mode profile.
   *
   * @var array|mixed|null
   */
  protected $simulateurCfModeProfile;

  /**
   * Simulateur credit mode simulateur.
   *
   * @var array|mixed|null
   */
  protected $simulateurCfModeSimulateur;

  /**
   * Entity Type Manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Form state.
   *
   * @var \Drupal\Core\Form\FormStateInterface
   */
  protected $formState = NULL;

  /**
   * Class constructor.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, RequestStack $requestStack) {
    $this->tempProfilesConfig = $this->config('vactory_simulation_credit.settings')->get('profiles');
    $this->simulateurCfModeProfile = $this->config('vactory_simulation_credit.settings')->get('v_simulateur_cf_mode_profile');
    $this->simulateurCfModeSimulateur = $this->config('vactory_simulation_credit.settings')->get('v_simulateur_cf_mode_simulateur');
    $this->entityTypeManager = $entityTypeManager;
    $this->request = $requestStack;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('request_stack')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'vactory_simulation_credit.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'vactory_simulation_credit_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    if (is_null($this->formState)) {
      $this->formState = $form_state;
    }
    // Get config.
    $profiles = $this->tempProfilesConfig;
    // Disable caching on this form.
    $form_state->setCached(FALSE);

    // Get number of profiles already loaded into config.
    if (!empty($profiles) && !$form_state->get('ajax_pressed')) {
      $this->profileItemTotal = count($profiles) > 0 ? count($profiles) : 1;
    }

    // Load profiles Taxonomy.
    $entity_profiles = $this->entityTypeManager->getStorage('taxonomy_term')->loadByProperties(['vid' => 'simulation_credit_profiles']);
    if (isset($entity_profiles) && !empty($entity_profiles)) {
      $profiles_term = [];
      foreach ($entity_profiles as $key => $term) {
        $profiles_term[$key] = $term->get('name')->value;
      }
    }
    $form['#attached']['library'][] = 'core/jquery.form';
    $form['#attached']['library'][] = 'core/drupal.ajax';
    $form['#attached']['library'][] = 'vactory_simulation_credit/vactory_simulation_credit.simulation_style';
    // Build profiles container.
    $form['profiles'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Profile'),
        $this->t('Taux'),
        $this->t('Montant (Simulateur Credit)'),
        $this->t('Durée'),
        $this->t("Mensualité (Capacité d'emprunt)"),
        $this->t('Actions'),
      ],
      '#responsive' => FALSE,
      '#empty' => $this->t('No profiles.'),
      '#tableselect' => FALSE,
      '#prefix' => '<div class="table-wrapper">',
      '#suffix' => '</div>',
      '#attributes' => ['id' => 'profiles-container'],
    ];
    // Without profiles.
    $form['profiles'][0] = [
      '#type' => 'fieldset',
    ];
    $form['profiles'][0]['v_simulateur_cf_profile'] = [
      '#enable' => 'markup',
      '#markup' => $this->t('sans profile'),
    ];
    $form['profiles'][0]['simulateur_taux'] = [
      '#type' => 'details',
      '#title' => 'Taux',
      '#attributes' => [
        'style' => 'width:200px',
      ],
    ];
    $form['profiles'][0]['simulateur_taux']['v_simulateur_cf_taux'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Taux par defaux'),
      '#attributes' => [
        'style' => 'width:120px',
      ],
      '#default_value' => !empty($profiles[0]['simulateur_taux']['v_simulateur_cf_taux']) ? $profiles[0]['simulateur_taux']['v_simulateur_cf_taux'] : '',
    ];
    $form['profiles'][0]['simulateur_taux']['v_simulateur_cf_taux_min'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Taux min'),
      '#attributes' => [
        'style' => 'width:120px',
      ],
      '#default_value' => !empty($profiles[0]['simulateur_taux']['v_simulateur_cf_taux_min']) ? $profiles[0]['simulateur_taux']['v_simulateur_cf_taux_min'] : 0,
    ];
    $form['profiles'][0]['simulateur_taux']['v_simulateur_cf_taux_max'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Taux max'),
      '#attributes' => [
        'style' => 'width:120px',
      ],
      '#default_value' => !empty($profiles[0]['simulateur_taux']['v_simulateur_cf_taux_max']) ? $profiles[0]['simulateur_taux']['v_simulateur_cf_taux_max'] : '',
    ];
    $form['profiles'][0]['simulateur_montant'] = [
      '#type' => 'details',
      '#title' => 'Montants',
      '#attributes' => [
        'style' => 'width:200px',
      ],
    ];
    $form['profiles'][0]['simulateur_montant']['v_simulateur_cf_montant'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Montant par defaut'),
      '#attributes' => [
        'style' => 'width:200px',
      ],
      '#default_value' => !empty($profiles[0]['simulateur_montant']['v_simulateur_cf_montant']) ? $profiles[0]['simulateur_montant']['v_simulateur_cf_montant'] : '',
    ];
    $form['profiles'][0]['simulateur_montant']['v_simulateur_cf_montant_min'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Montant min'),
      '#required' => TRUE,
      '#attributes' => [
        'style' => 'width:200px',
      ],
      '#default_value' => !empty($profiles[0]['simulateur_montant']['v_simulateur_cf_montant_min']) ? $profiles[0]['simulateur_montant']['v_simulateur_cf_montant_min'] : 0,
    ];
    $form['profiles'][0]['simulateur_montant']['v_simulateur_cf_montant_max'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Montant max'),
      '#attributes' => [
        'style' => 'width:200px',
      ],
      '#default_value' => !empty($profiles[0]['simulateur_montant']['v_simulateur_cf_montant_max']) ? $profiles[0]['simulateur_montant']['v_simulateur_cf_montant_max'] : '',
    ];
    $form['profiles'][0]['simulateur_duree'] = [
      '#type' => 'details',
      '#title' => 'Durées',
      '#attributes' => [
        'style' => 'width:200px',
      ],
    ];
    $form['profiles'][0]['simulateur_duree']['v_simulateur_cf_duree'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Durée par defaut'),
      '#attributes' => [
        'style' => 'width:120px',
      ],
      '#default_value' => !empty($profiles[0]['simulateur_duree']['v_simulateur_cf_duree']) ? $profiles[0]['simulateur_duree']['v_simulateur_cf_duree'] : '',
    ];
    $form['profiles'][0]['simulateur_duree']['v_simulateur_cf_duree_min'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Durée min'),
      '#attributes' => [
        'style' => 'width:120px',
      ],
      '#default_value' => !empty($profiles[0]['simulateur_duree']['v_simulateur_cf_duree_min']) ? $profiles[0]['simulateur_duree']['v_simulateur_cf_duree_min'] : 0,
    ];
    $form['profiles'][0]['simulateur_duree']['v_simulateur_cf_duree_max'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Durée max'),
      '#attributes' => [
        'style' => 'width:120px',
      ],
      '#default_value' => !empty($profiles[0]['simulateur_duree']['v_simulateur_cf_duree_max']) ? $profiles[0]['simulateur_duree']['v_simulateur_cf_duree_max'] : '',
    ];
    $form['profiles'][0]['capacite_emprunt'] = [
      '#type' => 'details',
      '#title' => $this->t('Mensualités'),
      '#attributes' => [
        'style' => 'width:200px',
      ],
    ];
    $form['profiles'][0]['capacite_emprunt']['v_simulateur_cf_mensualite'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Mensualité par dafaut'),
      '#attributes' => [
        'style' => 'width:200px',
      ],
      '#default_value' => !empty($profiles[0]['capacite_emprunt']['v_simulateur_cf_mensualite']) ? $profiles[0]['capacite_emprunt']['v_simulateur_cf_mensualite'] : 0,
    ];
    $form['profiles'][0]['capacite_emprunt']['v_simulateur_cf_mensualite_min'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Mensualité min'),
      '#attributes' => [
        'style' => 'width:200px',
      ],
      '#default_value' => !empty($profiles[0]['capacite_emprunt']['v_simulateur_cf_mensualite_min']) ? $profiles[0]['capacite_emprunt']['v_simulateur_cf_mensualite_min'] : 0,
    ];
    $form['profiles'][0]['capacite_emprunt']['v_simulateur_cf_mensualite_max'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Mensualité max'),
      '#attributes' => [
        'style' => 'width:200px',
      ],
      '#default_value' => !empty($profiles[0]['capacite_emprunt']['v_simulateur_cf_mensualite_max']) ? $profiles[0]['capacite_emprunt']['v_simulateur_cf_mensualite_max'] : 0,
    ];
    // Profiles.
    for ($i = 1; $i < $this->profileItemTotal; $i++) {
      $form['profiles'][$i] = [
        '#type' => 'fieldset',
      ];
      $form['profiles'][$i]['v_simulateur_cf_profile'] = [
        '#type' => 'select',
        '#options' => $profiles_term,
        '#attributes' => [
          'style' => 'width:120px',
        ],
        '#required' => TRUE,
        '#default_value' => !empty($profiles[$i]['v_simulateur_cf_profile']) ? $profiles[$i]['v_simulateur_cf_profile'] : '',
      ];
      $form['profiles'][$i]['simulateur_taux'] = [
        '#type' => 'details',
        '#title' => 'Taux',
        '#attributes' => [
          'style' => 'width:200px',
        ],
      ];
      $form['profiles'][$i]['simulateur_taux']['v_simulateur_cf_taux'] = [
        '#type' => 'textfield',
        '#required' => TRUE,
        '#title' => $this->t('Taux par defaut'),
        '#attributes' => [
          'style' => 'width:120px',
        ],
        '#default_value' => !empty($profiles[$i]['simulateur_taux']['v_simulateur_cf_taux']) ? $profiles[$i]['simulateur_taux']['v_simulateur_cf_taux'] : '',
      ];
      $form['profiles'][$i]['simulateur_montant'] = [
        '#type' => 'details',
        '#title' => 'Montants',
        '#attributes' => [
          'style' => 'width:200px',
        ],
      ];
      $form['profiles'][$i]['simulateur_montant']['v_simulateur_cf_montant'] = [
        '#type' => 'textfield',
        '#required' => TRUE,
        '#title' => $this->t('Montant par defaut'),
        '#attributes' => [
          'style' => 'width:200px',
        ],
        '#default_value' => !empty($profiles[$i]['simulateur_montant']['v_simulateur_cf_montant']) ? $profiles[$i]['simulateur_montant']['v_simulateur_cf_montant'] : '',
      ];
      $form['profiles'][$i]['simulateur_montant']['v_simulateur_cf_montant_min'] = [
        '#type' => 'textfield',
        '#required' => TRUE,
        '#title' => $this->t('Montant min'),
        '#attributes' => [
          'style' => 'width:200px',
        ],
        '#default_value' => !empty($profiles[$i]['simulateur_montant']['v_simulateur_cf_montant_min']) ? $profiles[$i]['simulateur_montant']['v_simulateur_cf_montant_min'] : '',
      ];
      $form['profiles'][$i]['simulateur_montant']['v_simulateur_cf_montant_max'] = [
        '#type' => 'textfield',
        '#required' => TRUE,
        '#title' => $this->t('Montant max'),
        '#attributes' => [
          'style' => 'width:200px',
        ],
        '#default_value' => !empty($profiles[$i]['simulateur_montant']['v_simulateur_cf_montant_max']) ? $profiles[$i]['simulateur_montant']['v_simulateur_cf_montant_max'] : '',
      ];
      $form['profiles'][$i]['simulateur_duree'] = [
        '#type' => 'details',
        '#title' => 'Durées',
        '#attributes' => [
          'style' => 'width:200px',
        ],
      ];
      $form['profiles'][$i]['simulateur_duree']['v_simulateur_cf_duree'] = [
        '#type' => 'textfield',
        '#required' => TRUE,
        '#title' => $this->t('Durée par defaut'),
        '#attributes' => [
          'style' => 'width:120px',
        ],
        '#default_value' => !empty($profiles[$i]['simulateur_duree']['v_simulateur_cf_duree']) ? $profiles[$i]['simulateur_duree']['v_simulateur_cf_duree'] : '',
      ];
      $form['profiles'][$i]['simulateur_duree']['v_simulateur_cf_duree_min'] = [
        '#type' => 'textfield',
        '#required' => TRUE,
        '#title' => $this->t('Durée min'),
        '#attributes' => [
          'style' => 'width:120px',
        ],
        '#default_value' => !empty($profiles[$i]['simulateur_duree']['v_simulateur_cf_duree_min']) ? $profiles[$i]['simulateur_duree']['v_simulateur_cf_duree_min'] : '',
      ];
      $form['profiles'][$i]['simulateur_duree']['v_simulateur_cf_duree_max'] = [
        '#type' => 'textfield',
        '#required' => TRUE,
        '#title' => $this->t('Durée max'),
        '#attributes' => [
          'style' => 'width:120px',
        ],
        '#default_value' => !empty($profiles[$i]['simulateur_duree']['v_simulateur_cf_duree_max']) ? $profiles[$i]['simulateur_duree']['v_simulateur_cf_duree_max'] : '',
      ];
      $form['profiles'][$i]['capacite_emprunt'] = [
        '#type' => 'details',
        '#title' => $this->t('Mensualités'),
        '#attributes' => [
          'style' => 'width:200px',
        ],
      ];
      $form['profiles'][$i]['capacite_emprunt']['v_simulateur_cf_mensualite'] = [
        '#type' => 'textfield',
        '#required' => TRUE,
        '#title' => $this->t('Mensualité par dafaut'),
        '#attributes' => [
          'style' => 'width:200px',
        ],
        '#default_value' => !empty($profiles[$i]['capacite_emprunt']['v_simulateur_cf_mensualite']) ? $profiles[$i]['capacite_emprunt']['v_simulateur_cf_mensualite'] : 0,
      ];
      $form['profiles'][$i]['capacite_emprunt']['v_simulateur_cf_mensualite_min'] = [
        '#type' => 'textfield',
        '#required' => TRUE,
        '#title' => $this->t('Mensualité min'),
        '#attributes' => [
          'style' => 'width:200px',
        ],
        '#default_value' => !empty($profiles[$i]['capacite_emprunt']['v_simulateur_cf_mensualite_min']) ? $profiles[$i]['capacite_emprunt']['v_simulateur_cf_mensualite_min'] : 0,
      ];
      $form['profiles'][$i]['capacite_emprunt']['v_simulateur_cf_mensualite_max'] = [
        '#type' => 'textfield',
        '#required' => TRUE,
        '#title' => $this->t('Mensualité max'),
        '#attributes' => [
          'style' => 'width:200px',
        ],
        '#default_value' => !empty($profiles[$i]['capacite_emprunt']['v_simulateur_cf_mensualite_max']) ? $profiles[$i]['capacite_emprunt']['v_simulateur_cf_mensualite_max'] : 0,
      ];

      // Remove button.
      $form['profiles'][$i]['remove_item_' . $i] = [
        '#type'                    => 'submit',
        '#name'                    => 'remove_' . $i,
        '#value'                   => $this->t('Remove'),
        '#submit'                  => ['::removeItem'],
        // Since we are removing an item, don't validate until later.
        '#limit_validation_errors' => [],
        '#ajax'                    => [
          'callback' => [$this, 'ajaxCallback'],
          'wrapper'  => 'profiles-container',
        ],
      ];
    }
    $form['v_simulateur_cf_mode_profile'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Activer le mode profil'),
      '#description' => $this->t('Le mode profil permet de rajouter un champ profil au simulateur, la configuration du simulateur va dépendre du profil choisi.'),
      '#default_value' => !empty($this->simulateurCfModeProfile) ? $this->simulateurCfModeProfile : 0,
    ];
    $form['v_simulateur_cf_mode_simulateur'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Activer le mode simulation sans demande'),
      '#description' => $this->t('Ce mode va limiter le formulaire à une simulation simple sans formulaire de coordonnées et sans demande de crédit.'),
      '#default_value' => !empty($this->simulateurCfModeSimulateur) ? $this->simulateurCfModeSimulateur : 0,
    ];
    // Add item button.
    $form['profiles']['actions'] = [
      '#type' => 'actions',
      'add_item' => [
        '#type'   => 'submit',
        '#value'  => $this->t('Add a new profile'),
        '#submit' => ['::addItem'],
        '#ajax'   => [
          'callback' => [$this, 'ajaxCallback'],
          'wrapper'  => 'profiles-container',
        ],
      ],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * Implements callback for Ajax event.
   *
   * @param array $form
   *   From render array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Current state of form.
   *
   * @return array
   *   Container section of the form.
   */
  public function ajaxCallback(array $form, FormStateInterface $form_state) {
    $this->setCurrentRequestVariable('remove_pressed', FALSE);
    return $form['profiles'];
  }

  /**
   * Adds an item to form.
   *
   * @param array $form
   *   Setting form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state object.
   */
  public function addItem(array &$form, FormStateInterface $form_state) {
    $entity_profiles = $this->entityTypeManager->getStorage('taxonomy_term')->loadByProperties(['vid' => 'simulation_credit_profiles']);
    if (!isset($entity_profiles) || empty($entity_profiles)) {
      \Drupal::messenger()->addWarning($this->t('La liste des profiles est vide.'));
    }
    else {
      $form_state->set('ajax_pressed', TRUE);
      $this->profileItemTotal++;
      $form_state->setRebuild();
    }
  }

  /**
   * Removes an item from form.
   *
   * @param array $form
   *   Setting form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state object.
   */
  public function removeItem(array &$form, FormStateInterface $form_state) {
    $form_state->set('ajax_pressed', TRUE);
    $this->setCurrentRequestVariable('remove_pressed', TRUE);
    $this->profileItemTotal--;
    // Get triggering item id.
    $triggering_element = $form_state->getTriggeringElement();
    preg_match_all('!\d+!', $triggering_element['#name'], $matches);
    $item_id = (int) $matches[0][0];
    $this->itemToRemove = $item_id;
    // Remove item from config, reindex at 1, and set tempPathsConfig to it.
    unset($this->tempProfilesConfig[$item_id]);
    $this->tempProfilesConfig = array_combine(range(0, count($this->tempProfilesConfig) - 1), array_values($this->tempProfilesConfig));
    // Rebuild form.
    $form_state->setRebuild();
  }

  /**
   * Set volatile variable, specific to current request time.
   *
   * @param string $name
   *   Request variable name.
   * @param mixed $value
   *   Request variable value.
   */
  protected function setCurrentRequestVariable($name, $value) {
    $vars_identifier = sha1($this->request->getCurrentRequest()->server->get('REQUEST_TIME'));
    $vars = $this->formState->get($vars_identifier) ? $this->formState->get($vars_identifier) : [];
    $vars[$name] = $value;
    $this->formState->set($vars_identifier, $vars);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    $values = $form_state->getValues();
    if (isset($values['profiles']) && !empty($values['profiles'])) {
      foreach ($values['profiles'] as $key => $item) {
        if (isset($item['add_item'])) {
          continue;
        }
        // Tester si les valeurs sont des nombres.
        if (!is_numeric($item['simulateur_taux']['v_simulateur_cf_taux'])) {
          $form_state->setError($form['profiles'][$key]['simulateur_taux']['v_simulateur_cf_taux'], $this->t('taux is numeric'));
        }
        elseif ($item['simulateur_taux']['v_simulateur_cf_taux'] < 0) {
          $form_state->setError($form['profiles'][$key]['simulateur_taux']['v_simulateur_cf_taux'], $this->t('la valeur du v_simulateur_cf_taux doit être positive'));
        }

        // Var v_simulateur_cf_taux must be into v_simulateur_cf_taux min.
        // And v_simulateur_cf_taux max.
        if (isset($item['simulateur_taux']['v_simulateur_cf_taux_min']) && isset($item['simulateur_taux']['v_simulateur_cf_taux_max']) && is_numeric($item['simulateur_taux']['v_simulateur_cf_taux']) && ($item['simulateur_taux']['v_simulateur_cf_taux'] > $item['simulateur_taux']['v_simulateur_cf_taux_max'] || $item['simulateur_taux']['v_simulateur_cf_taux'] < $item['simulateur_taux']['v_simulateur_cf_taux_min'])) {
          $form_state->setError($form['profiles'][$key]['simulateur_taux']['v_simulateur_cf_taux'], $this->t('la valeur du v_simulateur_cf_taux doit être entre taux min et taux max.'));
        }

        // Var v_simulateur_cf_taux min.
        if (isset($item['simulateur_taux']['v_simulateur_cf_taux_min']) && !is_numeric($item['simulateur_taux']['v_simulateur_cf_taux_min'])) {
          $form_state->setError($form['profiles'][$key]['simulateur_taux']['v_simulateur_cf_taux_min'], $this->t('taux min is numeric'));
        }
        elseif (isset($item['simulateur_taux']['v_simulateur_cf_taux_min']) && $item['simulateur_taux']['v_simulateur_cf_taux_min'] < 0) {
          $form_state->setError($form['profiles'][$key]['simulateur_taux']['v_simulateur_cf_taux_min'], $this->t('la valeur du v_simulateur_cf_taux min doit être positive'));
        }

        // Var v_simulateur_cf_taux max.
        if (isset($item['simulateur_taux']['v_simulateur_cf_taux_max']) && !is_numeric($item['simulateur_taux']['v_simulateur_cf_taux_max'])) {
          $form_state->setError($form['profiles'][$key]['simulateur_taux']['v_simulateur_cf_taux_max'], $this->t('taux max is numeric'));
        }
        elseif (isset($item['simulateur_taux']['v_simulateur_cf_taux_max']) && $item['simulateur_taux']['v_simulateur_cf_taux_max'] < 0) {
          $form_state->setError($form['profiles'][$key]['simulateur_taux']['v_simulateur_cf_taux_max'], $this->t('la valeur du v_simulateur_cf_taux max doit être positive'));
        }

        // Default price.
        if (isset($item['simulateur_montant']['v_simulateur_cf_montant']) && !is_numeric($item['simulateur_montant']['v_simulateur_cf_montant'])) {
          $form_state->setError($form['profiles'][$key]['simulateur_montant']['v_simulateur_cf_montant'], $this->t('price is numeric'));
        }
        elseif ($item['simulateur_montant']['v_simulateur_cf_montant'] < 0) {
          $form_state->setError($form['profiles'][$key]['simulateur_montant']['v_simulateur_cf_montant'], $this->t('la valeur du montant doit être positive'));
        }

        // Default price must be into price min and max.
        if (is_numeric($item['simulateur_montant']['v_simulateur_cf_montant']) && ($item['simulateur_montant']['v_simulateur_cf_montant'] > $item['simulateur_montant']['v_simulateur_cf_montant_max'] || $item['simulateur_montant']['v_simulateur_cf_montant'] < $item['simulateur_montant']['v_simulateur_cf_montant_min'])) {
          $form_state->setError($form['profiles'][$key]['simulateur_montant']['v_simulateur_cf_montant'], $this->t('la valeur du montant doit être entre montant min et max.'));
        }

        // Max price.
        if (isset($item['simulateur_montant']['v_simulateur_cf_montant_max']) && !is_numeric($item['simulateur_montant']['v_simulateur_cf_montant_max'])) {
          $form_state->setError($form['profiles'][$key]['simulateur_montant']['v_simulateur_cf_montant_max'], $this->t('price max is numeric'));
        }
        elseif ($item['simulateur_montant']['v_simulateur_cf_montant_max'] < 0) {
          $form_state->setError($form['profiles'][$key]['simulateur_montant']['v_simulateur_cf_montant_max'], $this->t('la valeur du montant max doit être positive'));
        }

        // Min price.
        if (isset($item['simulateur_montant']['v_simulateur_cf_montant_min']) && !is_numeric($item['simulateur_montant']['v_simulateur_cf_montant_min'])) {
          $form_state->setError($form['profiles'][$key]['simulateur_montant']['v_simulateur_cf_montant_min'], $this->t('price min is numeric'));
        }
        elseif ($item['simulateur_montant']['v_simulateur_cf_montant_min'] < 0) {
          $form_state->setError($form['profiles'][$key]['simulateur_montant']['v_simulateur_cf_montant_min'], $this->t('la valeur du montant min max doit être positive'));
        }

        // Time.
        if (isset($item['simulateur_duree']['v_simulateur_cf_duree']) && !is_numeric($item['simulateur_duree']['v_simulateur_cf_duree'])) {
          $form_state->setError($form['profiles'][$key]['simulateur_duree']['v_simulateur_cf_duree'], $this->t('time min is numeric'));
        }
        elseif ($item['simulateur_duree']['v_simulateur_cf_duree'] < 0) {
          $form_state->setError($form['profiles'][$key]['simulateur_duree']['v_simulateur_cf_duree'], $this->t('la valeur du temp doit être positive'));
        }

        if (is_numeric($item['simulateur_duree']['v_simulateur_cf_duree']) && ($item['simulateur_duree']['v_simulateur_cf_duree'] > $item['simulateur_duree']['v_simulateur_cf_duree_max'] || $item['simulateur_duree']['v_simulateur_cf_duree'] < $item['simulateur_duree']['v_simulateur_cf_duree_min'])) {
          $form_state->setError($form['profiles'][$key]['simulateur_duree']['v_simulateur_cf_duree'], $this->t('la valeur du temp doit être entre temp min et max.'));
        }

        // Time max.
        if (isset($item['simulateur_duree']['v_simulateur_cf_duree_max']) && !is_numeric($item['simulateur_duree']['v_simulateur_cf_duree_max'])) {
          $form_state->setError($form['profiles'][$key]['simulateur_duree']['v_simulateur_cf_duree_max'], $this->t('time max is numeric'));
        }
        elseif ($item['simulateur_duree']['v_simulateur_cf_duree_max'] < 0) {
          $form_state->setError($form['profiles'][$key]['simulateur_duree']['v_simulateur_cf_duree_max'], $this->t('la valeur du temp max doit être positive'));
        }

        // Time min.
        if (isset($item['simulateur_duree']['v_simulateur_cf_duree_min']) && !is_numeric($item['simulateur_duree']['v_simulateur_cf_duree_min'])) {
          $form_state->setError($form['profiles'][$key]['simulateur_duree']['v_simulateur_cf_duree_min'], $this->t('time min is numeric'));
        }
        elseif ($item['simulateur_duree']['v_simulateur_cf_duree_min'] < 0) {
          $form_state->setError($form['profiles'][$key]['simulateur_duree']['v_simulateur_cf_duree_min'], $this->t('la valeur du temp min doit être positive'));
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    // Add to config.
    $profiles_values = $form_state->getValue('profiles');
    $mode_profile = $form_state->getValue('v_simulateur_cf_mode_profile');
    $mode_simulateur = $form_state->getValue('v_simulateur_cf_mode_simulateur');
    unset($profiles_values['actions']);
    foreach ($profiles_values as $key => &$value) {
      unset($value['remove_item_' . $key]);
    }
    $this->config('vactory_simulation_credit.settings')
      ->set('profiles', $profiles_values)
      ->set('v_simulateur_cf_mode_profile', $mode_profile)
      ->set('v_simulateur_cf_mode_simulateur', $mode_simulateur)
      ->save();
  }

}
