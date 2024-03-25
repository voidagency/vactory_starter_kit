<?php

namespace Drupal\vactory_content_package\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Migration import form.
 */
class ContentPackageImportFormConfirm extends FormBase {

  /**
   * Content types.
   */
  const CONTENT_TYPES = ['vactory_page'];

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
    return 'vactory_content_package.import_confirmation';
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
    $is_page_delete = $form_state->get('is_page_delete') ?? 0;
    $is_block_delete = $form_state->get('is_block_delete') ?? 0;
    $is_menu_delete = $form_state->get('is_menu_delete') ?? 0;

    $form['is_page_delete'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Delete existing pages'),
      '#description' => $this->t('Check this to delete all pages'),
      '#default_value' => $is_page_delete,
    ];
    $form['is_block_delete'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Delete existing blocks'),
      '#description' => $this->t('Check this to delete all blocks'),
      '#default_value' => $is_block_delete,
    ];
    $form['is_menu_delete'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Delete existing menus'),
      '#description' => $this->t('Check this to delete all menus'),
      '#default_value' => $is_menu_delete,
    ];
    $form['delete_pages'] = [
      '#type' => 'fieldset',
      '#title' => '<strong>⚠️ All existing pages will be deleted before importing new ones!</strong><br>',
      '#states' => [
        'visible' => [
          'input[name="is_page_delete"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['delete_blocks'] = [
      '#type' => 'fieldset',
      '#title' => '<strong>⚠️ All existing blocks will be deleted before importing new ones!</strong><br>',
      '#states' => [
        'visible' => [
          'input[name="is_block_delete"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['delete_menus'] = [
      '#type' => 'fieldset',
      '#title' => '<strong>⚠️ All existing menus will be deleted before importing new ones!</strong><br>',
      '#states' => [
        'visible' => [
          'input[name="is_menu_delete"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['keep_all'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Nothing will be deleted, and new content will be imported!'),
      '#states' => [
        'visible' => [
          ':input[name="is_page_delete"]' => ['checked' => FALSE],
          ':input[name="is_block_delete"]' => ['checked' => FALSE],
          ':input[name="is_menu_delete"]' => ['checked' => FALSE],
        ],
      ],
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t("Start import"),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * Form Validation.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $url = \Drupal::request()->query->get('url');
    if (!file_exists($url)) {
      $form_state->setErrorByName('submit', $this->t('Import is currently unavailable.'));
    }
    parent::validateForm($form, $form_state);
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

    $url = \Drupal::request()->query->get('url');
    $is_page_delete = $form_state->getValue('is_page_delete') ?? 0;
    $is_block_delete = $form_state->getValue('is_block_delete') ?? 0;
    $is_menu_delete = $form_state->getValue('is_menu_delete') ?? 0;

    \Drupal::logger('vactory_content_package')->debug('Confirm Content of JSON file @url', ['@url' => $url]);

    if (!$is_page_delete && !$is_block_delete && !$is_menu_delete) {
      \Drupal::service('vactory_content_package.import.manager')
        ->importNodes($url);
    }

    if ($is_page_delete) {
      $result = \Drupal::service('vactory_content_package.import.manager')
        ->rollback(self::CONTENT_TYPES, $url, $is_block_delete);
    }
    if ($is_block_delete) {
      $result = \Drupal::service('vactory_content_package.import.manager')
        ->rollbackBlock($url);
    }
    if ($is_menu_delete) {
      $result = \Drupal::service('vactory_content_package.import.manager')
        ->rollbackMenu($url);
    }
    if ($is_page_delete || $is_block_delete || $is_menu_delete) {
      if (is_array($result) && empty($result)) {
        // Redirect to import form.
        $form_state->setRedirect('vactory_content_package.importing_exported_nodes', ['url' => $url]);
      }
    }
  }

}
