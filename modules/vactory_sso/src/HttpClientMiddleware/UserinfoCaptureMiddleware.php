<?php

namespace Drupal\vactory_sso\HttpClientMiddleware;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\vactory_sso\UserinfoCollector;
use GuzzleHttp\Psr7\Utils;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Captures the userinfo response fetched by oauth_login_oauth2.
 *
 * Since oauth_login_oauth2 3.x the resource owner is a local variable of the
 * SSO callback controller (flattened, then dropped) and no hook or event
 * exposes it. The module fetches it through the Drupal HTTP client: on the
 * SSO callback route only, the response of the configured userinfo endpoint
 * is decoded and kept in memory for vactory_sso_user_login(). The response
 * is handed back unchanged to the contrib module.
 */
final class UserinfoCaptureMiddleware {

  /**
   * Routes of the SSO callback.
   *
   * The oauth_login_oauth2 route (/mo_callback in 3.x) and the vactory_sso
   * route that serves the same controller on /mo_login, the return address
   * of 2.x.
   */
  public const CALLBACK_ROUTES = [
    'oauth_login_oauth2.mo_login',
    'vactory_sso.mo_login',
  ];

  /**
   * Whether the given route is an SSO callback route.
   */
  public static function isCallbackRoute(?string $route_name): bool {
    return in_array($route_name, self::CALLBACK_ROUTES, TRUE);
  }

  /**
   * Constructs a UserinfoCaptureMiddleware object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
   *   The current route match.
   * @param \Drupal\vactory_sso\UserinfoCollector $collector
   *   Keeps the claims for vactory_sso_user_login().
   */
  public function __construct(
    private readonly ConfigFactoryInterface $configFactory,
    private readonly RouteMatchInterface $routeMatch,
    private readonly UserinfoCollector $collector,
  ) {}

  /**
   * Returns the Guzzle middleware.
   */
  public function __invoke(): callable {
    return function (callable $handler): callable {
      return function (RequestInterface $request, array $options) use ($handler) {
        if (!$this->applies($request)) {
          return $handler($request, $options);
        }
        return $handler($request, $options)->then(function (ResponseInterface $response): ResponseInterface {
          $contents = (string) $response->getBody();
          $claims = json_decode($contents, TRUE);
          if ($response->getStatusCode() === 200 && is_array($claims) && !isset($claims['error'])) {
            $this->collector->set($claims);
          }
          // Hand a fresh, unread body back to the contrib module.
          return $response->withBody(Utils::streamFor($contents));
        });
      };
    };
  }

  /**
   * Whether the request is the userinfo call of the SSO callback.
   */
  private function applies(RequestInterface $request): bool {
    if ($request->getMethod() !== 'GET' || !self::isCallbackRoute($this->routeMatch->getRouteName())) {
      return FALSE;
    }
    $endpoint = (string) $this->configFactory->get('oauth_login_oauth2.settings')->get('miniorange_auth_client_user_info_ep');
    // The contrib module appends the access token to an endpoint ending
    // with "=", hence a prefix match.
    return $endpoint !== '' && str_starts_with((string) $request->getUri(), $endpoint);
  }

}
