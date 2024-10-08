<?php

namespace Drupal\vactory_dynamic_field_dummy\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;
use Drupal\vactory_dynamic_field_dummy\Services\GenerateDummyPageService;

/**
 * Implements generate page form.
 */
class GeneratePageForm extends FormBase {

  /**
   * The custom module path suffix.
   */
  const CUSTOM_MODULES_PATH = "modules/custom";

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'generate_page_with_all_dfs_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Page Title'),
      '#required' => TRUE,
      '#placeholder' => $this->t('Page title'),
    ];

    $langs = [];
    foreach (\Drupal::languageManager()->getLanguages() as $language) {
      $langs[$language->getId()] = $language->getName();
    }

    $form['languages'] = [
      '#type' => 'select',
      '#multiple' => TRUE,
      '#title' => $this->t('Languages'),
      '#description' => $this->t('Select the languages you would like to use. If none are selected, the page will be created in all available languages.'),
      '#options' => $langs,
    ];

    $vactoryProviderManager = \Drupal::service('vactory_dynamic_field.vactory_provider_manager');
    $widgets_list = $vactoryProviderManager->getModalWidgetsList([]);

    $form['advanced'] = [
      '#type' => 'details',
      '#title' => t('Available widgets'),
      '#open' => FALSE,
    ];

    $form['advanced']['templates_tabs'] = [
      '#type' => 'horizontal_tabs',
      '#group_name' => 'templates_tabs',
    ];
    $form['advanced']['settings_tab'] = [
      '#type' => 'vertical_tabs',
    ];

    $renderer = \Drupal::service('renderer');

    foreach ($widgets_list as $category => $widgets) {
      if (!empty($widgets)) {
        if (empty($category)) {
          $category = 'Others';
        }
        if (!isset($form['advanced']['templates_tabs'][$category])) {
          $form['advanced']['templates_tabs'][$category] = [
            '#type' => 'details',
            '#title' => ucfirst($category),
          ];
          if ($category == 'Others') {
            $form['advanced']['templates_tabs'][$category]['#weight'] = 99;
          }
        }

        $form['advanced']['templates_tabs'][$category]['widgets'] = [
          '#type' => 'container',
          '#attributes' => ['class' => ['widget-grid']],
        ];

        $row = [];
        $count = 0;

        foreach ($widgets as $widget_id => $widget) {
          $file_url_generator = \Drupal::service('file_url_generator');
          $undefined_screenshot = \Drupal::service('extension.path.resolver')
            ->getPath('module', 'vactory_dynamic_field') . '/images/undefined-screenshot.jpg';
          $widget_preview = [
            '#theme' => 'vactory_dynamic_select_template',
            '#content' => [
              'screenshot_url' => !empty($widget['screenshot']) ? $widget['screenshot'] : $file_url_generator->generateAbsoluteString($undefined_screenshot),
              'name' => $widget['name'],
            ],
          ];

          $row['selected_widget'][$widget_id] = [
            '#type' => 'checkbox',
            '#title' => $renderer->renderPlain($widget_preview),
            '#return_value' => $widget['uuid'],
          ];

          $count++;

          if ($count % 3 == 0 || $count == count($widgets)) {
            $form['advanced']['templates_tabs'][$category]['widgets'][] = [
              '#type' => 'container',
              '#attributes' => ['class' => ['widget-row']],
              'items' => $row,
            ];
            $row = [];
          }
        }
      }
    }
    $form['#attached']['library'][] = 'vactory_dynamic_field_dummy/widgets';

    $form['alert'] = [
      '#type' => 'markup',
      '#markup' => '<h5>⚠️ If no widgets are selected, the page will be created using all widgets under custom modules.</h5>',
    ];

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Run generate page'),
      '#button_type' => 'primary',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $selected_templates = array_values($form_state->getValues());
    $selected_templates_res = [];
    foreach ($selected_templates as $selected_template) {
      if (is_string($selected_template) && GenerateDummyPageService::matchesPattern($selected_template)) {
        array_push($selected_templates_res, $selected_template);
      }
    }
    $this->runCreatePage($form_state->getValues(), $selected_templates_res);
  }

  /**
   * Run create page function.
   */
  public function runCreatePage($values, $selected_widgets = []) {
    $current_lang = \Drupal::languageManager()->getCurrentLanguage();
    $langs = $values['languages'] ?? [];
    $title = $values['title'] ?? "";
    $vactoryProviderManager = \Drupal::service('vactory_dynamic_field.vactory_provider_manager');
    $widget_list = $vactoryProviderManager->getWidgetsList();
    $node = [
      'type' => 'vactory_page',
      'title' => $title,
      'langcode' => $current_lang->getId(),
    ];
    if (\Drupal::moduleHandler()->moduleExists('content_moderation')) {
      $node['moderation_state'] = 'published';
    }
    if (!empty($langs)) {
      $langs = array_values($langs);
      $node['langcode'] = $langs[0];
    }

    $node_entity = Node::create($node);
    $node_entity->setPublished(TRUE);
    $node_entity->isNew();
    $node_entity->save();

    // Create batches.
    $chunk_size = 5;
    $chunks = array_chunk($widget_list, $chunk_size);
    if (!empty($selected_widgets)) {
      $chunks = array_chunk($selected_widgets, $chunk_size);
    }
    $num_chunks = count($chunks);

    // Submit batches.
    $operations = [];
    for ($batch_id = 0; $batch_id < $num_chunks; $batch_id++) {
      $operations[] = [
        '\Drupal\vactory_dynamic_field_dummy\Form\GeneratePageForm::processBatch',
        [
          $batch_id + 1,
          $chunks[$batch_id],
          !empty($selected_widgets),
          $node_entity,
        ],
      ];
    }
    $batch = [
      'title' => $this->t('process Creating page'),
      'init_message' => $this->t('Starting to process.'),
      'progress_message' => $this->t('Completed @current out of @total batches.'),
      'finished' => '\Drupal\vactory_dynamic_field_dummy\Form\GeneratePageForm::batchFinished',
      'error_message' => $this->t('Creating page processing has encountered an error.'),
      'operations' => $operations,
    ];
    batch_set($batch);
    if (empty($langs)) {
      foreach (\Drupal::languageManager()->getLanguages() as $language) {
        if ($language->getId() !== $current_lang->getId()) {
          $translation = $node_entity->addTranslation($language->getId());
          $translation->set('title', $title);
          $translation->save();
        }
      }
    }
    else {
      foreach ($langs as $lang) {
        if ($lang !== $node['langcode']) {
          $translation = $node_entity->addTranslation($lang);
          $translation->set('title', $title);
          $translation->save();
        }
      }
    }
  }

  /**
   * Process batch.
   */
  public static function processBatch(int $batch_id, array $widget_list, $is_select_templates, $node, array &$context): void {
    if (!isset($context['sandbox']['progress'])) {
      $context['sandbox']['progress'] = 0;
      $context['sandbox']['current_node'] = 0;
      $context['sandbox']['max'] = 0;
    }
    if (!isset($context['results']['updated'])) {
      $context['results']['updated'] = 0;
      $context['results']['skipped'] = 0;
      $context['results']['failed'] = 0;
      $context['results']['progress'] = 0;
    }

    // Keep track of progress.
    $context['results']['progress'] += count($widget_list);
    $context['results']['process'] = 'Import request files';
    // Message above progress bar.
    $context['message'] = t('Processing batch #@batch_id batch size @batch_size for total @count items.', [
      '@batch_id' => number_format($batch_id),
      '@batch_size' => number_format(count($widget_list)),
      '@count' => number_format($context['sandbox']['max']),
    ]);
    $vactoryProviderManager = \Drupal::service('vactory_dynamic_field.vactory_provider_manager');
    if ($is_select_templates) {
      foreach ($widget_list as $widget_id) {
        $widget = $vactoryProviderManager->loadSettings($widget_id);
        $widget_data = GenerateDummyPageService::prepareContent($widget);
        $paragraph = GenerateDummyPageService::createParagraph($widget_id, $widget_data);
        $context['results']['node'] = $node;
        $context['results']['field_vactory_paragraphs'][] = [
          'target_id' => $paragraph->id(),
          'target_revision_id' => \Drupal::entityTypeManager()
            ->getStorage('paragraph')
            ->getLatestRevisionId($paragraph->id()),
        ];
      }
    }
    else {
      foreach ($widget_list as $widgets) {
        foreach ($widgets as $widget_id => $widget) {
          $path = $vactoryProviderManager->getWidgetsPath($widget_id);
          if (str_starts_with($path, self::CUSTOM_MODULES_PATH)) {
            $widget_data = GenerateDummyPageService::prepareContent($widget);
            $paragraph = GenerateDummyPageService::createParagraph($widget_id, $widget_data);
            $context['results']['node'] = $node;
            $context['results']['field_vactory_paragraphs'][] = [
              'target_id' => $paragraph->id(),
              'target_revision_id' => \Drupal::entityTypeManager()
                ->getStorage('paragraph')
                ->getLatestRevisionId($paragraph->id()),
            ];
          }
        }
      }
    }
  }

  /**
   * Batch finished.
   */
  public static function batchFinished(bool $success, array $results, array $operations, string $elapsed) {
    $messenger = \Drupal::messenger();
    if ($success) {
      $node = $results['node'];
      $paragraphs = $results['field_vactory_paragraphs'];
      $node->set('field_vactory_paragraphs', $paragraphs);
      $node->save();
      $messenger->addMessage(t('Creating pages with process was successful.'));
    }
    else {
      // An error occurred.
      $error_operation = reset($operations);
      $message = t('An error occurred while processing %error_operation with arguments: @arguments', [
        '%error_operation' => $error_operation[0],
        '@arguments' => print_r($error_operation[1], TRUE),
      ]);
      $messenger->addError($message);
    }
  }

}
