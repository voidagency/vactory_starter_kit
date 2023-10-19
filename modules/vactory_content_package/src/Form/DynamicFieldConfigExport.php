<?php

namespace Drupal\vactory_content_package\Form;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\vactory_dynamic_field\Form\ModalForm;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

/**
 * Exports DFs config.
 */
class DynamicFieldConfigExport extends ModalForm {

  const FILE_DESTINATION_URI = 'temporary://vactory-content-package-df-config';

  /**
   * Widgets Manager.
   *
   * @var \Drupal\vactory_dynamic_field\WidgetsManager
   */
  protected $dynamicFieldManager;

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->dynamicFieldManager = $container->get('vactory_dynamic_field.vactory_provider_manager');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'vactory_content_package_df_config_export';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Start export procress'),
      '#button_type' => 'primary',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Prepare data.
    $widgetsList = $this->dynamicFieldManager->getModalWidgetsList();
    $normalized = $this->excludeCategories($widgetsList);

    // Construct filename.
    $filename = $this->constructFilename();

    // Construct output path.
    $path = $this->constructOutputPath($filename);
    if (!$path) {
      $this->messenger()->addError($this->t('Failed to create the JSON file.<br>Please try again later.'));
    }

    // Create json file.
    $printed = $this->createJsonFileFromArray($normalized, $path);

    if (!$printed) {
      $this->messenger()->addError($this->t('Failed to create the JSON file.<br>Please try again later.'));
      return;
    }

    // Download file.
    $response = new BinaryFileResponse($path, 200, [], FALSE);
    $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $filename);
    $response->deleteFileAfterSend(TRUE);
    $response->send();

  }

  /**
   * Construct Filename using timestamp.
   */
  private function constructFilename(): string {
    $time = time();
    return "df_config_{$time}.json";
  }

  /**
   * Construct output file.
   */
  private function constructOutputPath($filename): mixed {
    if (!file_exists(self::FILE_DESTINATION_URI)) {
      mkdir(self::FILE_DESTINATION_URI, 0777, TRUE);
    }
    $file_path = self::FILE_DESTINATION_URI . '/' . $filename;
    return \Drupal::service('file_system')->realpath($file_path);
  }

  /**
   * Exclude categories from widgets list.
   */
  private function excludeCategories($widgetsList): array {
    $result = [];

    foreach ($widgetsList as $key => $subitems) {
      foreach ($subitems as $subkey => $value) {
        $result[$subkey] = $value;
      }
    }

    return $result;
  }

  /**
   * Transform an array to json file.
   */
  private function createJsonFileFromArray($array, $filename): bool {
    $jsonString = json_encode($array, JSON_PRETTY_PRINT);

    if ($jsonString === FALSE) {
      return FALSE;
    }

    if (file_put_contents($filename, $jsonString) !== FALSE) {
      return TRUE;
    }
    else {
      return FALSE;
    }

  }

}
