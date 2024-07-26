<?php

namespace Drupal\vactory_dynamic_field_generator\Generators;

use Drupal\vactory_dynamic_field_generator\VactoryGeneratorUtils;

/**
 * The text generator class.
 */
class TextGenerator implements GeneratorInterface {

  const HTML_CONTENT = "<h2>{{ [PREFIX][fieldName] }}</h2>";

  /**
   * The vactory generator utils.
   *
   * @var \Drupal\vactory_dynamic_field_generator\VactoryGeneratorUtils
   */
  protected $vactoryGeneratorUtils;

  /**
   * The class construct.
   */
  public function __construct(VactoryGeneratorUtils $vactoryGeneratorUtils) {
    $this->vactoryGeneratorUtils = $vactoryGeneratorUtils;
  }

  /**
   * {@inheritDoc}
   */
  public function generate($fieldName, $field, $isMultiple = FALSE, $isExtraField = FALSE) {
    $html = self::HTML_CONTENT;
    $prefix = '';
    switch (TRUE) {
      case $isExtraField:
        $prefix = self::PREFIX_EXTRA_FIELDS;
        break;

      case $isMultiple:
        $prefix = self::PREFIX_MULTIPLE;
        $html .= str_repeat(' ', 4);
        break;

      default:
        $prefix = self::PREFIX_SIMPLE;
        break;
    }

    $html = str_replace("[PREFIX][fieldName]", $prefix . $fieldName, $html) . PHP_EOL;
    $html = $this->vactoryGeneratorUtils->conditionWrapper($prefix . $fieldName, $html);
    $html = $this->vactoryGeneratorUtils->commentWrapper($fieldName, $html);
    return $html;
  }

}
