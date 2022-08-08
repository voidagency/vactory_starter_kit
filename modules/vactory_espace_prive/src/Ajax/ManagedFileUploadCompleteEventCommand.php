<?php

namespace Drupal\vactory_espace_prive\Ajax;

use Drupal\Core\Ajax\CommandInterface;
use Drupal\file\Entity\File;

/**
 * Command to trigger an event when managed file upload is complete.
 */
class ManagedFileUploadCompleteEventCommand implements CommandInterface {

  // Constructs a ReadMessageCommand object.
  public function __construct($form) {
    $this->form = $form;
  }
  /**
   * Implements Drupal\Core\Ajax\CommandInterface:render().
   */
  public function render() {
    $form = $this->form;
    $file_id = array_keys($this->form['#files'])[0] ;
    if ($file_id !== null) {
      $file = File::load($file_id);
      $uri = $file->getFileUri();
      $stream_wrapper_manager = \Drupal::service('stream_wrapper_manager')->getViaUri($uri);
      $file_path = $stream_wrapper_manager->getExternalUrl(); // the path to the uploaded image
    }else {
       $file_path = "/themes/vactory/assets/img/user-avatar.svg";
    }
    // $file_name = $file->getFilename();
    return [
      'command' => 'triggerManagedFileUploadComplete',
      'file_path' => $file_path,
    ];
  }

}
