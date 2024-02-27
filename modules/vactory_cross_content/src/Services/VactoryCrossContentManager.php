<?php

namespace Drupal\vactory_cross_content\Services;

use Drupal\vactory_core\Vactory;
use Drupal\views\Views;
use Drupal\Core\Entity\EntityRepositoryInterface;

/**
 * Vactory Cross Content Manager service.
 */
class VactoryCrossContentManager {

  /**
   * Vactory service.
   *
   * @var \Drupal\vactory_core\Vactory
   */
  protected $vactory;

  /**
   * Entity Repository.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * Cross content manager service constructor.
   */
  public function __construct(Vactory $vactory, EntityRepositoryInterface $entityRepository) {
    $this->vactory = $vactory;
    $this->entityRepository = $entityRepository;
  }

  /**
   * Get cross content view.
   */
  public function getCrossContentView($type, $node, $configuration) {
    $node = $this->entityRepository->getTranslationFromContext($node);
    if (!isset($node)) {
      return [];
    }
    $content_type_selected = $type->getThirdPartySetting('vactory_cross_content', 'content_type', '');
    $taxonomy_field = $type->getThirdPartySetting('vactory_cross_content', 'taxonomy_field', '');
    $term_id = $type->getThirdPartySetting('vactory_cross_content', 'terms', '');
    $nbr = $type->getThirdPartySetting('vactory_cross_content', 'nombre_elements', 3);
    $nbr = (!empty($configuration['nombre_elements'])) ? $configuration['nombre_elements'] : $nbr;
    $more_link = $type->getThirdPartySetting('vactory_cross_content', 'more_link', '');
    $more_link_label = $type->getThirdPartySetting('vactory_cross_content', 'more_link_label', '');
    $view_mode = $configuration['view_mode'];
    $view_mode_options = $configuration['view_options'][$view_mode . '_options'];
    $display_mode = $configuration['display_mode'];
    $field_name = $this->vactory->getFieldbyType($node, 'field_cross_content');
    $related_nodes = $field_name <> NULL ? $node->get($field_name)->value : '';
    $ignore = !empty($related_nodes);
    $id_table = 'node_field_data';
    $id_field = 'nid';
    // Configuring the Block View.
    $view = Views::getView('vactory_cross_content');
    if (!is_object($view)) {
      return [];
    }

    // Current display.
    $view->setDisplay('block_list');
    // Update plugin style.
    $view->display_handler->setOption('style', [
      'type'    => $view_mode,
      'options' => $view_mode_options,
    ]);
    $view->style_plugin = $view->display_handler->getPlugin('style');
    // Plugin style must be set before preExecute.
    $view->preExecute();
    // Set content type.
    $content_type_selected = !empty($content_type_selected) ? $content_type_selected : [$node->bundle() => $node->bundle()];
    $view->filter['type']->value = $content_type_selected;

    // Set number of items per page.
    $view->setItemsPerPage($nbr);

    // Update view mode.
    $view->rowPlugin->options['view_mode'] = $display_mode;

    // Update more link.
    if (!empty($more_link) || !empty($configuration['more_link'])) {
      $view->display_handler->overrideOption('use_more', TRUE);
      $view->display_handler->overrideOption('use_more_always', TRUE);
      $view->display_handler->overrideOption('link_display', 'custom_url');

      if (!empty($configuration['more_link'])) {
        if (!empty($configuration['more_link_label'])) {
          $view->display_handler
            ->overrideOption('use_more_text', $configuration['more_link_label']);
        }
        $view->display_handler->overrideOption('link_url', $configuration['more_link']);
      }
      else {
        if (!empty($more_link_label)) {
          $view->display_handler
            ->overrideOption('use_more_text', $more_link_label);
        }
        $view->display_handler->overrideOption('link_url', $more_link);
      }
    }
    // Get Taxonomy Stuff.
    $default_taxo = 'tid';
    $target = &$view->filter[$default_taxo];
    // In case we gathered data from the custom field.
    if ($ignore) {
      // Remove default taxonomy.
      unset($view->filter[$default_taxo]);
      // Custom query.
      $view->build($view->current_display);
      // Look for nodes.
      // If no pre-selected nodes, then get all possible nodes without this one.
      $related_nids = explode(" ", trim($related_nodes));
      $ids = array_map('intval', $related_nids);
      $view->query->addWhere(1, $id_table . '.' . $id_field, $ids, 'IN');

    }
    // Otherwise we'll use the view's filter.
    elseif (!$ignore) {
      if ($taxonomy_field !== 'none') {
        $target->value = [];
        if (!empty($term_id)) {
          foreach ($term_id as $key => $value) {
            $target->value[$key] = $key;
          }
        }
        else {
          $currentTerm = $node->get($taxonomy_field)->getValue();
          foreach ($currentTerm as $value) {
            $_termId = $value['target_id'];
            $target->value[$_termId] = $_termId;
          }
        }
      }
      else {
        unset($view->filter[$default_taxo]);
      }
      // Creating a hook to alter the block.
      $data = [
        'view'         => $view,
        'content_type' => !empty($content_type_selected) ? $content_type_selected : [$node->bundle() => $node->bundle()],
        'block'        => 'block_list',
      ];
      \Drupal::moduleHandler()->alter('vactory_cross_content_view', $data);
      // Custom query.
      $view->build($view->current_display);
      $ids = [$node->get("nid")->value];
      $view->query->addWhere(1, $id_table . '.' . $id_field, $ids, '!=');
    }

    $fields = \Drupal::service('entity_field.manager')->getFieldDefinitions('node', $node->bundle());
    if (isset($fields['field_vactory_date'])) {
      $view->query->orderby = [];
      $view->query->addOrderBy('node__field_vactory_date', 'field_vactory_date_value', 'DESC');
    }

    // Update views build info query.
    $view->build_info['query'] = $view->query->query();
    return $view;
  }

}