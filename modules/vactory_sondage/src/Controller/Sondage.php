<?php

namespace Drupal\vactory_sondage\Controller;

use Drupal\block_content\Entity\BlockContent;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormBuilder;
use Drupal\vactory_sondage\Services\SondageManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 *
 */
class Sondage extends ControllerBase {

  /**
   * Sondage Manager Service.
   *
   * @var \Drupal\vactory_sondage\Services\SondageManager
   */
  protected $sondageManager;

  /**
   * The Sondage Controller constructor.
   *
   *
   * @param \Drupal\vactory_sondage\Services\SondageManager $sondageManager
   * Sondage Manager Service.
   *
   */
  public function __construct(SondageManager $sondageManager) {
    $this->sondageManager = $sondageManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('vactory_sondage.manager')
    );
  }

  /**
   * Sondage method.
   */
  public function sondage($sondage_id, $option) {
    $block = BlockContent::load($sondage_id);
    if ($block){
      $this->sondageManager->vote($block,$option);
      $stats = $this->sondageManager->getStatistics($block);
      return new JsonResponse($stats);
    }
    return new JsonResponse([]);
  }

  /**
   * Statistics method.
   */
  public function statistics($sondage_id) {
    $block = BlockContent::load($sondage_id);
    if ($block){
      $stats = $this->sondageManager->getStatistics($block);
      return new JsonResponse($stats);
    }

    return new JsonResponse([]);
  }

}
