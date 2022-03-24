<?php

namespace Drupal\vactory_wysiwyg_301to200\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

class ClearLogForm extends FormBase {

  public function getFormId() {
    return 'vactory_wysiwyg_301to200_clear_log';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['message'] = [
      '#markup' => '<strong>Do you really want to clear Wysiwyg 301 redirects log?</strong>'
    ];
    $form['clear'] = [
      '#type' => 'submit',
      '#value' => $this->t('Clear log'),
      '#attributes' => [
        'class' => [
          'button--primary',
        ],
      ],
    ];

    $form['cancel'] = [
      '#type' => 'link',
      '#title' => $this->t('Cancel'),
      '#url' => Url::fromRoute('view.wysiwyg_301to200.admin_listing'),
      '#attributes' => [
        'class' => [
          'button',
        ],
      ],
    ];

    return $form;

  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    \Drupal::service('vactory_wysiwyg_301to200.logger')->clearLog();
    $this->redirect('view.wysiwyg_301to200.admin_listing')->send();
  }
}