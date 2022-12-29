<?php

namespace Drupal\vactory_decoupled\Plugin\jsonapi\FieldEnhancer;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Site\Settings;
use Drupal\Core\StreamWrapper\StreamWrapperManager;
use Drupal\Core\Url;
use Drupal\image\Entity\ImageStyle;
use Drupal\jsonapi_extras\Plugin\ResourceFieldEnhancerBase;
use Drupal\vactory_decoupled\MediaFilesManager;
use Shaper\Util\Context;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Use for internal media field value.
 *
 * @ResourceFieldEnhancer(
 *   id = "vactory_file_image",
 *   label = @Translation("Vactory Image"),
 *   description = @Translation("Use for internal media field.")
 * )
 */
class VactoryFileImageEnhancer extends ResourceFieldEnhancerBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The decoupled media files manager service.
   *
   * @var \Drupal\vactory_decoupled\MediaFilesManager
   */
  protected $mediaFilesManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    array $plugin_definition,
    EntityTypeManagerInterface $entity_type_manager,
    MediaFilesManager $mediaFilesManager
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->mediaFilesManager = $mediaFilesManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('vacory_decoupled.media_file_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function doUndoTransform($data, Context $context) {
    if (isset($data['value']) && !empty($data['value'])) {
      $origin_uri = $data['value'];

      $image_app_base_url = Url::fromUserInput('/app-image/')
        ->setAbsolute()->toString();
      $lqipImageStyle = ImageStyle::load('lqip');

      $medias = $this->entityTypeManager->getStorage('file')
        ->loadByProperties(['uri' => $origin_uri]);
      $media = reset($medias);

      $uri = $media->getFileUri();
      $data['value'] = [
        '_default'  => $this->mediaFilesManager->getMediaAbsoluteUrl($uri),
        '_lqip'     => $this->mediaFilesManager->convertToMediaAbsoluteUrl($lqipImageStyle->buildUrl($uri)),
        'uri'       => StreamWrapperManager::getTarget($uri),
        'fid'       => $media->id(),
        'file_name' => $media->label(),
        'base_url'  => $image_app_base_url,
        'meta' => $media->getAllMetadata()
      ];

//      $data['value'] = \Drupal::service('file_url_generator')->generateAbsoluteString($data['value']);
    }
    return $data;
  }

  /**
   * {@inheritdoc}
   */
  protected function doTransform($value, Context $context) {
    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public function getOutputJsonSchema() {
    return [
      'type' => 'object',
    ];
  }

}
