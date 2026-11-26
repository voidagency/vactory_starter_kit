<?php

namespace Drupal\vactory_node_view_count\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Flood\FloodInterface;
use Drupal\Core\Site\Settings;
use Drupal\node\NodeInterface;
use Drupal\vactory_node_view_count\NodeViewCounter;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Returns responses for Vactory node view count routes.
 */
class VactoryNodeViewCountController extends ControllerBase {

  /**
   * Default limit of views counted per node and per IP address: none.
   *
   * $settings['vactory_node_view_count_flood_threshold'] sets a limit per
   * node, per IP address and per minute (20 is a sensible value). Enable it
   * only when the reverse proxy is declared ($settings['reverse_proxy']):
   * otherwise every visitor has the IP address of the proxy and shares the
   * same limit.
   */
  public const FLOOD_THRESHOLD = 0;

  /**
   * Flood window, in seconds.
   */
  public const FLOOD_WINDOW = 60;

  /**
   * Constructs a VactoryNodeViewCountController object.
   */
  public function __construct(
    protected NodeViewCounter $counter,
    protected FloodInterface $flood,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('vactory_node_view_count.counter'),
      $container->get('flood'),
    );
  }

  /**
   * Update counter (get nid from path).
   */
  public function incrementCounter($nid, Request $request) {
    return $this->count($nid, $request);
  }

  /**
   * Update counter (get nid from request body).
   */
  public function updateCounter(Request $request) {
    return $this->count($request->request->all()['nid'] ?? NULL, $request);
  }

  /**
   * Counts one view of the node, if the current user may view it.
   */
  protected function count($nid, Request $request): JsonResponse {
    $node = is_scalar($nid) && ctype_digit((string) $nid)
      ? $this->entityTypeManager()->getStorage('node')->load($nid)
      : NULL;
    $error = $this->rejection($node);
    if ($error === NULL && !$this->registerView($node, $request)) {
      $error = [$this->t('Too many views counted for this node.'), -4, 429];
    }
    if ($error !== NULL) {
      return new JsonResponse(['message' => $error[0], 'code' => $error[1]], $error[2]);
    }
    return new JsonResponse([
      'message' => $this->t('Node count has been incremented'),
      'count' => $this->counter->increment($node),
      'code' => 1,
    ]);
  }

  /**
   * Returns why the view of the node cannot be counted, or NULL.
   *
   * @return array|null
   *   Message, code and HTTP status of the response, or NULL.
   */
  protected function rejection($node): ?array {
    $error = NULL;
    if (!$node instanceof NodeInterface || !$node->access('view')) {
      $error = [$this->t('Invalid node id!'), -2, 404];
    }
    elseif (!$this->entityTypeManager()->getStorage('node_type')->load($node->bundle())
      ->getThirdPartySetting('vactory_node_view_count', 'enabling_count_node', '')) {
      $message = $this->t('Views count is not enabled for @bundle', [
        '@bundle' => $node->bundle(),
      ]);
      $error = [$message, -3, 400];
    }
    elseif (!$node->hasField(NodeViewCounter::FIELD)) {
      $message = $this->t('The specific node has no field (count views)!');
      $error = [$message, -1, 400];
    }
    return $error;
  }

  /**
   * Registers the view for the flood limit; FALSE when over the limit.
   */
  protected function registerView(NodeInterface $node, Request $request): bool {
    $threshold = (int) Settings::get('vactory_node_view_count_flood_threshold', self::FLOOD_THRESHOLD);
    if ($threshold <= 0) {
      return TRUE;
    }
    $event = 'vactory_node_view_count.view';
    $identifier = $request->getClientIp() . ':' . $node->id();
    $allowed = $this->flood->isAllowed($event, $threshold, self::FLOOD_WINDOW, $identifier);
    if ($allowed) {
      $this->flood->register($event, self::FLOOD_WINDOW, $identifier);
    }
    return $allowed;
  }

}
