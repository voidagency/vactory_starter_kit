<?php

namespace Drupal\vactory_decoupled_theme_switcher\Form;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\FormStateInterface;
use Drupal\theme_switcher\Form\ThemeSwitcherRuleForm as ThemeSwitcherRuleFormBase;

/**
 * Override Form handler for the ThemeSwitcherRule add and edit forms.
 */
class ThemeSwitcherRuleForm extends ThemeSwitcherRuleFormBase {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    /** @var \Drupal\theme_switcher\Entity\ThemeSwitcherRule $entity */
    $entity = $this->entity;

    unset(
      $form['visibility']['node_type'],
      $form['visibility']['webform'],
      $form['visibility']['entity_bundle:webform_submission'],
      $form['visibility']['entity_bundle:taxonomy_term']
    );

    $visibility = $form['visibility'];
    unset($form['visibility']);
    $form['description']['#markup'] = theme_switcher_conditions_description();
    $form['visibility'] = $visibility;

    $form['admin_theme'] = [
      '#type' => 'hidden',
      '#default_value' => $entity->getAdminTheme() ?? '',
    ];

    $form['theme'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Theme'),
      '#maxlength' => 255,
      '#default_value' => $entity->getTheme() ?? '',
      '#description' => $this->t('The theme to apply in all pages that meet the conditions below.'),
      '#required' => TRUE,
    ];
    return $form;
  }

  /**
   * {@inheritDoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    Cache::invalidateTags(['theme_switcher']);
  }

}
