<?php

namespace Drupal\vactory_facebook_pixel\Services;

use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Language\LanguageManager;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Path\PathMatcher;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\path_alias\AliasManager;
use GuzzleHttp\ClientInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class FacebookValidation.
 *
 * Facebook Validation.
 */
class FacebookValidation {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The Facebook pixel configuration.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * The HTTP client to fetch the feed data with.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * The UUID service.
   *
   * @var \Drupal\Component\Uuid\UuidInterface
   */
  protected $uuidService;

  /**
   * The current user object.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $logger;

  /**
   * The current path.
   *
   * @var \Drupal\Core\Path\CurrentPathStack
   */
  protected $currentPath;

  /**
   * Alias manager.
   *
   * @var \Drupal\path_alias\AliasManager
   */
  protected $aliasManager;

  /**
   * Path matcher.
   *
   * @var \Drupal\Core\Path\PathMatcher
   */
  protected $pathMatcher;

  /**
   * The language manager interface.
   *
   * @var \Drupal\Core\Language\LanguageManager
   */
  protected $currentLanguage;

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $routeMatch;

  /**
   * Request.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $request;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    ConfigFactoryInterface $configFactory,
    ClientInterface $httpClient,
    UuidInterface $uuidService,
    AccountInterface $currentUser,
    RequestStack $request,
    LoggerChannelFactoryInterface $logger,
    CurrentPathStack $currentPath,
    AliasManager $aliasManager,
    PathMatcher $pathMatcher,
    LanguageManager $languageManager,
    CurrentRouteMatch $routeMatch
  ) {
    $this->configFactory = $configFactory;
    $this->config = $this->configFactory->get('vactory_facebook_pixel.settings');
    $this->httpClient = $httpClient;
    $this->uuidService = $uuidService;
    $this->currentUser = $currentUser;
    $this->request = $request->getCurrentRequest();
    $this->logger = $logger->get('Facebook Validation');
    $this->currentPath = $currentPath;
    $this->aliasManager = $aliasManager;
    $this->pathMatcher = $pathMatcher;
    $this->currentLanguage = $languageManager->getCurrentLanguage()->getId();
    $this->routeMatch = $routeMatch;
  }

  /**
   * Send Facebook Validation.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function facebookValidation($user, $event) {
    $facebook_validation_endpoint = $this->config->get('fv_endpoint');
    $facebook_key = $this->config->get('fb_key');
    $facebook_pixel_id = $this->config->get('pixel_id');
    // Check if the configuration is empty.
    if (!empty($facebook_validation_endpoint) && !empty($facebook_key) && !empty($facebook_pixel_id)) {
      if (isset($_COOKIE['_fbp'])) {
        // Facebook validation Data.
        $data = [];
        $data['event_name'] = $event;
        $data['event_time'] = time();
        $data['event_id'] = $this->uuidService->generate();
        $pixel_data['eventID'] = $data['event_id'];
        $data['event_source_url'] = Url::fromRoute('<current>', [], ["absolute" => TRUE])->toString();
        $data['action_source'] = 'website';
        $data['user_data']['client_ip_address'] = $this->request->getClientIp();
        $data['user_data']['client_user_agent'] = $_SERVER['HTTP_USER_AGENT'];
        $data['user_data']['fbp'] = $_COOKIE['_fbp'];

        if (!empty($user)) {
          $data['user_data']['em'] = !empty($user->mail->value) ? hash('sha256', $user->mail->value) : '';
        }
        $json_data['data'][] = $data;
        $headers = ['Content-Type' => 'application/json'];
        try {
          $request = $this->httpClient->request('POST', $facebook_validation_endpoint . $facebook_pixel_id . '/events?access_token=' . $facebook_key,
            ['headers' => $headers, 'body' => json_encode($json_data)]);
          $content_body_request = json_decode($request->getBody()->getContents());
          $pixel_data['token'] = $content_body_request->fbtrace_id;
          return $pixel_data;

        }
        catch (\Exception $exception) {
          $this->logger->error('An API Error Occurred : ' . $exception);
          return NULL;
        }
      }
    }
    else {
      $this->logger->error('Facebook Validation is not configured');
      return NULL;
    }
  }

  /**
   * Check path.
   *
   * @return bool
   *   TRUE if the path conditions are met; FALSE otherwise.
   */
  public function pathCheck() {
    $toggle = $this->config->get('manage_listed_paths');
    $paths = mb_strtolower($this->config->get('listed_paths'));

    if (empty($paths)) {
      $satisfied = ($toggle == 'exclude_listed_paths');
    }
    else {
      // Compare the lowercase path alias (if any) and internal path.
      $path = $this->currentPath->getPath($this->request);
      $path_alias = mb_strtolower($this->aliasManager->getAliasByPath($path));
      $satisfied = $this->pathMatcher->matchPath($path_alias, $paths) || (($path != $path_alias) && $this->pathMatcher->matchPath($path, $paths));
      $satisfied = ($toggle == 'exclude_listed_paths') ? !$satisfied : $satisfied;
    }
    return $satisfied;
  }

