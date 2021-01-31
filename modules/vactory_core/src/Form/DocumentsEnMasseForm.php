<?php

namespace Drupal\vactory_core\Form;

use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\Messenger;
use Drupal\media\Entity\Media;

/**
 * Provide form to upload multiple documents.
 */
class DocumentsEnMasseForm extends FormBase {

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
  public function getFormId() {
    return 'upload_documents_en_masse';
  }

  /**
   * {@inheritDoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['documents'] = [
      '#title' => t('Sélectionnez les documents à importer'),
      '#type' => 'dropzonejs',
      '#required' => TRUE,
      '#dropzone_description' => t('Glissez-déposez le(s) fichier(s) que vous souhaitez importer'),
      '#description' => t('La somme des tailles de documents sélectionnés doit être <= 20M. Seules les extensions suivantes sont autorisées: pdf xls xlsx doc docx jpg jpeg png gif.'),
      '#max_filesize' => '20M',
      '#extensions' => 'pdf xls xlsx doc docx jpg jpeg png gif',
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Importer'),
    ];
    return $form;
  }

  /**
   * {@inheritDoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $failure = [];
    $upload_success_counter = 0;
    $time = (new \DateTime('now'))->format('d-m-Y');
    if (!file_exists('public://documents-en-masse/' . $time)) {
      mkdir('public://documents-en-masse/' . $time, 0777, TRUE);
    }
    foreach ($values['documents']['uploaded_files'] as $file) {
      $handle = fopen($file['path'], 'r');
      if ($handle) {
        $file = file_save_data($handle, 'public://documents-en-masse/' . $time . '/' . $file['filename']);
        fclose($handle);
        if ($file) {
          $file->setPermanent();
          $type = $file->get('type')->target_id;
          try {
            $file->save();
            // Create related media entity.
            $media = Media::create([
              'bundle' => $type == 'image' ? 'image' : 'file',
              'name' => $file->getFilename(),
              'status' => 1,
              'uid' => \Drupal::service('current_user')->id(),
              ($type == 'image' ? 'field_media_image' : 'field_media_file') => [
                'target_id' => $file->id(),
              ],
            ]);
            $media->save();
            $upload_success_counter++;
          }
          catch (EntityStorageException $e) {
            $failure[] = $file['filename'];
          }
        }
        else {
          $failure[] = $file['filename'];
        }
      }
    }
    $message = "Import s'est terminé, " . $upload_success_counter . " fichiers importés avec succès, " . count($failure) . " cas d'echec.";
    if (count($failure) > 0) {
      $message .= '<br>Liste des fichiers non importés:<br><ul>';
      foreach ($failure as $filename) {
        $message .= '<li>' . $filename . '</li>';
      }
      $message .= '</ul>';
    }
    \Drupal::messenger()->addMessage($message, Messenger::TYPE_STATUS);
  }

}
