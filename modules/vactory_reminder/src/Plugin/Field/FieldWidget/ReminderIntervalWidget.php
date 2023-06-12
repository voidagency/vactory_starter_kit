<?php

namespace Drupal\vactory_reminder\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * A widget reminder interval.
 *
 * @FieldWidget(
 *   id = "reminder_interval_widget",
 *   label = @Translation("Default"),
 *   field_types = {
 *     "reminder_interval"
 *   }
 * )
 */
class ReminderIntervalWidget extends WidgetBase {

  /**
   * {@inheritDoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $default_day = isset($items[$delta]) ? $items[$delta]->day : '';
    $default_hour = isset($items[$delta]) ? $items[$delta]->hour : '';
    $default_minute = isset($items[$delta]) ? $items[$delta]->minute : '';
    $element += [
      '#type' => 'fieldset',
      '#title' => $this->t('Reminder interval'),
      '#attributes' => [
        'class' => ['reminder-interval-field'],
      ],
      '#attached' => [
        'library' => ['vactory_reminder/style'],
      ],
    ];
    $element['alarm'] = [
      '#markup' => '<div class="alarm"><span>⏰</span></div>',
    ];
    $element['day'] = [
      '#type' => 'number',
      '#title' => $this->t('Day'),
      '#default_value' => !empty($default_day) ? $default_day : '',
    ];
    $element['hour'] = [
      '#type' => 'number',
      '#title' => $this->t('Hour'),
      '#default_value' => !empty($default_hour) ? $default_hour : '',
    ];
    $element['minute'] = [
      '#type' => 'number',
      '#title' => $this->t('Minute'),
      '#default_value' => !empty($default_minute) ? $default_minute : '',
    ];

    return $element;
  }

}
