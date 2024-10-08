<?php

namespace Drupal\vactory_help_center\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\path_alias\AliasManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for returning Help Center nodes.
 */
class HelpCenterController extends ControllerBase {

  /**
   * The path alias manager.
   *
   * @var \Drupal\path_alias\AliasManagerInterface
   */
  protected $aliasManager;

  /**
   * Constructs a HelpCenterController object.
   */
  public function __construct(AliasManagerInterface $alias_manager) {
    $this->aliasManager = $alias_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('path_alias.manager')
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
    $langcode = $this->languageManager()->getCurrentLanguage()->getId();

    $query = $this->entityTypeManager()->getStorage('node')->getQuery()
      ->condition('type', 'vactory_help_center')
      ->condition('status', 1)
      ->condition('title', $keyword, 'CONTAINS')
      ->accessCheck(TRUE);

    $nids = $query->execute();

    $nodes = $this->entityTypeManager()->getStorage('node')->loadMultiple($nids);

    $result = [];
    foreach ($nodes as $node) {
      $path = '/node/' . $node->id();
      $alias = $this->aliasManager->getAliasByPath($path, $langcode);
      $node_translation = \Drupal::service('entity.repository')->getTranslationFromContext($node, $langcode);
      $result[] = [
        'title' => $node_translation->getTitle(),
        'alias' => "/{$langcode}{$alias}",
      ];
    }

    return new JsonResponse($result);
  }

}
