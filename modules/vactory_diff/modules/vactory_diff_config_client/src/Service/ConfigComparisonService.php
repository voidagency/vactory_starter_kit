<?php

namespace Drupal\vactory_diff_config_client\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\vactory_diff\Service\ConfigHelperService;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;

/**
 * Service for comparing configurations between local and remote instances.
 */
class ConfigComparisonService {

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * The config helper service.
   *
   * @var \Drupal\vactory_diff\Service\ConfigHelperService
   */
  protected $configHelper;

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
   * Constructs a ConfigComparisonService object.
   *
   * @param \GuzzleHttp\ClientInterface $http_client
   *   The HTTP client.
   * @param \Drupal\vactory_diff\Service\ConfigHelperService $config_helper
   *   The config helper service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   */
  public function __construct(ClientInterface $http_client, ConfigHelperService $config_helper, ConfigFactoryInterface $config_factory, LoggerChannelFactoryInterface $logger_factory) {
    $this->httpClient = $http_client;
    $this->configHelper = $config_helper;
    $this->configFactory = $config_factory;
    $this->logger = $logger_factory->get('vactory_diff_config_client');
  }

  /**
   * Fetch remote configuration data.
   *
   * @param string $url
   *   The remote URL.
   *
   * @return array|null
   *   The remote configuration data or NULL on failure.
   */
  public function fetchRemoteConfig(string $url): ?array {
    try {
      $settings = $this->configFactory->get('vactory_diff_config_client.settings');
      $timeout = $settings->get('connection_timeout') ?: 30;

      // Construire l'URL complète de l'API.
      $api_url = rtrim($url, '/') . '/api/vactory-diff/config/export';

      $this->logger->info('Fetching remote configuration from @url', [
        '@url' => $api_url,
      ]);
      $response = $this->httpClient->request('GET', $api_url, [
        'timeout' => $timeout,
        'headers' => [
          'Accept' => 'application/json',
          'Content-Type' => 'application/json',
        ],
      ]);

      if ($response->getStatusCode() === 200) {
        $body = $response->getBody()->getContents();
        $data = json_decode($body, TRUE);

        if (json_last_error() === JSON_ERROR_NONE && $this->configHelper->validateConfigData($data)) {
          $this->logger->info('Successfully fetched @count remote configurations', [
            '@count' => count($data['configs'] ?? []),
          ]);
          return $data;
        }
        else {
          $this->logger->error('Invalid JSON response or data structure from remote API');
          return NULL;
        }
      }
      else {
        $this->logger->error('HTTP error @status when fetching remote config', [
          '@status' => $response->getStatusCode(),
        ]);
        return NULL;
      }
    }
    catch (RequestException $e) {
      $this->logger->error('Request exception when fetching remote config: @message', [
        '@message' => $e->getMessage(),
      ]);
      return NULL;
    }
    catch (\Exception $e) {
      $this->logger->error('Unexpected error when fetching remote config: @message', [
        '@message' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

  /**
   * Test connection to remote server.
   *
   * @param string $url
   *   The remote URL to test.
   *
   * @return array
   *   Array with 'success' boolean and 'message' string.
   */
  public function testConnection(string $url): array {
    $remote_data = $this->fetchRemoteConfig($url);

    if ($remote_data === NULL) {
      return [
        'success' => FALSE,
        'message' => 'Impossible de se connecter au serveur distant ou de récupérer les données.',
      ];
    }

    return [
      'success' => TRUE,
      'message' => sprintf(
        'Connexion réussie. %d configurations trouvées sur "%s".',
        count($remote_data['configs'] ?? []),
        $remote_data['site_name'] ?? 'Site inconnu'
      ),
      'remote_info' => [
        'site_name' => $remote_data['site_name'] ?? '',
        'timestamp' => $remote_data['timestamp'] ?? '',
        'count' => count($remote_data['configs'] ?? []),
      ],
    ];
  }

  /**
   * Compare local and remote configurations.
   *
   * @param array $local_data
   *   Local configuration data.
   * @param array $remote_data
   *   Remote configuration data.
   *
   * @return array
   *   Array containing comparison results.
   */
  public function compareConfigs(array $local_data, array $remote_data): array {
    $local_configs = $local_data['configs'] ?? [];
    $remote_configs = $remote_data['configs'] ?? [];

    $added = [];
    $modified = [];
    $deleted = [];

    try {
      // Configs ajoutées (présentes en local mais pas sur le serveur distant).
      foreach ($local_configs as $config_name => $local_config) {
        if (!isset($remote_configs[$config_name])) {
          $added[] = [
            'name' => $config_name,
            'local_data' => $local_config,
          ];
        }
      }

      // Configs supprimées (présentes sur le serveur distant, pas en local).
      foreach ($remote_configs as $config_name => $remote_config) {
        if (!isset($local_configs[$config_name])) {
          $deleted[] = [
            'name' => $config_name,
            'remote_data' => $remote_config,
          ];
        }
      }

      // Configurations modifiées (différentes entre local et distant).
      foreach ($local_configs as $config_name => $local_config) {
        if (isset($remote_configs[$config_name])) {
          $remote_config = $remote_configs[$config_name];
          try {
            if ($this->configsAreDifferent($local_config, $remote_config)) {
              $modified[] = [
                'name' => $config_name,
                'local_data' => $local_config,
                'remote_data' => $remote_config,
                'diff' => $this->generateDiff($config_name, $local_config, $remote_config),
              ];
            }
          }
          catch (\Exception $e) {
            $this->logger->warning('Error comparing config @name: @message', [
              '@name' => $config_name,
              '@message' => $e->getMessage(),
            ]);
            // Continuer avec les autres configurations.
            continue;
          }
        }
      }
    }
    catch (\Exception $e) {
      $this->logger->error('Error during config comparison: @message', [
        '@message' => $e->getMessage(),
      ]);
      // Retourner des résultats partiels plutôt qu'échouer complètement.
    }

    $comparison_results = [
      'timestamp' => date('c'),
      'local_info' => [
        'site_name' => $local_data['site_name'] ?? '',
        'count' => count($local_configs),
      ],
      'remote_info' => [
        'site_name' => $remote_data['site_name'] ?? '',
        'count' => count($remote_configs),
      ],
      'summary' => [
        'added' => count($added),
        'modified' => count($modified),
        'deleted' => count($deleted),
        'total_differences' => count($added) + count($modified) + count($deleted),
      ],
      'differences' => [
        'added' => $added,
        'modified' => $modified,
        'deleted' => $deleted,
      ],
    ];

    $this->logger->info('Configuration comparison completed: @added added, @modified modified, @deleted deleted', [
      '@added' => count($added),
      '@modified' => count($modified),
      '@deleted' => count($deleted),
    ]);

    return $comparison_results;
  }

  /**
   * Generate detailed diff for a configuration.
   *
   * @param string $config_name
   *   The configuration name.
   * @param array $local_data
   *   Local configuration data.
   * @param array $remote_data
   *   Remote configuration data.
   *
   * @return array
   *   Array containing diff information.
   */
  public function generateDiff(string $config_name, array $local_data, array $remote_data): array {
    $diff = [
      'config_name' => $config_name,
      'changes' => [],
    ];

    // Comparer chaque clé récursivement.
    $all_keys = array_unique(array_merge(array_keys($local_data), array_keys($remote_data)));

    foreach ($all_keys as $key) {
      $local_value = $local_data[$key] ?? NULL;
      $remote_value = $remote_data[$key] ?? NULL;

      if ($local_value !== $remote_value) {
        $diff['changes'][$key] = [
          'key' => $key,
          'local_value' => $local_value,
          'remote_value' => $remote_value,
          'change_type' => $this->getChangeType($local_value, $remote_value),
        ];
      }
    }

    return $diff;
  }

  /**
   * Check if two configurations are different.
   *
   * @param array $config1
   *   First configuration.
   * @param array $config2
   *   Second configuration.
   *
   * @return bool
   *   TRUE if configurations are different.
   */
  protected function configsAreDifferent(array $config1, array $config2): bool {
    try {
      // Utiliser json_encode avec gestion d'erreurs.
      $options = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION;

      $json1 = json_encode($config1, $options);
      $json2 = json_encode($config2, $options);

      // Vérifier les erreurs JSON.
      if (json_last_error() !== JSON_ERROR_NONE) {
        $this->logger->warning('JSON encoding error during config comparison: @error', [
          '@error' => json_last_error_msg(),
        ]);
        // Utiliser une comparaison récursive en cas d'erreur JSON.
        return $this->deepArrayCompare($config1, $config2);
      }

      return $json1 !== $json2;
    }
    catch (\Exception $e) {
      $this->logger->warning('Error comparing configs: @message', [
        '@message' => $e->getMessage(),
      ]);
      // Utiliser une comparaison récursive de fallback.
      return $this->deepArrayCompare($config1, $config2);
    }
  }

  /**
   * Fallback method for deep array comparison.
   *
   * @param array $array1
   *   First array.
   * @param array $array2
   *   Second array.
   *
   * @return bool
   *   TRUE if arrays are different.
   */
  protected function deepArrayCompare(array $array1, array $array2): bool {
    // Comparer le nombre d'éléments.
    if (count($array1) !== count($array2)) {
      return TRUE;
    }

    // Comparer les clés.
    if (array_keys($array1) !== array_keys($array2)) {
      return TRUE;
    }

    // Comparer les valeurs récursivement.
    foreach ($array1 as $key => $value) {
      if (!array_key_exists($key, $array2)) {
        return TRUE;
      }

      if (is_array($value) && is_array($array2[$key])) {
        if ($this->deepArrayCompare($value, $array2[$key])) {
          return TRUE;
        }
      }
      elseif ($value != $array2[$key]) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * Get the type of change for a configuration key.
   *
   * @param mixed $local_value
   *   Local value.
   * @param mixed $remote_value
   *   Remote value.
   *
   * @return string
   *   The change type.
   */
  protected function getChangeType($local_value, $remote_value): string {
    if ($local_value === NULL && $remote_value !== NULL) {
      return 'deleted_locally';
    }
    elseif ($local_value !== NULL && $remote_value === NULL) {
      return 'added_locally';
    }
    else {
      return 'modified';
    }
  }

  /**
   * Perform a full comparison with remote server.
   *
   * @param string $url
   *   The remote URL.
   *
   * @return array|null
   *   Comparison results or NULL on failure.
   */
  public function performFullComparison(string $url): ?array {
    try {
      // Récupérer les données distantes.
      $remote_data = $this->fetchRemoteConfig($url);
      if ($remote_data === NULL) {
        return NULL;
      }

      // Récupérer les données locales.
      $local_configs = $this->configHelper->getAllConfigurations();
      $local_info = $this->configHelper->getSiteInfo();

      $local_data = [
        'timestamp' => $local_info['timestamp'],
        'site_name' => $local_info['site_name'],
        'configs' => $local_configs,
      ];

      // Comparer les configurations.
      $comparison_results = $this->compareConfigs($local_data, $remote_data);

      // Sauvegarder les résultats.
      $config = $this->configFactory->getEditable('vactory_diff_config_client.settings');
      $config->set('last_comparison', $comparison_results['timestamp']);
      $config->set('comparison_results', $comparison_results);
      $config->save();

      return $comparison_results;
    }
    catch (\Exception $e) {
      $this->logger->error('Error during full comparison: @message', [
        '@message' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

}
