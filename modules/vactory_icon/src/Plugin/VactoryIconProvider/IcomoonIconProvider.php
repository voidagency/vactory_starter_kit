<?php

namespace Drupal\vactory_icon\Plugin\VactoryIconProvider;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Archiver\ArchiverException;
use Drupal\Core\Archiver\Zip;
use Drupal\Core\Config\Config;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;
use Drupal\vactory_icon\Annotation\VactoryIconProvider;
use Drupal\vactory_icon\VactoryIconProviderBase;

/**
 * Iconmoon icon provider plugin definition.
 *
 * @VactoryIconProvider(
 *   id="icomoon_icon_provider",
 *   description=@Translation("Icomoon")
 * )
 */
class IcomoonIconProvider extends VactoryIconProviderBase {

  /**
   * {@inheritDoc}
   */
  public function settingsForm(ImmutableConfig|Config $config) {
    $form = [];
    // Check file.
    $fid = $config->get('icomoon_fid');
    $fids = [];
    if ($fid && $fid > 0 &&  ($file = File::load($fid))) {
      $fids[0] = $file->id();
    }
    $validators = [
      'file_validate_extensions' => ['zip'],
    ];
    $form['selection'] = [
      '#type' => 'managed_file',
      '#name' => 'selection',
      '#title' => t('Icons'),
      '#description' => t('Upload icomoon zip file'),
      '#upload_validators' => $validators,
      '#upload_location' => 'public://vactory_icon',
      '#required' => TRUE,
      '#default_value' => $fids,
    ];

    return $form;
  }

  /**
   * {@inheritDoc}
   */
  public function settingsFormSubmit(FormStateInterface $form_state, ImmutableConfig|Config $config) {
    // Get paths.
    $values = $form_state->getValues();
    $fid = reset($values['selection']);
    if (!$fid) {
      $config->set('fid', 0)
        ->save();
      return;
    }

    $config->set('icomoon_fid', $fid)
      ->save();
    $file = File::load($fid);
    $uri = $file->getFileUri();
    // Make sure that a used file is permanent.
    if (!$file->isPermanent()) {
      $file->setPermanent();
      $file->save();
    }
    $absolute_path = \Drupal::service('file_system')->realpath($uri);
    $absolute_path_parent = \Drupal::service('file_system')->realpath('public://vactory_icon');

    try {

      // Delete old files before unzip.
      \Drupal::service('file_system')->deleteRecursive('public://vactory_icon/style.css');
      \Drupal::service('file_system')->deleteRecursive('public://vactory_icon/selection.json');
      \Drupal::service('file_system')->deleteRecursive('public://vactory_icon/fonts');

      // Extract files.
      $zip = new Zip($absolute_path);
      $zip->extract($absolute_path_parent);
      $zip->getArchive()->close();

      // Delete useless files and dirs.
      \Drupal::service('file_system')->deleteRecursive('public://vactory_icon/demo.html');
      \Drupal::service('file_system')->deleteRecursive('public://vactory_icon/Read Me.txt');
      \Drupal::service('file_system')->deleteRecursive('public://vactory_icon/demo-files');

      \Drupal::messenger()->addMessage('file uploaded');
    }
    catch (ArchiverException $exception) {
      \Drupal::messenger()->addMessage('cannot extract files from uploaded file', 'error');
    }
  }

  /**
   * {@inheritDoc}
   */
  public function iconPickerLibraryInfoAlter(array &$library_info) {
    $stylesheet = 'public://vactory_icon/style.css';
    $library_info['css']['theme'][$stylesheet] = [];
  }

  /**
   * {@inheritDoc}
   */
  public function iconPickerFormElementAlter(array &$element, ImmutableConfig|Config $config) {
    $element['#default_value'] = !empty($element['#default_value']) ? 'icon-' . $element['#default_value'] : $element['#default_value'];
    $decoded_content = $this->fetchIcons($config);
    foreach ($decoded_content['icons'] as $icon) {
      $icon_name = $icon['properties']['name'];
      $element['#options']['icon-' . $icon_name] = $icon_name;
    }
  }

  /**
   * {@inheritDoc}
   */
  public function fetchIcons(ImmutableConfig|Config $config) {
    $json_file = \Drupal::service('file_system')->realpath("public://vactory_icon/selection.json");
    $file_content = file_get_contents($json_file);
    return Json::decode($file_content);
  }

}
