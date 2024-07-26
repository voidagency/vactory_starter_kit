<?php

namespace Drupal\vactory_dynamic_field_generator\Generators;

/**
 * Generator interface.
 */
interface GeneratorInterface {

  const PREFIX_SIMPLE = "content.0.";

  const PREFIX_MULTIPLE = "item.";

  const PREFIX_EXTRA_FIELDS = "extra_fields.";

  /**
   * Generate function.
   */
  public function generate($fieldName, $field, $isMultiple = FALSE, $isExtraField = FALSE);

}
