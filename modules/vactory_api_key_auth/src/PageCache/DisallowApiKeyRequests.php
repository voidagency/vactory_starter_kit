<?php

namespace Drupal\vactory_api_key_auth\PageCache;

use Symfony\Component\HttpFoundation\Request;

/**
 * Do not serve a page from cache if api key authentication is applicable.
 *
 * @internal
 */
class DisallowApiKeyRequests implements ApiKeyRequestPolicyInterface {

  /**
   * {@inheritdoc}
   */
  public function isApiKeyRequest(Request $request) {
    $form_api_key = $request->get('api_key');

    if (!empty($form_api_key)) {
      return $form_api_key;
    }

    $query_api_key = $request->query->get('api_key');
    if (!empty($query_api_key)) {
      return $query_api_key;
    }

    $header_api_key = $request->headers->get('apikey');
    if (!empty($header_api_key)) {
      return $header_api_key;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function check(Request $request) {
    return $this->isApiKeyRequest($request) ? static::DENY : NULL;
  }

}
