<?php

namespace Drupal\vactory_views\Plugin\views\filter;

use Drupal\Component\Datetime\DateTimePlus;
use Drupal\Core\Form\FormStateInterface;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\datetime\Plugin\views\filter\Date;
use Drupal\vactory_views\DateVactoryTrait;

/**
 * Date/time views filter.
 *
 * Even thought dates are stored as strings, the numeric filter is extended
 * because it provides more sensible operators.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("datetime_vactory")
 */
class DatetimeVactory extends Date {

  use DateVactoryTrait;

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
      $value = $this->value['value'] ?? '';
      // In Case of changed and created date is timestamp.
      if ($field == 'node_field_data.changed' || $field == 'node_field_data.created') {
        $this->query->addWhereExpression($this->options['group'], "YEAR(FROM_UNIXTIME($field)) $this->operator $value");
      }
      else {
        $this->query->addWhereExpression($this->options['group'], "YEAR($field) $this->operator $value");
      }
    }
    else {
      $timezone = $this->getTimezone();
      $origin_offset = $this->getOffset($this->value['value'], $timezone);

      // Convert to ISO. UTC timezone is used since dates are stored in UTC.
      $value = new DateTimePlus($this->value['value'], new \DateTimeZone($timezone));
      $value = $this->query->getDateFormat($this->query->getDateField("'" . $this->dateFormatter->format($value->getTimestamp() + $origin_offset, 'custom', DateTimeItemInterface::DATETIME_STORAGE_FORMAT, DateTimeItemInterface::STORAGE_TIMEZONE) . "'", TRUE, $this->calculateOffset), $this->dateFormat, TRUE);

      // This is safe because we are manually scrubbing the value.
      $field = $this->query->getDateFormat($this->query->getDateField($field, TRUE, $this->calculateOffset), $this->dateFormat, TRUE);
      $this->query->addWhereExpression($this->options['group'], "$field $this->operator $value");
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildExposedForm(&$form, FormStateInterface $form_state) {
    Date::buildExposedForm($form, $form_state);
    $this->applyDatePopupToForm($form);
  }

}
