<?php

namespace Drupal\vactory_jsonapi\Plugin\jsonapi\FieldEnhancer;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\jsonapi_extras\Plugin\ResourceFieldEnhancerBase;
use Drupal\node\Entity\Node;
use Shaper\Util\Context;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Use for internal metatag field value.
 *
 * @ResourceFieldEnhancer(
 *   id = "vactory_metatag",
 *   label = @Translation("Vactory Metatags"),
 *   description = @Translation("Use for internal metatag field.")
 * )
 */
class VactoryMetaTagEnhancer extends ResourceFieldEnhancerBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
//    debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
//      exit;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
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
  protected function doUndoTransform($data, Context $context) {
    $object = $context['field_item_object'];
    $entity = $object->getEntity();

//    var_dump($entity->label());

    if (!$entity instanceof Node) {
      return $data;
    }


    $metatag_manager = \Drupal::service('metatag.manager');
    $metatags = metatag_get_default_tags($entity);
    foreach ($metatag_manager->tagsFromEntity($entity) as $tag => $data) {
      $metatags[$tag] = $data;
    }

    $context = [
      'entity' => $entity,
    ];

    \Drupal::service('module_handler')->alter('metatags', $metatags, $context);

    $pre_rendered_tags = $metatag_manager->generateRawElements($metatags, $entity);

    // @note: This need to be json encoded,
    // we need all fields to be available to GraphQl.
    // we don't wanna have to select field by field.
    // We will be missing some fields when we have no content to work with.
    // Some nodes may have title & description, others may have open graph.
    $data = json_encode($pre_rendered_tags);
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
      'type' => 'string',
    ];
  }

}
