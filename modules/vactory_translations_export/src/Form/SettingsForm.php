<?php

namespace Drupal\vactory_translations_export\Form;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

/**
 * Configure Vactory translation export/import settings for this site.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * Interface translation manager service.
   *
   * @var \Drupal\vactory_translations_export\Services\TranslationExportManager
   */
  protected $interfaceTranslationManager;

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->interfaceTranslationManager = $container->get('vactory_translations_export.manager');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'vactory_translations_export_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['vactory_translations_export.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->configFactory->get('vactory_translations_export.settings');
    $form = parent::buildForm($form, $form_state);

    $form['delimiter'] = [
      '#type' => 'select',
      '#title' => $this->t('CSV Delimiter'),
      '#options' => [
        ',' => $this->t('Comma (,)'),
        ';' => $this->t('Semicolon (;)'),
      ],
      '#default_value' => $config->get('delimiter') ?? ',',
    ];

    $form['contexts'] = [
      '#type' => 'details',
      '#title' => $this->t('Concerned Contexts'),
    ];

    $form['contexts']['context'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Concerned contexts'),
      '#options' => $this->interfaceTranslationManager->getTranslationContexts(),
      '#default_value' => $config->get('context') ?? ['_FRONTEND'],
      '#description' => $this->t('When no context has been selected then all contexts are concerned'),
    ];

    $form['export'] = [
      '#type' => 'submit',
      '#submit' => [[$this, 'exportTranslations']],
      '#value' => $this->t('Export'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $context = array_filter($form_state->getValue('context'), fn($el) => $el !== 0);
    $this->config('vactory_translations_export.settings')
      ->set('delimiter', $form_state->getValue('delimiter'))
      ->set('context', $context)
      ->save();
    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function exportTranslations(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $contexts = array_filter($form_state->getValue('context'), fn($el) => $el !== 0);
    $header = [
      'source',
    ];

    $languages = \Drupal::languageManager()->getLanguages();

    foreach ($languages as $langcode => $language) {
      $langcode = strtoupper($langcode);
      $header[] = "{$langcode} translation";
    }

    $data = $this->interfaceTranslationManager->getTranslationsData($contexts);
    $path = $this->generateCsv($header, $data, $values['delimiter']);

    $response = new BinaryFileResponse(\Drupal::service('file_system')
      ->realPath($path), 200, [], FALSE);
    $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, "interface-translations.csv");
    $response->deleteFileAfterSend(TRUE);
    $form_state->setResponse($response);
  }

  /**
   * Transform array to csv file.
   */
  private function generateCsv($header, $data, $delimiter) {
    $time = time();

    $destination = 'public://interface-translations-exports';
    if (!file_exists($destination)) {
      mkdir($destination, 0777);
    }
    $path = "{$destination}/interface-translations-export-{$time}.csv";
    $fp = fopen($path, 'w');
    fputcsv($fp, $header, $delimiter);
    // Loop through file pointer and a line.
    foreach ($data as $item) {
      fputcsv($fp, $item, $delimiter);
    }

    fclose($fp);
    return $path;
  }

}
