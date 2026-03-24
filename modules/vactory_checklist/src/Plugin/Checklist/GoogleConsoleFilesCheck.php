<?php

namespace Drupal\vactory_checklist\Plugin\Checklist;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\Site\Settings;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Vérifie la présence des fichiers HTML de Google Search Console.
 *
 * @ChecklistPlugin(
 *   id = "google_console_files_check",
 *   label = @Translation("Google Search Console Files Check"),
 *   description = @Translation("Vérifie la présence des fichiers HTML de vérification Google Search Console"),
 *   category = "analytics"
 * )
 */
class GoogleConsoleFilesCheck extends ChecklistBase implements ContainerFactoryPluginInterface {

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * The settings service.
   *
   * @var \Drupal\Core\Site\Settings
   */
  protected $settings;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('http_client'),
      $container->get('settings')
    );
  }

  /**
   * Constructs a new GoogleConsoleFilesCheck object.
   */
  public function __construct(
    array $configuration,
          $plugin_id,
          $plugin_definition,
    ClientInterface $http_client,
    Settings $settings
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->httpClient = $http_client;
    $this->settings = $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function runCheck() {
    $frontend_url = $this->settings->get('BASE_FRONTEND_URL', '');

    if (empty($frontend_url)) {
      return $this->buildMissingFrontendUrlResult();
    }

    $frontend_url = rtrim($frontend_url, '/');
    $api_url = $frontend_url . '/api/google-console-files';

    $result = [];
    try {
      $response = $this->httpClient->get($api_url, [
        'timeout' => 10,
      ]);
      $body = (string) $response->getBody();
      $data = json_decode($body, TRUE);

      $result = json_last_error() !== JSON_ERROR_NONE
        ? $this->buildJsonParseErrorResult()
        : $this->buildResultFromFiles($frontend_url, $this->extractFileNamesFromResponse($data));
    }
    catch (RequestException $e) {
      $result = $this->buildRequestExceptionResult($e);
    }

    return $result;
  }

  /**
   * Builds the checklist result when BASE_FRONTEND_URL is not set.
   *
   * @return array
   *   Result array for the checklist plugin.
   */
  private function buildMissingFrontendUrlResult(): array {
    return [
      'status' => FALSE,
      'message' => $this->t("L'URL du frontend n'est pas configurée (BASE_FRONTEND_URL)"),
      'details' => [
        ['error' => "La variable 'BASE_FRONTEND_URL' n'est pas définie"],
      ],
    ];
  }

  /**
   * Builds the checklist result when the API response is not valid JSON.
   *
   * @return array
   *   Result array for the checklist plugin.
   */
  private function buildJsonParseErrorResult(): array {
    return [
      'status' => FALSE,
      'message' => $this->t('Erreur lors du parsing de la réponse JSON : @error', [
        '@error' => json_last_error_msg(),
      ]),
      'details' => [],
    ];
  }

  /**
   * Builds the checklist result when the HTTP request fails.
   *
   * @param \GuzzleHttp\Exception\RequestException $e
   *   The request exception.
   *
   * @return array
   *   Result array for the checklist plugin.
   */
  private function buildRequestExceptionResult(RequestException $e): array {
    return [
      'status' => FALSE,
      'message' => $this->t('Impossible de récupérer les fichiers depuis le frontend : @error', [
        '@error' => $e->getMessage(),
      ]),
      'details' => [
        ['error' => $e->getMessage()],
      ],
    ];
  }

  /**
   * Extracts file names from the decoded API payload.
   *
   * @param mixed $data
   *   Decoded JSON (expected list of items with a 'file' key).
   *
   * @return string[]
   *   Non-empty file names.
   */
  private function extractFileNamesFromResponse($data): array {
    $files = [];
    if (!is_array($data)) {
      return $files;
    }
    foreach ($data as $item) {
      if (isset($item['file']) && !empty($item['file'])) {
        $files[] = $item['file'];
      }
    }
    return $files;
  }

  /**
   * Builds the checklist result from the list of Google console file names.
   *
   * @param string $frontend_url
   *   Frontend base URL (no trailing slash).
   * @param string[] $files
   *   File names returned by the API.
   *
   * @return array
   *   Result array for the checklist plugin.
   */
  private function buildResultFromFiles(string $frontend_url, array $files): array {
    $file_count = count($files);

    if ($file_count === 0) {
      return [
        'status' => FALSE,
        'message' => $this->t('Aucun fichier HTML de Google Search Console trouvé'),
        'details' => [],
      ];
    }

    if ($file_count === 1) {
      return [
        'status' => TRUE,
        'message' => $this->t('Un fichier HTML de Google Search Console est présent'),
        'details' => [
          [
            'file' => $files[0],
            'url' => $this->buildFileLinkMarkup($frontend_url, $files[0]),
          ],
        ],
      ];
    }

    $details = [];
    foreach ($files as $file) {
      $details[] = [
        'file' => $file,
        'url' => $this->buildFileLinkMarkup($frontend_url, $file),
      ];
    }

    return [
      'status' => FALSE,
      'is_warning' => TRUE,
      'message' => $this->t('@count fichiers HTML de Google Search Console trouvés (un seul est attendu)', [
        '@count' => $file_count,
      ]),
      'details' => $details,
    ];
  }

  /**
   * Builds a safe link markup for a verification file on the frontend.
   *
   * @param string $frontend_url
   *   Frontend base URL (no trailing slash).
   * @param string $file
   *   File name (path segment).
   *
   * @return \Drupal\Core\Render\Markup
   *   Markup for the anchor element.
   */
  private function buildFileLinkMarkup(string $frontend_url, string $file): Markup {
    $file_url = $frontend_url . '/' . $file;
    $escaped = htmlspecialchars($file_url, ENT_QUOTES, 'UTF-8');
    return Markup::create('<a href="' . $escaped . '" target="_blank" rel="noopener noreferrer">' . $escaped . '</a>');
  }

}
