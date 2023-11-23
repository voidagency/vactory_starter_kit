<?php

namespace Drupal\vactory_content_package\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure Vactory content package settings for this site.
 */
class ContentPackageExportForm extends FormBase {

  const FORM_AJAX_WRAPPER = 'content-package-export-form';

  /**
   * Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritDoc}
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritDoc}
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
    return 'vactory_content_package_export';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $nodes = $form_state->get('nodes') ?? [];
    $partial_export = $form_state->get('partial_export') ?? 0;
    $form = [
      '#prefix' => '<div id="' . static::FORM_AJAX_WRAPPER . '">',
      '#suffix' => '</div>',
    ];
    $form['partial_export'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Partial export'),
      '#description' => $this->t('Check this to only export specific pages'),
      '#default_value' => $partial_export,
    ];
    $form['partial_export_wrapper'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Partial export'),
      '#states' => [
        'visible' => [
          'input[name="partial_export"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['partial_export_wrapper']['input'] = [
      '#type' => 'entity_autocomplete',
      '#title' => t('Page'),
      '#target_type' => 'node',
      '#selection_settings' => [
        'target_bundles' => ['vactory_page'],
      ],
      '#description' => $this->t("Please select the desired page..."),
      '#ajax' => [
        'event' => 'autocompleteclose',
        'callback' => [$this, 'closeAutocompleteCallback'],
      ],
    ];

    if (!empty($nodes)) {
      $form['partial_export_wrapper']['nodes'] = [
        '#type' => 'table',
        '#header' => [
          'nid' => $this->t('Node ID'),
          'node_title' => $this->t('Page Title'),
          'action' => $this->t('Operation'),
        ],
        '#sticky' => TRUE,
      ];
      $nodes = $this->entityTypeManager->getStorage('node')
        ->loadMultiple($nodes);
      foreach ($nodes as $node) {
        $form['partial_export_wrapper']['nodes'][] = [
          'nid' => [
            '#markup' => $node->id(),
          ],
          'node_title' => [
            '#markup' => $node->label(),
          ],
          'action' => [
            '#type' => 'submit',
            '#value' => $this->t('Remove'),
            '#name' => "remove_node_{$node->id()}",
            '#submit' => [[$this, 'removeNodeSubmit']],
            '#attributes' => [
              'class' => ['button', 'button--danger'],
            ],
            '#ajax' => [
              'event' => 'click',
              'callback' => [$this, 'updateForm'],
              'wrapper' => static::FORM_AJAX_WRAPPER,
            ],
          ],
        ];
      }
    }

    $form['partial_export_wrapper']['update'] = [
      '#type' => 'submit',
      '#name' => 'update_cp_export_form',
      '#value' => $this->t('Update'),
      '#submit' => [[$this, 'updateFormSubmit']],
      '#attributes' => [
        'class' => ['js-hide'],
      ],
      '#ajax' => [
        'event' => 'click',
        'callback' => [$this, 'updateForm'],
        'wrapper' => static::FORM_AJAX_WRAPPER,
      ],
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t("Start export process"),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * Close autocomplete callback.
   */
  public function closeAutocompleteCallback(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $response->addCommand(new InvokeCommand("input[name=\"update_cp_export_form\"]", 'click', []));
    return $response;
  }

  /**
   * Update form.
   */
  public function updateForm(array &$form, FormStateInterface $form_state) {
    return $form;
  }

  /**
   * Update form submit callback.
   */
  public function updateFormSubmit(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $nodes = $form_state->get('nodes') ?? [];
    $user_inputs = $form_state->getUserInput();
    if (isset($values['input']) && !empty($values['input'])) {
      $nodes[] = $values['input'];
      $nodes = array_unique($nodes);
      $form_state->set('nodes', $nodes);
    }
    $partial_export = $values['partial_export'] ?? 0;
    $user_inputs['input'] = '';
    $form_state->setUserInput($user_inputs);
    $form_state->set('partial_export', $partial_export);
    $form_state->setRebuild();
  }

  /**
   * Remove node submit callback.
   */
  public function removeNodeSubmit(array &$form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    $nid = str_replace('remove_node_', '', $triggering_element['#name']);
    $nodes = $form_state->get('nodes') ?? [];
    $nid_index = array_search($nid, $nodes);
    unset($nodes[$nid_index]);
    $form_state->set('nodes', $nodes);
    $form_state->setRebuild();
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $nodes = $form_state->get('nodes') ?? NULL;
    if (!empty($nodes)) {
      $nodes = array_values($nodes);
    }
    \Drupal::service('vactory_content_package.archiver.manager')
      ->zipContentTypeNodes('vactory_page', $nodes);
  }

}
