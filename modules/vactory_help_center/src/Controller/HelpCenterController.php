<?php

namespace Drupal\vactory_help_center\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\vactory_help_center\Services\HelpCenterHelper;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for returning Help Center nodes.
 */
class HelpCenterController extends ControllerBase {

  /**
   * Help center service.
   *
   * @var \Drupal\vactory_help_center\Services\HelpCenterHelper
   */
  protected $helpCenterHelper;

  /**
   * Constructs a HelpCenterController object.
   */
  public function __construct(HelpCenterHelper $helpCenterHelper) {
    $this->helpCenterHelper = $helpCenterHelper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('vactory_help_center.helper')
    );
  }

  /**
   * Returns a list of Help Center nodes filtered by keyword.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A JSON response containing the filtered list of nodes.
   */
  public function getHelpCenterNodes(Request $request) {
    $keyword = $request->query->get('keyword', '');
    $result = $this->helpCenterHelper->search($keyword);
    return new JsonResponse($result);
  }

}
