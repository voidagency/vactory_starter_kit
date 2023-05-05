<?php

namespace Drupal\vactory_decoupled\Plugin\Field;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Field\FieldItemList;
use Drupal\Core\Site\Settings;
use Drupal\Core\TypedData\ComputedItemListTrait;
use Drupal\Core\TypedData\TraversableTypedDataInterface;
use Drupal\node\Entity\Node;
use Drupal\vactory_decoupled\VactoryDecoupledHelper;

/**
 * Metatags per node.
 */
class InternalNodeMetatagsFieldItemList extends FieldItemList
{

  use ComputedItemListTrait;

  /**
   * Entity repository service.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * Meta tag manager service.
   *
   * @var \Drupal\metatag\MetatagManagerInterface
   */
  protected $metatagManager;

  /**
   * Module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Vactory decoupled helper service.
   *
   * @var \Drupal\vactory_decoupled\VactoryDecoupledHelper
   */
  protected $vactoryDecoupledHelper;

  /**
   * Current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  public static function createInstance($definition, $name = NULL, TraversableTypedDataInterface $parent = NULL)
  {
    $instance = parent::createInstance($definition, $name, $parent);
    $container = \Drupal::getContainer();
    $instance->entityRepository = $container->get('entity.repository');
    $instance->metatagManager = $container->get('metatag.manager');
    $instance->moduleHandler = $container->get('module_handler');
    $instance->vactoryDecoupledHelper = $container->get('vactory_decoupled.helper');
    $instance->request = $container->get('request_stack')->getCurrentRequest();
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  protected function computeValue()
  {
    /** @var Node $entity */
    $entity = $this->getEntity();
    $entity_type = $entity->getEntityTypeId();

    if (!in_array($entity_type, ['node'])) {
      return;
    }

    if ($entity->isNew()) {
      return;
    }

    $entity = $this->entityRepository->getTranslationFromContext($entity);

    $metatags = $this->vactoryDecoupledHelper->metatagGetDefaultTags($entity);

    $tags_from_entity = $this->metatagManager->tagsFromEntity($entity);

    foreach ($tags_from_entity as $tag => $data) {
      $metatags[$tag] = $data;
    }

    $context = [
      'entity' => $entity,
    ];

    $this->moduleHandler->alter('metatags', $metatags, $context);

    $tags = $this->metatagManager->generateRawElements($metatags, $entity);
    $normalized_tags = [];
    $host = $this->request->getSchemeAndHttpHost();
    $frontend_url = Settings::get('BASE_FRONTEND_URL', 'frontend_url');
    $media_url = Settings::get('BASE_MEDIA_URL', 'media_url');
    foreach ($tags as $key => &$tag) {
      foreach ($tag['#attributes'] as $attribute => &$value) {
        $concerned_attr = in_array($attribute, ['href', 'content']);
        $is_url = UrlHelper::isValid($value, TRUE);
        $is_internal_url = str_starts_with($value, $host);
        if ($concerned_attr && $is_url && $is_internal_url) {
          $url_pieces = explode('/', $value);
          $last_piece = array_pop($url_pieces) ?? '';
          $is_file = str_contains($last_piece, '.');
          $replacement = $is_file ? $media_url : $frontend_url;
          $value = str_replace($host, $replacement, $value);
        }
      }
      $normalized_tags[] = [
        'id' => $key,
        'tag' => $tag['#tag'],
        'attributes' => $tag['#attributes'],
      ];
    }

    $this->list[0] = $this->createItem(0, $normalized_tags);
  }
}
