<?php

namespace Drupal\vactory_diff_config_server\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\vactory_diff\Service\ConfigHelperService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Yaml\Yaml;

/**
 * Controller for configuration export API.
 */
class ConfigExportController extends ControllerBase {

  /**
   * The config helper service.
   *
   * @var \Drupal\vactory_diff\Service\ConfigHelperService
   */
  protected $configHelper;

  /**
   * Logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * Constructs a ConfigExportController object.
   *
   * @param \Drupal\vactory_diff\Service\ConfigHelperService $config_helper
   *   The config helper service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   */
  public function __construct(ConfigHelperService $config_helper, LoggerChannelFactoryInterface $logger_factory) {
    $this->configHelper = $config_helper;
    $this->logger = $logger_factory->get('vactory_diff_config_server');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('vactory_diff.config_helper'),
      $container->get('logger.factory')
    );
  }

  /**
   * Export all site configurations.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response containing all configurations.
   */
  public function export(Request $request): JsonResponse {
    try {
      // Récupérer les informations du site.
      $site_info = $this->configHelper->getSiteInfo();

      // Récupérer toutes les configurations.
      $configs = $this->configHelper->getAllConfigurations();

      // Grouper les configurations par type.
      $configs_by_type = $this->configHelper->groupConfigsByType($configs);

      // Convertir les configs en YAML pour préserver les types de données.
      $configs_yaml = $this->convertConfigsToYaml($configs_by_type);

      // Préparer la réponse avec les configurations classées par type.
      $response_data = [
        'timestamp' => $site_info['timestamp'],
        'site_name' => $site_info['site_name'],
        'site_uuid' => $site_info['site_uuid'],
        'total_count' => count($configs),
        'configs' => $configs_yaml,
      ];

      $this->logger->info('Configuration export successful. @count configurations exported.', [
        '@count' => count($configs),
      ]);

      $response = new JsonResponse($response_data);

      return $response;
    }
    catch (\Exception $e) {
      $this->logger->error('Error during configuration export: @message', [
        '@message' => $e->getMessage(),
      ]);

      return new JsonResponse([
        'error' => TRUE,
        'message' => 'Une erreur est survenue lors de l\'export des configurations.',
        'timestamp' => date('c'),
      ], 500);
    }
  }

  /**
   * Convert configurations to YAML format to preserve data types.
   *
   * @param array $configs_by_type
   *   Configurations grouped by type.
   *
   * @return array
   *   Configurations with YAML strings instead of arrays.
   */
  protected function convertConfigsToYaml(array $configs_by_type): array {
    $yaml_configs = [];

    foreach ($configs_by_type as $type => $type_data) {
      $yaml_configs[$type] = [
        'type' => $type_data['type'],
        'count' => $type_data['count'],
        'configs' => [],
      ];

      foreach ($type_data['configs'] as $config_name => $config_data) {
        // Convertir chaque configuration en YAML.
        $yaml_string = Yaml::dump($config_data, 10, 2, Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK);
        $yaml_configs[$type]['configs'][$config_name] = $yaml_string;
      }
    }

    return $yaml_configs;
  }

}
