<?php

namespace Drupal\vactory_hreflang\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configures hreflang mappings for enabled Drupal languages.
 */
class VactoryHreflangSettingsForm extends ConfigFormBase {

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Constructs the settings form.
   *
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   */
  public function __construct(LanguageManagerInterface $language_manager) {
    $this->languageManager = $language_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('language_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'vactory_hreflang_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['vactory_hreflang.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $stored_mappings = $this->config('vactory_hreflang.settings')->get('mappings') ?? [];

    $form['description'] = [
      '#markup' => '<p>' . $this->t(
        'Assign the hreflang value emitted for each enabled Drupal language. The URL language prefix is not changed.'
      ) . '</p>',
    ];
    $form['mappings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Language mappings'),
      '#tree' => TRUE,
    ];

    foreach ($this->languageManager->getLanguages() as $langcode => $language) {
      $form['mappings'][$langcode] = [
        '#type' => 'textfield',
        '#title' => $this->t('@language (@langcode)', [
          '@language' => $language->getName(),
          '@langcode' => $langcode,
        ]),
        '#default_value' => $stored_mappings[$langcode] ?? $langcode,
        '#description' => $this->t(
          'For example: ar-MA, fr-MA, or en. Language subtags are normalized when saved.'
        ),
        '#required' => TRUE,
        '#maxlength' => 35,
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    foreach ($form_state->getValue('mappings', []) as $langcode => $hreflang) {
      $hreflang = trim($hreflang);
      if (!preg_match('/^[A-Za-z]{2,8}(?:-[A-Za-z0-9]{1,8})*$/D', $hreflang)) {
        $form_state->setErrorByName(
          'mappings][' . $langcode,
          $this->t('The hreflang value for %language is not a valid language tag.', [
            '%language' => $langcode,
          ])
        );
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $mappings = [];
    foreach ($form_state->getValue('mappings', []) as $langcode => $hreflang) {
      $mappings[$langcode] = $this->normalizeLanguageTag(trim($hreflang));
    }

    $this->config('vactory_hreflang.settings')
      ->set('mappings', $mappings)
      ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Normalizes the casing of a language tag's common subtags.
   *
   * @param string $tag
   *   The language tag.
   *
   * @return string
   *   The normalized language tag.
   */
  private function normalizeLanguageTag(string $tag): string {
    $subtags = explode('-', $tag);
    $subtags[0] = strtolower($subtags[0]);

    foreach (array_slice($subtags, 1, NULL, TRUE) as $index => $subtag) {
      if (strlen($subtag) === 4 && ctype_alpha($subtag)) {
        $subtags[$index] = ucfirst(strtolower($subtag));
      }
      elseif (
        (strlen($subtag) === 2 && ctype_alpha($subtag)) ||
        (strlen($subtag) === 3 && ctype_digit($subtag))
      ) {
        $subtags[$index] = strtoupper($subtag);
      }
      else {
        $subtags[$index] = strtolower($subtag);
      }
    }

    return implode('-', $subtags);
  }

}
