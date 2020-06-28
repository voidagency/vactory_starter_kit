<?php

namespace Drupal\vactory_cross_content\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'field_cross_content_widget' widget.
 *
 * @FieldWidget(
 *   id = "field_cross_content_widget_text",
 *   module = "vactory_cross_content",
 *   label = @Translation("Cross Content Texte"),
 *   field_types = {
 *     "field_cross_content"
 *   },
 *   multiple_values = FALSE
 * )
 */
class CrossContentText extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element += [
      '#type'          => 'textfield',
      '#default_value' => isset($items[$delta]->value) ? trim($items[$delta]->value) : '',
    ];
    return ['value' => $element];
  }

}
