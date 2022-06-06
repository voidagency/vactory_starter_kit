<?php

namespace Drupal\vactory_decoupled_webform\Plugin\rest\resource;

use Drupal\Component\Utility\Bytes;
use Drupal\Component\Utility\Environment;
use Drupal\Core\File\FileSystemInterface;
use Drupal\file\Entity\File;
use Drupal\file\Plugin\rest\resource\FileUploadResource;
use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;
use Drupal\webform\WebformSubmissionForm;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Drupal\rest\ModifiedResourceResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Creates a resource for webform file uploads.
 *
 * @RestResource(
 *   id = "vactory_decoupled_webform_rest_file_upload",
 *   label = @Translation("Vactory Webform File Upload"),
 *   serialization_class = "Drupal\file\Entity\File",
 *   uri_paths = {
 *     "https://www.drupal.org/link-relations/create" = "/webform_rest/{webform_id}/upload/{field_name}"
 *   }
 * )
 */
class WebformFileUploadResource extends FileUploadResource {

  /**
   * Creates a file from an endpoint.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   * @param string $webform_id
   *   The webform ID.
   * @param string $field_name
   *   The field name.
   * @param string $placeholder
   *   An unused placeholder to maintain compatibility with the parent method.
   *
   * @return \Drupal\rest\ResourceResponse
   *   The HTTP response object.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   *   Throws HttpException in case of error.
   */
  public function post(Request $request, $webform_id, $field_name, $placeholder = '') {
    // Check for a valid webform.
    $webform = Webform::load($webform_id);
    if (!$webform) {
      throw new BadRequestHttpException('Invalid webform_id value.');
    }

    // Check webform is open.
    $is_open = WebformSubmissionForm::isOpen($webform);

    if ($is_open === TRUE) {

      $filename = $this->validateAndParseContentDispositionHeader($request);

      $element = $webform->getElement($field_name);

      $webform_submission = WebformSubmission::create([
        'webform_id' => $webform->id(),
      ]);

      // Prepare upload location and validators for the element
      $element_plugin = $this->getElementPlugin($element);
      $element_plugin->prepare($element, $webform_submission);

      $destination = $element['#upload_location'];

      // Check the destination file path is writable.
      if (!\Drupal::service('file_system')->prepareDirectory($destination, FileSystemInterface::CREATE_DIRECTORY)) {
        throw new HttpException(500, 'Destination file path is not writable');
      }

      $validators = $this->getElementValidators($element);

      $prepared_filename = $this->prepareFilename($filename, $validators);

      // Create the file.
      if (substr($destination, -1) === '/') {
        $file_uri = "{$destination}{$prepared_filename}";
      }
      else {
        $file_uri = "{$destination}/{$prepared_filename}";
      }

      $temp_file_path = $this->streamUploadData();

      // This will take care of altering $file_uri if a file already exists.
      \Drupal::service('file_system')->getDestinationFilename($temp_file_path, $file_uri);

      // Lock based on the prepared file URI.
      $lock_id = $this->generateLockIdFromFileUri($file_uri);

      if (!$this->lock->acquire($lock_id)) {
        throw new HttpException(503, sprintf('File "%s" is already locked for writing'), NULL, ['Retry-After' => 1]);
      }

      // Begin building file entity.
      $file = File::create([]);
      $file->setOwnerId($this->currentUser->id());
      $file->setFilename($prepared_filename);
      $file->setMimeType($this->mimeTypeGuesser->guess($prepared_filename));
      $file->setFileUri($file_uri);
      // Set the size. This is done in File::preSave() but we validate the file
      // before it is saved.
      $file->setSize(@filesize($temp_file_path));

      // Validate the file entity against entity-level validation and field-level
      // validators.
      $this->validate($file, $validators);

      // Move the file to the correct location after validation. Use
      // FILE_EXISTS_ERROR as the file location has already been determined above
      // in file_unmanaged_prepare().
      if (!\Drupal::service('file_system')->move($temp_file_path, $file_uri, FileSystemInterface::EXISTS_ERROR)) {
        throw new HttpException(500, 'Temporary file could not be moved to file location');
      }

      $file->save();

      $this->lock->release($lock_id);

      // 201 Created responses return the newly created entity in the response
      // body. These responses are not cacheable, so we add no cacheability
      // metadata here.
      return new ModifiedResourceResponse($file, 201);


    }
    else {
      throw new AccessDeniedHttpException('This webform is closed, or too many submissions have been made.');
    }
  }

  /**
   * Retrieves the upload validators for an element.
   *
   * This is copied from \Drupal\file\Plugin\Field\FieldType\FileItem as there
   * is no entity instance available here that a FileItem would exist for.
   *
   * @param array $element
   *   The element for which to get validators.
   *
   * @return array
   *   An array suitable for passing to file_save_upload() or the file field
   *   element's '#upload_validators' property.
   */
  protected function getElementValidators(array $element) {
    $validators = [
      // Add in our check of the file name length.
      'file_validate_name_length' => [],
    ];

    // Cap the upload size according to the PHP limit.
    $max_filesize = Bytes::toNumber(Environment::getUploadMaxSize());
    if (!empty($element["#max_filesize"])) {
      $max_filesize = min($max_filesize, Bytes::toNumber($element['#max_filesize'] * 1024 * 1024));
    }

    // There is always a file size limit due to the PHP server limit.
    $validators['file_validate_size'] = [$max_filesize];

    // Add the extension check if necessary.
    if (!empty($element['#file_extensions'])) {
      $validators['file_validate_extensions'] = [$element['#file_extensions']];
    }

    return $validators;
  }

  /**
   * Loads the webform element plugin for the provided element.
   *
   * @param array $element
   *   The element for which to get the plugin.
   *
   * @return \Drupal\Core\Render\Element\ElementInterface
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  protected function getElementPlugin(array $element) {
    /** @var \Drupal\Core\Render\ElementInfoManager $plugin_manager */
    $plugin_manager = \Drupal::service('plugin.manager.webform.element');
    $plugin_definition = $plugin_manager->getDefinition($element['#type']);

    $element_plugin = $plugin_manager->createInstance($element['#type'], $plugin_definition);

    return $element_plugin;
  }
}
