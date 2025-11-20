<?php

namespace Drupal\vactory_diff_config_client\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\vactory_diff_config_client\Service\TodoListGeneratorService;
use Drupal\vactory_diff_config_client\Service\ModuleInstallationService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller for displaying the to-do list.
 */
class TodoListController extends ControllerBase {

  /**
   * The to-do list generator service.
   *
   * @var \Drupal\vactory_diff_config_client\Service\TodoListGeneratorService
   */
  protected $todoListGenerator;

  /**
   * The module installation service.
   *
   * @var \Drupal\vactory_diff_config_client\Service\ModuleInstallationService
   */
  protected $moduleInstallation;

  /**
   * Constructs a TodoListController object.
   *
   * @param \Drupal\vactory_diff_config_client\Service\TodoListGeneratorService $todo_list_generator
   *   The to-do list generator service.
   * @param \Drupal\vactory_diff_config_client\Service\ModuleInstallationService $module_installation
   *   The module installation service.
   */
  public function __construct(
    TodoListGeneratorService $todo_list_generator,
    ModuleInstallationService $module_installation
  ) {
    $this->todoListGenerator = $todo_list_generator;
    $this->moduleInstallation = $module_installation;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('vactory_diff_config_client.todo_list_generator'),
      $container->get('vactory_diff_config_client.module_installation')
    );
  }

  /**
   * Display the to-do list page.
   *
   * @return array
   *   Render array.
   */
  public function view(): array {
    $todo_list = $this->todoListGenerator->generateTodoList();
    $module_changes = $this->moduleInstallation->analyzeModuleChanges();

    if ($this->isEmptyTodoList($todo_list, $module_changes)) {
      $this->messenger()->addWarning($this->t('No comparison results found. Please run a comparison first.'));
      return [
        '#markup' => $this->t('No TODO items to display. Please <a href="@url">run a comparison</a> first.', [
          '@url' => '/admin/config/development/vactory-diff/compare',
        ]),
      ];
    }

    $build = [];
    $build['download_button'] = $this->buildDownloadButton();
    $build['summary'] = $this->buildSummarySection($todo_list, $module_changes);

    if ($module_changes['has_changes']) {
      $build['modules'] = $this->buildModuleSection($module_changes);
    }

    if (!empty($todo_list['features'])) {
      $build += $this->buildFeaturesSection($todo_list);
    }

    if (!empty($todo_list['unmatched_configs'])) {
      $build['unmatched'] = $this->buildUnmatchedSection($todo_list);
    }

    if (!empty($todo_list['content_sync']) && ($todo_list['content_sync']['has_changes'] ?? FALSE)) {
      $build['content_sync'] = $this->buildContentSyncSection($todo_list);
    }

    $build['#attached']['library'][] = 'vactory_diff_config_client/comparison';

    return $build;
  }

  /**
   * Check if the to-do list is empty.
   */
  protected function isEmptyTodoList(array $todo_list, array $module_changes): bool {
    return empty($todo_list['features'])
      && empty($todo_list['unmatched_configs'])
      && !$module_changes['has_changes'];
  }

  /**
   * Build the download button section.
   *
   * @return array
   *   Render array for download button.
   */
  protected function buildDownloadButton(): array {
    return [
      '#type' => 'container',
      '#attributes' => ['class' => ['vactory-diff-download-section']],
      'link' => [
        '#type' => 'link',
        '#title' => $this->t('Télécharger la TODO list'),
        '#url' => Url::fromRoute('vactory_diff_config_client.todo_list_download'),
        '#attributes' => [
          'class' => [
            'button',
            'button--primary',
            'vactory-diff-download-button',
          ],
          'target' => '_blank',
        ],
      ],
    ];
  }

  /**
   * Build the summary section.
   */
  protected function buildSummarySection(array $todo_list, array $module_changes): array {
    $summary = [
      '#type' => 'container',
      '#attributes' => ['class' => ['vactory-diff-todo-summary']],
      'title' => [
        '#type' => 'html_tag',
        '#tag' => 'h2',
        '#value' => $this->t('Summary'),
      ],
    ];

    $summary_items = [
      $this->t('Features to revert: <strong>@count</strong>', ['@count' => $todo_list['summary']['total_features']]),
      $this->t('Configurations processed: <strong>@count</strong>', ['@count' => $todo_list['summary']['total_configs']]),
      $this->t('Matched configurations: <strong>@count</strong>', ['@count' => $todo_list['summary']['matched_configs']]),
      $this->t('Unmatched configurations: <strong>@count</strong>', ['@count' => $todo_list['summary']['unmatched_configs']]),
    ];

    if ($module_changes['has_changes']) {
      $summary_items[] = $this->t('Modules to install: <strong>@count</strong>', ['@count' => count($module_changes['to_install'])]);
      $summary_items[] = $this->t('Modules to uninstall: <strong>@count</strong>', ['@count' => count($module_changes['to_uninstall'])]);
    }

    $summary['stats'] = [
      '#theme' => 'item_list',
      '#items' => $summary_items,
    ];

    if (!empty($todo_list['timestamp'])) {
      $summary['timestamp'] = [
        '#markup' => '<p>' . $this->t('Last comparison: @time', ['@time' => $todo_list['timestamp']]) . '</p>',
      ];
    }

    return $summary;
  }

  /**
   * Build the module installation/uninstallation section.
   *
   * @param array $module_changes
   *   The module changes array.
   *
   * @return array
   *   Render array for module section.
   */
  protected function buildModuleSection(array $module_changes): array {
    $modules = [
      '#type' => 'container',
      '#attributes' => ['class' => ['vactory-diff-modules-section']],
      'title' => [
        '#type' => 'html_tag',
        '#tag' => 'h2',
        '#value' => $this->t('Module Installation/Uninstallation'),
      ],
      'description' => [
        '#markup' => '<p>' . $this->t('The following modules need to be installed or uninstalled based on core.extension changes:') . '</p>',
      ],
    ];

    $module_commands = [];
    foreach ($module_changes['commands'] as $cmd) {
      $module_commands[] = $cmd['command'];
    }

    if (!empty($module_commands)) {
      foreach ($module_commands as $key => $cmd) {
        $modules['commands'][$key] = [
          '#type' => 'html_tag',
          '#tag' => 'pre',
          '#value' => $cmd,
          '#attributes' => ['class' => ['vactory-diff-commands-block']],
        ];
      }
    }

    return $modules;
  }

  /**
   * Build the features section (commands and details).
   */
  protected function buildFeaturesSection(array $todo_list): array {
    $build = [];

    // Commands section.
    $build['commands'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['vactory-diff-commands-section']],
      'title' => [
        '#type' => 'html_tag',
        '#tag' => 'h2',
        '#value' => $this->t('Features to revert'),
      ],
      'description' => [
        '#markup' => '<p>' . $this->t('Copy and paste these commands in your production environment:') . '</p>',
      ],
    ];

    $all_commands = [];
    foreach ($todo_list['features'] as $feature) {
      $all_commands[] = $feature['command'];
    }

    foreach ($all_commands as $key => $command) {
      $build['commands']['commands'][$key] = [
        '#type' => 'html_tag',
        '#tag' => 'pre',
        '#value' => $command,
        '#attributes' => ['class' => ['vactory-diff-commands-block']],
      ];
    }

    // Detailed features table.
    $build['features_details'] = $this->buildFeaturesDetailsTable($todo_list['features']);

    return $build;
  }

  /**
   * Build the detailed features table.
   *
   * @param array $features
   *   The features array.
   *
   * @return array
   *   Render array for features details table.
   */
  protected function buildFeaturesDetailsTable(array $features): array {
    $details = [
      '#type' => 'details',
      '#title' => $this->t('Detailed Features Information'),
      '#open' => FALSE,
      '#attributes' => ['class' => ['vactory-diff-features-details']],
      'description' => [
        '#markup' => '<p>' . $this->t('Detailed information about each feature and the configurations affected.') . '</p>',
      ],
      'list' => [
        '#type' => 'table',
        '#header' => [
          $this->t('#'),
          $this->t('Feature Module'),
          $this->t('Command'),
          $this->t('Changes'),
          $this->t('Configurations'),
        ],
        '#rows' => [],
        '#attributes' => ['class' => ['vactory-diff-todo-table']],
      ],
    ];

    $index = 1;
    foreach ($features as $feature) {
      $configs_list = [];
      foreach ($feature['configs'] as $config) {
        $configs_list[] = "[{$config['change_type']}] {$config['name']} ({$config['type']})";
      }

      $details['list']['#rows'][] = [
        $index,
        $feature['module'],
        [
          'data' => [
            '#type' => 'html_tag',
            '#tag' => 'code',
            '#value' => $feature['command'],
            '#attributes' => ['class' => ['vactory-diff-command']],
          ],
        ],
        $this->t('@added added, @modified modified', [
          '@added' => $feature['stats']['added'],
          '@modified' => $feature['stats']['modified'],
        ]),
        [
          'data' => [
            '#theme' => 'item_list',
            '#items' => $configs_list,
            '#attributes' => ['class' => ['vactory-diff-config-list']],
          ],
        ],
      ];
      $index++;
    }

    return $details;
  }

  /**
   * Build the unmatched configurations section.
   */
  protected function buildUnmatchedSection(array $todo_list): array {
    $unmatched = [
      '#type' => 'details',
      '#title' => $this->t('Unmatched Configurations (@count)', ['@count' => count($todo_list['unmatched_configs'])]),
      '#open' => FALSE,
      '#attributes' => ['class' => ['vactory-diff-unmatched']],
      'description' => [
        '#markup' => '<p>' . $this->t('The following configurations could not be matched to a feature module. Manual intervention may be required.') . '</p>',
      ],
      'list' => [
        '#type' => 'table',
        '#header' => [
          $this->t('Configuration'),
          $this->t('Type'),
          $this->t('Change'),
          $this->t('Reason'),
        ],
        '#rows' => [],
      ],
    ];

    foreach ($todo_list['unmatched_configs'] as $unmatched_config) {
      $unmatched['list']['#rows'][] = [
        $unmatched_config['config_name'],
        $unmatched_config['type'],
        $unmatched_config['change_type'],
        $unmatched_config['reason'],
      ];
    }

    return $unmatched;
  }

  /**
   * Build the content sync section.
   */
  protected function buildContentSyncSection(array $todo_list): array {
    $content_sync = [
      '#type' => 'container',
      '#attributes' => ['class' => ['vactory-diff-commands-section']],
      'title' => [
        '#type' => 'html_tag',
        '#tag' => 'h2',
        '#value' => $this->t('Content Sync'),
      ],
      'intro' => [
        '#markup' => '<p>' . $this->t('Run the commands below to export changed content.') . '</p>',
      ],
    ];

    if (!empty($todo_list['content_sync']['export_commands'])) {
      foreach ($todo_list['content_sync']['export_commands'] as $key => $command) {
        $content_sync['export_commands'][$key] = [
          '#type' => 'html_tag',
          '#tag' => 'pre',
          '#value' => $command,
          '#attributes' => ['class' => ['vactory-diff-commands-block']],
        ];
      }

      $content_sync['instruction'] = [
        '#type' => 'container',
        'text' => [
          '#markup' => '<p>' . $this->t('Then copy the generated archives to PROD, then run the import commands') . '</p>',
        ],
      ];

      $content_sync['import_commands'] = [
        '#type' => 'html_tag',
        '#tag' => 'pre',
        '#value' => 'drush content:import [archive_path]',
        '#attributes' => ['class' => ['vactory-diff-commands-block']],
      ];
    }

    return $content_sync;
  }

  /**
   * Download to-do list as text file.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The response with text file.
   */
  public function download(): Response {
    $todo_list = $this->todoListGenerator->generateTodoList();
    $text = $this->todoListGenerator->generateReadableText($todo_list);

    $response = new Response($text);
    $response->headers->set('Content-Type', 'text/plain');
    $response->headers->set('Content-Disposition', 'attachment; filename="vactory-diff-todo-list.txt"');

    return $response;
  }

}
