<?php

namespace Drupal\vactory_locator\PathProcessor;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Schema;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManager;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\PathProcessor\InboundPathProcessorInterface;
use Drupal\Core\PathProcessor\OutboundPathProcessorInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Routing\CurrentRouteMatch;
use Symfony\Component\HttpFoundation\Request;

/**
 * Vactory Locator Path Processor.
 *
 * @package Drupal\vactory_locator\PathProcessor
 */
class LocatorPathProcessor implements InboundPathProcessorInterface, OutboundPathProcessorInterface {

  /**
   * Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Database Service Object.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Current route match object.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $routeMatch;

  /**
   * Current language code.
   *
   * @var string
   */
  protected $langcode;

  /**
   * Current path stack.
   *
   * @var \Drupal\Core\Path\CurrentPathStack
   */
  protected $currentPathStack;

  /**
   * VactoryViewsPrettyPathProcessor constructor.
   */
  public function __construct(Connection $connection, EntityTypeManagerInterface $entityTypeManager, CurrentRouteMatch $routeMatch, LanguageManager $languageManager, CurrentPathStack $currentPathStack) {
    $this->connection = $connection;
    $this->entityTypeManager = $entityTypeManager;
    $this->routeMatch = $routeMatch;
    $this->langcode = $languageManager->getCurrentLanguage()->getId();
    $this->currentPathStack = $currentPathStack;
  }

  /**
   * Processes the inbound path.
   *
   * Implementations may make changes to the request object passed in but should
   * avoid all other side effects. This method can be called to process requests
   * other than the current request.
   *
   * @param string $path
   *   The path to process, with a leading slash.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The HttpRequest object representing the request to process. Note, if this
   *   method is being called via the path_processor_manager service and is not
   *   part of routing, the current request object must be cloned before being
   *   passed in.
   *
   * @return string
   *   The processed path.
   */
  public function processInbound($path, Request $request) {
    $current_path = $this->currentPathStack->getPath();
    if ($this->isLocatorPath($path) && !\Drupal::service('router.admin_context')->isAdminRoute()) {
      $pieces = explode('/', $current_path);
      $index = $pieces[1] === $this->langcode ? 3 : 2;
      if (isset($pieces[$index])) {
        $path_alias = urldecode($pieces[$index]);
        $locator = \Drupal::entityQuery('locator_entity')
          ->condition('field_locator_path_alias', $path_alias, '=')
          ->execute();
        if (!empty($locator)) {
          $locator_entity_id = array_values($locator)[0];
          $path = '/locator_entity/' . $locator_entity_id;
        }
      }
    }

    return $path;
  }

  /**
   * Processes the outbound path.
   *
   * @param string $path
   *   The path to process, with a leading slash.
   * @param mixed $options
   *   (optional) An associative array of additional options, with the following
   *   elements:
   *   - 'query': An array of query key/value-pairs (without any URL-encoding)
   *     to append to the URL.
   *   - 'fragment': A fragment identifier (named anchor) to append to the URL.
   *     Do not include the leading '#' character.
   *   - 'absolute': Defaults to FALSE. Whether to force the output to be an
   *     absolute link (beginning with http:). Useful for links that will be
   *     displayed outside the site, such as in an RSS feed.
   *   - 'language': An optional language object used to look up the alias
   *     for the URL. If $options['language'] is omitted, it defaults to the
   *     current language for the language type LanguageInterface::TYPE_URL.
   *   - 'https': Whether this URL should point to a secure location. If not
   *     defined, the current scheme is used, so the user stays on HTTP or HTTPS
   *     respectively. TRUE enforces HTTPS and FALSE enforces HTTP.
   *   - 'base_url': Only used internally by a path processor, for example, to
   *     modify the base URL when a language dependent URL requires so.
   *   - 'prefix': Only used internally, to modify the path when a language
   *     dependent URL requires so.
   *   - 'route': The route object for the given path. It will be set by
   *     \Drupal\Core\Routing\UrlGenerator::generateFromRoute().
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The HttpRequest object representing the current request.
   * @param \Drupal\Core\Render\BubbleableMetadata $bubbleable_metadata
   *   (optional) Object to collect path processors' bubbleable metadata.
   *
   * @return string
   *   The processed path.
   */
  public function processOutbound($path, &$options = [], Request $request = NULL, BubbleableMetadata $bubbleable_metadata = NULL) {
    if ($this->isLocatorPath($path) && !\Drupal::service('router.admin_context')->isAdminRoute()) {
      $locator = $this->routeMatch->getParameter('locator_entity');
      if ($locator) {
        $path_alias = $locator->get('field_locator_path_alias')->value;
        $config = \Drupal::config('vactory_locator.settings');
        $prefix = !empty($config->get('path_url')) ? trim($config->get('path_url'), '/') : 'locator';
        $path = '/' . $prefix . '/' . $path_alias;
      }
    }

    return $path;
  }

  /**
   * Check if current path is locator.
   *
   * @return bool
   *   A boolean indicating within the current path is locator full page.
   */
  public function isLocatorPath($current_path) {
    $pieces = explode('/', $current_path);
    $path_alias = urldecode(end($pieces));
    $count = 0;
    if ($this->connection->schema()->tableExists('locator_entity__field_locator_path_alias')) {
      $count = (int) $this->connection->query("SELECT count(1) FROM locator_entity__field_locator_path_alias where
            field_locator_path_alias_value= :path_alias", [':path_alias' => $path_alias])->fetchField();
    }
    return strpos($current_path, '/locator_entity/') === 0 ||
      strpos($current_path, '/' . $this->langcode . '/locator_entity/') === 0 || $count > 0;
  }

}
