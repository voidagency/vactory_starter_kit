<?php

namespace Drupal\vactory_page_import\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;
use Drupal\node\Entity\Node;
use Drupal\paragraphs\Entity\Paragraph;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Imports (create/update) page from excel.
 */
class Import extends FormBase {

  /**
   * Page import helpers service.
   *
   * @var \Drupal\vactory_page_import\Services\PageImportService
   */
  protected $pageImportService;

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->pageImportService = $container->get('vactory_page_import.helpers');
    return $instance;
  }

  /**
   * Returns a unique string identifying the form.
   *
   * The returned ID should be a unique string that can be a valid PHP function
   * name, since it's used in hook implementation names such as
   * hook_form_FORM_ID_alter().
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'vactory_page_import.import';
  }

  /**
   * Form constructor.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The form structure.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['excel'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Excel file'),
      '#name' => 'csv',
      '#upload_location' => 'private://page-import',
      '#upload_validators' => [
        'file_validate_extensions' => ['xlsx'],
      ],
      '#description' => t("Load the Excel file to import.<br>"),
      '#required' => TRUE,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t("Start process"),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $file_id = $form_state->getValue('excel');
    $file_id = (int) reset($file_id);
    $file = File::load($file_id);
    $file_path = \Drupal::service('file_system')
      ->realpath($file->getFileUri());
    $data = $this->pageImportService->readExcelToArray($file_path);
    foreach ($data as $key => $page) {
      // Check if page alrady exists (by node_id).
      $nid = (int) ($page['original']['id'] ?? -1);
      $query = \Drupal::entityTypeManager()->getStorage('node')->getQuery();
      $query->accessCheck(FALSE);
      $query->condition('type', 'vactory_page');
      $query->condition('nid', $nid);
      $ids = $query->execute();
      $df_settings = $this->pageImportService->prepareDfSettings($page['original']);
      if (empty($ids)) {
        $this->createNode($key, $page, $df_settings);
      }
      elseif (count($ids) == 1) {
        // Load the page (node) and update it.
        $nid = reset($ids);
        $node = Node::load($nid);
        $this->updatePage($node, $page, $df_settings);
      }
    }
  }

  /**
   * Created node (page).
   */
  private function createNode($page_key, $data, $df_settings) {
    // @todo Insure that original is the first item
    $node_entity = NULL;
    foreach ($data as $language => $language_value) {
      $node = [
        'type' => 'vactory_page',
        'status' => 1,
        'node_id' => $page_key,
      ];
      foreach ($language_value as $key => $value) {
        $split = explode('|', $key);
        if (str_starts_with($key, 'paragraph')) {
          $widget_id = end($split);
          if (str_starts_with($widget_id, 'vactory_page_import:')) {
            $single_df_settings = [$widget_id => $df_settings[$widget_id]];
            $this->pageImportService->createDynamicFields($single_df_settings);
          }
          $widget_data = $this->pageImportService->normalizeWidgetData($value);
          $paragraph = [
            "type" => "vactory_component",
            "paragraph_key" => $page_key . '|' . $key,
            "field_vactory_title" => $this->pageImportService->snakeToHuman(end($split)),
            "field_vactory_component" => [
              "widget_id" => $widget_id,
              "widget_data" => $widget_data,
            ],
          ];
          if ($language == 'original') {
            $paragraph = Paragraph::create($paragraph);
            $paragraph->save();
            $node['field_vactory_paragraphs'][] = [
              'target_id' => $paragraph->id(),
              'target_revision_id' => \Drupal::entityTypeManager()
                ->getStorage('paragraph')
                ->getLatestRevisionId($paragraph->id()),
            ];
          }
          else {
            $concerned_paragraph = $this->pageImportService->findParagraphByNodeAndKey($node_entity, $key);
            $concerned_paragraph->addTranslation($language, $paragraph);
            $concerned_paragraph->save();
          }

        }
        elseif (str_starts_with($key, 'multiple')) {
          $multi_paragraph_type = end($split);
          $paragraph = [
            'type' => 'vactory_paragraph_multi_template',
            'paragraph_key' => $page_key . '|' . $key,
            'field_multi_paragraph_type' => $multi_paragraph_type,
            'field_vactory_paragraph_tab' => [],
          ];
          foreach ($value as $tab_key => $tab_values) {
            $split_key = explode('|', $tab_key);
            $tab_title = $this->pageImportService->snakeToHuman(end($split_key));
            $paragraph_tab_template = [
              "type" => "vactory_paragraph_tab",
              "paragraph_key" => $page_key . '|' . $key . '|' . $tab_key,
              "field_vactory_title" => $tab_title,
              "field_tab_templates" => [],
            ];
            $templates = [];
            foreach ($tab_values as $field_key => $field_value) {
              $split = explode('|', $field_key);
              $dynamic_field_id = reset($split);
              $dynamic_field_field = substr($field_key, strlen($dynamic_field_id) + 1);
              $templates[$dynamic_field_id][$dynamic_field_field] = $field_value;
            }
            foreach ($templates as $df_key => $data) {
              $widget_data = $this->pageImportService->normalizeWidgetData($data);
              if (str_starts_with($df_key, 'vactory_page_import:')) {
                $single_df_settings = [$df_key => $df_settings[$df_key]];
                $this->pageImportService->createDynamicFields($single_df_settings);
              }
              $paragraph_tab_template['field_tab_templates'][] = [
                "widget_id" => $df_key,
                "widget_data" => $widget_data,
              ];
            }
            $paragraph_tab_template = Paragraph::create($paragraph_tab_template);
            $paragraph_tab_template->save();
            $paragraph['field_vactory_paragraph_tab'][] = [
              'target_id' => $paragraph_tab_template->id(),
              'target_revision_id' => \Drupal::entityTypeManager()
                ->getStorage('paragraph')
                ->getLatestRevisionId($paragraph_tab_template->id()),
            ];
          }
          $paragraph = Paragraph::create($paragraph);
          $paragraph->save();
          $node['field_vactory_paragraphs'][] = [
            'target_id' => $paragraph->id(),
            'target_revision_id' => \Drupal::entityTypeManager()
              ->getStorage('paragraph')
              ->getLatestRevisionId($paragraph->id()),
          ];
        }
        else {
          $node[$key] = $value;
        }
      }
      if ($language == 'original') {
        $node_entity = Node::create($node);
        $node_entity->save();
      }
      else {
        $node_entity->addTranslation($language, $node);
        $node_entity->save();
      }
    }

  }

  /**
   * Updates the node (page).
   */
  private function updatePage(Node $node, $page, $df_settings) {
    $node_id = $node->get('node_id')->value;
    foreach ($page as $language => $data) {
      if ($language !== 'original' && !$node->hasTranslation($language)) {
        $node_translation = [];
        foreach ($data as $field => $value) {
          if (!str_starts_with($field, 'paragraph') && !str_starts_with($field, 'multiple')) {
            $node_translation[$field] = $value;
          }
        }
        $node->addTranslation($language, $node_translation);
        $node->save();
      }
      foreach ($data as $key => $value) {
        if (str_starts_with($key, 'paragraph')) {
          // Search for paragraph with identifier [node_id]|$key.
          $paragraph_entity = $this->pageImportService->findParagraphByNodeAndKey($node, $key);
          $split = explode('|', $key);
          if (str_starts_with(end($split), 'vactory_page_import:')) {
            $single_df_settings = [end($split) => $df_settings[end($split)]];
            $widget_id = end($split);
            $this->pageImportService->createDynamicFields($single_df_settings);
          }
          else {
            $widget_id = end($split);
          }

          $widget_data = $this->pageImportService->normalizeWidgetData($value);
          $field_vactory_component = [
            "widget_id" => $widget_id,
            "widget_data" => $widget_data,
          ];
          // Add new paragraph.
          $paragraph = [
            "type" => "vactory_component",
            "paragraph_key" => $node_id . '|' . $key,
            "field_vactory_title" => $this->pageImportService->snakeToHuman(end($split)),
            "field_vactory_component" => $field_vactory_component,
          ];
          if ($language == 'original') {
            if (!empty($paragraph_entity)) {
              // Update founded paragraph.
              $paragraph_entity->field_vactory_component = $field_vactory_component;
              $paragraph_entity->save();
            }
            else {
              $paragraph = Paragraph::create($paragraph);
              $paragraph->save();
              $node->field_vactory_paragraphs[] = [
                'target_id' => $paragraph->id(),
                'target_revision_id' => \Drupal::entityTypeManager()
                  ->getStorage('paragraph')
                  ->getLatestRevisionId($paragraph->id()),
              ];
              $node->save();
            }
          }
          else {
            if ($paragraph_entity->hasTranslation($language)) {
              $concerned_paragraph_trans = $paragraph_entity->getTranslation($language);
              $concerned_paragraph_trans->field_vactory_component = $field_vactory_component;
              $concerned_paragraph_trans->save();
            }
            else {
              $paragraph_entity->addTranslation($language, $paragraph);
              $paragraph_entity->save();
            }
          }
        }
        elseif (str_starts_with($key, 'multiple')) {
          $paragraph_entity = $this->pageImportService->findParagraphByNodeAndKey($node, $key);
          if ($paragraph_entity) {
            foreach ($value as $tab_key => $tab_values) {
              $paragraph_identifier = $key . '|' . $tab_key;

              $templates = [];
              $tab_templates = [];
              foreach ($tab_values as $field_key => $field_value) {
                $split = explode('|', $field_key);
                $dynamic_field_id = reset($split);
                $dynamic_field_field = substr($field_key, strlen($dynamic_field_id) + 1);
                $templates[$dynamic_field_id][$dynamic_field_field] = $field_value;
              }
              foreach ($templates as $df_key => $data) {
                $widget_data = $this->pageImportService->normalizeWidgetData($data);
                if (str_starts_with($df_key, 'vactory_page_import:')) {
                  $single_df_settings = [reset($split) => $df_settings[reset($split)]];
                  $widget_id = $df_key;
                  $this->pageImportService->createDynamicFields($single_df_settings);
                }
                else {
                  $widget_id = $df_key;
                }
                $tab_templates[] = [
                  "widget_id" => $widget_id,
                  "widget_data" => $widget_data,
                ];
              }

              $paragraph_tab_entity = $this->pageImportService->findParagraphTabByParagraph($paragraph_entity, $paragraph_identifier, $node_id);
              if ($paragraph_tab_entity) {
                $paragraph_tab_entity->field_tab_templates = $tab_templates;
                $paragraph_tab_entity->save();
              }
              else {
                $split_key = explode('|', $tab_key);
                $tab_title = $this->pageImportService->snakeToHuman(end($split_key));
                $paragraph_tab_template = [
                  "type" => "vactory_paragraph_tab",
                  "paragraph_key" => $node_id . '|' . $key . '|' . $tab_key,
                  "field_vactory_title" => $tab_title,
                  "field_tab_templates" => $tab_templates,
                ];
                $paragraph_tab_template = Paragraph::create($paragraph_tab_template);
                $paragraph_tab_template->save();

                $paragraph_entity->field_vactory_paragraph_tab[] = [
                  'target_id' => $paragraph_tab_template->id(),
                  'target_revision_id' => \Drupal::entityTypeManager()
                    ->getStorage('paragraph')
                    ->getLatestRevisionId($paragraph_tab_template->id()),
                ];
                $paragraph_entity->save();
              }
            }
          }
          else {
            $split = explode('|', $key);
            $multi_paragraph_type = end($split);
            $paragraph = [
              'type' => 'vactory_paragraph_multi_template',
              'paragraph_key' => $node_id . '|' . $key,
              'field_multi_paragraph_type' => $multi_paragraph_type,
              'field_vactory_paragraph_tab' => [],
            ];
            foreach ($value as $tab_key => $tab_values) {
              $split_key = explode('|', $tab_key);
              $tab_title = $this->pageImportService->snakeToHuman(end($split_key));
              $paragraph_tab_template = [
                "type" => "vactory_paragraph_tab",
                "paragraph_key" => $node_id . '|' . $key . '|' . $tab_key,
                "field_vactory_title" => $tab_title,
                "field_tab_templates" => [],
              ];
              $templates = [];
              foreach ($tab_values as $field_key => $field_value) {
                $split = explode('|', $field_key);
                $dynamic_field_id = reset($split);
                $dynamic_field_field = substr($field_key, strlen($dynamic_field_id) + 1);
                $templates[$dynamic_field_id][$dynamic_field_field] = $field_value;
              }
              foreach ($templates as $df_key => $data) {
                $widget_data = $this->pageImportService->normalizeWidgetData($data);
                if (str_starts_with($df_key, 'vactory_page_import:')) {
                  $single_df_settings = [reset($split) => $df_settings[reset($split)]];
                  $widget_id = $df_key;
                  $this->pageImportService->createDynamicFields($single_df_settings);
                }
                else {
                  $widget_id = $df_key;
                }
                $paragraph_tab_template['field_tab_templates'][] = [
                  "widget_id" => $widget_id,
                  "widget_data" => $widget_data,
                ];
              }
              $paragraph_tab_template = Paragraph::create($paragraph_tab_template);
              $paragraph_tab_template->save();
              $paragraph['field_vactory_paragraph_tab'][] = [
                'target_id' => $paragraph_tab_template->id(),
                'target_revision_id' => \Drupal::entityTypeManager()
                  ->getStorage('paragraph')
                  ->getLatestRevisionId($paragraph_tab_template->id()),
              ];
            }
            $paragraph = Paragraph::create($paragraph);
            $paragraph->save();
            $node->field_vactory_paragraphs[] = [
              'target_id' => $paragraph->id(),
              'target_revision_id' => \Drupal::entityTypeManager()
                ->getStorage('paragraph')
                ->getLatestRevisionId($paragraph->id()),
            ];
            $node->save();
          }

        }
        elseif (!in_array($key, ['language', 'id', 'machine_name'])) {
          $concerned_node_trans = $node;
          if ($language !== 'original' && $node->hasTranslation($language)) {
            $concerned_node_trans = $node->getTranslation($language);
          }
          $concerned_node_trans->set($key, $value);
          $concerned_node_trans->save();
        }
      }
    }
  }

}
