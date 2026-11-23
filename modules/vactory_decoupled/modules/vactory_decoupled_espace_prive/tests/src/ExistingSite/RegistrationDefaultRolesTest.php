<?php

namespace Drupal\Tests\vactory_decoupled_espace_prive\ExistingSite;

use Drupal\user\Entity\Role;
use Drupal\user\UserInterface;
use weitzman\DrupalTestTraits\ExistingSiteBase;
use weitzman\DrupalTestTraits\Mail\MailCollectionTrait;

/**
 * Roles of accounts created by the "espace privé" registration webform.
 *
 * The front registers through the decoupled webform
 * "vactory_espace_prive_register". The account gets the default roles chosen
 * in the back office (vactory_decoupled.settings:registration_default_roles),
 * filtered like the JSON:API registration, and never a role taken from the
 * submitted data.
 *
 * @see vactory_decoupled_espace_prive_decoupled_webform_data_presubmit_alter()
 * @see \Drupal\vactory_decoupled\EventSubscriber\UserRegistrationRolesSubscriber
 *
 * @group vactory_decoupled
 * @group vactory_decoupled_espace_prive
 */
class RegistrationDefaultRolesTest extends ExistingSiteBase {

  use MailCollectionTrait;

  /**
   * The decoupled settings before the test, restored in tearDown().
   *
   * @var array
   */
  protected $originalSettings;

  /**
   * Mail of the account registered by the test.
   *
   * @var string
   */
  protected $mail;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    if (!\Drupal::moduleHandler()->moduleExists('vactory_decoupled_espace_prive')) {
      $this->markTestSkipped('vactory_decoupled_espace_prive is not installed on this site.');
    }
    $this->originalSettings = \Drupal::config('vactory_decoupled.settings')->getRawData();
    $this->mail = strtolower('ep_roles_' . $this->randomMachineName(8)) . '@example.com';
    // Registration notifies the user: keep the mails in memory.
    $this->startMailCollection();
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    if ($this->originalSettings !== NULL) {
      // Delete the account even if an assertion failed.
      $accounts = \Drupal::entityTypeManager()->getStorage('user')
        ->loadByProperties(['mail' => $this->mail]);
      foreach ($accounts as $account) {
        $account->delete();
      }
      \Drupal::configFactory()->getEditable('vactory_decoupled.settings')
        ->setData($this->originalSettings)
        ->save();
      $this->restoreMailSettings();
    }
    parent::tearDown();
  }

  /**
   * Creates a role for the test.
   */
  protected function createTestRole(bool $is_admin = FALSE): Role {
    $role = Role::create([
      'id' => 'ep_reg_' . strtolower($this->randomMachineName(8)),
      'label' => 'Espace privé registration test',
      'is_admin' => $is_admin,
    ]);
    $role->save();
    $this->markEntityForCleanup($role);
    return $role;
  }

  /**
   * Sets the default roles of the back-office setting.
   */
  protected function setDefaultRoles(array $role_ids): void {
    \Drupal::configFactory()->getEditable('vactory_decoupled.settings')
      ->set('registration_default_roles', $role_ids)
      ->save();
  }

  /**
   * Registers through the hook the decoupled webform controller invokes.
   *
   * @see \Drupal\vactory_decoupled_webform\Controller\WebformController
   */
  protected function register(array $extra = []): UserInterface {
    $webform_data = $extra + [
      'webform_id' => 'vactory_espace_prive_register',
      'field_first_name' => 'Test',
      'field_last_name' => 'Roles',
      'mail' => $this->mail,
      'pass' => $this->randomMachineName(12) . 'aA1!',
    ];
    $error_message = NULL;
    \Drupal::moduleHandler()->alter('decoupled_webform_data_presubmit', $webform_data, $error_message);
    $this->assertNull($error_message, print_r($error_message, TRUE));

    $storage = \Drupal::entityTypeManager()->getStorage('user');
    $storage->resetCache();
    $accounts = $storage->loadByProperties(['mail' => $this->mail]);
    $this->assertCount(1, $accounts);
    return reset($accounts);
  }

  /**
   * Without configured role, the account has none.
   */
  public function testNoRoleByDefault(): void {
    $this->setDefaultRoles([]);
    $this->assertSame([], $this->register()->getRoles(TRUE));
  }

  /**
   * The configured default role is added to the account.
   */
  public function testConfiguredRoleIsGranted(): void {
    $role = $this->createTestRole();
    $this->setDefaultRoles([$role->id()]);
    $this->assertSame([$role->id()], $this->register()->getRoles(TRUE));
  }

  /**
   * A configured administrator, locked or unknown role is ignored.
   */
  public function testConfiguredAdminRoleIsIgnored(): void {
    $role = $this->createTestRole();
    $admin_role = $this->createTestRole(TRUE);
    $this->setDefaultRoles([
      $admin_role->id(),
      'authenticated',
      'anonymous',
      'ep_reg_missing_role',
      $role->id(),
    ]);
    $this->assertSame([$role->id()], $this->register()->getRoles(TRUE));
  }

  /**
   * Roles and status of the submitted data are ignored.
   */
  public function testSubmittedRolesAreIgnored(): void {
    $role = $this->createTestRole();
    $other_role = $this->createTestRole();
    $admin_role = $this->createTestRole(TRUE);
    $this->setDefaultRoles([$role->id()]);

    $submitted_roles = [$admin_role->id(), $other_role->id()];
    $account = $this->register(['roles' => $submitted_roles, 'uid' => 1]);
    $this->assertSame([$role->id()], $account->getRoles(TRUE));
    $this->assertNotEquals(1, $account->id());

    $this->setDefaultRoles([]);
    $account->delete();
    $account = $this->register(['roles' => $submitted_roles]);
    $this->assertSame([], $account->getRoles(TRUE));
  }

}
