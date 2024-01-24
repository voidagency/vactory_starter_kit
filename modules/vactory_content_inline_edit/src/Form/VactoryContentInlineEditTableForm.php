<?php

namespace Drupal\vactory_content_inline_edit\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\vactory_content_inline_edit\Controller\VactoryContentInlineEditController;
use Drupal\Core\Pager\PagerParametersInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;

class VactoryContentInlineEditTableForm extends FormBase
{

    protected $entityTypeManager;
    protected $pagerParameters;

    public function __construct(EntityTypeManagerInterface $entityTypeManager, PagerParametersInterface $pagerParameters) {
        $this->entityTypeManager = $entityTypeManager;
        $this->pagerParameters = $pagerParameters;
    }

    public static function create(ContainerInterface $container) {
        return new static(
            $container->get('entity_type.manager'),
            $container->get('pager.parameters')
        );
    }

    public function getFormId() {
        return 'vactory_content_inline_edit_table_form';
    }

    public function buildForm(array $form, FormStateInterface $form_state) {
        $form['#attached']['library'][] = 'vactory_content_inline_edit/vactory-content-inline-edit-js';

        // Node filter
        $form['filter_wrapper'] = [
            '#type' => 'fieldset',
            '#title' => $this->t('Page Filter'),
        ];

        $form['filter_wrapper']['node_filter'] = [
            '#type' => 'entity_autocomplete',
            '#target_type' => 'node',
            '#selection_settings' => ['target_bundles' => ['vactory_page']],
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

        // Table and pager
        $form['nodes_table_wrapper'] = [
            '#type' => 'container',
            '#attributes' => ['id' => 'nodes-table-wrapper'],
        ];

        // $form['ajax_submit'] = [
        //     '#type' => 'submit',
        //     '#value' => $this->t('AJAX Submit'),
        //     '#ajax' => [
        //         'callback' => '::ajaxFormSubmitHandler',
        //         'wrapper' => 'nodes-table-wrapper', // The ID of the form or part of the form to be replaced.
        //     ],
        //     '#attributes' => ['class' => ['visually-hidden','ajax-submit-button']], // Hide the button.
        // ];

        $this->buildTable($form, $form_state);

        return $form;
    }

    private function buildTable(&$form, FormStateInterface $form_state) {
        $node_id = $form_state->getValue('node_filter', NULL);
        $current_page = max($this->pagerParameters->findPage(), 0);
        $num_per_page = 10;

        $controller = new VactoryContentInlineEditController();
        $nodes_data = $node_id ? $controller->getPaginatedNodeData($current_page, $node_id, $num_per_page) : [];

        // Define the table with headers
        $form['nodes_table_wrapper']['nodes_table'] = [
            '#type' => 'table',
            '#header' => [
                $this->t('Title'),
                $this->t('Paragraphs')
            ],
            '#empty' => $this->t('Please select a page'),
        ];

        foreach ($nodes_data as $node_data) {
            $row_key = 'node_' . $node_data['nodeId'];

            // Create the URL to the node edit page
            $editUrl = Url::fromRoute('entity.node.edit_form', ['node' => $node_data['nodeId']]);
            // Create a Link object
            $editLink = Link::fromTextAndUrl($node_data['title'], $editUrl)->toString();
            // Add the clickable link to the table
            $form['nodes_table_wrapper']['nodes_table'][$row_key]['title'] = ['#markup' => $editLink];

            // Parent container for all paragraphs to enable horizontal layout
            $form['nodes_table_wrapper']['nodes_table'][$row_key]['paragraphs'] = [
                '#type' => 'container',
                '#attributes' => ['class' => ['paragraphs-container']],
            ];

            foreach ($node_data['paragraphs'] as $paragraph) {
                $paragraph_key = 'paragraph_' . $paragraph['paragraphId'];
                $form['nodes_table_wrapper']['nodes_table'][$row_key]['paragraphs'][$paragraph_key] = $this->createParagraphFields($node_data['nodeId'], $paragraph, $form, $form_state);
            }
        }

        // Add the pager
        $form['nodes_table_wrapper']['pager'] = [
            '#type' => 'pager',
            '#quantity' => $num_per_page,
        ];
    }

    private function createParagraphFields($nodeId, $paragraph, &$form, FormStateInterface $form_state) {
        $container = [
            '#type' => 'container',
            '#attributes' => ['class' => ['paragraph-wrapper']],
        ];

        $container['title'] = [
            '#markup' => '<h1>'. $this->t($paragraph['name']) . '</h1>',
        ];

        if (isset($paragraph['elements']['extra_fields'])) {
            foreach ($paragraph['elements']['extra_fields'] as $fieldName => $fieldConfig) {
                $container[$fieldName] = $this->createField($nodeId, $paragraph['paragraphId'], $fieldConfig, $fieldName, true);
            }
        }

        if (isset($paragraph['elements']['components'])) {
            foreach ($paragraph['elements']['components'] as $index => $component) {
                foreach ($component as $fieldName => $fieldConfig) {
                    $container[$fieldName] = $this->createField($nodeId, $paragraph['paragraphId'], $fieldConfig, $fieldName, false, $index);
                }
            }
        }

        return $container;
    }

    private function createField($nodeId, $paragraphId, $fieldConfig, $fieldName, $isExtraField, $componentIndex = null) {
        $field = [];

        switch ($fieldConfig['type']) {
            case 'text':
                $field = [
                    '#type' => 'textfield',
                    '#title' => $fieldConfig['label'],
                    '#default_value' => $fieldConfig['value'],
                    '#attributes' => [
                        'class' => ['paragraph-field'],
                        'data-original-value' => $fieldConfig['value']
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
                        'data-original-value' => $fieldConfig['value']
                    ],
                ];
                break;
            case 'text_format':
                $field = [
                    '#type' => 'text_format',
                    '#title' => $fieldConfig['label'],
                    '#default_value' => $fieldConfig['value'],
                    '#format' => $fieldConfig['format'] ?? 'full_html', // Default format
                    '#attributes' => [
                        'class' => ['paragraph-field'],
                        'data-original-value' => $fieldConfig['value'],
                        'data-field-format' => $fieldConfig['format']
                    ],
                ];
                break;
                // case 'image':
                //     $field = [
                //         '#type' => 'media_library',
                //         '#title' => $fieldConfig['label'],
                //         '#allowed_bundles' => ['image'],
                //         '#title' => $fieldConfig['label'],
                //         '#default_value' => $fieldConfig['mid'],
                //         '#description' => t('Upload or select your profile image.'),
                //         // '#attributes' => [
                //         //     'class' => ['paragraph-field'],
                //         //     'data-original-value' => $fieldConfig['mid']
                //         // ],
                //     ];
                //     break;

            case 'url_extended':
                $field = [
                    '#type' => 'url_extended',
                    '#title' => $fieldConfig['label'],
                    '#size' => 30,
                    '#default_value' => ['title' => $fieldConfig['title'], 'url' => $fieldConfig['url']],
                    '#attributes' => [
                        'class' => ['paragraph-url-extended-field'],
                        'data-original-title' => $fieldConfig['title'],
                        'data-original-url' => $fieldConfig['url'],
                    ],
                ];
                break;
        }

        // Handle additional data attributes for AJAX functionality
        $field['#attributes']['data-node-id'] = $nodeId;
        $field['#attributes']['data-paragraph-id'] = $paragraphId;
        $field['#attributes']['data-field-name'] = $fieldName;
        $field['#attributes']['data-is-extra-field'] = $isExtraField;
        if ($componentIndex !== null) {
            $field['#attributes']['data-component-index'] = $componentIndex;
        }

        return $field;
    }


    public function submitForm(array &$form, FormStateInterface $form_state) {
        $this->buildTable($form, $form_state);
        $form_state->setRebuild();
    }

    public function resetFilter(array &$form, FormStateInterface $form_state) {
        $form_state->setValue(['filter_wrapper', 'node_filter'], NULL);
        $this->buildTable($form, $form_state);
        $form_state->setRebuild();
    }

    // public function ajaxFormSubmitHandler(array &$form, FormStateInterface $form_state) {
    //     // Handle the form submission.
    //     // Form validation will automatically be triggered.

    //     $response = new AjaxResponse();

    //     if ($form_state->getErrors()) {
    //         // Handle validation errors.
    //         $response->addCommand(new ReplaceCommand('#nodes-table-wrapper', $form));
    //     } else {
    //         // Process the valid submission.
    //         // You can add more Ajax commands to the response as needed.
    //         // For example, to close a modal or update part of the page.
    //     }

    //     return $response;
    // }
}
