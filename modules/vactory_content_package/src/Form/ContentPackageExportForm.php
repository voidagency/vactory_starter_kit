<?php

namespace Drupal\vactory_content_package\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Menu\MenuLinkTreeInterface;

/**
 * Configure Vactory content package settings for this site.
 */
class ContentPackageExportForm extends FormBase {

  const FORM_AJAX_WRAPPER = 'content-package-export-form';

  /**
   * Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritDoc}
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, MenuLinkTreeInterface $menuLinkTree) {
    $this->entityTypeManager = $entityTypeManager;
    $this->menuLinkTree = $menuLinkTree;
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('menu.link_tree')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'vactory_content_package_export';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $nodes = $form_state->get('nodes') ?? [];
    $blocks = $form_state->get('blocks') ?? [];
    $menus = $form_state->get('menus') ?? [];
    $partial_export = $form_state->get('partial_export') ?? 0;
    $form = [
      '#prefix' => '<div id="' . static::FORM_AJAX_WRAPPER . '">',
      '#suffix' => '</div>',
    ];
    $form['partial_export'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Partial export'),
      '#description' => $this->t('Check this to only export specific pages'),
      '#default_value' => $partial_export,
    ];
    $form['partial_export_wrapper'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Partial export'),
      '#states' => [
        'visible' => [
          'input[name="partial_export"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['partial_export_wrapper']['input'] = [
      '#type' => 'entity_autocomplete',
      '#title' => t('Page'),
      '#target_type' => 'node',
      '#selection_settings' => [
        'target_bundles' => ['vactory_page'],
      ],
      '#description' => $this->t("Please select the desired page..."),
      '#ajax' => [
        'event' => 'autocompleteclose',
        'callback' => [$this, 'closeAutocompleteCallback'],
      ],
    ];

    if (!empty($nodes)) {
      $form['partial_export_wrapper']['nodes'] = [
        '#type' => 'table',
        '#header' => [
          'nid' => $this->t('Node ID'),
          'node_title' => $this->t('Page Title'),
          'action' => $this->t('Operation'),
        ],
        '#sticky' => TRUE,
      ];
      $nodes = $this->entityTypeManager->getStorage('node')
        ->loadMultiple($nodes);
      foreach ($nodes as $node) {
        $form['partial_export_wrapper']['nodes'][] = [
          'nid' => [
            '#markup' => $node->id(),
          ],
          'node_title' => [
            '#markup' => $node->label(),
          ],
          'action' => [
            '#type' => 'submit',
            '#value' => $this->t('Remove'),
            '#name' => "remove_node_{$node->id()}",
            '#submit' => [[$this, 'removeNodeSubmit']],
            '#attributes' => [
              'class' => ['button', 'button--danger'],
            ],
            '#ajax' => [
              'event' => 'click',
              'callback' => [$this, 'updateForm'],
              'wrapper' => static::FORM_AJAX_WRAPPER,
            ],
          ],
        ];
      }
    }

    $form['partial_export_wrapper']['input_block'] = [
      '#type' => 'entity_autocomplete',
      '#title' => t('Block'),
      '#target_type' => 'block_content',
      '#selection_settings' => [
        'target_bundles' => ['vactory_block_component'],
      ],
      '#description' => $this->t("Please select the desired block..."),
      '#ajax' => [
        'event' => 'autocompleteclose',
        'callback' => [$this, 'closeAutocompleteCallback'],
      ],
    ];

    if (!empty($blocks)) {
      $form['partial_export_wrapper']['blocks'] = [
        '#type' => 'table',
        '#header' => [
          'block_id' => $this->t('Block ID'),
          'block_title' => $this->t('Block Title'),
          'action_block' => $this->t('Operation'),
        ],
        '#sticky' => TRUE,
      ];
      $blocks = $this->entityTypeManager->getStorage('block_content')
        ->loadMultiple($blocks);
      foreach ($blocks as $block) {
        $form['partial_export_wrapper']['blocks'][] = [
          'block_id' => [
            '#markup' => $block->id(),
          ],
          'block_title' => [
            '#markup' => $block->label(),
          ],
          'action_block' => [
            '#type' => 'submit',
            '#value' => $this->t('Remove'),
            '#name' => "remove_block_{$block->id()}",
            '#submit' => [[$this, 'removeBlockSubmit']],
            '#attributes' => [
              'class' => ['button', 'button--danger'],
            ],
            '#ajax' => [
              'event' => 'click',
              'callback' => [$this, 'updateForm'],
              'wrapper' => static::FORM_AJAX_WRAPPER,
            ],
          ],
        ];
      }
    }

    $form['partial_export_wrapper']['input_menu'] = [
      '#type' => 'entity_autocomplete',
      '#title' => t('Menu'),
      '#target_type' => 'menu',
      '#description' => $this->t("Please select the desired menu..."),
      '#ajax' => [
        'event' => 'autocompleteclose',
        'callback' => [$this, 'closeAutocompleteCallback'],
      ],
    ];

    if (!empty($menus)) {
      $form['partial_export_wrapper']['menus'] = [
        '#type' => 'table',
        '#header' => [
          'menu_id' => $this->t('Menu ID'),
          'menu_title' => $this->t('Menu Title'),
          'action_menu' => $this->t('Operation'),
        ],
        '#sticky' => TRUE,
      ];
      $menus = $this->entityTypeManager->getStorage('menu')
        ->loadMultiple($menus);
      foreach ($menus as $menu) {
        $form['partial_export_wrapper']['menus'][] = [
          'menu_id' => [
            '#markup' => $menu->id(),
          ],
          'menu_title' => [
            '#markup' => $menu->label(),
          ],
          'action_menu' => [
            '#type' => 'submit',
            '#value' => $this->t('Remove'),
            '#name' => "remove_menu_{$menu->id()}",
            '#submit' => [[$this, 'removeMenuSubmit']],
            '#attributes' => [
              'class' => ['button', 'button--danger'],
            ],
            '#ajax' => [
              'event' => 'click',
              'callback' => [$this, 'updateForm'],
              'wrapper' => static::FORM_AJAX_WRAPPER,
            ],
          ],
        ];
      }
    }

    $form['partial_export_wrapper']['update'] = [
      '#type' => 'submit',
      '#name' => 'update_cp_export_form',
      '#value' => $this->t('Update'),
      '#submit' => [[$this, 'updateFormSubmit']],
      '#attributes' => [
        'class' => ['js-hide'],
      ],
      '#ajax' => [
        'event' => 'click',
        'callback' => [$this, 'updateForm'],
        'wrapper' => static::FORM_AJAX_WRAPPER,
      ],
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t("Start export process"),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * Close autocomplete callback.
   */
  public function closeAutocompleteCallback(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $response->addCommand(new InvokeCommand("input[name=\"update_cp_export_form\"]", 'click', []));
    return $response;
  }

