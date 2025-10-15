<?php

namespace Drupal\vactory_diff_config_client\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\vactory_diff_config_client\Service\TodoListGeneratorService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller for displaying the TODO list.
 */
class TodoListController extends ControllerBase {

  /**
   * The TODO list generator service.
   *
   * @var \Drupal\vactory_diff_config_client\Service\TodoListGeneratorService
   */
  protected $todoListGenerator;

  /**
   * Constructs a TodoListController object.
   *
   * @param \Drupal\vactory_diff_config_client\Service\TodoListGeneratorService $todo_list_generator
   *   The TODO list generator service.
   */
  public function __construct(TodoListGeneratorService $todo_list_generator) {
    $this->todoListGenerator = $todo_list_generator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('vactory_diff_config_client.todo_list_generator')
    );
  }

  /**
   * Display the TODO list page.
   *
   * @return array
   *   Render array.
   */
  public function view(): array {
    $todo_list = $this->todoListGenerator->generateTodoList();

    if (empty($todo_list['features']) && empty($todo_list['unmatched_configs'])) {
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

    $build['summary']['stats'] = [
      '#theme' => 'item_list',
      '#items' => [
        $this->t('Features to revert: <strong>@count</strong>', ['@count' => $todo_list['summary']['total_features']]),
        $this->t('Configurations processed: <strong>@count</strong>', ['@count' => $todo_list['summary']['total_configs']]),
        $this->t('Matched configurations: <strong>@count</strong>', ['@count' => $todo_list['summary']['matched_configs']]),
        $this->t('Unmatched configurations: <strong>@count</strong>', ['@count' => $todo_list['summary']['unmatched_configs']]),
      ],
    ];

    if (!empty($todo_list['timestamp'])) {
      $build['summary']['timestamp'] = [
        '#markup' => '<p>' . $this->t('Last comparison: @time', ['@time' => $todo_list['timestamp']]) . '</p>',
      ];
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
        '#value' => $this->t('Commands to Execute'),
      ];

      $build['commands']['description'] = [
        '#markup' => '<p>' . $this->t('Copy and paste these commands in your production environment:') . '</p>',
      ];

      // Generate all commands.
      $all_commands = [];
      foreach ($todo_list['features'] as $feature) {
        $all_commands[] = $feature['command'];
      }

      $build['commands']['commands'] = [
        '#type' => 'html_tag',
        '#tag' => 'pre',
        '#value' => implode("\n", $all_commands),
        '#attributes' => ['class' => ['vactory-diff-commands-block']],
      ];

      // Section 2: Detailed table (in accordion).
      $build['features_details'] = [
        '#type' => 'details',
        '#title' => $this->t('Detailed Features Information'),
        '#open' => FALSE,
        '#attributes' => ['class' => ['vactory-diff-features-details']],
      ];

      $build['features_details']['description'] = [
        '#markup' => '<p>' . $this->t('Click to see detailed information about each feature and the configurations affected.') . '</p>',
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

    // Add CSS.
    $build['#attached']['library'][] = 'vactory_diff_config_client/comparison';

    return $build;
  }

  /**
   * Download TODO list as text file.
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
