<?php

namespace Drupal\vactory_views;

/**
 * Shared code between the Date and Datetime plugins.
 */
trait DateVactoryTrait {

  /**
   * Apply the HTML5 date popup to the views filter form.
   *
   * @param array $form
   *   The form to apply it to.
   */
  protected function applyDatePopupToForm(array &$form) {
    $value = $this->options['expose']['identifier'];
    if (!empty($value)) {
      // Attempt to implement a datepicker UI.
      // find out that better exposed filter does it.
      $form[$value]['#attributes']['data-provide'] = 'datepicker';
      // Detect filters that are using min/max.
      if (isset($form[$value]['min'])) {
        $form[$value]['min']['#attributes']['type'] = 'date';
        $form[$value]['max']['#attributes']['type'] = 'date';
      }
      else {
        $form[$value]['#attributes']['type'] = 'date';
      }

      // Add config for date_year filter.
      if (isset($this->options['value']['type']) && $this->options['value']['type'] === 'date_year') {
        $form[$value]['#attributes']['type'] = 'textfield';
        $form[$value]['#attributes']['class'][] = 'js-date-year-filter';
        $form[$value]['#attributes']['data-date-format'] = 'yyyy';
        $form[$value]['#attributes']['data-date-view-mode'] = 'years';
        $form[$value]['#attributes']['data-date-min-view-mode'] = 'years';
      }
    }
  }

}
