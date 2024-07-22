<?php

namespace Drupal\vactory_content_inline_edit\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;
use Drupal\vactory_content_inline_edit\Controller\VactoryContentInlineEditController;
use Drupal\Core\Pager\PagerParametersInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;

/**
 * Edit inline form.
 */
class VactoryContentInlineEditTableForm extends FormBase {

  /**
   * Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Page service.
   *
   * @var \Drupal\Core\Pager\PagerParametersInterface
   */
  protected $pagerParameters;

  /**
   * Current node id.
   *
   * @var int|null
   */
  protected $nodeId = NULL;

  /**
   * Constructor.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, PagerParametersInterface $pagerParameters) {
    $params = \Drupal::request()->query->all();
    if (array_key_exists('node', $params)) {
      $this->nodeId = $params['node'];
    }

    $this->entityTypeManager = $entityTypeManager;
    $this->pagerParameters = $pagerParameters;
  }

  /**
   * Create.
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('pager.parameters')
    );
  }

  /**
   * {@inheritDoc}
   */
  public function getFormId() {
    return 'vactory_content_inline_edit_table_form';
  }

  /**
   * {@inheritDoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#attached']['library'][] = 'vactory_content_inline_edit/vactory-content-inline-edit-js';

    // Node filter.
    $form['filter_wrapper'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Page Filter'),
    ];

    $form['filter_wrapper']['node_filter'] = [
      '#type' => 'entity_autocomplete',
      '#target_type' => 'node',
      '#selection_settings' => ['target_bundles' => ['vactory_page']],
      '#default_value' => !empty($this->nodeId) ? Node::load($this->nodeId) : $this->nodeId,
    ];

    $form['filter_wrapper']['reset'] = [
      '#type' => 'submit',
      '#value' => $this->t('Reset'),
      '#submit' => ['::resetFilter'],
    ];

    $form['filter_wrapper']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Filter'),
      '#submit' => ['::submitForm'],
    ];

    // Table and pager.
    $form['nodes_table_wrapper'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'nodes-table-wrapper'],
    ];

    $this->buildTable($form, $form_state);

    return $form;
  }

  /**
   * Build data to display node DFs.
   */
  private function buildTable(&$form, FormStateInterface $form_state) {
    if (is_null($this->nodeId)) {
      return;
    }
    $params = \Drupal::request()->query->all();
    if (!array_key_exists('node', $params)) {
      return;
    }
    $node_id = $params['node'];
    $current_page = max($this->pagerParameters->findPage(), 0);
    $num_per_page = 10;

    $controller = new VactoryContentInlineEditController();
    $nodes_data = $node_id ? $controller->getPaginatedNodeData($current_page, $node_id, $num_per_page) : [];

    // Define the table with headers.
    $form['nodes_table_wrapper']['nodes_table'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Title'),
        $this->t('Paragraphs'),
      ],
      '#empty' => $this->t('Please select a page'),
    ];

    foreach ($nodes_data as $node_data) {
      $row_key = 'node_' . $node_data['nodeId'];

      // Create the URL to the node edit page.
      $editUrl = Url::fromRoute('entity.node.edit_form', ['node' => $node_data['nodeId']]);
      // Create a Link object.
      $editLink = Link::fromTextAndUrl($node_data['title'], $editUrl)->toString();
      // Add the clickable link to the table.
      $form['nodes_table_wrapper']['nodes_table'][$row_key]['title'] = ['#markup' => $editLink];

      // Parent container for all paragraphs to enable horizontal layout.
      $form['nodes_table_wrapper']['nodes_table'][$row_key]['paragraphs'] = [
        '#type' => 'container',
        '#attributes' => ['class' => ['paragraphs-container']],
      ];

      foreach ($node_data['paragraphs'] as $paragraph) {
        if ($paragraph['type'] == 'vactory_component') {
          $paragraph_key = 'paragraph_' . $paragraph['paragraphId'];
          $form['nodes_table_wrapper']['nodes_table'][$row_key]['paragraphs'][$paragraph_key] = $this->createParagraphFields($node_data['nodeId'], $paragraph, $form, $form_state);
        }
        if ($paragraph['type'] == 'vactory_paragraph_multi_template') {
          $paragraph_key = 'paragraph_' . $paragraph['paragraphId'];
          $form['nodes_table_wrapper']['nodes_table'][$row_key]['paragraphs'][$paragraph_key] = $this->createParagraphMultiplePreview($node_data['nodeId'], $paragraph, $form, $form_state);
        }
      }
    }

