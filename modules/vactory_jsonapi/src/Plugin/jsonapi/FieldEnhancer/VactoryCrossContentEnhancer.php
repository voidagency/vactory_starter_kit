<?php

namespace Drupal\vactory_jsonapi\Plugin\jsonapi\FieldEnhancer;

use Drupal;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Drupal\jsonapi_extras\Plugin\ResourceFieldEnhancerBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Shaper\Util\Context;
use Drupal\vactory_core\Vactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Use for internal vcc field value.
 *
 * @ResourceFieldEnhancer(
 *   id = "vactory_cross_content",
 *   label = @Translation("Vactory VCC"),
 *   description = @Translation("Use for internal Vcc field.")
 * )
 */
class VactoryCrossContentEnhancer extends ResourceFieldEnhancerBase implements ContainerFactoryPluginInterface {

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

    if (!$entity instanceof Node) {
      return $data;
    }
    if (!$entity->hasField('field_contenu_lie')) {
      return $data;
    }
    $related_nodes = trim($entity->get('field_contenu_lie')->value);
    $config = [
      'terms' => '',
      'nombre_elements' => '',
      'more_link' => '',
      'more_link_label' => ''
    ];

    $node_type = array_filter($entity->referencedEntities(), function ($element) {
      return $element instanceof NodeType;
    });
    $node_type = reset($node_type);

    foreach ($config as $key => $value) {
      $config[$key] = $node_type->getThirdPartySetting('vactory_cross_content', $key, $value);

      if ($key === 'more_link' && !empty($config[$key])) {
        $config[$key] = str_replace('/backend', '', Url::fromUserInput($config[$key])->toString());
      }
    }

    if (empty($related_nodes) || !isset($related_nodes)) {
      $taxonomy = Vactory::getTaxonomyList($entity->bundle());
      $taxonomy_fields = array_map(function($element) {
        return $element[1];
      }, $taxonomy);
      $taxonomy_fields = reset($taxonomy_fields);
      $tid = $taxonomy_fields . '.entity:taxonomy_term.tid';
      $query = Drupal::entityQuery('node')
        ->condition('type', $entity->bundle())
        ->condition('nid', $entity->id(), '<>')
        ->condition('langcode', $entity->language()->getId())
        ->range(0, $config['nombre_elements']);
      $query = !empty($config['terms']) ?
        $query->condition($tid, $config['terms'], 'in')->execute() :
        $query->execute();

      $related_nodes = array_values($query);
    } else {
      $related_nodes = explode(' ', $related_nodes);
    }
    unset($config['terms']);

    $related_nodes_list = array_values(Node::loadMultiple($related_nodes));

    /*
     * Allow other modules to override nodes format.
     *
     * @code
     * Implements hook_json_api_vcc_alter().
     * function myModule_json_api_vcc_alter(&$related_nodes_list, $node_type) {
     * }
     * @endcode
     */
    $content_type = $entity->bundle();
    \Drupal::moduleHandler()->alter('json_api_vcc', $related_nodes_list, $content_type);

    $config['nodes'] = $related_nodes_list;
    $data = $config;
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
