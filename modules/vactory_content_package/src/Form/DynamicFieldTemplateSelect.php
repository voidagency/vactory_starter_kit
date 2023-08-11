<?php

namespace Drupal\vactory_content_package\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\vactory_dynamic_field\Form\ModalForm;
use Drupal\vactory_dynamic_field\ModalEnum;

/**
 * Configure Vactory content package settings for this site.
 */
class DynamicFieldTemplateSelect extends ModalForm {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'vactory_content_package_df_generator';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = $this->buildWidgetSelectorForm($form, $form_state, FALSE);
    unset($form['templates_tabs']['auto_populate']);
    foreach ($form['templates_tabs'] as $key => &$element) {
      if (str_starts_with($key, '#')) {
        continue;
      }
      $element['template']['#ajax'] = [
        'callback' => [$this, 'updateFormCallback'],
        'event'    => 'change',
        'progress' => [
          'type' => 'throbber',
          'message' => 'Generating JSON format for selected template...',
        ],
      ];
    }

    $form['actions']['send'] = [
      '#type' => 'link',
      '#title' => $this->t('Update'),
      '#url' => Url::fromRoute('vactory_content_package.df_json_generator', [], ['query' => ['widget_data' => '']]),
      '#attributes' => [
        'class' => [
          'use-ajax',
          'df-console-modal-opener',
          'button',
        ],
        'data-dialog-options' => '',
      ],
    ];

    $form['#attached']['library'][] = 'vactory_content_package/scripts';
    return $form;
  }

  /**
   * {@inheritDoc}
   */
  public function updateFormCallback(array &$form, FormStateInterface $form_state) {
    if ($form_state->hasAnyErrors()) {
      return (new AjaxResponse())->addCommand(new ReplaceCommand('#' . ModalEnum::FORM_WIDGET_SELECTOR_AJAX_WRAPPER, $form));
    }
    $values = $form_state->getValues();
    $template = $values['template'] ?? '';
    $form['actions']['send']['#url'] = Url::fromRoute('vactory_content_package.df_json_generator', ['widget_id' => $template]);
    $element = $form['actions']['send'];
    $link = \Drupal::service('renderer')->renderPlain($element);
    $response = new AjaxResponse();
    $response->addCommand(new InvokeCommand("div > .df-console-modal-opener", 'replaceWith', [$link]));
    if (isset($form['#attached'])) {
      $response->setAttachments($form['#attached']);
    }

    return $response;
  }

  /**
   * {@inheritDoc}
   */
  public function selectWidget(array &$form, FormStateInterface $form_state) {
    if (!$form_state->hasAnyErrors()) {
      $selected = $form_state->getValue('template');
      $form_state->set('widget_id', $selected);
      $form_state->setRebuild();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

  }

}
