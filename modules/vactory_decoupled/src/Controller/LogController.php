<?php

namespace Drupal\vactory_decoupled\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Component\Utility\Html;

// @todo: this should be protected somehow.
class LogController extends ControllerBase
{
  /**
   * Store log message to dblog.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   */
  public function storeLogMessage(Request $request)
  {
    $payload = json_decode($request->getContent(), true);
    if (!isset($payload['reason']) || !isset($payload['path'])) {
      return new JsonResponse(['error' => 'missing params'], 503);
    }

    \Drupal::logger('nextjs')->error(
      '@reason @path',
      [
        '@reason' => Html::escape($payload['reason']),
        '@path' => Html::escape($payload['path'])
      ]
    );
    return new JsonResponse($payload);
  }
}
