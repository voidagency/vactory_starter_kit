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

    // Check if remote URL is configured.
    if (empty($remote_url)) {
      $error_message = $this->t('Veuillez d\'abord configurer l\'URL de l\'instance distante dans les <a href="@settings_url">paramètres</a>.', [
        '@settings_url' => '/admin/config/development/vactory-diff/settings',
      ]);

      return [
        '#theme' => 'vactory_diff_comparison',
        '#remote_url' => '',
        '#results' => [],
        '#error_message' => $error_message,
        '#attached' => ['library' => ['vactory_diff_config_client/comparison']],
      ];
    }

    // Get last comparison results if available.
    $comparison_results = $this->comparisonService->loadComparisonResults();

    return [
      '#theme' => 'vactory_diff_comparison',
      '#remote_url' => $remote_url,
      '#results' => $comparison_results,
      '#error_message' => '',
      '#attached' => ['library' => ['vactory_diff_config_client/comparison']],
    ];
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

      // Generate HTML using Twig template.
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
   * Render comparison results using Twig template.
   *
   * @param array $results
   *   The comparison results.
   *
   * @return string
   *   HTML string of the results.
   */
  protected function renderComparisonResults(array $results): string {
    $build = [
      '#theme' => 'vactory_diff_results',
      '#results' => $results,
    ];
    return \Drupal::service('renderer')->render($build);
  }

}
