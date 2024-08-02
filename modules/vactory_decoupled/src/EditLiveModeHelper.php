<?php

namespace Drupal\vactory_decoupled;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Edit live mode helper.
 */
class EditLiveModeHelper {

  /**
   * Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Current user service.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs an EditLiveModeHelper object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   The current user service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory service.
   */
  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    AccountProxyInterface $currentUser,
    ConfigFactoryInterface $configFactory
  ) {
    $this->entityTypeManager = $entityTypeManager;
    $this->currentUser = $currentUser;
    $this->configFactory = $configFactory;
  }

  /**
   * Checks if the current user has access to edit content in live mode.
   *
   * @return bool
   *   TRUE if the user has access, FALSE otherwise.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function checkAccess() {
    $user = $this->entityTypeManager->getStorage('user')->load($this->currentUser->id());
    $user_granted = $user->hasPermission('edit content live mode');

    // Check if the feature is enabled from vactory_dynamic_field settings.
    $decoupled_edit_live_mode = $this->configFactory->get('vactory_dynamic_field.settings')->get('decoupled_edit_live_mode');

    return $user_granted && $decoupled_edit_live_mode;
  }

}
