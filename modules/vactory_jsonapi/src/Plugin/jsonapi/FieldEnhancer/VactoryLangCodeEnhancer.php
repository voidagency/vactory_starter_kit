<?php

namespace Drupal\vactory_jsonapi\Plugin\jsonapi\FieldEnhancer;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\jsonapi_extras\Plugin\ResourceFieldEnhancerBase;
use Shaper\Util\Context;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Use for internal langcode field value.
 *
 * @ResourceFieldEnhancer(
 *   id = "vactory_langcode",
 *   label = @Translation("Vactory Langcode"),
 *   description = @Translation("Use for langcode field.")
 * )
 */
class VactoryLangCodeEnhancer extends ResourceFieldEnhancerBase implements ContainerFactoryPluginInterface
{

  /**
   * Language Id.
   *
   * @var string
   */
  protected $language;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition)
  {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->language = \Drupal::languageManager()->getCurrentLanguage()->getId();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition)
  {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function doUndoTransform($data, Context $context)
  {
    $data = $this->language;

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
      'type' => 'string',
    ];
  }

}
