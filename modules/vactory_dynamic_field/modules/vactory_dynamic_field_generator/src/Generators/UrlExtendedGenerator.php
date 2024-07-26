<?php

namespace Drupal\vactory_dynamic_field_generator\Generators;

use Drupal\vactory_dynamic_field_generator\VactoryGeneratorUtils;

/**
 * The url extended generator class.
 */
class UrlExtendedGenerator implements GeneratorInterface {

  /**
   * Link attributes html content.
   */
  const LINK_ATTRIBUTES = "{% set moreLink_attributes = create_attribute() %}";

  /**
   * Link attributes id.
   */
  const LINK_ATTRIBUTES_ID = "{% set moreLink_attributes = [fieldGroup].[fieldName].attributes.id is not empty ? moreLink_attributes.setAttribute('id', [fieldGroup].[fieldName].attributes.id ) : moreLink_attributes %}";

  /**
   * Link attributes target.
   */
  const LINK_ATTRIBUTES_TARGET = "{% set moreLink_attributes = [fieldGroup].[fieldName].attributes.target is not empty ? moreLink_attributes.setAttribute('target', [fieldGroup].[fieldName].attributes.target ) : moreLink_attributes %}";

  /**
   * Link attributes class.
   */
  const LINK_ATTRIBUTES_CLASS = "{% set moreLink_attributes = [fieldGroup].[fieldName].attributes.target is not empty ? moreLink_attributes.setAttribute('class', [fieldGroup].[fieldName].attributes.class ~ 'btn btn-primary')  : moreLink_attributes.setAttribute('class','btn btn-primary') %}";

  /**
   * Link attributes rel.
   */
  const LINK_ATTRIBUTES_REL = "{% set moreLink_attributes = [fieldGroup].[fieldName].attributes.rel is not empty ? moreLink_attributes.setAttribute('rel', [fieldGroup].[fieldName].attributes.rel ) : moreLink_attributes %}";

  /**
   * Link url extended html content.
   */
  const HTML_URL_EXTENDED = "<div class='text-center mt-4'>
    <a href='{{  [fieldGroup].[fieldName].url }}' {{ moreLink_attributes }}>{{ [fieldGroup].[fieldName].title }}</a>
    </div>";

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
        $html = $this->prepareUrlExtended($fieldName, "extra_fields.");
        $prefix = self::PREFIX_EXTRA_FIELDS;
        break;

      case $isMultiple:
        $html = $this->prepareUrlExtended($fieldName, "item.", $isMultiple);
        $prefix = self::PREFIX_MULTIPLE;
        break;

      default:
        $html = $this->prepareUrlExtended($fieldName, "content.0.");
        $prefix = self::PREFIX_SIMPLE;
        break;
    }
    $html = $this->vactoryGeneratorUtils->conditionWrapper($prefix . $fieldName . '.url', $html);
    $html = $this->vactoryGeneratorUtils->commentWrapper($fieldName, $html);
    return $html;
  }

  /**
   * Prepare url extended html content.
   */
  protected function prepareUrlExtended($fieldName, $replace, $isMultiple = FALSE) {
    $html = "";
    $whitespaces = $isMultiple ? str_repeat(' ', 4) : '';
    $html .= $whitespaces . self::LINK_ATTRIBUTES;
    $html .= $whitespaces . str_replace("[fieldGroup].[fieldName]", $replace . $fieldName, self::LINK_ATTRIBUTES_ID) . PHP_EOL;
    $html .= $whitespaces . str_replace("[fieldGroup].[fieldName]", $replace . $fieldName, self::LINK_ATTRIBUTES_TARGET) . PHP_EOL;
    $html .= $whitespaces . str_replace("[fieldGroup].[fieldName]", $replace . $fieldName, self::LINK_ATTRIBUTES_CLASS) . PHP_EOL;
    $html .= $whitespaces . str_replace("[fieldGroup].[fieldName]", $replace . $fieldName, self::LINK_ATTRIBUTES_REL) . PHP_EOL;
    $html .= $whitespaces . str_replace("[fieldGroup].[fieldName]", $replace . $fieldName, self::HTML_URL_EXTENDED) . PHP_EOL;
    return $html;
  }

}
