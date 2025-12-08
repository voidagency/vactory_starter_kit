<?php

namespace Drupal\vactory_diff_config_client\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\vactory_diff\Service\ConfigHelperService;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\Yaml\Yaml;
use SebastianBergmann\Diff\Differ;
use SebastianBergmann\Diff\Output\UnifiedDiffOutputBuilder;

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
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

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
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file system service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   */
  public function __construct(ClientInterface $http_client, ConfigHelperService $config_helper, ConfigFactoryInterface $config_factory, FileSystemInterface $file_system, LoggerChannelFactoryInterface $logger_factory) {
    $this->httpClient = $http_client;
    $this->configHelper = $config_helper;
    $this->configFactory = $config_factory;
    $this->fileSystem = $file_system;
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
      $api_key = $settings->get('remote_api_key');

      // Construire l'URL complète de l'API.
      $api_url = rtrim($url, '/') . '/api/vactory-diff/config/export';

      $this->logger->info('Fetching remote configuration from @url', [
        '@url' => $api_url,
      ]);

      // Préparer les headers avec l'API key.
      $headers = [
        'Accept' => 'application/json',
        'Content-Type' => 'application/json',
      ];

      if (!empty($api_key)) {
        $headers['apikey'] = $api_key;
      }

      $response = $this->httpClient->request('GET', $api_url, [
        'timeout' => $timeout,
        'headers' => $headers,
      ]);

      if ($response->getStatusCode() === 200) {
        $body = $response->getBody()->getContents();
        $data = json_decode($body, TRUE);

        if (json_last_error() === JSON_ERROR_NONE) {
          $this->logger->info('Successfully fetched remote configurations');
          return $data;
        }
        else {
          $this->logger->error('Invalid JSON response from remote API');
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

    // Compter les configurations depuis le format groupé.
    $total = 0;
    foreach ($remote_data['configs'] ?? [] as $type_data) {
      $total += $type_data['count'] ?? 0;
    }

    return [
      'success' => TRUE,
      'message' => sprintf(
        'Connexion réussie. %d configurations trouvées sur "%s".',
        $total,
        $remote_data['site_name'] ?? 'Site inconnu'
      ),
      'remote_info' => [
        'site_name' => $remote_data['site_name'] ?? '',
        'timestamp' => $remote_data['timestamp'] ?? '',
        'count' => $total,
      ],
    ];
  }

  /**
   * Compare local and remote configurations.
   *
   * @param array $local_data
   *   Local configuration data grouped by type.
   * @param array $remote_data
   *   Remote configuration data grouped by type.
   *
   * @return array
   *   Array containing comparison results.
   */
  public function compareConfigs(array $local_data, array $remote_data): array {
    $local_by_type = $local_data['configs_by_type'] ?? [];
    $remote_by_type = $remote_data['configs'] ?? [];

    $added_by_type = [];
    $modified_by_type = [];
    $deleted_by_type = [];

    $total_added = 0;
    $total_modified = 0;
    $total_deleted = 0;

    try {
      // Obtenir tous les types uniques.
      $all_types = array_unique(array_merge(
        array_keys($local_by_type),
        array_keys($remote_by_type)
      ));

      // Comparer type par type.
      foreach ($all_types as $type) {
        $local_configs = $local_by_type[$type]['configs'] ?? [];
        $remote_configs_raw = $remote_by_type[$type]['configs'] ?? [];

        // Parser les configurations YAML du serveur distant.
        $remote_configs = $this->parseRemoteConfigs($remote_configs_raw);

        // Configs ajoutées pour ce type.
        foreach ($local_configs as $config_name => $local_config) {
          if (!isset($remote_configs[$config_name])) {
            if (!isset($added_by_type[$type])) {
              $added_by_type[$type] = [];
            }
            $added_by_type[$type][] = [
              'name' => $config_name,
              'type' => $type,
              'local_data' => $local_config,
            ];
            $total_added++;
          }
        }

        // Configs supprimées pour ce type.
        foreach ($remote_configs as $config_name => $remote_config) {
          if (!isset($local_configs[$config_name])) {
            if (!isset($deleted_by_type[$type])) {
              $deleted_by_type[$type] = [];
            }
            $deleted_by_type[$type][] = [
              'name' => $config_name,
              'type' => $type,
              'remote_data' => $remote_config,
            ];
            $total_deleted++;
          }
        }

        // Configs modifiées pour ce type.
        foreach ($local_configs as $config_name => $local_config) {
          if (isset($remote_configs[$config_name])) {
            $remote_config = $remote_configs[$config_name];
            try {
              if ($this->configsAreDifferent($local_config, $remote_config)) {
                if (!isset($modified_by_type[$type])) {
                  $modified_by_type[$type] = [];
                }
                $modified_by_type[$type][] = [
                  'name' => $config_name,
                  'type' => $type,
                  'local_data' => $local_config,
                  'remote_data' => $remote_config,
                  'diff' => $this->generateDiff($config_name, $local_config, $remote_config),
                ];
                $total_modified++;
              }
            }
            catch (\Exception $e) {
              $this->logger->warning('Error comparing config @name: @message', [
                '@name' => $config_name,
                '@message' => $e->getMessage(),
              ]);
              continue;
            }
          }
        }
      }
    }
    catch (\Exception $e) {
      $this->logger->error('Error during config comparison: @message', [
        '@message' => $e->getMessage(),
      ]);
    }

    // Trier les résultats par type.
    ksort($added_by_type);
    ksort($modified_by_type);
    ksort($deleted_by_type);

    $comparison_results = [
      'timestamp' => date('c'),
      'local_info' => [
        'site_name' => $local_data['site_name'] ?? '',
        'count' => $local_data['total_count'] ?? 0,
      ],
      'remote_info' => [
        'site_name' => $remote_data['site_name'] ?? '',
        'count' => $remote_data['total_count'] ?? 0,
      ],
      'summary' => [
        'added' => $total_added,
        'modified' => $total_modified,
        'deleted' => $total_deleted,
        'total_differences' => $total_added + $total_modified + $total_deleted,
      ],
      'differences_by_type' => [
        'added' => $added_by_type,
        'modified' => $modified_by_type,
        'deleted' => $deleted_by_type,
      ],
    ];

    $this->logger->info('Configuration comparison completed: @added added, @modified modified, @deleted deleted', [
      '@added' => $total_added,
      '@modified' => $total_modified,
      '@deleted' => $total_deleted,
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
      'visual_diff' => $this->generateVisualDiff($config_name, $local_data, $remote_data),
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
      // Récupérer les données distantes (déjà groupées par type).
      $remote_data = $this->fetchRemoteConfig($url);
      if ($remote_data === NULL) {
        return NULL;
      }

      // Récupérer les données locales et les grouper par type.
      $local_configs = $this->configHelper->getAllConfigurations();
      $local_info = $this->configHelper->getSiteInfo();
      $local_by_type = $this->configHelper->groupConfigsByType($local_configs);

      $local_data = [
        'timestamp' => $local_info['timestamp'],
        'site_name' => $local_info['site_name'],
        'total_count' => count($local_configs),
        'configs_by_type' => $local_by_type,
      ];

      // Comparer les configurations (type par type).
      $comparison_results = $this->compareConfigs($local_data, $remote_data);

      // Sauvegarder les résultats dans un fichier privé.
      $this->saveComparisonResults($comparison_results);

      return $comparison_results;
    }
    catch (\Exception $e) {
      $this->logger->error('Error during full comparison: @message', [
        '@message' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

  /**
   * Generate the complete config comparison report.
   *
   * This method orchestrates the entire process:
   * - Gets remote URL from settings
   * - Performs full comparison
   * - Saves results to file.
   *
   * @return array
   *   Array with 'success', 'message', 'summary', etc.
   */
  public function generateDiffReport(): array {
    $config = \Drupal::config('vactory_diff_config_client.settings');
    $remote_url = $config->get('remote_url');

    if (empty($remote_url)) {
      return [
        'success' => FALSE,
        'message' => 'Remote URL is required. Please configure it in Vactory Diff Settings (/admin/config/development/vactory-diff/settings).',
      ];
    }

    $this->logger->info('Starting configuration comparison with remote URL: @url', [
      '@url' => $remote_url,
    ]);

    $results = $this->performFullComparison($remote_url);

    if ($results === NULL) {
      return [
        'success' => FALSE,
        'message' => 'Failed to perform comparison. Check logs for details.',
      ];
    }

    $summary = $results['summary'] ?? [];

    return [
      'success' => TRUE,
      'message' => 'Comparison completed successfully',
      'remote_url' => $remote_url,
      'summary' => $summary,
      'added_count' => $summary['added'] ?? 0,
      'modified_count' => $summary['modified'] ?? 0,
      'deleted_count' => $summary['deleted'] ?? 0,
      'total_differences' => $summary['total_differences'] ?? 0,
    ];
  }

  /**
   * Save comparison results to a private file.
   *
   * @param array $results
   *   The comparison results to save.
   *
   * @return bool
   *   TRUE if saved successfully, FALSE otherwise.
   */
  protected function saveComparisonResults(array $results): bool {
    $success = FALSE;

    try {
      // Créer le répertoire si nécessaire.
      $directory = 'private://config-diff';
      if (!$this->fileSystem->prepareDirectory($directory, FileSystemInterface::CREATE_DIRECTORY)) {
        $this->logger->error('Unable to create directory @directory', [
          '@directory' => $directory,
        ]);
        return FALSE;
      }

      // Chemin du fichier de résultats.
      $file_path = $directory . '/report.json';

      // Encoder les résultats en JSON.
      $json_data = json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
      if ($json_data === FALSE) {
        $this->logger->error('Unable to encode comparison results to JSON');
      }
      else {
        // Écrire le fichier.
        $file_uri = $this->fileSystem->saveData($json_data, $file_path, FileSystemInterface::EXISTS_REPLACE);

        if ($file_uri === FALSE) {
          $this->logger->error('Unable to save comparison results to @file', [
            '@file' => $file_path,
          ]);
        }
        else {
          $this->logger->info('Comparison results saved to @file', [
            '@file' => $file_uri,
          ]);
          $success = TRUE;
        }
      }
    }
    catch (\Exception $e) {
      $this->logger->error('Error saving comparison results: @message', [
        '@message' => $e->getMessage(),
      ]);
      return FALSE;
    }

    return $success;
  }

  /**
   * Load comparison results from the private file.
   *
   * @return array|null
   *   The comparison results or NULL if not found or error.
   */
  public function loadComparisonResults(): ?array {
    $results = NULL;

    try {
      $config = \Drupal::config('vactory_diff_config_client.settings');
      $file_path = $config->get('config_report_path') ?? $this->getDefaultReportPath();

      // Vérifier si le fichier existe.
      if (!file_exists($file_path)) {
        $this->logger->info('No comparison results file found at @file', [
          '@file' => $file_path,
        ]);
        return NULL;
      }

      // Lire le contenu du fichier.
      $json_data = file_get_contents($file_path);
      if ($json_data === FALSE) {
        $this->logger->error('Unable to read comparison results from @file', [
          '@file' => $file_path,
        ]);
      }
      else {
        // Décoder le JSON.
        $decoded = json_decode($json_data, TRUE);
        if ($decoded === NULL) {
          $this->logger->error('Invalid JSON in comparison results file @file', [
            '@file' => $file_path,
          ]);
        }
        else {
          $this->logger->info('Comparison results loaded from @file', [
            '@file' => $file_path,
          ]);
          $results = $decoded;
        }
      }
    }
    catch (\Exception $e) {
      $this->logger->error('Error loading comparison results: @message', [
        '@message' => $e->getMessage(),
      ]);
      return NULL;
    }

    return $results;
  }

  /**
   * Get the timestamp of the last comparison.
   *
   * @return string|null
   *   The timestamp of the last comparison or NULL if not available.
   */
  public function getLastComparisonTimestamp(): ?string {
    $results = $this->loadComparisonResults();
    return $results['timestamp'] ?? NULL;
  }

  /**
   * Parse remote configurations from YAML strings to arrays.
   *
   * @param array $remote_configs_raw
   *   Raw remote configurations (YAML strings).
   *
   * @return array
   *   Parsed configurations as arrays.
   */
  protected function parseRemoteConfigs(array $remote_configs_raw): array {
    $parsed_configs = [];

    foreach ($remote_configs_raw as $config_name => $yaml_string) {
      try {
        // Parser le YAML en array.
        $config_data = Yaml::parse($yaml_string);
        $parsed_configs[$config_name] = $config_data;
      }
      catch (\Exception $e) {
        $this->logger->warning('Error parsing YAML for config @name: @message', [
          '@name' => $config_name,
          '@message' => $e->getMessage(),
        ]);

        $parsed_configs[$config_name] = [];
      }
    }

    return $parsed_configs;
  }

  /**
   * Generate visual diff for configuration comparison.
   *
   * @param string $config_name
   *   The configuration name.
   * @param array $local_data
   *   Local configuration data.
   * @param array $remote_data
   *   Remote configuration data.
   *
   * @return array
   *   Array containing visual diff information.
   */
  protected function generateVisualDiff(string $config_name, array $local_data, array $remote_data): array {
    try {
      // Convertir les données en YAML pour une meilleure lisibilité.
      $local_yaml = Yaml::dump($local_data, 10, 2, Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK);
      $remote_yaml = Yaml::dump($remote_data, 10, 2, Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK);

      // Créer le diff avec SebastianBergmann\Diff.
      $builder = new UnifiedDiffOutputBuilder(
        "--- Local: $config_name\n+++ Remote: $config_name\n",
        TRUE
      );
      $differ = new Differ($builder);
      $diff_output = $differ->diff($remote_yaml, $local_yaml);

      // Parser le diff pour créer une structure plus lisible.
      $diff_lines = explode("\n", $diff_output);
      $parsed_diff = [
        'raw_diff' => $diff_output,
        'lines' => [],
        'summary' => [
          'added' => 0,
          'removed' => 0,
          'modified' => 0,
        ],
      ];

      foreach ($diff_lines as $line) {
        $line_type = 'context';
        $content = $line;

        if (str_starts_with($line, '+')) {
          $line_type = 'added';
          $parsed_diff['summary']['added']++;
        }
        elseif (str_starts_with($line, '-')) {
          $line_type = 'removed';
          $parsed_diff['summary']['removed']++;
        }
        elseif (str_starts_with($line, '@')) {
          $line_type = 'header';
        }

        $parsed_diff['lines'][] = [
          'type' => $line_type,
          'content' => $content,
          'line_number' => count($parsed_diff['lines']),
        ];
      }

      return $parsed_diff;
    }
    catch (\Exception $e) {
      $this->logger->warning('Error generating visual diff for @name: @message', [
        '@name' => $config_name,
        '@message' => $e->getMessage(),
      ]);

      return [
        'raw_diff' => 'Error generating diff',
        'lines' => [],
        'summary' => ['added' => 0, 'removed' => 0, 'modified' => 0],
      ];
    }
  }

  /**
   * Default report path.
   */
  private function getDefaultReportPath(): string {
    return 'private://config-diff/report.json';
  }

}
