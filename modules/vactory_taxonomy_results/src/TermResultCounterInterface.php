<?php

namespace Drupal\vactory_taxonomy_results;

use Drupal\Core\Entity\EntityInterface;

/**
 * Term result counter interface.
 */
interface TermResultCounterInterface {

  /**
   * Term result count callback.
   *
   * @param EntityInterface $entity
   * @return NULL|integer
   */
  public function termResultCount(EntityInterface $entity, $field_name);

}