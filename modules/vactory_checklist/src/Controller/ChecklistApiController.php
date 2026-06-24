<?php

namespace Drupal\vactory_checklist\Controller;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Controller\ControllerBase;
use Drupal\vactory_checklist\ChecklistPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * API to run a single checklist plugin (authenticated via API key).
 */
class ChecklistApiController extends ControllerBase {

  /**
   * The checklist plugin manager.
   *
   * @var \Drupal\vactory_checklist\ChecklistPluginManager
   */
  protected $pluginManager;

  /**
   * Constructs a ChecklistApiController.
   *
   * @param \Drupal\vactory_checklist\ChecklistPluginManager $plugin_manager
   *   The checklist plugin manager.
   */
  public function __construct(ChecklistPluginManager $plugin_manager) {
    $this->pluginManager = $plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.vactory_checklist')
    );
  }

  /**
   * Runs one checklist plugin and returns JSON.
   */
  public function run(string $plugin_id): JsonResponse {
    if (!$this->pluginManager->hasDefinition($plugin_id)) {
      throw new NotFoundHttpException();
    }

    $plugin = $this->pluginManager->createInstance($plugin_id);
    $result = $plugin->runCheck();

    $status = $this->mapApiStatus($result);
    $label = (string) $plugin->getLabel();
    $message = $this->buildApiMessage($result);

    $response = new JsonResponse([
      'status' => $status,
      'label' => $label,
      'message' => $message,
    ]);
    $response->setEncodingOptions(JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $response->headers->set('Cache-Control', 'no-store, private');
    return $response;
  }

  /**
   * Maps plugin result to API status integer.
   */
  protected function mapApiStatus(array $result): int {
    if (!empty($result['status'])) {
      return 1;
    }
    if (!empty($result['is_warning'])) {
      return 0;
    }
    return -1;
  }

  /**
   * Builds the message string: summary plus details when present.
   */
  protected function buildApiMessage(array $result): string {
    $summary = isset($result['message']) ? (string) $result['message'] : '';
    $details = $result['details'] ?? [];
    if (!is_array($details) || $details === []) {
      return $summary;
    }

    $encoded = Json::encode($this->normalizeDetailsForJson($details));
    return $summary !== ''
      ? $summary . "\n\n" . $encoded
      : $encoded;
  }

  /**
   * Normalizes detail rows for JSON (scalars and nested arrays).
   */
  protected function normalizeDetailsForJson(array $details): array {
    $out = [];
    foreach ($details as $row) {
      if (!is_array($row)) {
        $out[] = $this->scalarDetailValue($row);
        continue;
      }
      $row_out = [];
      foreach ($row as $k => $v) {
        if (is_array($v)) {
          $row_out[$k] = $v;
        }
        else {
          $row_out[$k] = $this->scalarDetailValue($v);
        }
      }
      $out[] = $row_out;
    }
    return $out;
  }

  /**
   * Scalar Detail Value.
   */
  protected function scalarDetailValue(mixed $value): string|int|float|bool|null {
    if ($value === NULL || is_bool($value) || is_int($value) || is_float($value)) {
      return $value;
    }
    $s = (string) $value;
    $s = html_entity_decode(strip_tags($s), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    return trim(preg_replace('/\s+/u', ' ', $s));
  }

}
