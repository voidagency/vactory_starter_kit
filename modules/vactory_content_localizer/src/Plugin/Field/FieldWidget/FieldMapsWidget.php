<?php

namespace Drupal\vactory_content_localizer\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\geofield\Plugin\Field\FieldWidget\GeofieldLatLonWidget;

/**
 * Plugin implementation of the 'geofield_latlon' widget.
 *
 * @FieldWidget(
 *   id = "content_localizer_maps_field",
 *   label = @Translation("Content Localizer Field Maps"),
 *   field_types = {
 *     "geofield"
 *   }
 * )
 */
class FieldMapsWidget extends GeofieldLatLonWidget {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $latlon_value = [];
    $instance_delta = $items->getName() . '-' . $delta;
    $element['#attached']['library'][] = 'vactory_content_localizer/google-map-field-widget-renderer';
    $element['#attached']['library'][] = 'vactory_google_map_field/google-map-apis';

    foreach ($this->components as $component) {
      $latlon_value[$component] = isset($items[$delta]->{$component}) ? floatval($items[$delta]->{$component}) : '';
    }

    $element += [
      '#type' => 'geofield_latlon',
      '#default_value' => $latlon_value,
      '#geolocation' => $this->getSetting('html5_geolocation'),
      '#error_label' => !empty($element['#title']) ? $element['#title'] : $this->fieldDefinition->getLabel(),
    ];

    $element['container'] = [
      '#type' => 'fieldset',
    ];
    $element['container']['preview'] = [
      '#type' => 'item',
      '#title' => $this->t('Preview'),
      '#markup' => '<div class="google-map-field-preview" data-delta="' . $instance_delta . '"></div>',
      '#prefix' => '<div class="google-map-field-widget">',
      '#suffix' => '</div>',
    ];

    $element['container']['zoom'] = [
      '#type' => 'hidden',
      '#default_value' => isset($items[$delta]->zoom) ? $items[$delta]->zoom : 9,
      '#attributes' => [
        'data-zoom-delta' => $instance_delta,
      ],
    ];

    $element['container']['type'] = [
      '#type' => 'hidden',
      '#default_value' => isset($items[$delta]->type) ? $items[$delta]->type : 'roadmap',
      '#attributes' => [
        'data-type-delta' => $instance_delta,
      ],
    ];

    $element['container']['actions'] = [
      '#type' => 'actions',
      '#attributes' => [
        'class' => ['field-map-actions'],
      ],
    ];

    $element['container']['actions']['open_map'] = [
      '#type' => 'button',
      '#value' => $this->t('Set Map'),
      '#attributes' => [
        'data-delta' => $instance_delta,
        'id' => 'map_setter_' . $instance_delta,
      ],
    ];

    return ['value' => $element];
  }

}