  /**
   * Check roles of current user.
   *
   * @return bool
   *   TRUE if the role conditions are met; FALSE otherwise.
   */
  public function roleCheck() {
    $toggle = $this->config->get('manage_listed_roles');
    $roles = array_filter($this->config->get('listed_roles'));

    if (empty($roles)) {
      $satisfied = ($toggle == 'exclude_listed_roles');
    }
    else {
      $satisfied = FALSE;
      // Check user roles against listed roles.
      $satisfied = (bool) array_intersect($roles, $this->currentUser->getRoles());
      $satisfied = ($toggle == 'exclude_listed_roles') ? !$satisfied : $satisfied;
    }
    return $satisfied;
  }

  /**
   * Check authorised languages.
   *
   * @return bool
   *   TRUE if the role conditions are met; FALSE otherwise.
   */
  public function languageCheck() {
    $toggle = $this->config->get('manage_listed_language');
    $languages = array_filter($this->config->get('listed_languages'));
    if (empty($languages)) {
      $satisfied = ($toggle == 'exclude_listed_languages');
    }
    else {
      $satisfied = FALSE;
      // Check current language against listed languages.
      $satisfied = array_key_exists($this->currentLanguage, $languages);
      $satisfied = ($toggle == 'exclude_listed_languages') ? !$satisfied : $satisfied;
    }
    return $satisfied;
  }

  /**
   * Check authorised Content types.
   *
   * @return bool
   *   TRUE if the role conditions are met; FALSE otherwise.
   */
  public function contentTypesCheck() {
    $toggle = $this->config->get('manage_listed_content');
    $contentTypes = array_filter($this->config->get('listed_content_types'));
    $currentNode = $this->routeMatch->getParameter('node');
    $currentBundle = !empty($currentNode) ? $currentNode->bundle() : NULL;
    if (empty($contentTypes)) {
      $satisfied = ($toggle == 'exclude_listed_content');
    }
    else {
      $satisfied = FALSE;
      $satisfied = array_key_exists($currentBundle, $contentTypes);
      $satisfied = ($toggle == 'exclude_listed_content') ? !$satisfied : $satisfied;
    }
    return $satisfied;
  }

  /**
   * Check route names.
   *
   * @return bool
   *   TRUE if the path conditions are met; FALSE otherwise.
   */
  public function routeNamesCheck() {
    $routes = mb_strtolower($this->config->get('listed_routes'));
    if (empty($routes)) {
      $satisfied = FALSE;
    }
    else {
      $currentRouteName = $this->routeMatch->getRouteName();
      $satisfied = $this->pathMatcher->matchPath($currentRouteName, $routes);
    }
    return $satisfied;
  }

}
