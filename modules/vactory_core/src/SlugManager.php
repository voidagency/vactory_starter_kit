<?php

namespace Drupal\vactory_core;

use Drupal\taxonomy\Entity\Term;

/**
 * SlugManager.
 *
 * Service to make it easy to generate and manage custom Slug.
 */
class SlugManager {

  /**
   * Retrieve slug from taxonomy alias url.
   *
   * Example:
   * <code>
   * # /category/immobilier -> /taxonomy/term/4
   * $this->taxonomy2Slug(Term(4)) -> 'immobilier'
   * </code>
   *
   * @param \Drupal\taxonomy\Entity\Term $term
   *   Taxonomy Term.
   *
   * @return string|null
   *   Return the according slug or null if when not found.
   *
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  public function taxonomy2Slug(Term $term) {
    if (!empty($term)) {
      $url = $term->toUrl()->toString();
      return substr($url, strrpos($url, '/') + 1);
    }
    return NULL;
  }

}