    // Add the pager.
    $form['nodes_table_wrapper']['pager'] = [
      '#type' => 'pager',
      '#quantity' => $num_per_page,
    ];
  }

  /**
   * Generate paragraph fields.
   */
  private function createParagraphFields($nodeId, $paragraph, &$form, FormStateInterface $form_state) {
    $container = [
      '#type' => 'container',
      '#attributes' => ['class' => ['paragraph-wrapper']],
    ];

    $container['screenshot'] = [
      '#markup' => '<img src="' . $paragraph['screenshot'] . '" alt="DF Image" width="300" height="250">',
    ];
    $container['title'] = [
      '#markup' => '<h1>' . $paragraph['name'] . '</h1><hr>',
    ];

    if (isset($paragraph['elements']['extra_fields'])) {
      foreach ($paragraph['elements']['extra_fields'] as $fieldName => $fieldConfig) {
        if (str_starts_with($fieldName, 'group_')) {
          $container[$fieldName] = [
            '#type' => 'details',
            '#title' => $fieldName,
          ];
          foreach ($fieldConfig as $sub_key => $sub_config) {
            $container[$fieldName][$sub_key] = $this->createField($nodeId, $paragraph['paragraphId'], $sub_config, $sub_key, TRUE, NULL, $fieldName);
          }
        }
        else {
          $container[$fieldName] = $this->createField($nodeId, $paragraph['paragraphId'], $fieldConfig, $fieldName, TRUE);
        }
      }
    }

    if (isset($paragraph['elements']['components'])) {
      foreach ($paragraph['elements']['components'] as $index => $component) {
        foreach ($component as $fieldName => $fieldConfig) {
          if (str_starts_with($fieldName, 'group_')) {
            $container[$fieldName . '_' . $index] = [
              '#type' => 'details',
              '#title' => $fieldName,
            ];
            foreach ($fieldConfig as $sub_key => $sub_config) {
              $container[$fieldName . '_' . $index][$sub_key] = $this->createField($nodeId, $paragraph['paragraphId'], $sub_config, $sub_key, FALSE, $index, $fieldName);
            }
          }
          elseif (!in_array($fieldName, ['_weight', 'remove'])) {
            $container[$fieldName . '_' . $index] = $this->createField($nodeId, $paragraph['paragraphId'], $fieldConfig, $fieldName, FALSE, $index);
          }
        }
      }
    }

    return $container;
  }

  /**
   * Generate single fields.
   */
  private function createField($nodeId, $paragraphId, $fieldConfig, $fieldName, $isExtraField, $componentIndex = NULL, $group = NULL) {
    $field = [];

    switch ($fieldConfig['type']) {
      case 'text':
        $field = [
          '#type' => 'textfield',
          '#title' => $fieldConfig['label'],
          '#default_value' => $fieldConfig['value'],
          '#attributes' => [
            'class' => ['paragraph-field'],
            'data-original-value' => $fieldConfig['value'],
          ],
        ];
        break;

      case 'textarea':
        $field = [
          '#type' => 'textarea',
          '#title' => $fieldConfig['label'],
          '#default_value' => $fieldConfig['value'],
          '#attributes' => [
            'class' => ['paragraph-field'],
            'data-original-value' => $fieldConfig['value'],
          ],
        ];
        break;

      case 'text_format':
        $field = [
          '#type' => 'text_format',
          '#title' => $fieldConfig['label'],
          '#default_value' => $fieldConfig['value'],
          '#format' => $fieldConfig['format'] ?? 'full_html',
          '#attributes' => [
            'class' => ['paragraph-field'],
            'data-original-value' => $fieldConfig['value'],
            'data-field-format' => $fieldConfig['format'],
          ],
        ];
        break;

      case 'image':
        $field = [
          '#type' => 'media_library',
          '#title' => $fieldConfig['label'],
          '#allowed_bundles' => ['image'],
          '#default_value' => $fieldConfig['mid'],
          '#description' => t('Upload or select your image.'),
          '#attributes' => [
            'class' => ['paragraph-field'],
            'data-original-value' => $fieldConfig['mid'],
            'data-is-media' => 'true',
          ],
        ];
        break;

      case 'remote_video':
        $field = [
          '#type' => 'media_library',
          '#title' => $fieldConfig['label'],
          '#allowed_bundles' => ['remote_video'],
          '#default_value' => $fieldConfig['mid'],
          '#description' => t('Upload or select your remote video.'),
          '#attributes' => [
            'class' => ['paragraph-field'],
            'data-original-value' => $fieldConfig['mid'],
            'data-is-media' => 'true',
          ],
        ];
        break;

      case 'video':
        $field = [
          '#type' => 'media_library',
          '#title' => $fieldConfig['label'],
          '#allowed_bundles' => ['video'],
          '#default_value' => $fieldConfig['mid'],
          '#description' => t('Upload or select your video.'),
          '#attributes' => [
            'class' => ['paragraph-field'],
            'data-original-value' => $fieldConfig['mid'],
            'data-is-media' => 'true',
          ],
        ];
        break;

      case 'file':
        $field = [
          '#type' => 'media_library',
          '#title' => $fieldConfig['label'],
          '#allowed_bundles' => ['file'],
          '#default_value' => $fieldConfig['mid'],
          '#description' => t('Upload or select your file.'),
          '#attributes' => [
            'class' => ['paragraph-field'],
            'data-original-value' => $fieldConfig['mid'],
            'data-is-media' => 'true',
          ],
        ];
        break;

      case 'url_extended':
        $field = [
          '#type' => 'url_extended',
          '#title' => $fieldConfig['label'],
          '#size' => 30,
          '#default_value' => [
            'title' => $fieldConfig['title'],
            'url' => $fieldConfig['url'],
          ],
          '#attributes' => [
            'class' => ['paragraph-url-extended-field'],
            'data-original-title' => $fieldConfig['title'],
            'data-original-url' => $fieldConfig['url'],
          ],
        ];
        break;
    }

    // Handle additional data attributes for AJAX functionality.
    $field['#attributes']['data-node-id'] = $nodeId;
    $field['#attributes']['data-paragraph-id'] = $paragraphId;
    $field['#attributes']['data-field-name'] = $fieldName;
    if ($isExtraField) {
      $field['#attributes']['data-is-extra-field'] = 'true';
    }
    if ($componentIndex !== NULL) {
      $field['#attributes']['data-component-index'] = $componentIndex;
    }
    if (!is_null($group)) {
      $field['#attributes']['data-group'] = $group;
    }

    return $field;
  }

  /**
   * {@inheritDoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->setRedirect('vactory_content_inline_edit.admin_page', [
      'node' => $form_state->getValue('node_filter'),
    ]);
  }

  /**
   * Reset filter.
   */
  public function resetFilter(array &$form, FormStateInterface $form_state) {
    $form_state->setRedirect('vactory_content_inline_edit.admin_page');
  }

  /**
   * Generate preview for multiple paragraph.
   */
  private function createParagraphMultiplePreview($nodeId, $paragraph, &$form, FormStateInterface $form_state) {
    $container = [
      '#type' => 'container',
      '#attributes' => [
        'class' => [
          'paragraph-wrapper',
        ],
        'data-no-control' => 'true',
      ],
    ];

    $container['title'] = [
      '#markup' => '<h1>' . $paragraph['title'] . '</h1>',
    ];

    $container['intro'] = [
      '#markup' => '<p>' . $paragraph['introduction'] . '</p>',
    ];

    // Create the URL to the node edit page.
    $editUrl = Url::fromRoute('entity.node.edit_form', ['node' => $nodeId]);
    // Create a Link object.
    $editLink = Link::fromTextAndUrl('click here', $editUrl)->toString();
    // Add the clickable link to the table.
    $container['introduction'] = [
      '#markup' => 'This field represents a multiple-paragraph. To edit it, ' . $editLink,
    ];

    return $container;
  }

}
