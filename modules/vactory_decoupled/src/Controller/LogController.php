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
    if (!isset($payload['reason']) || !isset($payload['path']) || !isset($payload['source']) || !isset($payload['stack'])) {
      return new JsonResponse(['error' => 'missing params'], 503);
    }

    $channel = $payload['source'];
    \Drupal::logger("Nextjs [{$channel}]")->error(
      '<strong>Path</strong>: @path <br><hr> <strong>Error message</strong>: @reason <br><hr> <strong>Error stack</strong>: @stack',
      [
        '@reason' => Html::escape($payload['reason']),
        '@path' => Html::escape($payload['path']),
        '@stack' => Html::escape($payload['stack']),
      ]
    );
    return new JsonResponse($payload);
  }
}
