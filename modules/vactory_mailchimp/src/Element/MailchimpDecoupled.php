<?php

namespace Drupal\vactory_mailchimp\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\FormElement;

/**
 * Provide a Mailchimp form element for dynamic field.
 *
 * @FormElement("mailchimp_decoupled")
 */
class MailchimpDecoupled extends FormElement
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
    $element['#tree'] = TRUE;

    $element['id'] = [
      '#type' => 'select',
      '#required' => TRUE,
      '#title' => t('List ID'),
      '#options' => self::getWebforms(),
      '#default_value' => $element['#default_value']['id'] ?? '',
    ];

    return $element;
  }

  /**
   * Form element validate callback.
   */
  public static function validateElement(&$element, FormStateInterface $form_state, &$form)
  {

  }

  /**
   * The mailchimp list to use in options.
   *
   * @return array
   *   The mailchimp list.
   */
  protected static function getWebforms(): array
  {
    $forms_options = [];
    $mcapi = mailchimp_get_api_object('MailchimpLists');
    $result = $mcapi->getLists(['count' => 500]);

    foreach ($result->lists as $list) {
      $forms_options[$list->id] = $list->name;
    }

    return $forms_options;
  }

}
