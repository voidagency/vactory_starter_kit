<?php

namespace Drupal\vactory_decoupled_webform\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\FormElement;
use Drupal\webform\Entity\Webform;

/**
 * Provide a Webform form element for dynamic field.
 *
 * @FormElement("webform_decoupled")
 */
class WebformDecoupled extends FormElement
{

  /**
   * {@inheritDoc}
   */
  public function getInfo()
  {
    $class = get_class($this);

    return [
      '#input' => TRUE,
      '#process' => [
        [$class, 'processElement'],
      ],
      '#element_validate' => [
        [$class, 'validateElement'],
      ],
      '#theme_wrappers' => ['form_element'],
    ];
  }

  /**
   * Element process callback.
   */
  public static function processElement(array &$element, FormStateInterface $form_state, array &$complete_form)
  {
    $has_access = \Drupal::currentUser()
      ->hasPermission('administer webform_decoupled props');
    $element['#tree'] = TRUE;

    $element['id'] = [
      '#type' => 'select',
      '#required' => TRUE,
      '#title' => t('Webform'),
      '#options' => self::getWebforms(),
      '#default_value' => $element['#default_value']['webform_id'] ?? '',
    ];

    $element['style'] = [
      '#type' => 'textarea',
      '#title' => t('Style'),
      '#description' => t('A style Object used for theming.'),
      '#default_value' => $element['#default_value']['style'] ?? '',
      '#attributes' => array('dir' => 'ltr'),
      '#access' => $has_access,
    ];

    $element['buttons'] = [
      '#type' => 'textarea',
      '#title' => t('Buttons'),
      '#description' => t('A style Object used for theming.'),
      '#default_value' => $element['#default_value']['buttons'] ?? '',
      '#attributes' => array('dir' => 'ltr'),
      '#access' => $has_access,
    ];

    return $element;
  }

  /**
   * Form element validate callback.
   */
  public static function validateElement(&$element, FormStateInterface $form_state, &$form)
  {
    $webform_id = $element['id']['#value'];
    $style = $element['style']['#value'];
    $webform = Webform::load($webform_id);
    if (!$webform) {
      $form_state->setError($element['id'], t("Webform ID @webform_id is not valid.", ['@webform_id' => $webform_id]));
    }

    if (!empty($style)) {
      @json_decode($style);
      if (json_last_error() !== JSON_ERROR_NONE) {
        $form_state->setError($element['style'], t("Style field is not a valid JSON object."));
      }
    }

  }

  /**
   * The webforms list to use in options.
   *
   * @return array
   *   The webforms list.
   */
  protected static function getWebforms(): array
  {
    $forms_options = [];
    $styles = \Drupal::entityTypeManager()->getStorage('webform')->loadMultiple();

    foreach ($styles as $webform) {
      $forms_options[$webform->id()] = $webform->label();
    }

    return $forms_options;
  }

}
