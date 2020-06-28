<?php

namespace Drupal\vactory_cross_content\Plugin\Block;

use Drupal;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\vactory_core\Vactory;
use Drupal\views\Views;
use Drupal\node\Entity\NodeType;

/**
 * Provides a "Vactory Cross Content Block" block.
 *
 * @Block(
 *   id = "vactory_cross_content",
 *   admin_label = @Translation("Cross Content Block"),
 *   category = @Translation("Vactory")
 * )
 */
class CrossContentBlock extends BlockBase implements BlockPluginInterface {

  /**
   * {@inheritdoc}
   *
   * This method sets the block default configuration. This configuration
   * determines the block's behavior when a block is initially placed in a
   * region. Default values for the block configuration form should be added to
   * the configuration array. System default configurations are assembled in
   * BlockBase::__construct() e.g. cache setting and block title visibility.
   *
   * @see \Drupal\block\BlockBase::__construct()
   */
  public function defaultConfiguration() {
    return [
      'nombre_elements' => 3,
      'more_link'       => '',
      'more_link_label' => '',
      'view_mode'       => '',
      'display_mode'    => '',
    ];
  }

  /**
   * Builds and returns the renderable array for this block plugin.
   *
   * If a block should not be rendered because it has no content, then this
   * method must also ensure to return no content: it must then only return an
   * empty array, or an empty array with #cache set (with cacheability metadata
   * indicating the circumstances for it being empty).
   *
   * @return array
   *   A renderable array representing the content of the block.
   *
   * @see \Drupal\block\BlockViewBuilder
   */
  public function build() {
    /** @var \Drupal\node\NodeInterface $node */
    $node = Drupal::routeMatch()->getParameter('node');

    if (!$node) {
      return [];
    }

    /** @var \Drupal\node\NodeTypeInterface $type */
    $type = NodeType::load($node->getType());
    if ($type->getThirdPartySetting('vactory_cross_content', 'enabling', '') <> 1) {
      return NULL;
    }
    $taxonomy_field = $type->getThirdPartySetting('vactory_cross_content', 'taxonomy_field', '');
    $term_id = $type->getThirdPartySetting('vactory_cross_content', 'terms', '');
    $nbr = $type->getThirdPartySetting('vactory_cross_content', 'nombre_elements', 3);
    $nbr = (!empty($this->configuration['nombre_elements'])) ? $this->configuration['nombre_elements'] : $nbr;
    $more_link = $type->getThirdPartySetting('vactory_cross_content', 'more_link', '');
    $more_link_label = $type->getThirdPartySetting('vactory_cross_content', 'more_link_label', '');
    $view_mode = $this->configuration['view_mode'];
    $view_mode_options = $this->configuration['view_options'][$view_mode . '_options'];
    $display_mode = $this->configuration['display_mode'];
    $field_name = Vactory::getFieldbyType($node, 'field_cross_content');
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
    $view->filter['type']->value = [$node->bundle() => $node->bundle()];

    // Set number of items per page.
    $view->setItemsPerPage($nbr);

    // Update view mode.
    $view->rowPlugin->options['view_mode'] = $display_mode;

    // Update more link.
    if (!empty($more_link) || !empty($this->configuration['more_link'])) {
      $view->display_handler->overrideOption('use_more', TRUE);
      $view->display_handler->overrideOption('use_more_always', TRUE);
      $view->display_handler->overrideOption('link_display', 'custom_url');

      if (!empty($this->configuration['more_link'])) {
        if (!empty($this->configuration['more_link_label'])) {
          $view->display_handler
            ->overrideOption('use_more_text', $this->configuration['more_link_label']);
        }
        $view->display_handler->overrideOption('link_url', $this->configuration['more_link']);
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
    else if (!$ignore ) {
      if ($taxonomy_field !== 'none') {
        $target->value = [];
        if (!empty($term_id)) {
          foreach ($term_id as $key => $value) {
            $target->value[$key] = $key;
          }
        } else {
          $currentTerm = $node->get($taxonomy_field)->getValue();
          foreach ($currentTerm as $value) {
            $_termId = $value['target_id'];
            $target->value[$_termId] = $_termId;
          }
        }
      } else {
        unset($view->filter[$default_taxo]);
      }
      // Creating a hook to alter the block.
      $data = [
        'view'         => $view,
        'content_type' => $node->bundle(),
        'block'        => 'block_list',
      ];
      Drupal::moduleHandler()->alter('vactory_cross_content_view', $data);
      // Custom query.
      $view->build($view->current_display);
      $ids = [$node->get("nid")->value];
      $view->query->addWhere(1, $id_table . '.' . $id_field, $ids, '!=');
    }
    // Update views build info query.
    $view->build_info['query'] = $view->query->query();

    $view->execute();
    // If no results are available we won't render the block.
    if (count($view->result) == 0) {
      return NULL;
    }
    return $view->render('block_list');
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);
    $view = Views::getView('vactory_cross_content');
    $view->initDisplay();
    $view->setDisplay('block_list');
    $view_modes = Views::fetchPluginNames('style', $view->display_handler->getType(), [$view->storage->get('base_table')]);
    $form['view_mode'] = [
      '#type'          => 'radios',
      '#title'         => t('View Styles'),
      '#description'   => '',
      '#options'       => $view_modes,
      '#default_value' => $this->configuration['view_mode'],
    ];

    $form['view_options'] = [
      '#type'  => 'details',
      '#title' => t('View style Options'),
      '#open'  => TRUE,
    ];
    foreach ($view_modes as $mode => $value) {
      $form['view_options'][$mode] = [
        '#type'   => 'details',
        '#title'  => $value . ' Options',
        '#open'   => TRUE,
        '#states' => [
          "visible" => [
            "input[name='settings[view_mode]']" => ['value' => $mode],
          ],
        ],
      ];
      $current_mode = Views::pluginManager('style')->createInstance($mode);
      $temp = [];
      $current_mode->init($view, $view->display_handler, $temp);
      $my_form = [];
      $my_form_state = new FormState();
      $current_mode->buildOptionsForm($my_form, $my_form_state);
      $form['view_options'][$mode][$mode . '_options'] = $my_form;
      if (isset($this->configuration['view_options'][$mode . '_options'])) {
        foreach ($form['view_options'][$mode][$mode . '_options'] as $entry => $value) {
          if (array_key_exists($entry, $this->configuration['view_options'][$mode . '_options'])) {
            $form['view_options'][$mode][$mode . '_options'][$entry]['#default_value'] = $this->configuration['view_options'][$mode . '_options'][$entry];
          }
        }
      }
    }
    $display_modes = [];
    foreach (\Drupal::service('entity_display.repository')->getViewModes('node') as $key => $value) {
      $display_modes[$key] = $value['label'];
    }
    $form['display_mode'] = [
      '#type'          => 'radios',
      '#title'         => t('View Modes'),
      '#description'   => '',
      '#options'       => $display_modes,
      '#default_value' => $this->configuration['display_mode'],
    ];
    $form['nombre_elements'] = [
      '#type'          => 'textfield',
      '#title'         => t('Number of nodes to display'),
      '#description'   => t('Select the number of node to display in the cross content block'),
      '#default_value' => $this->configuration['nombre_elements'],
    ];

    $form['more_link'] = [
      '#type'          => 'textfield',
      '#title'         => t('Choose the redirection link for the more Link , leave it empty to disable it'),
      '#description'   => t('Choose the redirection link for the more Link , leave it empty to disable it'),
      '#default_value' => $this->configuration['more_link'],
    ];

    $form['more_link_label'] = [
      '#type'          => 'textfield',
      '#title'         => t('More Link title'),
      '#description'   => t('Choose the title to display for the more Link , leave it empty to disable it'),
      '#default_value' => $this->configuration['more_link_label'],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    $this->configuration['nombre_elements']
      = $form_state->getValue('nombre_elements');
    $this->configuration['more_link']
      = $form_state->getValue('more_link');
    $this->configuration['more_link_label']
      = $form_state->getValue('more_link_label');
    $this->configuration['view_mode']
      = $form_state->getValue('view_mode');
    $this->configuration['view_options']
      = $form_state->getValue('view_options')[$form_state->getValue('view_mode')];
    $this->configuration['display_mode']
      = $form_state->getValue('display_mode');
  }

}
