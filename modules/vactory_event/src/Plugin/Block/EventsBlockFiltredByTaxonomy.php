<?php

namespace Drupal\vactory_event\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a "Vactory Evente Filtred By Taxonomy Block " block.
 *
 * @Block(
 *   id = "vactory_vactory_event_block_filtred_by_taxonomy",
 *   admin_label = @Translation("Vactory Events Block Filtred By Taxonomy"),
 *   category = @Translation("Vactory")
 * )
 */
class EventsBlockFiltredByTaxonomy extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritDoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entityTypeManager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritDoc}
   */
  public function defaultConfiguration() {
    return [
      'event_block_selected' => '',
      'category_filter' => '',
      'city_filter' => '',
      'override_more_link' => '',
    ];
  }

  /**
   * Block form function.
   */
  public function blockForm($form, FormStateInterface $form_state) {
    parent::blockForm($form, $form_state);
    $event_view = $this->entityTypeManager->getStorage('view')->load('vactory_event');
    $event_blocks = [];
    if (isset($event_view) && !empty($event_view)) {
      $event_displays = $event_view->get('display');
      if (!empty($event_displays)) {
        foreach ($event_displays as $display) {
          if ($display['display_plugin'] == 'block' && isset($display['display_options']['arguments']) && !empty($display['display_options']['arguments'])) {
            $event_blocks[$display['id']] = $display['display_title'];
          }
        }
      }
    }
    $categopries_term = $this->entityTypeManager->getStorage('taxonomy_term')
      ->loadByProperties(['vid' => 'vactory_event_category']);
    $category_terms = ['all' => '- Select -'];
    if (isset($categopries_term) && !empty($categopries_term)) {
      foreach ($categopries_term as $key => $term) {
        $category_terms[$key] = $term->get('name')->value;
      }
    }

    $cities_term = $this->entityTypeManager->getStorage('taxonomy_term')
      ->loadByProperties(['vid' => 'vactory_event_citys']);
    $city_terms = ['all' => '- Select -'];
    if (isset($cities_term) && !empty($cities_term)) {
      foreach ($cities_term as $key => $term) {
        $city_terms[$key] = $term->get('name')->value;
      }
    }

    $form['event_block'] = [
      '#type' => 'select',
      '#title' => $this->t('Event Blocks'),
      '#options' => $event_blocks,
      '#default_value' => $this->configuration['event_block_selected'],
      '#required' => TRUE,
    ];

    $form['category_filter'] = [
      '#type' => 'select',
      '#title' => $this->t('Categories'),
      '#options' => $category_terms,
      '#default_value' => $this->configuration['category_filter'],
    ];

    $form['city_filter'] = [
      '#type' => 'select',
      '#title' => $this->t('Cities'),
      '#options' => $city_terms,
      '#default_value' => $this->configuration['city_filter'],
    ];

    $form['override_more_link'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Appliquer le filtre par taxonomie sur le lien Voir plus du bloc'),
      '#description' => $this->t('Si coché alors le lien voir plus du bloc sera surchargé de façon à appliquer un filtre par le term de taxonomy choisi'),
      '#default_value' => $this->configuration['override_more_link'],
    ];
    return $form;
  }

  /**
   * Block submit function.
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    $this->configuration['event_block_selected'] = $form_state->getValue('event_block');
    $this->configuration['category_filter'] = $form_state->getValue('category_filter');
    $this->configuration['city_filter'] = $form_state->getValue('city_filter');
    $this->configuration['override_more_link'] = $form_state->getValue('override_more_link');
  }

  /**
   * Build function.
   */
  public function build() {
    $event_block_id = $this->configuration['event_block_selected'];
    $category_term_id = $this->configuration['category_filter'];
    $city_term_id = $this->configuration['city_filter'];
    $override_more_link = $this->configuration['override_more_link'];
    $field_name = [];
    $children = $this->entityTypeManager->getStorage('taxonomy_term')->loadChildren($category_term_id);
    $selected_category_term_id = $category_term_id;
    if (!empty($children)) {
      $children_ids = array_keys($children);
      $children_ids[] = $category_term_id;
      $category_term_id = implode('+', $children_ids);
    }
    if (!empty($category_term_id)) {
      $field_name[] = 'field_vactory_taxonomy_1';
    }
    if (!empty($city_term_id)) {
      $field_name[] = 'field_vactory_taxonomy_2';
    }
    return [
      '#theme' => 'vactory_event_block_filtered_by_taxonomy',
      '#content'  => [
        'category_term_id' => $category_term_id,
        'city_term_id' => $city_term_id,
        'event_block_id' => $event_block_id,
        'meta_data' => [
          'override_more_link' => $override_more_link,
          'field_name' => $field_name,
          'category_term_id' => $selected_category_term_id,
        ],
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
