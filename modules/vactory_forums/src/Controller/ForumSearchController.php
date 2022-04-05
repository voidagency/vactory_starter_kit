<?php

namespace Drupal\vactory_forums\Controller;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Url;
use Drupal\metatag\MetatagManagerInterface;
//use Drupal\search_api\Plugin\views\query\SearchApiQuery;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\vactory_jsonapi\Controller\SearchController;
//use Drupal\search_api\Entity\Index;

/**
 * Class SearchController
 *
 * @package Drupal\vactory_jsonapi\Controller
 */
class ForumSearchController extends SearchController
{
  protected function getSearchMachineName()
  {
    return 'forum_content_index';
  }
}
