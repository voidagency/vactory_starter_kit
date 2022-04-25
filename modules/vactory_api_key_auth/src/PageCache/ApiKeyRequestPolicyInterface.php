<?php

namespace Drupal\vactory_api_key_auth\PageCache;

use Drupal\Core\PageCache\RequestPolicyInterface;
use Drupal\Core\PageCache\ResponsePolicyInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * The interface for determining the requests with OAuth data.
 *
 * The service that implements the interface is used to determine whether
 * the page should be served from cache and also if the request contains
 * an access token to proceed to the authentication.
 *
 * @see \Drupal\vactory_api_key_auth\PageCache\DisallowApiKeyRequests::check()
 * @see \Drupal\vactory_api_key_auth\Authentication\Provider\ApiKeyAuthAuthenticationProvider::applies()
 */
interface ApiKeyRequestPolicyInterface extends RequestPolicyInterface {

  /**
   * Returns a state whether the request has an API KEY param.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The incoming request object.
   *
   * @return bool
   *   A state whether the request has an OAuth2 access token.
   */
  public function isApiKeyRequest(Request $request);

}
