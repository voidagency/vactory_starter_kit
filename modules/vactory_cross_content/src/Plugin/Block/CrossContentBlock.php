<?php

namespace Drupal\vactory_cross_content\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Extension\ModuleHandler;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\vactory_cross_content\Services\VactoryCrossContentManager;
use Drupal\views\Views;
use Drupal\node\Entity\NodeType;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a "Vactory Cross Content Block" block.
 *
 * @Block(
 *   id = "vactory_cross_content",
 *   admin_label = @Translation("Cross Content Block"),
 *   category = @Translation("Vactory")
 * )
 */
class CrossContentBlock extends BlockBase implements BlockPluginInterface, ContainerFactoryPluginInterface {

  /**
   * Vactory Cross Content Manager service.
   *
   * @var \Drupal\vactory_cross_content\Services\VactoryCrossContentManager
   */
  protected $crossContentManager;

  /**
   * Module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandler
   */
  protected $moduleHandler;

  /**
   * {@inheritDoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, VactoryCrossContentManager $crossContentManager, ModuleHandler $moduleHandler) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->crossContentManager = $crossContentManager;
    $this->moduleHandler = $moduleHandler;
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('vactory_cross_content.manager'),
      $container->get('module_handler')
    );
  }

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
    $node = \Drupal::routeMatch()->getParameter('node');

    if (!$node) {
      return [];
    }

    /** @var \Drupal\node\NodeTypeInterface $type */
    $type = NodeType::load($node->getType());
    if ($type->getThirdPartySetting('vactory_cross_content', 'enabling', '') <> 1) {
      return NULL;
    }
    $title = (!empty($this->configuration['title'])) ? $this->configuration['title'] : '';
    $view = $this->crossContentManager->getCrossContentView($type, $node, $this->configuration);
    if (empty($view) || !is_object($view)) {
      return [];
    }
    $view->execute();
    // If no results are available we won't render the block.
    if (count($view->result) == 0) {
      return ['#markup' => ''];
    }
    return [
      '#theme' => 'vcc_block',
      '#block' => $view->render('block_list'),
      '#title' => $title,
      '#cache' => [
        // Set the caching policy to match the default block caching policy.
        'max-age' => 0,
        'contexts' => ['url'],
        'tags' => ['rendered'],
      ],
    ];
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

    $form['title'] = [
      '#type'          => 'textfield',
      '#title'         => t('Block Title'),
      '#description'   => t('the title of the cross content block'),
      '#default_value' => isset($this->configuration['title']) && !empty($this->configuration['title']) ? $this->configuration['title'] : '',
    ];

    $form['view_styles_options'] = [
      '#type'  => 'details',
      '#title' => t('View Styles'),
      '#open'  => FALSE,
    ];
    $form['view_styles_options']['view_mode'] = [
      '#type'          => 'radios',
      '#description'   => '',
      '#options'       => $view_modes,
      '#default_value' => isset($this->configuration['view_mode']) && !empty($this->configuration['view_mode']) ? $this->configuration['view_mode'] : 'vactory_views_grid',
    ];

    $form['view_options'] = [
      '#type'  => 'details',
      '#title' => t('View style Options'),
      '#open'  => FALSE,
    ];
    foreach ($view_modes as $mode => $value) {
      $form['view_options'][$mode] = [
        '#type'   => 'details',
        '#title'  => $value . ' Options',
        '#open'   => FALSE,
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

    $form['view_modes'] = [
      '#type'  => 'details',
      '#title' => t('View Modes'),
      '#open'  => FALSE,
    ];

    $form['view_modes']['display_mode'] = [
      '#type'          => 'radios',
      '#description'   => '',
      '#options'       => $display_modes,
      '#default_value' => isset($this->configuration['display_mode']) && !empty($this->configuration['display_mode']) ? $this->configuration['display_mode'] : 'card',
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

    // Hide view config if vactory_decoupled is enabled.
    if ($this->moduleHandler->moduleExists('vactory_decoupled')) {
      $form['view_styles_options']['#access'] = FALSE;
      $form['view_options']['#access'] = FALSE;
      $form['view_modes']['#access'] = FALSE;
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    $this->configuration['title']
      = $form_state->getValue('title');
    $this->configuration['nombre_elements']
      = $form_state->getValue('nombre_elements');
    $this->configuration['more_link']
      = $form_state->getValue('more_link');
    $this->configuration['more_link_label']
      = $form_state->getValue('more_link_label');
    $this->configuration['view_mode']
      = $form_state->getValue('view_styles_options')['view_mode'];
    $this->configuration['view_options']
      = $form_state->getValue('view_options')[$form_state->getValue('view_styles_options')['view_mode']];
    $this->configuration['display_mode']
      = $form_state->getValue('view_modes')['display_mode'];
  }

}
