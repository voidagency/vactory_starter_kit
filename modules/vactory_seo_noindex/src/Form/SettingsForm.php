<?php

namespace Drupal\vactory_seo_noindex\Form;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Configure vactory_seo_noindex settings for this site.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'vactory_seo_noindex_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['vactory_seo_noindex.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $modele_file = drupal_get_path('module', 'vactory_seo_noindex') . '/example/noindex-model.xls';
    $modele_file_url = file_create_url($modele_file);
    $form['artifact'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Upload excel file'),
      '#default_value' => $this->config('vactory_seo_noindex.settings')->get('artifact'),
      '#upload_validators' => [
        'file_validate_extensions' => ['xls'],
      ],
      '#description' => "Charger un fichier excel (xls) contenant une liste des chemins des pages concernées, <strong><a href='". $modele_file_url ."' download>Télécharger le modèle excel</a></strong>"
    ];
    $form['artifact_json'] = [
      '#type' => 'textarea',
      '#default_value' => $this->config('vactory_seo_noindex.settings')->get('artifact_json'),
      '#access' => FALSE,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $fid = $form_state->getValue('artifact')[0];
    $paths = $this->getExcelData($fid);
    $this->config('vactory_seo_noindex.settings')
      ->set('artifact', $form_state->getValue('artifact'))
      ->set('artifact_json', Json::encode($paths))
      ->save();
    parent::submitForm($form, $form_state);
  }

  /**
   * Load no indexed paths from excel.
   */
  public function getExcelData($file_id) {
    $paths = [];
    $languages = \Drupal::languageManager()->getLanguages();
    $languages = array_keys($languages);
    $languages = array_map(function ($langcode) {
      return '/' . $langcode . '/';
    }, $languages);
    if (isset($file_id)) {
      $file = File::load($file_id);
      if (isset($file)) {
        $excel_data = IOFactory::load($file->getFileUri());
        $paths = $excel_data->getActiveSheet()->toArray();
        unset($paths[0]);
        $paths = array_map(function ($path) use ($languages) {
          if (strpos($path[0], '/') !== 0) {
            $path[0] = '/' . $path[0];
          }
          $path[0] = str_replace($languages, '/', $path[0]);
          return $path[0];
        }, $paths);
      }
    }
    return array_values($paths);
  }

}
