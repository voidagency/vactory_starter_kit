<?php

namespace Drupal\vactory_core\Element;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\Element\FormElement;

/**
 * Provides a form element for Custom input of a URL.
 *
 * Properties:
 * - #default_value: A valid URL string.
 * - #size: The size of the input element in characters.
 *
 * Usage example:
 *
 * @code
 * $form['homepage'] = array(
 *   '#type' => 'url_extended',
 *   '#title' => $this->t('Home Page'),
 *   '#size' => 30,
 * );
 * @endcode
 *
 * @see \Drupal\Core\Render\Element\Textfield
 *
 * @FormElement("url_extended")
 */
class VactoryExtendedUrl extends FormElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#input'                   => TRUE,
      '#size'                    => 60,
      '#maxlength'               => 255,
      '#autocomplete_route_name' => FALSE,
      '#process'                 => [
        [$class, 'processAutocomplete'],
        [$class, 'processAjaxForm'],
        [$class, 'processPattern'],
      ],
      '#element_validate'        => [
        [$class, 'validateCustomUrl'],
      ],
      '#pre_render'              => [
        [$class, 'preRenderCustomUrl'],
      ],
      '#theme'                   => 'input__textfield',
      '#theme_wrappers'          => ['form_element'],
    ];
  }

  /**
   * Form element validation handler for #type 'custom_url'.
   *
   * Note that #maxlength and #required is validated by _form_validate()
   * already.
   */
  public static function validateCustomUrl(&$element, FormStateInterface $form_state, &$complete_form) {
    $value = trim($element['#value']);
    $form_state->setValueForElement($element, $value);

    if ($value !== '' && UrlHelper::isExternal($value) && !UrlHelper::isValid($value, TRUE)) {
      $form_state->setError($element, t('The External URL %url is not valid.', ['%url' => $value]));
    }

  }

  /**
   * {@inheritdoc}
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    if ($input !== FALSE && $input !== NULL) {
      return $input;
    }

    return NULL;
  }

  /**
   * Prepares a #type 'url' render element for input.html.twig.
   *
   * @param array $element
   *   An associative array containing the properties of the element.
   *   Properties used: #title, #value, #description, #size, #maxlength,
   *   #placeholder, #required, #attributes.
   *
   * @return array
   *   The $element with prepared variables ready for input.html.twig.
   */
  public static function preRenderCustomUrl(array $element) {
    $element['#attributes']['type'] = 'text';
    $element['#attributes']['placeholder'] = t('External Url example http://example.com Or Internal Url /node/nid');
    Element::setAttributes($element, [
      'id',
      'name',
      'value',
      'size',
      'maxlength',
      'placeholder',
    ]);
    static::setAttributes($element, ['form-url']);

    return $element;
  }

}
