<?php

namespace Drupal\vactory_news\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a "Vactory News Filtred By Taxonomy Block " block.
 *
 * @Block(
 *   id = "vactory_vactory_news_block_filtred_by_taxonomy",
 *   admin_label = @Translation("Vactory News Block Filtred By Taxonomy"),
 *   category = @Translation("Vactory")
 * )
 */
class NewsBlockFiltredByTaxonomy extends BlockBase {

  /**
   * Block form function.
   */
  public function blockForm($form, FormStateInterface $form_state) {
    parent::blockForm($form, $form_state);
    $news_view = \Drupal::service('entity_type.manager')->getStorage('view')->load('vactory_news');
    $news_blocks = [];
    if (isset($news_view) && !empty($news_view)) {
      $news_displays = $news_view->get('display');
      if (!empty($news_displays)) {
        foreach ($news_displays as $display) {
          if ($display['display_plugin'] == 'block' && isset($display['display_options']['arguments']) && !empty($display['display_options']['arguments'])) {
            $news_blocks[$display['id']] = $display['display_title'];
          }
        }
      }
    }
    $news_term = \Drupal::service('entity_type.manager')->getStorage('taxonomy_term')->loadByProperties(['vid' => 'vactory_news_theme']);
    $terms = [];
    if (isset($news_term) && !empty($news_term)) {
      foreach ($news_term as $key => $term) {
        $terms[$key] = $term->get('name')->value;
      }
    }
    $form['news_block'] = [
      '#type' => 'select',
      '#title' => $this->t('Blocks News'),
      '#options' => $news_blocks,
      '#default_value' => $this->configuration['news_block_selected'],
      '#required' => TRUE,
    ];

    $form['filter'] = [
      '#type' => 'select',
      '#title' => $this->t('Thématique News'),
      '#options' => $terms,
      '#default_value' => $this->configuration['filtred_term'],
      '#required' => TRUE,
    ];
    return $form;
  }

  /**
   * Block submit function.
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    $this->configuration['news_block_selected'] = $form_state->getValue('news_block');
    $this->configuration['filtred_term'] = $form_state->getValue('filter');
  }

  /**
   * Build function.
   */
  public function build() {
    // TODO: Implement build() method.
    $news_block_id = $this->configuration['news_block_selected'];
    $news_term_id = $this->configuration['filtred_term'];
    return [
      '#theme' => 'vactory_news_block_filtred_by_taxonomy',
      '#content'  => [
        'news_id' => $news_term_id,
        'news_block_id' => $news_block_id,
      ],
    ];
  }

}
