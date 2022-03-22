<?php

namespace Drupal\vactory_jsonapi\Controller;

use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class BreadcrumbController.
 */
class BreadcrumbController extends ControllerBase {

  /**
   * The breadcrumb manager.
   *
   * @var \Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface
   */
  protected $breadcrumbManager;

  /**
   * Constructs a new SystemBreadcrumbBlock object.
   *
   * @param \Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface $breadcrumb_manager
   *   The breadcrumb manager.
   */
  public function __construct(BreadcrumbBuilderInterface $breadcrumb_manager) {
    $this->breadcrumbManager = $breadcrumb_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('breadcrumb')
    );
  }

  /**
   * Get breadcrumb data for current page.
   *
   * @return string
   *   Return JSON.
   */
  public function index() {
    $breadcrumbBuilder = \Drupal::service('vactory_jsonapi.breadcrumb.generate');
    $data = $breadcrumbBuilder->build();

    return new JsonResponse($data);
  }

}
