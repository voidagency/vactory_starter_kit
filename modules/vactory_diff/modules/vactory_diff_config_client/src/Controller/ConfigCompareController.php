<?php

namespace Drupal\vactory_diff_config_client\Controller;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\vactory_diff_config_client\Service\ConfigComparisonService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller for configuration comparison interface.
 */
class ConfigCompareController extends ControllerBase {

  /**
   * The config comparison service.
   *
   * @var \Drupal\vactory_diff_config_client\Service\ConfigComparisonService
   */
  protected $comparisonService;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Constructs a ConfigCompareController object.
   *
   * @param \Drupal\vactory_diff_config_client\Service\ConfigComparisonService $comparison_service
   *   The comparison service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   */
  public function __construct(ConfigComparisonService $comparison_service, ConfigFactoryInterface $config_factory, LoggerChannelFactoryInterface $logger_factory, MessengerInterface $messenger) {
    $this->comparisonService = $comparison_service;
    $this->configFactory = $config_factory;
    $this->logger = $logger_factory->get('vactory_diff_config_client');
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('vactory_diff_config_client.comparison'),
      $container->get('config.factory'),
      $container->get('logger.factory'),
      $container->get('messenger')
    );
  }

  /**
   * Display the comparison interface.
   *
   * @return array
   *   Render array for the comparison page.
   */
  public function compareView(): array {
    $config = $this->configFactory->get('vactory_diff_config_client.settings');
    $remote_url = $config->get('remote_url');

    $build = [];

    // Vérifier si l'URL distante est configurée.
    if (empty($remote_url)) {
      $build['error'] = [
        '#type' => 'item',
        '#markup' => '
          <div class="messages messages--warning">' .
        $this->t('Veuillez d\'abord configurer l\'URL de l\'instance distante dans les <a href="@settings_url">paramètres</a>.', [
          '@settings_url' => '/admin/config/development/vactory-diff/settings',
        ])
        . '</div>',
      ];
      return $build;
    }

    // Interface de comparaison.
    $build['header'] = [
      '#type' => 'item',
      '#markup' => '<h2>' . $this->t('Comparaison des configurations') . '</h2>',
    ];

    $build['description'] = [
      '#type' => 'item',
      '#markup' => '<p>' . $this->t("Comparez les configurations entre cette instance locale et l'instance distante configurée : <strong>@url</strong>", [
        '@url' => $remote_url,
      ]) . '</p>',
    ];

    // Bouton de comparaison.
    $build['compare_button'] = [
      '#type' => 'button',
      '#value' => $this->t('Récupérer et comparer'),
      '#attributes' => [
        'id' => 'compare-configs-btn',
        'class' => ['button', 'button--primary'],
        'onclick' => 'startComparison()',
      ],
    ];

    // Zone de chargement.
    $build['loading'] = [
      '#type' => 'container',
      '#attributes' => [
        'id' => 'comparison-loading',
        'style' => 'display: none;',
      ],
      'content' => [
        '#markup' => '<div class="ajax-progress ajax-progress-throbber"><div class="throbber">&nbsp;</div><div class="message">' .
        $this->t('Comparaison en cours, veuillez patienter...') . '</div></div>',
      ],
    ];

    // Zone de résultats.
    $build['results'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'comparison-results'],
    ];

    // Afficher les résultats de la dernière comparaison si disponible.
    $comparison_results = $config->get('comparison_results');
    if (!empty($comparison_results)) {
      $build['results']['content'] = $this->buildComparisonResults($comparison_results);
    }

    // Ajouter le JavaScript et CSS nécessaires.
    $build['#attached']['library'][] = 'vactory_diff_config_client/comparison';

    return $build;
  }

  /**
   * Ajax callback for performing comparison.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response with comparison results.
   */
  public function ajaxCompare(Request $request): JsonResponse {
    try {
      $config = $this->configFactory->get('vactory_diff_config_client.settings');
      $remote_url = $config->get('remote_url');

      if (empty($remote_url)) {
        return new JsonResponse([
          'success' => FALSE,
          'message' => 'URL distante non configurée.',
        ], 400);
      }

      $comparison_results = $this->comparisonService->performFullComparison($remote_url);

      if ($comparison_results === NULL) {
        return new JsonResponse([
          'success' => FALSE,
          'message' => 'Impossible de récupérer les configurations distantes. Vérifiez la connexion et l\'URL.',
        ], 500);
      }

      // Générer le HTML des résultats.
      $results_html = $this->renderComparisonResults($comparison_results);

      return new JsonResponse([
        'success' => TRUE,
        'message' => 'Comparaison terminée avec succès.',
        'results_html' => $results_html,
        'summary' => $comparison_results['summary'],
      ]);
    }
    catch (\Exception $e) {
      $this->logger->error('Error during AJAX comparison: @message', [
        '@message' => $e->getMessage(),
      ]);

      return new JsonResponse([
        'success' => FALSE,
        'message' => 'Une erreur est survenue pendant la comparaison.',
      ], 500);
    }
  }

  /**
   * Ajax callback for testing connection.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response with connection test results.
   */
  public function ajaxTestConnection(Request $request): JsonResponse {
    $data = json_decode($request->getContent(), TRUE);
    $url = $data['url'] ?? '';

    if (empty($url)) {
      return new JsonResponse([
        'success' => FALSE,
        'message' => 'URL manquante.',
      ], 400);
    }

    $test_result = $this->comparisonService->testConnection($url);

    return new JsonResponse($test_result);
  }

  /**
   * Build comparison results render array.
   *
   * @param array $results
   *   The comparison results.
   *
   * @return array
   *   Render array for the results.
   */
  protected function buildComparisonResults(array $results): array {
    $build = [];

    if (empty($results['summary'])) {
      return $build;
    }

    $summary = $results['summary'];
    $differences = $results['differences'] ?? [];

    // En-tête avec résumé.
    $build['summary'] = [
      '#type' => 'item',
      '#markup' => $this->t(
        '<div class="comparison-summary">
       <h3>@title</h3>
       <p>@date_text</p>
       <div class="summary-stats">
         <span class="stat added">@added</span> | 
         <span class="stat modified">@modified</span> | 
         <span class="stat deleted">@deleted</span>
       </div>
     </div>',
        [
          '@title' => $this->t('Résumé de la comparaison'),
          '@date_text' => $this->t('Comparaison effectuée le @date', [
            '@date' => date('d/m/Y H:i:s', strtotime($results['timestamp'])),
          ]),
          '@added' => $this->t('@count ajoutées', ['@count' => $summary['added']]),
          '@modified' => $this->t('@count modifiées', ['@count' => $summary['modified']]),
          '@deleted' => $this->t('@count supprimées', ['@count' => $summary['deleted']]),
        ]
      ),
    ];

    // Affichage des différences.
    if ($summary['total_differences'] > 0) {
      $build['differences'] = [
        '#type' => 'item',
        '#markup' => $this->buildLinearResults($differences),
      ];
    }
    else {
      $build['no_differences'] = [
        '#type' => 'item',
        '#markup' => '<div class="messages messages--status">' .
        $this->t('Aucune différence trouvée. Les configurations sont identiques.') . '</div>',
      ];
    }

    return $build;
  }

  /**
   * Render comparison results to HTML.
   *
   * @param array $results
   *   The comparison results.
   *
   * @return string
   *   HTML string of the results.
   */
  protected function renderComparisonResults(array $results): string {
    $build = $this->buildComparisonResults($results);
    return \Drupal::service('renderer')->render($build);
  }

  /**
   * Build linear interface for different types of differences.
   *
   * @param array $differences
   *   The differences array.
   *
   * @return string
   *   HTML for the linear interface.
   */
  protected function buildLinearResults(array $differences): string {
    $html = '<div class="config-diff-results">';

    // Configurations ajoutées.
    if (!empty($differences['added'])) {
      $html .= '<div class="config-section config-section--added">';
      $html .= '<h3 class="config-section-title">' .
        $this->t('Configurations ajoutées (@count)', ['@count' => count($differences['added'])]) . '</h3>';
      $html .= '<p class="config-section-description">' .
        $this->t('Ces configurations existent localement mais pas sur le serveur distant.') . '</p>';
      $html .= $this->buildConfigList($differences['added'], 'added');
      $html .= '</div>';
    }

    // Configurations modifiées.
    if (!empty($differences['modified'])) {
      $html .= '<div class="config-section config-section--modified">';
      $html .= '<h3 class="config-section-title">' .
        $this->t('Configurations modifiées (@count)', ['@count' => count($differences['modified'])]) . '</h3>';
      $html .= '<p class="config-section-description">' .
        $this->t('Ces configurations ont des valeurs différentes entre local et distant.') . '</p>';
      $html .= $this->buildConfigList($differences['modified'], 'modified');
      $html .= '</div>';
    }

    // Configurations supprimées.
    if (!empty($differences['deleted'])) {
      $html .= '<div class="config-section config-section--deleted">';
      $html .= '<h3 class="config-section-title">' .
        $this->t('Configurations supprimées (@count)', ['@count' => count($differences['deleted'])]) . '</h3>';
      $html .= '<p class="config-section-description">' .
        $this->t('Ces configurations existent sur le serveur distant mais pas localement.') . '</p>';
      $html .= $this->buildConfigList($differences['deleted'], 'deleted');
      $html .= '</div>';
    }

    $html .= '</div>';

    return $html;
  }

  /**
   * Build configuration list for a specific type.
   *
   * @param array $configs
   *   The configurations.
   * @param string $type
   *   The type of difference.
   *
   * @return string
   *   HTML for the configuration list.
   */
  protected function buildConfigList(array $configs, string $type): string {
    $html = '<div class="config-list config-list--' . $type . '">';

    foreach ($configs as $config_data) {
      $config_name = $config_data['name'] ?? 'Configuration inconnue';

      $html .= '<div class="config-item">';
      $html .= '<h4 class="config-name">' . htmlspecialchars($config_name) . '</h4>';

      if ($type === 'modified' && isset($config_data['diff'])) {
        $html .= '<div class="config-diff">';
        $html .= $this->buildDiffView($config_data['diff']);
        $html .= '</div>';
      }

      $html .= '</div>';
    }

    $html .= '</div>';

    return $html;
  }

  /**
   * Build diff view for modified configurations.
   *
   * @param array $diff
   *   The diff data.
   *
   * @return string
   *   HTML for the diff view.
   */
  protected function buildDiffView(array $diff): string {
    $html = '<div class="diff-view">';

    if (!empty($diff['changes'])) {
      $html .= '<table class="diff-table">';
      $html .= '<thead><tr><th>' . $this->t('Clé') . '</th><th>' . $this->t('Local') . '</th><th>' . $this->t('Distant') . '</th></tr></thead>';
      $html .= '<tbody>';

      foreach ($diff['changes'] as $change) {
        $html .= '<tr class="diff-row diff-row--' . $change['change_type'] . '">';
        $html .= '<td class="diff-key">' . htmlspecialchars($change['key']) . '</td>';
        $html .= '<td class="diff-local">' . htmlspecialchars(json_encode($change['local_value'], JSON_PRETTY_PRINT)) . '</td>';
        $html .= '<td class="diff-remote">' . htmlspecialchars(json_encode($change['remote_value'], JSON_PRETTY_PRINT)) . '</td>';
        $html .= '</tr>';
      }

      $html .= '</tbody></table>';
    }

    $html .= '</div>';

    return $html;
  }

}
