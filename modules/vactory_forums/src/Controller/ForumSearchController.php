<?php

namespace Drupal\vactory_forums\Controller;

use Drupal\vactory_decoupled_search\Controller\SearchController;

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
