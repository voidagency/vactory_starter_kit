<?php

namespace Drupal\vactory_dynamic_field\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;

/**
 * Pending content filter form.
 */
class PendingContentFilterForm extends FormBase {

  /**
   * {@inheritDoc}
   */
  public function getFormId() {
    return 'pending_content_filter';
  }

  /**
   * {@inheritDoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $params = \Drupal::request()->query->all();
    $form['entity_type'] = [
      '#type' => 'select',
      '#options' => [
        'node' => $this->t('Page'),
        'block_content' => $this->t('Block'),
      ],
      '#empty_option' => $this->t("- Type d'entité -"),
      '#prefix' => '<div class="views-exposed-form__item">',
      '#suffix' => '</div>',
      '#default_value' => $params['entity_type'] ?? NULL,
    ];
    $form['page'] = [
      '#type' => 'entity_autocomplete',
      '#target_type' => 'node',
      '#default_value' => isset($params['nid']) && !empty($params['nid']) ? Node::load($params['nid']) : NULL,
      '#selection_handler' => 'default',
      '#selection_settings' => [
        'target_bundles' => 'vactory_page',
      ],
      '#attributes' => [
        'placeholder' => $this->t('Page title...'),
      ],
      '#multiple' => FALSE,
      '#tag' => FALSE,
      '#prefix' => '<div class="views-exposed-form__item">',
      '#suffix' => '</div>',
      '#states' => [
        'visible' => [
          'select[name="entity_type"]' => ['value' => 'node'],
        ],
      ],
    ];
    $languages = \Drupal::languageManager()->getLanguages();
    $languages = array_map(fn($language) => $language->getName(), $languages);
    $form['language'] = [
      '#type' => 'select',
      '#options' => $languages,
      '#empty_option' => $this->t("- Langue -"),
      '#prefix' => '<div class="views-exposed-form__item">',
      '#suffix' => '</div>',
      '#default_value' => $params['language'] ?? NULL,
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t("Appliquer"),
      '#prefix' => '<div class="views-exposed-form__item views-exposed-form__item--actions">',
      '#suffix' => '</div>',
      '#name' => 'apply',
    ];
    $form['reset'] = [
      '#type' => 'submit',
      '#value' => $this->t("Réinitialiser"),
      '#prefix' => '<div class="views-exposed-form__item views-exposed-form__item--actions">',
      '#suffix' => '</div>',
      '#name' => 'reset',
    ];
    $form['#attributes']['class'][] = 'views-exposed-form form-inline';
    $form['#attached']['library'][] = 'views/views.module';

    return $form;
  }

  /**
   * {@inheritDoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    if ($triggering_element['#name'] === 'apply') {
      $entity_type = $form_state->getValue('entity_type');
      $language = $form_state->getValue('language');
      $page_nid = $form_state->getValue('page');
      $params = [
        'entity_type' => $entity_type,
        'language' => $language,
        'nid' => $page_nid,
      ];
      $form_state->setRedirect('df_pending_content.dashboard', $params);
    }
    if ($triggering_element['#name'] === 'reset') {
      $form_state->setRedirect('df_pending_content.dashboard');
    }
  }

}
