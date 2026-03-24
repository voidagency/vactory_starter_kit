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
      return [
        'status' => FALSE,
        'message' => $this->t("L'URL du frontend n'est pas configurée (BASE_FRONTEND_URL)"),
        'details' => [
          ['error' => "La variable 'BASE_FRONTEND_URL' n'est pas définie"],
        ],
      ];
    }

    $frontend_url = rtrim($frontend_url, '/');
    $api_url = $frontend_url . '/api/google-console-files';

    try {
      $response = $this->httpClient->get($api_url, [
        'timeout' => 10,
      ]);
      $body = (string) $response->getBody();
      $data = json_decode($body, TRUE);

      if (json_last_error() !== JSON_ERROR_NONE) {
        return [
          'status' => FALSE,
          'message' => $this->t('Erreur lors du parsing de la réponse JSON : @error', [
            '@error' => json_last_error_msg(),
          ]),
          'details' => [],
        ];
      }

      // Extraire les noms de fichiers depuis la réponse.
      $files = [];
      if (is_array($data)) {
        foreach ($data as $item) {
          if (isset($item['file']) && !empty($item['file'])) {
            $files[] = $item['file'];
          }
        }
      }

      $file_count = count($files);

      // Déterminer le statut et le message selon le nombre de fichiers.
      if ($file_count === 0) {
        return [
          'status' => FALSE,
          'message' => $this->t('Aucun fichier HTML de Google Search Console trouvé'),
          'details' => [],
        ];
      }
      elseif ($file_count === 1) {
        $file_url = $frontend_url . '/' . $files[0];
        $url_markup = Markup::create('<a href="' . htmlspecialchars($file_url, ENT_QUOTES, 'UTF-8') . '" target="_blank" rel="noopener noreferrer">' . htmlspecialchars($file_url, ENT_QUOTES, 'UTF-8') . '</a>');
        return [
          'status' => TRUE,
          'message' => $this->t('Un fichier HTML de Google Search Console est présent'),
          'details' => [
            [
              'file' => $files[0],
              'url' => $url_markup,
            ],
          ],
        ];
      }
      else {
        // Plusieurs fichiers : warning.
        $details = [];
        foreach ($files as $file) {
          $file_url = $frontend_url . '/' . $file;
          $url_markup = Markup::create('<a href="' . htmlspecialchars($file_url, ENT_QUOTES, 'UTF-8') . '" target="_blank" rel="noopener noreferrer">' . htmlspecialchars($file_url, ENT_QUOTES, 'UTF-8') . '</a>');
          $details[] = [
            'file' => $file,
            'url' => $url_markup,
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
    }
    catch (RequestException $e) {
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
  }

}
