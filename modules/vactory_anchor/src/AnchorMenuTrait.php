<?php

namespace Drupal\vactory_anchor;

use Drupal\node\NodeInterface;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\Component\Utility\Html;

/**
 * Trait AnchorMenuTrait.
 *
 * @package Drupal\vactory_anchor
 */
trait AnchorMenuTrait {

  /**
   * Get node paragraphs formatted.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node.
   *
   * @return array
   *   An array representing the formatted data for anchor.
   */
  public function getNodeParagraphs(NodeInterface $node) {
    $menu_links = [];
    $field_definitions = \Drupal::service('entity.manager')
      ->getFieldDefinitions('node', $node->bundle());

    foreach (array_keys($field_definitions) as $field_name) {
      if ($field_definitions[$field_name]->getType() == 'entity_reference_revisions') {
        if ($field_definitions[$field_name]->getSettings()['target_type'] == 'paragraph' && $node->hasField($field_name)) {
          $paragraph = $node->get($field_name)->getValue();

          foreach ($paragraph as $element) {
            $p = Paragraph::load($element['target_id'])
              ->getTranslation($node->language()->getId());
            $display = (bool) $p->field_vactory_flag_2->value;
            // phpcs:disable
            $title = ($p->hasField('field_titre_ancre') && !empty($p->get('field_titre_ancre')
                ->getValue())) ? $p->get('field_titre_ancre')->value : $p->field_vactory_title->value;
            // phpcs:enable

            if ($p->get('paragraph_identifier')->value != NULL) {
              $identifier = $p->get('paragraph_identifier')->value;
            }
            else {
              $identifier = Html::cleanCssIdentifier($title . '-' . $p->id());
            }

            if (
              $p->hasField('paragraph_hide_en') &&
              isset($p->paragraph_hide_en->value) &&
              (bool) $p->paragraph_hide_en->value &&
              $node->language()->getId() == 'en'
            ) {
              $display = ($display) ? FALSE : $display;
            }

            if ($display) {
              array_push($menu_links, [
                'id'    => strtolower($identifier),
                'label' => $title,
              ]);
            }
          }
        }
      }
    }

    return $menu_links;

  }

}
