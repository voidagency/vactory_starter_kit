<?php

namespace Drupal\vactory_decoupled_webform\Plugin\jsonapi\FieldEnhancer;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\jsonapi_extras\Plugin\ResourceFieldEnhancerBase;
use Drupal\vactory_decoupled_webform\Webform;
use Shaper\Util\Context;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Use for webform elements field value.
 *
 * @ResourceFieldEnhancer(
 *   id = "vactory_decoupled_webform_elements",
 *   label = @Translation("Vactory Webform Elements"),
 *   description = @Translation("Transform Webform elements field to usable format.")
 * )
 */
class VactoryWebformElements extends ResourceFieldEnhancerBase implements ContainerFactoryPluginInterface
{

  /**
   * @var \Drupal\vactory_decoupled_webform\Webform
   */
  protected $webformNormalizer;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, string $plugin_id, $plugin_definition, Webform $webformNormalizer)
  {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->webformNormalizer = $webformNormalizer;
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
      $container->get('vactory.webform.normalizer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration()
  {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  protected function doUndoTransform($data, Context $context)
  {
    $webform_id = $context['field_item_object']->getField("drupal_internal__id");
    return $this->webformNormalizer->normalize($webform_id);
  }

  /**
   * {@inheritdoc}
   */
  protected function doTransform($data, Context $context)
  {
    return $data;
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

  /**
   * {@inheritdoc}
   */
  public function getSettingsForm(array $resource_field_info)
  {
    return [];
  }

}
