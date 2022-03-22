<?php

namespace Drupal\vactory_jsonapi\Plugin\jsonapi\FieldEnhancer;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManager;
use Drupal\Core\Url;
use Drupal\image\Entity\ImageStyle;
use Drupal\jsonapi_extras\Plugin\ResourceFieldEnhancerBase;
use Shaper\Util\Context;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Use for internal media field value.
 *
 * @ResourceFieldEnhancer(
 *   id = "vactory_file_document",
 *   label = @Translation("Vactory documents"),
 *   description = @Translation("Use for internal media field.")
 * )
 */
class VactoryFileDocumentEnhancer extends ResourceFieldEnhancerBase implements ContainerFactoryPluginInterface
{

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, EntityTypeManagerInterface $entity_type_manager)
  {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition)
  {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function doUndoTransform($data, Context $context)
  {
    if (isset($data['value']) && !empty($data['value'])) {
      $origin_uri = $data['value'];

      $medias = \Drupal::entityTypeManager()
        ->getStorage('file')
        ->loadByProperties(['uri' => $origin_uri]);
      $media = reset($medias);

      $uri = $media->getFileUri();

      $data['value'] = [
        '_default' => \Drupal::service('file_url_generator')->generateAbsoluteString($uri),
        'uri' => StreamWrapperManager::getTarget($uri),
        'fid' => $media->id(),
        'file_name' => $media->label(),
      ];

    }
    return $data;
  }

  /**
   * {@inheritdoc}
   */
  protected function doTransform($value, Context $context)
  {
    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public function getOutputJsonSchema()
  {
    return [
      'type' => 'object',
    ];
  }

}
