<?php

namespace Drupal\vactory_cloudinary\Services;

use Cloudinary\Api\ApiResponse;
use Cloudinary\Api\Exception\NotFound;
use Cloudinary\Api\Upload\UploadApi;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\File\Exception\FileWriteException;
use Drupal\Core\File\FileSystem;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Drupal\file\FileInterface;

/**
 * Vactory cloudinary manager service.
 */
class VactoryCloudinaryManager {

  /**
   * File system service.
   *
   * @var \Drupal\Core\File\FileSystem
   */
  protected $fileSystem;

  /**
   * Stream wrapper manager service.
   *
   * @var \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface
   */
  protected $streamWrapperManager;

  /**
   * Logger channel factory service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $logger;

  /**
   * Module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Immutable config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $cloudinarySdkSettings;

  /**
   * Cloudinary upload API
   */
  protected $uploadApi;

  /**
   * Cloudinary cloud name.
   */
  protected $cloudName;

  /**
   * Cloudinary API key.
   */
  protected $apiKey;

  /**
   * Cloudinary API secret.
   */
  protected $apiSecret;

  /**
   * Vactory cloudinary service constructor.
   */
  public function __construct(
    FileSystem $fileSystem,
    StreamWrapperManagerInterface $streamWrapperManager,
    LoggerChannelFactoryInterface $logger,
    ModuleHandlerInterface $moduleHandler,
    ConfigFactoryInterface $configFactory
  ) {
    $this->fileSystem = $fileSystem;
    $this->streamWrapperManager = $streamWrapperManager;
    $this->logger = $logger;
    $this->moduleHandler = $moduleHandler;
    $this->configFactory = $configFactory;
    $this->cloudinarySdkSettings = $this->configFactory->get('cloudinary_sdk.settings');
    $config = $this->configFactory->get('cloudinary_sdk.settings');
    $this->cloudName = $config->get('cloudinary_sdk_cloud_name');
    $this->apiKey = $config->get('cloudinary_sdk_api_key');
    $this->apiSecret = $config->get('cloudinary_sdk_api_secret');
    $configuration = [
      'api_key' => $this->apiKey,
      'api_secret' => $this->apiSecret,
      'cloud_name' => $this->cloudName,
    ];
    $this->uploadApi = new UploadApi($configuration);
  }

  /**
   * Move file from cloudinary to Drupal file system.
   */
  public function moveFromCloudinary(FileInterface $source, $destination, $path, $source_url) {
    $dirname = $this->fileSystem->dirname($path);
    if (!empty($dirname) && !file_exists('public://' . $dirname)) {
      mkdir('public://' . $dirname, 0775, TRUE);
    }
    $uri = $this->moveFileWithoutUnlink($source_url, $destination);
    return $this->updateFileEntity($source, $uri, $destination);
  }

  /**
   * From Drupal to cloudinary.
   */
  public function moveToCloudinary(FileInterface $source, $destination, $resource_type) {
    $this->uploadFile($source->getFileUri(), $resource_type);
    $this->updateFileEntity($source, $destination);
    if (strpos($destination, 'cloudinary://') === 0) {
      unlink($source->getFileUri());
    }
  }

  /**
   * Update file entity.
   */
  public function updateFileEntity($source, $uri) {
    $file = clone $source;
    $file->setFileUri($uri);
    $file->save();
    return $file;
  }

  /**
   * Move given source to destination without unlink source.
   */
  public function moveFileWithoutUnlink($source, $destination, $replace = FileSystemInterface::EXISTS_RENAME) {
    // Rename destination file if already exist.
    $destination = $this->fileSystem->getDestinationFilename($destination, $replace);

    // Attempt to resolve the URIs. This is necessary in certain
    // configurations (see above) and can also permit fast moves across local
    // schemes.
    $real_source = $this->fileSystem->realpath($source) ?: $source;
    $real_destination = $this->fileSystem->realpath($destination) ?: $destination;
    // Fall back to slow copy and unlink procedure. This is necessary for
    // renames across schemes that are not local, or where rename() has not
    // been implemented. It's not necessary to use FileSystem::unlink() as the
    // Windows issue has already been resolved above.
    if (!@copy($real_source, $real_destination)) {
      $this->logger->get('Vactory Cloudinary')->error("The specified file '%source' could not be moved to '%destination'.", [
        '%source' => $source,
        '%destination' => $destination,
      ]);
      throw new FileWriteException("The specified file '$source' could not be moved to '$destination'.");
      //file_put_contents($real_destination, file_get_contents($real_source));
    }

    // Set the permissions on the new file.
    $this->fileSystem->chmod($destination);

    return $destination;
  }

  /**
   * Get cloudinary public ID from URI.
   */
  public function getPublicId($cloudinaryUri) {
    list($scheme, $target) = explode('://', $cloudinaryUri, 2);
    // Remove erroneous leading or trailing, forward-slashes and backslashes.
    $public_id = trim($target, '\/');
    if (cloudinary_stream_wrapper_is_image($public_id)) {
      $this->resourceType = CLOUDINARY_STREAM_WRAPPER_RESOURCE_IMAGE;
      $public_id = preg_replace('/(.*)\.(jpe?g|png|gif|bmp)$/i', '\1', $public_id);
    }
    return $public_id;
  }

  /**
   * Get cloudinary ressource from URI.
   */
  public function getCloudinaryRessource($uri) {
    $public_id = $this->getPublicId($uri);
    /** @var ApiResponse $response */
    $response = $this->uploadApi->explicit($public_id, ['type' => 'upload']);
    $resource = $response->getArrayCopy();
    return $resource;
  }

  /**
   * Upload file to cloudinary.
   */
  public function uploadFile($uri, $resource_type) {
    $options = $this->getBasicOptions($uri);
    $options['resource_type'] = $resource_type;
    $this->uploadApi->upload($uri, $options);
  }

  /**
   * Cloudinary API basic option.
   */
  public function getBasicOptions($uri) {
    return [
      'public_id' => $this->getPublicId($uri),
    ];
  }

}
