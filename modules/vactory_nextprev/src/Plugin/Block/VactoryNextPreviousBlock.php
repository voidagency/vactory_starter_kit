<?php

namespace Drupal\vactory_nextprev\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Url;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\node\NodeInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\vactory_core\Vactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Provides a 'Next Previous' block.
 *
 * @Block(
 *   id = "vactory_next_prev_block",
 *   admin_label = @Translation("Vactory Next Previous link"),
 *   category = @Translation("Blocks")
 * )
 */
class VactoryNextPreviousBlock extends BlockBase implements BlockPluginInterface, ContainerFactoryPluginInterface {

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Creates a NextPreviousBlock instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RouteMatchInterface $route_match, EntityTypeManagerInterface $entityTypeManager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->routeMatch = $route_match;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_route_match'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $node_types = node_type_get_names();
    $form['content_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Content Types'),
      '#empty_option' => $this->t('-None-'),
      '#options' => $node_types,
      '#default_value' => isset($this->configuration['content_type']) ? $this->configuration['content_type'] : '',
      '#required' => TRUE,
    ];
    $this->getTaxonomiesFields($form, $form_state);
    $form['previous_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Previous text'),
      '#description' => $this->t('Add your previous button name'),
      '#default_value' => isset($this->configuration['previous_text']) ? $this->configuration['previous_text'] : '',
      '#required' => TRUE,
    ];
    $form['next_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Next text'),
      '#description' => $this->t('Add your next button name'),
      '#default_value' => isset($this->configuration['next_text']) ? $this->configuration['next_text'] : '',
      '#required' => TRUE,
    ];
    $form['previouslink_class'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Previous link class'),
      '#description' => $this->t('Add your class in previous link'),
      '#default_value' => isset($this->configuration['previouslink_class']) ? $this->configuration['previouslink_class'] : '',
    ];
    $form['nextlink_class'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Next link class'),
      '#description' => $this->t('Add your class in next link'),
      '#default_value' => isset($this->configuration['nextlink_class']) ? $this->configuration['nextlink_class'] : '',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $artifact = $form_state->get('artifact');
    $selected_content_type = $form_state->getValue('content_type');
    $content_type_taxonomies = $form_state->getValue('content_type_taxonomies');

    foreach ($content_type_taxonomies[$selected_content_type] as $field_name => &$value) {
      if (!in_array($field_name, $artifact[$selected_content_type])) {
        $value = 'empty';
      }

    }
    $this->configuration['content_type'] = $form_state->getValue('content_type');
    $this->configuration['previous_text'] = $values['previous_text'];
    $this->configuration['next_text'] = $values['next_text'];
    $this->configuration['previouslink_class'] = $values['previouslink_class'];
    $this->configuration['nextlink_class'] = $values['nextlink_class'];
    $this->configuration['content_type_taxonomies'] = $content_type_taxonomies[$selected_content_type];
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $content = [];

    // Get the created time of the current node.
    $node = $this->routeMatch->getParameter('node');

    if ($node instanceof NodeInterface && $node->getType() == $this->configuration['content_type']) {
      $current_nid = $node->id();

      $prev = $this->generatePrevious($node);
      if (!empty($prev)) {
        $content['prev'] = $prev;
      }

      $next = $this->generateNext($node);
      if (!empty($next)) {
        $content['next'] = $next;
      }
    }
    return [
      '#theme' => 'block__vactory_nextprev',
      '#content' => $content,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    // Get the created time of the current node.
    $node = $this->routeMatch->getParameter('node');
    if (!empty($node) && $node instanceof NodeInterface) {
      // If there is node add its cachetag.
      return Cache::mergeTags(parent::getCacheTags(), ['node:*']);
    }
    else {
      // Return default tags instead.
      return parent::getCacheTags();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return Cache::mergeContexts(parent::getCacheContexts(), ['route']);
  }

  /**
   * Lookup the previous node,youngest node which is still older than the node.
   */
  private function generatePrevious($node): array {
    return $this->generateNextPrevious($node, 'prev');
  }

  /**
   * Lookup the next node,oldest node which is still younger than the node.
   */
  private function generateNext($node) {
    return $this->generateNextPrevious($node, 'next');
  }

  const DIRECTION__NEXT = 'next';

  /**
   * Lookup the next or previous node.
   */
  private function generateNextPrevious($node, string $direction = self::DIRECTION__NEXT): array {
    $comparison_opperator = '>';
    $sort = 'ASC';
    $display_text = $this->configuration['next_text'];
    $class = $this->configuration['nextlink_class'] ? $this->configuration['nextlink_class'] : 'btn';
    $current_nid = $node->id();
    $current_langcode = $node->get('langcode')->value;
    $content_type_taxonomies = $this->configuration['content_type_taxonomies'];

    if ($direction === 'prev') {
      $comparison_opperator = '<';
      $sort = 'DESC';
      $display_text = $this->configuration['previous_text'];
      $class = $this->configuration['previouslink_class'] ? $this->configuration['previouslink_class'] : 'btn';
    }

    // Lookup 1 node younger (or older) than the current node.
    $query = $this->entityTypeManager->getStorage('node');
    $query_result = $query->getQuery();
    $next = $query_result->condition('nid', $current_nid, $comparison_opperator)
      ->condition('type', $this->configuration['content_type'])
      ->condition('status', 1)
      ->condition('langcode', $current_langcode);
    foreach ($content_type_taxonomies as $key => $value) {
      if ($value !== 'empty') {
        $next->condition($key, $value);
      }
    }
    $next = $next->sort('nid', $sort)
      ->range(0, 1)
      ->execute();

    // If this is not the youngest (or oldest) node.
    if (!empty($next) && is_array($next)) {
      $next = array_values($next);
      $next = $next[0];

      // Find the alias of the next node.
      $nid = $next;
      $nextnode = \Drupal::entityTypeManager()->getStorage('node')->load($nid);
      $url = Url::fromRoute('entity.node.canonical', ['node' => $nid], [])->toString();
      $link['#attributes'] = ['class' => ['nextpre__btn', $class]];

      return [
        'link' => $url,
        'title' => $nextnode->label(),
      ];
    }
    return [];
  }

  /**
   * Callback : Get taxonomies of selected Content Type.
   */
  public function getTaxonomiesFields(&$form, FormStateInterface $form_state) {
    $node_types = node_type_get_names();
    $form['content_type_taxonomies'] = [
      '#title' => t('Filter by taxonomies'),
      '#type' => 'fieldset',
      '#prefix' => '<div id="taxonomies_selector">',
      '#suffix' => '</div>',
    ];
    $artifact = [];
    foreach ($node_types as $content_type => $label) {
      $taxonomyList = Vactory::getTaxonomyList($content_type);
      if (!empty($taxonomyList)) {
        foreach ($taxonomyList as $taxonomyInfo) {
          $vid = $taxonomyInfo[0];
          $options = [];
          $options['empty'] = $this->t('- Select -');
          $fieldname = $taxonomyInfo[1];
          $artifact[$content_type][] = $fieldname;
          $vocabulary = \Drupal::entityTypeManager()->getStorage('taxonomy_vocabulary')->load($vid);
          $terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadByProperties([
            'vid' => $vid,
          ]);
          if (!empty($terms)) {
            foreach ($terms as $tid => $term) {
              $options[$tid] = $term->getName();
            }
          }
          $form['content_type_taxonomies'][$content_type][$fieldname] = [
            '#type' => 'select',
            '#title' => $vocabulary->label(),
            '#options' => $options,
            '#default_value' => isset($this->configuration['content_type_taxonomies']) ? $this->configuration['content_type_taxonomies'] : '',
            '#states' => [
              'visible' => [
                '#edit-settings-content-type' => ['value' => $content_type],
              ],
            ],
          ];
        }
        $form_state->set('artifact', $artifact);
      }
    }

  }

}
