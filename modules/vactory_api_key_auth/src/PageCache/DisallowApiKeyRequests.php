<?php

namespace Drupal\vactory_api_key_auth\PageCache;

use Drupal\vactory_api_key_auth\Authentication\Provider\ApiKeyAuth;
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
    return ApiKeyAuth::keyFromRequest($request);
  }

  /**
   * {@inheritdoc}
   */
  public function check(Request $request) {
    return $this->isApiKeyRequest($request) ? static::DENY : NULL;
  }

}
