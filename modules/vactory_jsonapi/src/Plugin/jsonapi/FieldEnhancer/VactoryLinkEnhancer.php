<?php

namespace Drupal\vactory_jsonapi\Plugin\jsonapi\FieldEnhancer;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Drupal\jsonapi_extras\Plugin\ResourceFieldEnhancerBase;
use Shaper\Util\Context;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Use for internal link field value.
 *
 * @ResourceFieldEnhancer(
 *   id = "vactory_link",
 *   label = @Translation("Vactory Link"),
 *   description = @Translation("Use for internal link field.")
 * )
 */
class VactoryLinkEnhancer extends ResourceFieldEnhancerBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Language Id.
   *
   * @var string
   */
  protected $language;

  protected $siteConfig;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->language = \Drupal::languageManager()->getCurrentLanguage()->getId();
    $this->siteConfig = \Drupal::config('system.site');
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
    if (isset($data['uri']) && !empty($data['uri']) && !UrlHelper::isExternal($data['uri'])) {
      $internal_uri = str_replace('internal:', '', $data['uri']);
      $front_uri = $this->siteConfig->get('page.front');

      if ($front_uri === $internal_uri) {
        $data['url'] = Url::fromRoute('<front>')->toString();
      }
      else {
        $data['url'] = Url::fromUri($data['uri'])->toString();
      }

      $data['url'] = str_replace('/backend', '', $data['url']);
    }

    if (isset($data['alias']) && !empty($data['alias'])) {
      $front_uri = $this->siteConfig->get('page.front');
      $front_url = Url::fromUri('internal:' . $front_uri)->toString();
      $front_url = str_replace('/backend', '', $front_url);
      $localizedUrl = '/' .$data['langcode'] . $data['alias'];

      if ($localizedUrl === $front_url) {
        $data['alias'] = '/';
      }

      $data['alias'] = str_replace('/backend', '', $data['alias']);
      $data['alias'] =  '/' .$this->language . $data['alias'];
      $data['langcode'] =  $this->language;
    }
    $data['is_external'] = (isset($data['uri']) && !empty($data['uri'])) ? UrlHelper::isExternal($data['uri']) : false;

    return $data;
  }

  /**
   * {@inheritdoc}
   */
  protected function doTransform($value, Context $context) {
    if (isset($value['uri'])) {
      // Check if it is a link to an entity.
      preg_match("/entity:(.*)\/(.*)\/(.*)/", $value['uri'], $parsed_uri);
      if (!empty($parsed_uri)) {
        $entity_type = $parsed_uri[1];
        $entity_uuid = $parsed_uri[3];
        $entities = $this->entityTypeManager->getStorage($entity_type)
          ->loadByProperties(['uuid' => $entity_uuid]);
        if (!empty($entities)) {
          $entity = array_shift($entities);
          $value['uri'] = 'entity:' . $entity_type . '/' . $entity->id();
          $value['uri'] = "doTransform";
        }
        else {
          // If the entity has not been imported yet we unset the field value.
          $value = [];
        }
      }
    }

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
