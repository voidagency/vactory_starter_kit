<?php

namespace Drupal\vactory_icon\Form;

use Drupal\Core\Archiver\ArchiverException;
use Drupal\Core\Archiver\Zip;
use \Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\file\Entity\File;

class VactotyIconSettingsForm extends ConfigFormBase
{

  /**
   * Gets the configuration names that will be editable.
   *
   * @return array
   *   An array of configuration object names that are editable if called in
   *   conjunction with the trait's config() method.
   */
  protected function getEditableConfigNames()
  {
    return [
      'vactory_icon.settings'
    ];
  }

  /**
   * Returns a unique string identifying the form.
   *
   * The returned ID should be a unique string that can be a valid PHP function
   * name, since it's used in hook implementation names such as
   * hook_form_FORM_ID_alter().
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId()
  {
    return 'vactory_icon_admin_settings';
  }

  public function buildForm(array $form, FormStateInterface $form_state)
  {

    $url_options = array('absolute' => TRUE);
    $t_args = [
      ':settings_url' => Url::fromUri('base:/admin/structure/file-types/manage/document/edit', $url_options)->toString(),
    ];
    $message = t('If your having trouble uploading the zip file. Add <strong><em>application/zip</em></strong> <a href=":settings_url"> to the allowed <em>MIME types</em></a>.', $t_args);
    \Drupal::messenger()->addWarning($message);

    // Check file.
    $fid = $this->config('vactory_icon.settings')->get('fid');
    $fids = [];
    if ($fid && $fid > 0 &&  ($file = File::load($fid))) {
      $fids[0] = $file->id();
    }

    $validators = array(
      'file_validate_extensions' => array('zip'),
    );

    $form['selection'] = array(
      '#type' => 'managed_file',
      '#name' => 'selection',
      '#title' => t('Icons'),
      '#description' => t('Upload icomoon zip file'),
      '#upload_validators' => $validators,
      '#upload_location' => 'public://vactory_icon',
      '#required' => TRUE,
      '#default_value' => $fids,
    );

    return parent::buildForm($form, $form_state);
  }

  public function submitForm(array &$form, FormStateInterface $form_state)
  {
    //============ Get Paths ============
    $fid = reset($form_state->getValue('selection'));
    if (!$fid) {
      $this->config('vactory_icon.settings')
        ->set('fid', 0)
        ->save();
      return;
    }

    $this->config('vactory_icon.settings')
      ->set('fid', $fid)
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

      //============ Delete old files before unzip ============
      \Drupal::service('file_system')->deleteRecursive('public://vactory_icon/style.css');
      \Drupal::service('file_system')->deleteRecursive('public://vactory_icon/selection.json');
      \Drupal::service('file_system')->deleteRecursive('public://vactory_icon/fonts');

      //============ Extract files ============
      $zip = new Zip($absolute_path);
      $zip->extract($absolute_path_parent);
      $zip->getArchive()->close();

      //============ Delete useless files and dirs ============
      \Drupal::service('file_system')->deleteRecursive('public://vactory_icon/demo.html');
      \Drupal::service('file_system')->deleteRecursive('public://vactory_icon/Read Me.txt');
      \Drupal::service('file_system')->deleteRecursive('public://vactory_icon/demo-files');

      //============ Delete zip itself ============
      \Drupal::messenger()->addMessage('file uploaded');
    } catch (ArchiverException $exception) {
      \Drupal::messenger()->addMessage('cannot extract files from uploaded file', 'error');
    }


  }

}
