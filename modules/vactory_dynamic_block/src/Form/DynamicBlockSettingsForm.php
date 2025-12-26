<?php

declare(strict_types=1);

namespace Drupal\vactory_dynamic_block\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configuration form for a dynamic block entity type.
 */
final class DynamicBlockSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'vactory_dynamic_block_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['vactory_dynamic_block.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config('vactory_dynamic_block.settings');

    $form['tailwind'] = [
      '#type' => 'details',
      '#title' => $this->t('Tailwind CSS Configuration'),
      '#open' => TRUE,
    ];

    $form['tailwind']['custom_theme_css'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Custom Theme CSS'),
      '#description' => $this->t('Add custom Tailwind theme CSS. This will be injected into the Tailwind CSS compiler. Use Tailwind V4 syntax.'),
      '#default_value' => $config->get('custom_theme_css') ?? '',
      '#rows' => 20,
      '#attributes' => [
        'style' => 'font-family: monospace; font-size: 13px;',
        'spellcheck' => 'false',
      ],
      '#placeholder' => '@theme {
  /* Custom Colors */
  --color-primary: #D3232A;
  
  /* Animations */
  --animate-fade-in: fade-in 0.3s ease-out;
}

@keyframes fade-in {
  0% { opacity: 0; }
  100% { opacity: 1; }
}',
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->config('vactory_dynamic_block.settings')
      ->set('custom_theme_css', $form_state->getValue('custom_theme_css'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
