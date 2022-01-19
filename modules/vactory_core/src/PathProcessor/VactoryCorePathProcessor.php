<?php

namespace Drupal\vactory_core\PathProcessor;

use Drupal\Core\PathProcessor\OutboundPathProcessorInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Symfony\Component\HttpFoundation\Request;

/**
 * Vactory Core Path Processor.
 *
 * @package Drupal\vactory_core\PathProcessor
 */
class VactoryCorePathProcessor implements OutboundPathProcessorInterface {

  /**
   * {@inheritDoc}
   */
  public function processOutbound($path, &$options = [], Request $request = NULL, BubbleableMetadata $bubbleable_metadata = NULL) {
    // Remove _wrapper_format query param from non ajax requests.
    if (!empty($request->query) && !$request->isXmlHttpRequest()) {
      $params = $request->query->all();
      if (isset($params['_wrapper_format'])) {
        $request->query->remove('_wrapper_format');
        $request->overrideGlobals();
      }
    }
    return $path;
  }

}
