<?php

namespace Drupal\vactory_views\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\vactory_views\DateVactoryTrait;
use Drupal\views\Plugin\views\filter\Date;

/**
 * Date/time views filter.
 *
 * Even thought dates are stored as strings, the numeric filter is extended
 * because it provides more sensible operators.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("date_vactory")
 */
class DateVactory extends Date {

  use DateVactoryTrait;

  /**
   * Defines the timezone that dates should be in.
   */
  const STORAGE_TIMEZONE = 'UTC';

  /**
   * Defines the format that date and time should be in.
   */
  const DATETIME_STORAGE_FORMAT = 'Y-m-d\TH:i:s';

  /**
   * Defines the format that dates should be in.
   */
  const DATE_STORAGE_FORMAT = 'Y-m-d';

  /**
   * Defines the format that dates should be used by the database.
   */
  const DATABASE_DATE_STORAGE_FORMAT = '%Y-%m-%d';

  /**
   * Add a type selector to the value form.
   */
  protected function valueForm(&$form, FormStateInterface $form_state) {
    parent::valueForm($form, $form_state);
    if (!$form_state->get('exposed')) {
      $form['value']['type'] = [
        '#type'          => 'radios',
        '#title'         => $this->t('Value type'),
        '#options'       => [
          'date'      => $this->t('A date in any machine readable format. CCYY-MM-DD HH:MM:SS is preferred.'),
          'date_year' => $this->t('A date in yyyy format.'),
          'offset'    => $this->t('An offset from the current time such as "@example1" or "@example2"', [
            '@example1' => '+1 day',
            '@example2' => '-2 hours -30 minutes',
          ]),
        ],
        '#default_value' => !empty($this->value['type']) ? $this->value['type'] : 'date',
      ];
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function opSimple($field) {
    // If year filter choosed.
    if (!empty($this->value['type']) && $this->value['type'] == 'date_year') {
      $value = $this->value['value'];
      // In Case of changed and created date is timestamp.
      if ($field == 'node_field_data.changed' || $field == 'node_field_data.created') {
        $this->query->addWhereExpression($this->options['group'], "YEAR(FROM_UNIXTIME($field)) $this->operator $value");
      }
      else {
        $this->query->addWhereExpression($this->options['group'], "YEAR($field) $this->operator $value");
      }
    }
    else {
      // Drupal try to match timestamp field against submitted data.
      // Doesn't work properly,
      // We converted it for now to date patterns like %d/%m/%Y.
      // @todo: not looking good for performance.
      $date_formater = \Drupal::service('date.formatter');
      $language_manager = \Drupal::service('language_manager');

      $input = $this->value['value'];

      // Strtotime don't work for French.
      // -  Dates in the m/d/y or d-m-y formats are.
      // disambiguated by looking at the.
      // -  separator between the various components:
      // if the separator is a slash (/).
      // -  then the American m/d/y is assumed;
      // -  whereas if the separator is a dash (-) or a dot (.),
      // -  then the European d-m-y format is assumed.
      if ($language_manager->getCurrentLanguage()->getId() == 'fr') {
        $input = str_replace("/", "-", $input);
      }

      $input_timestamp = intval(strtotime($input, 0));

      // Convert to ISO. UTC is used since dates are stored in UTC.
      $value = $this->query->getDateFormat(
        "'" . $date_formater->format($input_timestamp, 'custom', self::DATETIME_STORAGE_FORMAT, self::STORAGE_TIMEZONE) . "'",
        self::DATE_STORAGE_FORMAT,
        TRUE);

      $this->query->addWhereExpression($this->options['group'], "DATE_FORMAT(FROM_UNIXTIME($field), '" . self::DATABASE_DATE_STORAGE_FORMAT . "') $this->operator $value");

      $value = intval(strtotime($this->value['value'], 0));
      if (!empty($this->value['type']) && $this->value['type'] == 'offset') {
        // Keep sign.
        $value = '***CURRENT_TIME***' . sprintf('%+d', $value);
        $this->query->addWhereExpression($this->options['group'], "$field $this->operator $value");
      }
      // This is safe because we are manually scrubbing the value.
      // It is necessary to do it this way because $value is a.
      // formula when using an offset.
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildExposedForm(&$form, FormStateInterface $form_state) {
    parent::buildExposedForm($form, $form_state);
    $this->applyDatePopupToForm($form);
  }

}