  /**
   * Update form.
   */
  public function updateForm(array &$form, FormStateInterface $form_state) {
    return $form;
  }

  /**
   * Update form submit callback.
   */
  public function updateFormSubmit(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $nodes = $form_state->get('nodes') ?? [];
    $blocks = $form_state->get('blocks') ?? [];
    $menus = $form_state->get('menus') ?? [];
    $user_inputs = $form_state->getUserInput();
    if (isset($values['input']) && !empty($values['input'])) {
      $nodes[] = $values['input'];
      $nodes = array_unique($nodes);
      $form_state->set('nodes', $nodes);
    }
    if (isset($values['input_block']) && !empty($values['input_block'])) {
      $blocks[] = $values['input_block'];
      $blocks = array_unique($blocks);
      $form_state->set('blocks', $blocks);
    }
    if (isset($values['input_menu']) && !empty($values['input_menu'])) {
      $menus[] = $values['input_menu'];
      $menus = array_unique($menus);
      $form_state->set('menus', $menus);
    }
    $partial_export = $values['partial_export'] ?? 0;
    $user_inputs['input'] = '';
    $user_inputs['input_block'] = '';
    $user_inputs['input_menu'] = '';
    $form_state->setUserInput($user_inputs);
    $form_state->set('partial_export', $partial_export);
    $form_state->setRebuild();
  }

  /**
   * Remove node submit callback.
   */
  public function removeNodeSubmit(array &$form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    $nid = str_replace('remove_node_', '', $triggering_element['#name']);
    $nodes = $form_state->get('nodes') ?? [];
    $nid_index = array_search($nid, $nodes);
    unset($nodes[$nid_index]);
    $form_state->set('nodes', $nodes);
    $form_state->setRebuild();
  }

  /**
   * Remove block submit callback.
   */
  public function removeBlockSubmit(array &$form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    $block_id = str_replace('remove_block_', '', $triggering_element['#name']);
    $blocks = $form_state->get('blocks') ?? [];
    $block_id_index = array_search($block_id, $blocks);
    unset($blocks[$block_id_index]);
    $form_state->set('blocks', $blocks);
    $form_state->setRebuild();
  }

  /**
   * Remove menu submit callback.
   */
  public function removeMenuSubmit(array &$form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    $menu_id = str_replace('remove_menu_', '', $triggering_element['#name']);
    $menus = $form_state->get('menus') ?? [];
    $menu_id_index = array_search($menu_id, $menus);
    unset($menus[$menu_id_index]);
    $form_state->set('menus', $menus);
    $form_state->setRebuild();
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $nodes = $form_state->get('nodes') ?? NULL;
    $blocks = $form_state->get('blocks') ?? NULL;
    $menus = $form_state->get('menus') ?? NULL;
    $is_partial = $form_state->get('partial_export') ?? FALSE;
    \Drupal::logger('vactory_content_package_export_form')->debug(sprintf("nodes: %s, blocks: %s, menus: %s", json_encode($nodes), json_encode($blocks), json_encode($menus)));

    // Zip nodes.
    \Drupal::service('vactory_content_package.archiver.manager')
      ->zipContentTypeNodes('vactory_page', $nodes, $blocks, $menus, $is_partial);
  }

}
