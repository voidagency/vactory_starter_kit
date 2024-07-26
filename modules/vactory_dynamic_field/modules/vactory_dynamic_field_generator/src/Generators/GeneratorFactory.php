<?php

namespace Drupal\vactory_dynamic_field_generator\Generators;

/**
 * Generator factory class.
 */
class GeneratorFactory {

  /**
   * Generators.
   *
   * @var array
   */
  protected $generators;

  /**
   * The class construct.
   */
  public function __construct(array $generators) {
    $this->generators = $generators;
  }

  /**
   * Get generator by type.
   */
  public function getGenerator(string $type) {
    return $this->generators[$type] ?? NULL;
  }

}
