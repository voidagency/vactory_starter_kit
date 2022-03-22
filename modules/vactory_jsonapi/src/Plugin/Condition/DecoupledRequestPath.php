<?php

namespace Drupal\vactory_jsonapi\Plugin\Condition;

use Drupal\system\Plugin\Condition\RequestPath;

/**
 * Provides a 'Decoupled Request Path' condition.
 *
 * @Condition(
 *   id = "decoupled_request_path",
 *   label = @Translation("Decoupled Request Path"),
 *   context_definitions = {
 *     "path" = @ContextDefinition("string", default_value = ""),
 *   }
 * )
 */
class DecoupledRequestPath extends RequestPath
{

  /**
   * {@inheritdoc}
   */
  public function evaluate()
  {
    // Convert path to lowercase. This allows comparison of the same path
    // with different case. Ex: /Page, /page, /PAGE.
    $pages = mb_strtolower($this->configuration['pages']);
    if (!$pages) {
      return TRUE;
    }

    $path = $this->getContextValue('path');

    // Do not trim a trailing slash if that is the complete path.
    $path = $path === '/' ? $path : rtrim($path, '/');
    $path_alias = mb_strtolower($this->aliasManager->getAliasByPath($path));

    return $this->pathMatcher->matchPath($path_alias, $pages) || (($path != $path_alias) && $this->pathMatcher->matchPath($path, $pages));
  }

}
