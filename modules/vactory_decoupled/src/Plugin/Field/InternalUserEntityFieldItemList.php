<?php

namespace Drupal\vactory_decoupled\Plugin\Field;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Field\FieldItemList;
use Drupal\Core\StreamWrapper\StreamWrapperManager;
use Drupal\Core\TypedData\ComputedItemListTrait;
use Drupal\Core\TypedData\TraversableTypedDataInterface;
use Drupal\Core\Url;
use Drupal\image\Entity\ImageStyle;
use Drupal\user\Entity\User;
use Drupal\vactory_decoupled\MediaFilesManager;

/**
 * Defines a user list class for better normalization targeting.
 */
class InternalUserEntityFieldItemList extends FieldItemList
{

  use ComputedItemListTrait;

  /**
   * Entity field manager service.
   *
   * @var EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Vactory decoupled media file manager service.
   *
   * @var MediaFilesManager
   */
  protected $mediaFilesManager;

  /**
   * Entity repository service.
   *
   * @var EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * {@inheritDoc}
   */
  public static function createInstance($definition, $name = NULL, TraversableTypedDataInterface $parent = NULL)
  {
    $instance = parent::createInstance($definition, $name, $parent);
    $container = \Drupal::getContainer();
    $instance->entityFieldManager = $container->get('entity_field.manager');
    $instance->mediaFilesManager = $container->get('vacory_decoupled.media_file_manager');
    $instance->entityRepository = $container->get('entity.repository');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  protected function computeValue()
  {
    /** @var \Drupal\node\NodeInterface $node */
    $entity = $this->getEntity();
    $entity_type = $entity->getEntityTypeId();
    $bundle = $entity->bundle();

    if (!in_array($entity_type, ['comment', 'node'])) {
      return;
    }

    $fields = $this->entityFieldManager->getFieldDefinitions($entity_type, $bundle);

    $value = [];
    foreach ($fields as $name => $definition) {
      if ($definition->getType() !== 'entity_reference') {
        continue;
      }

      if ($definition->getSetting('target_type') !== 'user') {
        continue;
      }

      $uid = $entity->get($name)->getString();
      $user = User::load($uid);

      if (!$user) {
        continue;
      }
      
      $user = $this->entityRepository->getTranslationFromContext($user);

      // Process Image.
      $image_value = NULL;
      $file_entity = $user->get('user_picture')->entity;
      if (!empty($file_entity)) {
        $image_app_base_url = Url::fromUserInput('/app-image/')
          ->setAbsolute()->toString();
        $lqipImageStyle = ImageStyle::load('lqip');

        $uri = $file_entity->getFileUri();

        $image_value = [
          '_default' => $this->mediaFilesManager->getMediaAbsoluteUrl($uri),
          '_lqip' => $this->mediaFilesManager->convertToMediaAbsoluteUrl($lqipImageStyle->buildUrl($uri)),
          'uri' => StreamWrapperManager::getTarget($uri),
          'fid' => $file_entity->id(),
          'file_name' => $file_entity->label(),
          'base_url' => $image_app_base_url,
        ];
      }

      $user_fullname = $user->getDisplayName();
      $author_first_name = $user->get('field_first_name')->getString();
      $author_last_name = $user->get('field_last_name')->getString();

      if (!empty($author_first_name)) {
        $user_fullname = $author_first_name;
      }
      if (!empty($author_first_name) && !empty($author_last_name)) {
        $user_fullname = $author_first_name . ' ' . $author_last_name;
      }

      $value[$name] = [
        'id' => $user->id(),
        'name' => $user->getDisplayName(),
        'first_name' => $user->get('field_first_name')->getString(),
        'last_name' => $user->get('field_last_name')->getString(),
        'full_name' => $user_fullname,
        'picture' => $image_value,
      ];
    }

    $this->list[0] = $this->createItem(0, $value);
  }
}
