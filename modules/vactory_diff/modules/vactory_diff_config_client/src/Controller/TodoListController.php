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

    if (empty($todo_list['features']) && empty($todo_list['unmatched_configs']) && !$module_changes['has_changes']) {
      $this->messenger()->addWarning($this->t('No comparison results found. Please run a comparison first.'));
      return [
        '#markup' => $this->t('No TODO items to display. Please <a href="@url">run a comparison</a> first.', [
          '@url' => '/admin/config/development/vactory-diff/compare',
        ]),
      ];
    }

    $build = [];

    // Add download button at the top.
    $build['download_button'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['vactory-diff-download-section']],
    ];

    $build['download_button']['link'] = [
      '#type' => 'link',
      '#title' => $this->t('Télécharger la TODO list'),
      '#url' => Url::fromRoute('vactory_diff_config_client.todo_list_download'),
      '#attributes' => [
        'class' => ['button', 'button--primary', 'vactory-diff-download-button'],
        'target' => '_blank',
      ],
    ];

    // Summary section.
    $build['summary'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['vactory-diff-todo-summary']],
    ];

    $build['summary']['title'] = [
      '#type' => 'html_tag',
      '#tag' => 'h2',
      '#value' => $this->t('Summary'),
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

    $build['summary']['stats'] = [
      '#theme' => 'item_list',
      '#items' => $summary_items,
    ];

    if (!empty($todo_list['timestamp'])) {
      $build['summary']['timestamp'] = [
        '#markup' => '<p>' . $this->t('Last comparison: @time', ['@time' => $todo_list['timestamp']]) . '</p>',
      ];
    }

    // Module Installation/Uninstallation section.
    if ($module_changes['has_changes']) {
      $build['modules'] = [
        '#type' => 'container',
        '#attributes' => ['class' => ['vactory-diff-modules-section']],
      ];

      $build['modules']['title'] = [
        '#type' => 'html_tag',
        '#tag' => 'h2',
        '#value' => $this->t('Module Installation/Uninstallation'),
      ];

      $build['modules']['description'] = [
        '#markup' => '<p>' . $this->t('The following modules need to be installed or uninstalled based on core.extension changes:') . '</p>',
      ];

      // Generate commands for copy-paste.
      $module_commands = [];
      foreach ($module_changes['commands'] as $cmd) {
        $module_commands[] = $cmd['command'];
      }

      if (!empty($module_commands)) {
        foreach ($module_commands as $key => $cmd) {
          $build['modules']['commands'][$key] = [
            '#type' => 'html_tag',
            '#tag' => 'pre',
            '#value' => $cmd,
            '#attributes' => ['class' => ['vactory-diff-commands-block']],
          ];
        }
      }
    }

    // Features section.
    if (!empty($todo_list['features'])) {
      // Section 1: Commands to execute (visible by default).
      $build['commands'] = [
        '#type' => 'container',
        '#attributes' => ['class' => ['vactory-diff-commands-section']],
      ];

      $build['commands']['title'] = [
        '#type' => 'html_tag',
        '#tag' => 'h2',
        '#value' => $this->t('Features to revert'),
      ];

      $build['commands']['description'] = [
        '#markup' => '<p>' . $this->t('Copy and paste these commands in your production environment:') . '</p>',
      ];

      // Generate all commands.
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

      // Section 2: Detailed table (in accordion).
      $build['features_details'] = [
        '#type' => 'details',
        '#title' => $this->t('Detailed Features Information'),
        '#open' => FALSE,
        '#attributes' => ['class' => ['vactory-diff-features-details']],
      ];

      $build['features_details']['description'] = [
        '#markup' => '<p>' . $this->t('Detailed information about each feature and the configurations affected.') . '</p>',
      ];

      $build['features_details']['list'] = [
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
      ];

      $index = 1;
      foreach ($todo_list['features'] as $feature) {
        $configs_list = [];
        foreach ($feature['configs'] as $config) {
          $configs_list[] = "[{$config['change_type']}] {$config['name']} ({$config['type']})";
        }

        $build['features_details']['list']['#rows'][] = [
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
    }

    // Unmatched configurations section.
    if (!empty($todo_list['unmatched_configs'])) {
      $build['unmatched'] = [
        '#type' => 'details',
        '#title' => $this->t('Unmatched Configurations (@count)', ['@count' => count($todo_list['unmatched_configs'])]),
        '#open' => FALSE,
        '#attributes' => ['class' => ['vactory-diff-unmatched']],
      ];

      $build['unmatched']['description'] = [
        '#markup' => '<p>' . $this->t('The following configurations could not be matched to a feature module. Manual intervention may be required.') . '</p>',
      ];

      $build['unmatched']['list'] = [
        '#type' => 'table',
        '#header' => [
          $this->t('Configuration'),
          $this->t('Type'),
          $this->t('Change'),
          $this->t('Reason'),
        ],
        '#rows' => [],
      ];

      foreach ($todo_list['unmatched_configs'] as $unmatched) {
        $build['unmatched']['list']['#rows'][] = [
          $unmatched['config_name'],
          $unmatched['type'],
          $unmatched['change_type'],
          $unmatched['reason'],
        ];
      }
    }

    // Content Sync section (single_content_sync based on content diff CSV).
    if (!empty($todo_list['content_sync']) && ($todo_list['content_sync']['has_changes'] ?? FALSE)) {
      $build['content_sync'] = [
        '#type' => 'container',
        '#attributes' => ['class' => ['vactory-diff-commands-section']],
      ];

      $build['content_sync']['title'] = [
        '#type' => 'html_tag',
        '#tag' => 'h2',
        '#value' => $this->t('Content Sync'),
      ];

      // Small intro/description for spacing and context.
      $build['content_sync']['intro'] = [
        '#markup' => '<p>' . $this->t('Run the commands below to export changed content.') . '</p>',
      ];

      // Export commands.
      if (!empty($todo_list['content_sync']['export_commands'])) {
        foreach ($todo_list['content_sync']['export_commands'] as $key => $command) {
          $build['content_sync']['export_commands'][$key] = [
            '#type' => 'html_tag',
            '#tag' => 'pre',
            '#value' => $command,
            '#attributes' => ['class' => ['vactory-diff-commands-block']],
          ];
        }
        // Copy instruction.
        $build['content_sync']['instruction'] = [
          '#type' => 'container',
          'text' => [
            '#markup' => '<p>' . $this->t('Then copy the generated archives to PROD, then run the import commands') . '</p>',
          ],
        ];

        // Import commands.
        $build['content_sync']['import_commands'] = [
          '#type' => 'html_tag',
          '#tag' => 'pre',
          '#value' => 'drush content:import [archive_path]',
          '#attributes' => ['class' => ['vactory-diff-commands-block']],
        ];
      }

    }

    // Add CSS.
    $build['#attached']['library'][] = 'vactory_diff_config_client/comparison';

    return $build;
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
