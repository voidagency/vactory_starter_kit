<?php

namespace Drupal\Tests\vactory_decoupled_webform\ExistingSite;

use Drupal\Core\Serialization\Yaml;
use Drupal\Tests\vactory_core\Functional\VactoryExistingSiteBase;
use Drupal\user\Entity\Role;
use Drupal\user\RoleInterface;

/**
 * Webform configuration must not be readable by anonymous/authenticated users.
 *
 * The configuration of a webform contains its handlers (recipient emails,
 * remote post URLs and headers...). Webform restricts the permission to
 * trusted roles.
 *
 * @group vactory_decoupled_webform
 */
class WebformConfigurationAccessTest extends VactoryExistingSiteBase {

  const PERMISSION = 'access any webform configuration';

  const ROLES = [RoleInterface::ANONYMOUS_ID, RoleInterface::AUTHENTICATED_ID];

  /**
   * Permission state of the roles before the test, restored afterwards.
   *
   * @var bool[]
   */
  protected array $initialState = [];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    \Drupal::moduleHandler()->loadInclude('vactory_decoupled_webform', 'install');
    foreach (self::ROLES as $rid) {
      $this->initialState[$rid] = Role::load($rid)->hasPermission(self::PERMISSION);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    foreach ($this->initialState as $rid => $granted) {
      $granted
        ? user_role_grant_permissions($rid, [self::PERMISSION])
        : user_role_revoke_permissions($rid, [self::PERMISSION]);
    }
    parent::tearDown();
  }

  /**
   * Asserts that no generic role has the permission.
   */
  protected function assertPermissionNotGranted(): void {
    foreach (self::ROLES as $rid) {
      $this->assertFalse(Role::load($rid)->hasPermission(self::PERMISSION), "Role $rid must not have '" . self::PERMISSION . "'.");
    }
  }

  /**
   * The permissions granted at install do not include the restricted one.
   */
  public function testInstallDoesNotGrantPermission(): void {
    foreach (self::ROLES as $rid) {
      user_role_revoke_permissions($rid, [self::PERMISSION]);
    }
    _vactory_decoupled_webform_grant_permissions();
    $this->assertPermissionNotGranted();
  }

  /**
   * The update hook revokes the permission granted by previous versions.
   */
  public function testUpdateRevokesPermission(): void {
    foreach (self::ROLES as $rid) {
      user_role_grant_permissions($rid, [self::PERMISSION]);
    }
    $this->assertTrue(function_exists('vactory_decoupled_webform_update_10102'), 'Update hook 10102 exists.');
    vactory_decoupled_webform_update_10102();
    $this->assertPermissionNotGranted();
  }

  /**
   * Anonymous JSON:API requests do not return a webform's configuration.
   */
  public function testAnonymousJsonApi(): void {
    foreach (self::ROLES as $rid) {
      user_role_revoke_permissions($rid, [self::PERMISSION]);
    }
    $webform = $this->createWebform([
      'id' => 'test_config_' . strtolower($this->randomMachineName(8)),
      'title' => 'Config access test',
      'elements' => Yaml::encode([
        'name' => ['#type' => 'textfield', '#title' => 'Name'],
      ]),
    ]);
    $prefix = \Drupal::config('jsonapi_extras.settings')->get('path_prefix') ?: 'jsonapi';
    $langcode = \Drupal::languageManager()->getDefaultLanguage()->getId();
    $url = $this->baseUrl . '/' . $langcode . '/' . $prefix . '/webform/webform/' . $webform->uuid();
    $response = \Drupal::httpClient()->get($url, [
      'headers' => ['Accept' => 'application/vnd.api+json'],
      'http_errors' => FALSE,
    ]);
    $this->assertSame(403, $response->getStatusCode());
    $this->assertStringNotContainsString('Config access test', (string) $response->getBody());
  }

}
