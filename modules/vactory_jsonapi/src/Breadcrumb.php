<?php

namespace Drupal\vactory_jsonapi;

use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Routing\RouteMatch;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\Database\Connection;
use Symfony\Component\Routing\Matcher\RequestMatcherInterface;

/**
 * {@inheritdoc}
 */
class Breadcrumb {

  /**
   * The breadcrumb manager.
   *
   * @var \Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface
   */
  protected $breadcrumbManager;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The router doing the actual routing.
   *
   * @var \Symfony\Component\Routing\Matcher\RequestMatcherInterface
   */
  protected $router;

  /**
   * The entity repository manager.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    MenuBasedBreadcrumbBuilder $breadcrumb_manager,
    Connection $connection,
    RequestMatcherInterface $router,
    EntityRepositoryInterface $entity_repository
  ) {
    $this->breadcrumbManager = $breadcrumb_manager;
    $this->connection = $connection;
    $this->router = $router;
    $this->entityRepository = $entity_repository;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $data = [];
    $alias = $this->getAlias();

    foreach ($alias as $row) {
      $alias_new = '/' . $row['langcode'] . $row['alias'];
      $breadcrumb = $this->getBreadcrumbForPath($alias_new, $row['langcode']);
      if ($breadcrumb) {
        $data[] = [
          'path'  => $alias_new,
          'items' => $breadcrumb,
        ];
      }
    }

    return $data;
  }

  /**
   * {@inheritdoc}
   */
  protected function getAlias() {
    $language = \Drupal::languageManager()->getCurrentLanguage()->getId();
    $query = $this->connection->select('path_alias', 'base_table');
    $query->condition('base_table.status', 1);
    $query->condition('base_table.path', '%/node/%', 'LIKE');
    $query->condition('base_table.langcode', $language);
    $query->fields('base_table', ['langcode', 'path', 'alias']);

    return $query->execute()->fetchAll(\PDO::FETCH_ASSOC);
  }

  /**
   * Get breadcrumb data for current page.
   *
   * @param string $path
   *   Path string.
   *
   * @param string $langcode
   *   Path lang code.
   *
   * @return array
   *   Return breadcrumb data.
   */
  public function getBreadcrumbForPath($path, $langcode) {
    $breadcrumbs_data = [];

    // Setup Request.
    try {
      $routeMatch = $this->router->match($path);
      $request_stack = new RequestStack();
      $request = Request::create($path, 'GET');
      $request->attributes = new ParameterBag($routeMatch);
      $request_stack->push($request);

      $current_route_match = new CurrentRouteMatchWithRequest($request_stack);


      /* @var \Drupal\Core\Breadcrumb\Breadcrumb $breadcrumbs */
      $breadcrumbs = $this->breadcrumbManager->build($current_route_match, $langcode);

      /* @var \Drupal\Core\Link $link */
      foreach ($breadcrumbs->getLinks() as $link) {
        $text = $link->getText();
        $url = $link->getUrl()->toString();
        $url = str_replace('/backend', '', $url);

        array_push($breadcrumbs_data, [
          'url'  => $url,
          'text' => $text,
        ]);
      }
    } catch (\Exception $exception) {
    }

    return $breadcrumbs_data;
  }

}
