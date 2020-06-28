<?php

namespace Drupal\vactory_dynamic_field\Element;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\FormElement;
use Drupal\node\Entity\Node;

/**
 * Provide an URL form element with link attributes.
 *
 * @FormElement("url_extended")
 */
class UrlExtendedElement extends FormElement {

  /**
   * Returns the element properties for this element.
   *
   * @return array
   *   An array of element properties. See
   *   \Drupal\Core\Render\ElementInfoManagerInterface::getInfo() for
   *   documentation of the standard properties of all elements, and the
   *   return value format.
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#input' => TRUE,
      '#process' => [
        [$class, 'processUrlExtended'],
      ],
      '#element_validate' => [
        [$class, 'validateUrlExtended'],
      ],
      '#theme_wrappers' => ['fieldset'],
      '#multiple' => FALSE,
    ];
  }

  /**
   * Url extended form element process callback.
   */
  public static function processUrlExtended(&$element, FormStateInterface $form_state, &$form) {
    $default_values = $element['#default_value'];
    $element['title'] = [
      '#type' => 'textfield',
      '#title' => t('Link title'),
      '#required' => $element['#required'],
      '#default_value' => isset($default_values['title']) ? $default_values['title'] : '',
    ];
    $element['url'] = [
      '#type' => 'textfield',
      '#title' => t('Link URL'),
      '#required' => $element['#required'],
      '#default_value' => isset($default_values['url']) ? $default_values['url'] : '',
      '#description' => t('An external URL or internal path, <br> Examples for an internal path: <strong>/node/1</strong> or <strong>/path-example-alias</strong><br>Examples for an external path: <strong>https://example.com</strong>'),
    ];
    $element['attributes'] = [
      '#type' => 'details',
      '#title' => t('Link attributes'),
    ];
    $element['attributes']['class'] = [
      '#type' => 'textfield',
      '#title' => t('Link classes'),
      '#description' => t('Link classes separated with spaces between.'),
      '#default_value' => isset($default_values['attributes']['class']) ? $default_values['attributes']['class'] : '',
    ];
    $element['attributes']['id'] = [
      '#type' => 'textfield',
      '#title' => t('Link ID'),
      '#description' => t('Enter a valid CSS ID for the link.'),
      '#default_value' => isset($default_values['attributes']['id']) ? $default_values['attributes']['id'] : '',
    ];
    $element['attributes']['target'] = [
      '#type' => 'select',
      '#title' => t('Link Target'),
      '#options' => [
        '_self' => 'Load in the same frame as it was clicked (_self)',
        '_blank' => 'Load in a new window (_blank)',
        '_parent' => 'Load in the parent frameset (_parent)',
        '_top' => 'Load in the full body of the window (_top)',
        'framename' => 'Load in a named frame (framename)',
      ],
      '#default_value' => isset($default_values['attributes']['target']) ? $default_values['attributes']['target'] : '',
    ];
    $element['attributes']['rel'] = [
      '#type' => 'textfield',
      '#title' => t('Link rel'),
      '#default_value' => isset($default_values['attributes']['rel']) ? $default_values['attributes']['rel'] : '',
    ];

    return $element;
  }

  /**
   * URL extended form element validate callback.
   */
  public static function validateUrlExtended(&$element, FormStateInterface $form_state, &$form) {
    if (isset($element['url']['#value']) && !empty($element['url']['#value'])) {
      $url = $element['url']['#value'];
      if (strpos($url, '/node/') === 0) {
        $url_params = explode('/', $url);
        $nid = end($url_params);
        if (!empty($nid) && is_numeric($nid)) {
          $node = Node::load($nid);
          if (!isset($node)) {
            $form_state->setError($element['url'], t("No node has been founded for @nid node ID.", ['@nid' => $nid]));
          }
        }
      }
      elseif (!empty($url) && !UrlHelper::isExternal($url)) {
        if ((strpos($url, '/') !== 0) && (strpos($url, '#') !== 0) && (strpos($url, '?') !== 0)) {
          $form_state->setError($element['url'], t("The user-entered string '@url' must begin with a '/', '?', or '#'.", ['@url' => $url]));
        }
      }

    }
  }

}
