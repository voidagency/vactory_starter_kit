<?php

namespace Drupal\vactory_forums\Controller;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\node\Entity\Node;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class Views Count Controller
 *
 * @package Drupal\vactory_forums\Controller
 */
class ViewsCountController extends ControllerBase {

  /**
   * @var EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * @var ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * @var AccountProxyInterface
   */
  protected $currentUser;

  /**
   * ViewsCountController constructor.
   *
   * @param EntityTypeManagerInterface $entityTypeManager
   * @param LanguageManagerInterface $languageManager
   * @param ConfigFactoryInterface $configFactory
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager,
                              LanguageManagerInterface $languageManager,
                              ConfigFactoryInterface $configFactory,
                              AccountProxyInterface $currentUser) {
    $this->entityTypeManager = $entityTypeManager;
    $this->languageManager = $languageManager;
    $this->configFactory = $configFactory;
    $this->currentUser = $currentUser;
  }

  /**
   * @param ContainerInterface $container
   *
   * @return ControllerBase|static
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('language_manager'),
      $container->get('config.factory'),
      $container->get('current_user')
    );
  }

  /**
   * @param Request $request
   *
   * @return JsonResponse
   * @throws InvalidPluginDefinitionException
   * @throws PluginNotFoundException
   */
  public function index(int $nid) {
    if (!isset($nid) || empty($nid)) {
      return new JsonResponse([
        'resources' => $this->t('Empty PARAMS!'),
        'status' => 404,
      ]);
    }
    /* @var Node */
    $node = $this->entityTypeManager->getStorage('node')->load($nid);
    $value = $node->get('field_forum_views_count')->value ?? 0;
    try {
      $node->set('field_forum_views_count', (int) $value + 1)->save();
    } catch (\Exception $exception) {
      \Drupal::logger('vactory_forums')->error($exception->getMessage());
    }
    return new JsonResponse([
      'resources' => $this->t('Count changed succesfully'),
      'status' => 200,
    ]);
  }
}