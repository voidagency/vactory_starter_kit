<?php

namespace Drupal\vactory_sondage\Plugin\jsonapi\FieldEnhancer;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\file\Entity\File;
use Drupal\jsonapi_extras\Plugin\ResourceFieldEnhancerBase;
use Drupal\media\Entity\Media;
use Drupal\vactory_decoupled\MediaFilesManager;
use Shaper\Util\Context;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Use for formatting sondage options.
 *
 * @ResourceFieldEnhancer(
 *   id = "sondage_options_enhancer",
 *   label = @Translation("Sondage Options Enhancer"),
 *   description = @Translation("Sondage Options Enhancer.")
 * )
 */
class SondageOptionsEnhancer extends ResourceFieldEnhancerBase implements ContainerFactoryPluginInterface {

  /**
   * Media File Manager Service.
   *
   * @var \Drupal\vactory_decoupled\MediaFilesManager
   */
  protected $mediaFilesManager;

  /**
   * SondageOptionsEnhancer constructor.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MediaFilesManager $mediaFilesManager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->mediaFilesManager = $mediaFilesManager;

  }

  /**
   * Creates an instance of the plugin.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The container to pull out services used in the plugin.
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   *
   * @return static
   *   Returns an instance of this plugin.
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition, $container->get('vacory_decoupled.media_file_manager'));
  }

  /**
   * {@inheritdoc}
   */
  protected function doUndoTransform($data, Context $context) {
    $mid = $data['option_image'];
    if (!is_null($mid)) {
      $media = Media::load($mid);
      if ($media) {
        $fid = $media->get('field_media_image')->target_id;
        $alt = $media->get('field_media_image')->alt;
        $file = $fid ? File::load($fid) : NULL;
        $image_uri = '';
        if ($file) {
          $image_uri = $file->get('uri')->value;
          $alt = $media->get('field_media_image')->alt;
        }
        $data['option_image'] = [];
        $data['option_image']['image'] = $this->mediaFilesManager->getMediaAbsoluteUrl($image_uri);
        $data['option_image']['alt'] = $alt;
      }
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
      'oneOf' => [
        ['type' => 'array'],
        ['type' => 'null'],
        ['type' => 'string'],
        ['type' => 'object'],
      ],
    ];
  }

}
