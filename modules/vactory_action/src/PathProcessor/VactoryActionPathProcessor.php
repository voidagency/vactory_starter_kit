<?php

namespace Drupal\vactory_action\PathProcessor;

use Drupal\Core\PathProcessor\InboundPathProcessorInterface;
use Drupal\Core\PathProcessor\OutboundPathProcessorInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Symfony\Component\HttpFoundation\Request;

/**
 * Vactory action path processor to forward action query param if exist.
 *
 * @package Drupal\vactory_action\PathProcessor
 */
class VactoryActionPathProcessor implements InboundPathProcessorInterface, OutboundPathProcessorInterface {

  /**
   * {@inheritDoc}
   */
  public function processInbound($path, Request $request) {
    return $path;
  }

  /**
   * {@inheritDoc}
   */
  public function processOutbound($path, &$options = [], Request $request = NULL, BubbleableMetadata $bubbleable_metadata = NULL) {
    $params = $request && $request->query ? $request->query->all() : [];
    // Check on action params recieved from frontend instance.
    if (isset($params['action']) && isset($params['action']['id'])) {
      // Forward action params across the query string.
      $options['query'] = $params;
    }
    return $path;
  }

}
