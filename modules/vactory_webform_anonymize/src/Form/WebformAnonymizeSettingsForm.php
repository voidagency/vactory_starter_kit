<?php

namespace Drupal\vactory_webform_anonymize\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Entity\Role;
use Drupal\webform\Entity\Webform;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Webform anonymize settings form.
 */
class WebformAnonymizeSettingsForm extends FormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new WebformAnonymizeSettingsForm.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'webform_anonymize_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, Webform $webform = NULL) {
    $form_state->set('webform', $webform);

    $third_party_settings = $webform->getThirdPartySetting('vactory_webform_anonymize', 'settings', []);

    $form['enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable anonymization'),
      '#default_value' => $third_party_settings['enabled'] ?? FALSE,
      '#description' => $this->t('Enable data anonymization for this webform.'),
    ];

    $rolesEntities = Role::loadMultiple();
    $roles = [];
    foreach ($rolesEntities as $role) {
      $roles[$role->id()] = $role->label();
    }
    unset($roles['administrator']);
    unset($roles['anonymous']);

    $form['roles'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Roles to anonymize'),
      '#options' => $roles,
      '#default_value' => $third_party_settings['roles'] ?? [],
      '#description' => $this->t('Select roles that should see anonymized data. Administrators always see full data.'),
      '#states' => [
        'visible' => [
          ':input[name="enabled"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['anonymize_mode'] = [
      '#type' => 'radios',
      '#title' => $this->t('Anonymization mode'),
      '#options' => [
        'half' => $this->t('Show first half, mask the rest (e.g., "John****")'),
        'partial' => $this->t('Show first 2 characters, mask the rest (e.g., "Jo********")'),
        'full' => $this->t('Mask everything (e.g., "**********")'),
        'custom' => $this->t('Custom: Show first N characters'),
      ],
      '#default_value' => $third_party_settings['anonymize_mode'] ?? 'half',
      '#states' => [
        'visible' => [
          ':input[name="enabled"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['custom_chars'] = [
      '#type' => 'number',
      '#title' => $this->t('Number of characters to show'),
      '#default_value' => $third_party_settings['custom_chars'] ?? 3,
      '#min' => 1,
      '#max' => 50,
      '#states' => [
        'visible' => [
          ':input[name="enabled"]' => ['checked' => TRUE],
          ':input[name="anonymize_mode"]' => ['value' => 'custom'],
        ],
      ],
    ];

    $form['mask_character'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Mask character'),
      '#default_value' => $third_party_settings['mask_character'] ?? '*',
      '#maxlength' => 1,
      '#size' => 5,
      '#description' => $this->t('Character to use for masking (default: *)'),
      '#states' => [
        'visible' => [
          ':input[name="enabled"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save settings'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $webform = $form_state->get('webform');

    $settings = [
      'enabled' => (bool) $form_state->getValue('enabled'),
      'roles' => array_filter($form_state->getValue('roles')),
      'anonymize_mode' => $form_state->getValue('anonymize_mode'),
      'custom_chars' => (int) $form_state->getValue('custom_chars'),
      'mask_character' => $form_state->getValue('mask_character'),
    ];

    $webform->setThirdPartySetting('vactory_webform_anonymize', 'settings', $settings);
    $webform->save();

    $this->messenger()
      ->addStatus($this->t('Anonymization settings have been saved.'));
  }

}
