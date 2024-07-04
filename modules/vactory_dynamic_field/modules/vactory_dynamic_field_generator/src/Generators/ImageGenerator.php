<?php

namespace Drupal\vactory_dynamic_field_generator\Generators;

use Drupal\vactory_dynamic_field_generator\VactoryGeneratorUtils;

/**
 * The image generator class.
 */
class ImageGenerator implements GeneratorInterface {

  /**
   * Image uri html content.
   */
  const IMAGE_URI = "{% set image_uri = ([fieldGroup].[fieldName].0 is defined) ? get_image([fieldGroup].[fieldName].0) : '' %}";

  /**
   * Ligip image html content.
   */
  const LIQIP_IMAGE = "{% set lqip_image = image_uri|image_style('lqip') %}";

  /**
   * Fluid image html content.
   */
  const FLUID_IMAGE = "{% set fluid_image = file_url(image_uri) %}";

  /**
   * Html image content.
   */
  const HTML_IMAGE = "<img
        class='img-fluid lazyload'
        src='{{ lqip_image }}'
        data-src='{{ fluid_image }}'
    >";

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
    $html = "";
    $prefix = '';
    switch (TRUE) {
      case $isExtraField:
        $html = $this->prepareImage($fieldName, self::PREFIX_EXTRA_FIELDS);
        $prefix = self::PREFIX_EXTRA_FIELDS;
        break;

      case $isMultiple:
        $html = $this->prepareImage($fieldName, self::PREFIX_MULTIPLE, $isMultiple);
        $prefix = self::PREFIX_MULTIPLE;
        break;

      default:
        $html = $this->prepareImage($fieldName, self::PREFIX_SIMPLE, $isMultiple);
        $prefix = self::PREFIX_SIMPLE;
        break;
    }

    $html = $this->vactoryGeneratorUtils->conditionWrapper($prefix . $fieldName . ".0", $html);
    $html = $this->vactoryGeneratorUtils->commentWrapper($fieldName, $html);
    return $html;
  }

  /**
   * Prepare image html.
   */
  protected function prepareImage($fieldName, $replace, $isMultiple = FALSE) {
    $html = "";
    $whitespaces = $isMultiple ? str_repeat(' ', 4) : '';
    $html .= $whitespaces . str_replace("[fieldGroup].[fieldName]", $replace . $fieldName, self::IMAGE_URI) . PHP_EOL;
    $html .= $whitespaces . self::LIQIP_IMAGE . PHP_EOL;
    $html .= $whitespaces . self::FLUID_IMAGE . PHP_EOL;
    $html .= $whitespaces . self::HTML_IMAGE . PHP_EOL;
    return $html;
  }

}
