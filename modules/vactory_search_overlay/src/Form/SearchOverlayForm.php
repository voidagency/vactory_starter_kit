<?php

namespace Drupal\vactory_search_overlay\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provide vactory overlay search form.
 */
class SearchOverlayForm extends FormBase {

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
    return 'vactory_search_overlay.form';
  }

  /**
   * Form constructor.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param string $variant
   *   Variant of the form should be rendered for example variant1 or variant2.
   *
   * @return array
   *   The form structure.
   *
   * @see: \Drupal\vactory_search_overlay\Plugin\Block\VactorySearchOverlayBlock2::build()
   */
  public function buildForm(array $form, FormStateInterface $form_state, $variant = '') {
    $form['search_api_fulltext'] = [
      '#type'      => 'search',
      '#maxlength' => 128,
    ];
    $form['submit'] = [
      '#type'  => 'submit',
      '#value' => $this->t('Search'),
    ];
    if ($variant == 'variant1') {
      // Add  theme to search_overlay form variant 1.
      $form['#theme'] = 'vactory_search_overlay_form_variant1';
      // Add Form classes for v1.
      $form['#attributes']['class'] = [
        'search-block-form',
        'navbar-form',
        'form-inline',
      ];
    }
    elseif ($variant == 'variant2') {
      // Add  theme to search_overlay form variant 2.
      $form['#theme'] = 'vactory_search_overlay_form_variant2';
      // Add Form classes for v2.
      $form['#attributes']['class'] = ['search-block-form'];
    }
    else {
      $form['#theme'] = 'vactory_search_overlay_form_' . $variant;
    }

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
    // Get the keywords.
    $argument = $form_state->getValue('search_api_fulltext');
    // Redirect to search view, with the keyword.
    $form_state->setRedirect('view.vactory_search.global', ['search_api_fulltext' => $argument]);
  }

}
