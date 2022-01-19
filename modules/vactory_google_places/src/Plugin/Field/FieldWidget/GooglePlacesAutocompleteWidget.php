<?php

namespace Drupal\vactory_google_places\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines the 'vactory_google_places_autocomplete' field widget.
 *
 * @FieldWidget(
 *   id = "vactory_google_places_autocomplete",
 *   label = @Translation("Google Places Autocomplete"),
 *   field_types = {"vactory_google_places"},
 * )
 */
class GooglePlacesAutocompleteWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element += [
      '#type' => 'vactory_google_places',
      '#title' => $this->t('Place'),
      '#default_value' => isset($items[$delta]->place) ? $items[$delta]->getValue() : NULL,
    ];
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    foreach ($values as &$value) {
      if (isset($value['google_places']['place'])) {
        $value['place'] = $value['google_places']['place'];
        $value['longitude'] = $value['google_places']['longitude'];
        $value['latitude'] = $value['google_places']['latitude'];
        unset($value['google_places']);
      }
    }
    return parent::massageFormValues($values, $form, $form_state);
  }

}
