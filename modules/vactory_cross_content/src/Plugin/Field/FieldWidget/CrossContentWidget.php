<?php

namespace Drupal\vactory_cross_content\Plugin\Field\FieldWidget;

use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\OptionsSelectWidget;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'field_cross_content_widget' widget.
 *
 * @FieldWidget(
 *   id = "field_cross_content_widget",
 *   module = "vactory_cross_content",
 *   label = @Translation("Cross Content Multiselect"),
 *   field_types = {
 *     "field_cross_content"
 *   },
 *   multiple_values = TRUE
 * )
 */
class CrossContentWidget extends OptionsSelectWidget {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    $element["#multiple"] = TRUE;
    $element["#default_value"] = $this->getSelectedOptions($items);
    $element += [
      '#type'    => 'select',
      '#options' => $this->getOptions($items->getEntity()),
    ];
    return ['value' => $element];
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    $options_to_string = '';
    foreach ($values['value'] as $value) {
      foreach ($value as $nid) {
        $options_to_string .= $nid . ' ';
      }
    }
    $values['value'] = $options_to_string;
    return parent::massageFormValues($values, $form, $form_state);
  }

  /**
   * Returns the array of options for the widget.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity for which to return options.
   *
   * @return array
   *   The array of options for the widget.
   */
  protected function getOptions(FieldableEntityInterface $entity) {
    $language = \Drupal::languageManager()->getCurrentLanguage()->getId();

    $node_list = \Drupal::entityTypeManager()
      ->getListBuilder('node')
      ->getStorage()
      ->loadByProperties([
        'type'     => $entity->bundle(),
        'langcode' => $language,
      ]);

    $current_node = $entity->get('nid')->getValue();
    if (empty($current_node)) {
      $current_node = 0;
    }
    else {
      $current_node = $current_node[0]['value'];
    }
    $options = [];
    foreach ($node_list as $key => $value) {
      if ($key == $current_node) {
        continue;
      }
      $options[$key] = $value->label();
    }
    return $options;
  }

  /**
   * Determines selected options from the incoming field values.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $items
   *   The field values.
   *
   * @return array
   *   The array of corresponding selected options.
   */
  protected function getSelectedOptions(FieldItemListInterface $items) {
    $default_options = !empty($items->getValue()[0]) ? explode(' ', trim($items->getValue()[0]['value']))
      : [];
    $selected_options = parent::getSelectedOptions($items);
    foreach ($default_options as $key => $value) {
      $selected_options[$value] = $value;
    }
    return $selected_options;
  }

}
