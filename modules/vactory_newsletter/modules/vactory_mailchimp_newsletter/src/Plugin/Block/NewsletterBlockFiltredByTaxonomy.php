<?php

namespace Drupal\vactory_mailchimp_newsletter\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a "Vactory Newsletter Filtred By Taxonomy Block " block.
 *
 * @Block(
 *   id = "vactory_mailchimp_newsletter_block_filtred_by_taxonomy",
 *   admin_label = @Translation("Newsletter Block Filtred By Taxonomy"),
 *   category = @Translation("Vactory Newsletter")
 * )
 */
class NewsletterBlockFiltredByTaxonomy extends BlockBase implements ContainerFactoryPluginInterface {

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
      'newsletter_block_selected' => '',
      'filtred_term' => '',
      'override_more_link' => '',
    ];
  }

  /**
   * Block form function.
   */
  public function blockForm($form, FormStateInterface $form_state) {
    parent::blockForm($form, $form_state);
    $newsletter_view = $this->entityTypeManager->getStorage('view')->load('newsletter');
    $newsletter_blocks = [];
    if (isset($newsletter_view) && !empty($newsletter_view)) {
      $newsletter_displays = $newsletter_view->get('display');
      if (!empty($newsletter_displays)) {
        foreach ($newsletter_displays as $display) {
          if ($display['display_plugin'] == 'block' && isset($display['display_options']['arguments']) && !empty($display['display_options']['arguments'])) {
            $newsletter_blocks[$display['id']] = $display['display_title'];
          }
        }
      }
    }
    $newsletter_term = $this->entityTypeManager->getStorage('taxonomy_term')
      ->loadByProperties(['vid' => 'insight_theme']);
    $terms = [];
    if (isset($newsletter_term) && !empty($newsletter_term)) {
      foreach ($newsletter_term as $key => $term) {
        $terms[$key] = $term->get('name')->value;
      }
    }
    $form['newsletter_block'] = [
      '#type' => 'select',
      '#title' => $this->t('Blocks Newsletter'),
      '#options' => $newsletter_blocks,
      '#default_value' => $this->configuration['newsletter_block_selected'],
      '#required' => TRUE,
    ];

    $form['filter'] = [
      '#type' => 'select',
      '#title' => $this->t('Thématique Articles'),
      '#options' => $terms,
      '#default_value' => $this->configuration['filtred_term'],
      '#required' => TRUE,
    ];
    /* $form['override_more_link'] = [
    '#type' => 'checkbox',
    '#title' => $this->t('Appliquer le filtre par taxonomie sur
    le lien Voir plus du bloc'),
    '#description' => $this->t('Si coché alors le lien voir plus du bloc
    sera surchargé de façon à appliquer
    un filtre par le term de taxonomy choisi'),
    '#default_value' => $this->configuration['override_more_link'],
    ];*/

    return $form;
  }

  /**
   * Block submit function.
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    $this->configuration['newsletter_block_selected'] = $form_state->getValue('newsletter_block');
    $this->configuration['filtred_term'] = $form_state->getValue('filter');
    $this->configuration['override_more_link'] = $form_state->getValue('override_more_link');
  }

  /**
   * Build function.
   */
  public function build() {
    $newsletter_block_id = $this->configuration['newsletter_block_selected'];
    $newsletter_term_id = $this->configuration['filtred_term'];
    $override_more_link = $this->configuration['override_more_link'];
    $children = $this->entityTypeManager->getStorage('taxonomy_term')->loadChildren($newsletter_term_id);
    $selected_newsletter_term_id = $newsletter_term_id;
    if (!empty($children)) {
      $children_ids = array_keys($children);
      $children_ids[] = $newsletter_term_id;
      $newsletter_term_id = implode('+', $children_ids);
    }
    return [
      '#theme' => 'vactory_mailchimp_newsletter_block_filtred_by_taxonomy',
      '#content'  => [
        'newsletter_id' => $newsletter_term_id,
        'newsletter_block_id' => $newsletter_block_id,
        'meta_data' => [
          'override_more_link' => $override_more_link,
          'field_name' => 'field_vactory_theme',
          'newsletter_term_id' => $selected_newsletter_term_id,
        ],
      ],
    ];
  }

}
