<?php

namespace Drupal\vactory_cloudinary\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;
use Drupal\file_entity\Entity\FileEntity;
use Drupal\file_entity\Form\FileEditForm as OriginalFileEditForm;

/**
 * File edit form override.
 */
class FileEditForm extends OriginalFileEditForm {

  /**
   * {@inheritDoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('replace_upload')) {
      $replacement = $form_state->getValue('replace_upload')[0];
      if ($replacement instanceof FileEntity) {
        $entity_replacement = $replacement;
      } else {
        $entity_replacement = File::load($replacement);
      }
      $log_args = array('@old' => $this->entity->getFilename(), '@new' => $entity_replacement->getFileName());
      $is_cloudinary = strpos($entity_replacement->getFileUri(), 'cloudinary') === 0;
      if ($is_cloudinary) {
        // Update old cloudinary file uri.
        $this->entity->setFileUri($entity_replacement->getFileUri());
        // Use fake uri for entity replacement to avoid removing file from cloudinary.
        $entity_replacement->setFileUri('public://');
        $entity_replacement->save();
        // Remove temporary replacement entity.
        $entity_replacement->delete();
        \Drupal::logger('file_entity')->info('File @old was replaced by @new', $log_args);
        drupal_flush_all_caches();
      }
      else {
        parent::submitForm($form, $form_state);
      }
    }

  }

}
