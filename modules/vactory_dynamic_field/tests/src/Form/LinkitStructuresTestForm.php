<?php

namespace Drupal\Tests\vactory_dynamic_field\Form;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\vactory_dynamic_field\Plugin\Field\FieldWidget\FormWidgetTrait;

/**
 * Test form placing url_extended elements like the Dynamic Fields modal.
 *
 * The elements are built by FormWidgetTrait::getFormElement() with the
 * #parents the modal form gives: components[extra_field|delta][group][field].
 * The number of items comes from the form state, so that an "Add one more"
 * AJAX rebuild can be reproduced.
 */
class LinkitStructuresTestForm extends FormBase {

  use FormWidgetTrait;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'vactory_dynamic_field_linkit_structures_test';
  }

  /**
   * Parents of the url_extended elements for a number of items.
   *
   * @param int $items
   *   Number of multiple items.
   *
   * @return array
   *   The #parents of every url_extended element.
   */
  public static function structure(int $items): array {
    $structure = [
      ['components', 'extra_field', 'cta'],
      ['components', 'extra_field', 'group_extra', 'link'],
      ['components', 0, 'cta_social'],
      ['components', 0, 'group_item', 'link'],
    ];
    for ($i = 0; $i < $items; $i++) {
      $structure[] = ['components', $i, 'link'];
    }
    return $structure;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#tree'] = TRUE;
    $form['#parents'] = [];
    foreach (self::structure($form_state->get('items') ?? 2) as $parents) {
      $field = end($parents);
      $element = $this->getFormElement('url_extended', $this->t('Link'), '', [], $form, $form_state, $field, $field);
      NestedArray::setValue($form, $parents, $element);
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

}
