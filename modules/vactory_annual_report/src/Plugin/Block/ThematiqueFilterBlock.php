<?php

namespace Drupal\vactory_annual_report\Plugin\Block;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\taxonomy\Entity\Term;

/**
 * Provides a 'Drupalup Block' Block.
 *
 * @Block(
 *   id = "thematique_block_filter",
 *   admin_label = @Translation("Rapport digital thématiques"),
 *   category = @Translation("Vactory"),
 * )
 */
class ThematiqueFilterBlock extends BlockBase {

  /**
   * Default configuration for the block form.
   */
  public function defaultConfiguration() {
    return [
      'rapport_digital_year' => NULL,
    ];
  }

  /**
   * Function block form.
   */
  public function blockForm($form, FormStateInterface $form_state) {
    parent::blockForm($form, $form_state);
    $taxonomy_entity = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadByProperties(['vid' => 'rapport_digital_annees']);
    $rapports = [];
    if (isset($taxonomy_entity) && !empty($taxonomy_entity)) {
      foreach ($taxonomy_entity as $key => $term) {
        $rapports[$term->get('name')->value] = 'Rapport digital ' . $term->get('name')->value;
      }
    }
    $form['rapport_digital_year'] = [
      '#type' => 'select',
      '#title' => 'Rapport digital : Année',
      '#options' => $rapports,
      '#default_value' => $this->configuration['rapport_digital_year'],
      '#required' => TRUE,
    ];

    return $form;

  }

  /**
   * Function block submit.
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    $this->configuration['rapport_digital_year'] = $form_state->getValue('rapport_digital_year');
  }

  /**
   * Function build form.
   */
  public function build() {
    $rapport_year = $this->configuration['rapport_digital_year'];
    if (!empty($rapport_year)) {
      $vid = 'vactory_ar_thematic';
      $terms = \Drupal::service('vactory_annual_report.manager')->load($vid);
      $terms = array_values($terms);
      $links = [
        0 => [
          'tid' => 'All',
        ],
      ];
      if (isset($terms) && !empty($terms)) {
        foreach ($terms as $term) {
          if ($term->name == $rapport_year) {
            foreach ($term->children as $key => $child) {
              $theme_term = \Drupal::service('entity_type.manager')->getStorage('taxonomy_term')
                ->load($key);
              $color = Xss::filter($theme_term->get('field_color')->value);
              array_push($links, [
                "tid" => $key,
                "name" => $child->name,
                "color" => $color,
              ]);
            }
          }
        }
      }
    }
    return [
      '#theme' => 'block_thematique_filter_block',
      '#content' => [
        'rapport_year' => $rapport_year,
        'links' => $links,
      ],
      '#cache' => [
        // Set the caching policy to match the default block caching policy.
        'max-age' => 0,
        'contexts' => ['url'],
        'tags' => ['rendered'],
      ],
    ];
  }

}
