<?php

namespace Drupal\vactory_checklist\Plugin\Checklist;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Site\Settings;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Verifies Google Tag Manager configuration on the frontend.
 *
 * @ChecklistPlugin(
 *   id = "gtm_check",
 *   label = @Translation("Google Tag Manager Configuration Check"),
 *   description = @Translation("Verifies that Google Tag Manager is properly configured with a valid GTM ID"),
 *   category = "Analytics"
 * )
 */
class GtmCheck extends ChecklistBase implements ContainerFactoryPluginInterface {

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
   * Constructs a new GtmCheck object.
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
      return $this->buildResult(
        FALSE,
        'Frontend URL is not set in site settings.'
      );
    }

    try {
      $response = $this->httpClient->get($frontend_url);
      $html = (string) $response->getBody();

      $gtm_id = $this->extractGtmIdFromHtml($html);

      if ($gtm_id) {
        return $this->buildResult(
          TRUE,
          "Google Tag Manager is properly configured (GTM ID: {$gtm_id})"
        );
      }

      return $this->buildResult(
        FALSE,
        'Either the GTM script is missing or the GTM ID format is invalid.'
      );
    }
    catch (RequestException $e) {
      return $this->buildResult(
        FALSE,
        "Failed to fetch frontend page: {$e->getMessage()}"
      );
    }
  }

  /**
   * Extracts GTM ID from HTML content.
   */
  private function extractGtmIdFromHtml(string $html): ?string {
    $dom = new \DOMDocument();
    libxml_use_internal_errors(TRUE);
    $dom->loadHTML($html);
    libxml_clear_errors();

    foreach ($dom->getElementsByTagName('script') as $script) {
      if ($script->getAttribute('id') === 'gtm') {
        $script_content = $script->textContent;
        if (preg_match('/GTM-[A-Z0-9]{6,8}/', $script_content, $matches)) {
          return $matches[0];
        }
      }
    }

    return NULL;
  }

  /**
   * Builds standardized result array.
   */
  private function buildResult(bool $status, string $message): array {
    return [
      'status' => $status,
      'message' => $message,
      'details' => [],
    ];
  }

}
