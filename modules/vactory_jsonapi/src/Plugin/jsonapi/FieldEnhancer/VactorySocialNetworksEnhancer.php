<?php

namespace Drupal\vactory_jsonapi\Plugin\jsonapi\FieldEnhancer;

use CommerceGuys\Addressing\Country\CountryRepositoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\jsonapi_extras\Plugin\ResourceFieldEnhancerBase;
use Drupal\social_media_links\SocialMediaLinksPlatformManager;
use Shaper\Util\Context;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Use for social networks field value.
 *
 * @ResourceFieldEnhancer(
 *   id = "vactory_social_networks",
 *   label = @Translation("Social Networks"),
 *   description = @Translation("Use for social networks field.")
 * )
 */
class VactorySocialNetworksEnhancer extends ResourceFieldEnhancerBase implements ContainerFactoryPluginInterface {

  /**
   * Social media links platforms manager.
   *
   * @var \Drupal\social_media_links\SocialMediaLinksPlatformManager
   */
  protected $mediaLinkPlateformManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, SocialMediaLinksPlatformManager $mediaLinkPlateformManager)
  {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->mediaLinkPlateformManager = $mediaLinkPlateformManager;
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
      $container->get('plugin.manager.social_media_links.platform')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function doUndoTransform($data, Context $context)
  {
    $data = isset($data['platform_values']) ? $data['platform_values'] : [];
    $platforms = \Drupal::service('plugin.manager.social_media_links.platform')->getPlatforms();
    foreach ($data as $plateform_id => &$platform) {
      $url_prefix = $platforms[$plateform_id]['urlPrefix'];
      $platform['fullUrl'] = $url_prefix .  $platform['value'] ;
      $platform['name'] = $platforms[$plateform_id]['name'];
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
