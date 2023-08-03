<?php

namespace Drupal\vactory_attached_assets\Services;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Executable\ExecutableManagerInterface;
use Drupal\file\Entity\File;
use Drupal\user\Entity\User;

/**
 * Attached Assets Service.
 *
 * @package Drupal\vactory_attached_assets\Manager
 */
class AttachedAssetsService {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The condition plugin manager.
   *
   * @var \Drupal\Core\Condition\ConditionManager
   */
  protected $conditionManager;

  /**
   * Constructs a new AttachedAssetsService class.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager.
   * @param \Drupal\Core\Executable\ExecutableManagerInterface $condition_manager
   *   The ConditionManager for building the insertion conditions.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ExecutableManagerInterface $condition_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->conditionManager = $condition_manager;
  }

  /**
   * Add the asset to the attachments array.
   *
   * @param array $attachments
   *   The page attachments array.
   */
  public function attachAssets(array &$attachments) {

    $attachedAssets = $this->loadAttachedAssets();
    foreach ($attachedAssets as $attachedAsset) {
      if (!$this->evaluateCondition($attachedAsset)) {
        continue;
      }

      $type = $attachedAsset->getType();
      $fid = $attachedAsset->getFileId()[0];
      $file = File::load($fid);

      switch ($type) {
        case 'script':
          $attachments['#attached']['html_head'][] = $this->attachScript($file, $attachedAsset->label());
          break;

        default:
          break;
      }

    }
  }

  /**
   * Add inline assets.
   */
  public function attachAssetsToHtml() {
    $attachedAssets = $this->loadAttachedAssets();
    $asset = [];
    $all_css_assets = [];
    foreach ($attachedAssets as $attachedAsset) {
      if (!$this->evaluateCondition($attachedAsset)) {
        continue;
      }

      $type = $attachedAsset->getType();
      $fid = $attachedAsset->getFileId()[0];
      $file = File::load($fid);
      $url = \Drupal::service('file_url_generator')->transformRelative(\Drupal::service('file_url_generator')->generateAbsoluteString($file->getFileUri()));

      if ($type == 'style') {
        $asset['type'] = 'css';
        $asset['url'] = $url;
        array_push($all_css_assets, $asset);
      }

    }
    return $all_css_assets;
  }

  /**
   * Returns attached assets entities.
   *
   * @return array
   *   The entities array.
   */
  public function loadAttachedAssets() {
    $entityManager = $this->entityTypeManager
      ->getStorage('attached_assets_entity');
    $ids = $entityManager->getQuery()
      ->condition('status', 1)
      ->accessCheck(FALSE)
      ->execute();

    return (array) $entityManager->loadMultiple($ids);
  }

  /**
   * Returns a style asset element.
   */
  public function attachStyle($url) {
    return [
      [
        "rel" => 'stylesheet',
        "href" => $url,
        "media" => 'all',
      ],
    ];
  }

  /**
   * Returns a script asset element.
   */
  public function attachScript($file, $assetLabel) {
    return [
      [
        '#type' => 'html_tag',
        '#tag' => 'script',
        '#value' => file_get_contents($file->getFileUri()),
      ],
      "attached_asset_{$assetLabel}",
    ];
  }

  /**
   * Evaluates an asset entity conditions.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function evaluateCondition($attached_assets_entity) {
    $conditions = $attached_assets_entity->getConditions();
    $flag = TRUE;
    // Define the context of each condition.
    $contexts = [
      'entity_bundle:node' => [
        'node',
        \Drupal::routeMatch()->getParameter('node'),
      ],
      'request_path' => [],
      'user_role' => [
        'user',
        User::load(\Drupal::currentUser()->id()),
      ],
      'language' => [
        'language',
        \Drupal::languageManager()->getCurrentLanguage(),
      ],
    ];
    foreach ($conditions as $condition_id => $condition_config) {
      /** @var \Drupal\system\Plugin\Condition\RequestPath $condition */
      $condition = $this->conditionManager->createInstance($condition_id);
      $condition->setConfiguration($condition_config);

      if (!empty($contexts[$condition_id])) {
        // Set the condition context.
        $condition->setContextValue($contexts[$condition_id][0], $contexts[$condition_id][1]);
      }

      if (!$condition->evaluate()) {
        return FALSE;
      }
    }

    return $flag;
  }

}
