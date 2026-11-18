<?php

namespace Drupal\Tests\vactory_decoupled_webform\ExistingSite;

use Drupal\user\Entity\Role;
use Drupal\user\RoleInterface;
use weitzman\DrupalTestTraits\ExistingSiteBase;

/**
 * Webform configuration must not be readable by anonymous/authenticated users.
 *
 * The configuration of a webform contains its handlers (recipient emails,
 * remote post URLs and headers...). Webform restricts the permission to
 * trusted roles.
 *
 * @group vactory_decoupled_webform
 */
class WebformConfigurationAccessTest extends ExistingSiteBase {

  const PERMISSION = 'access any webform configuration';

  const ROLES = [RoleInterface::ANONYMOUS_ID, RoleInterface::AUTHENTICATED_ID];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    \Drupal::moduleHandler()->loadInclude('vactory_decoupled_webform', 'install');
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    // Never leave the permission granted on the site under test.
    user_role_revoke_permissions(RoleInterface::ANONYMOUS_ID, [self::PERMISSION]);
    user_role_revoke_permissions(RoleInterface::AUTHENTICATED_ID, [self::PERMISSION]);
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
   * Installing the module does not grant the permission.
   */
  public function testInstallDoesNotGrantPermission(): void {
    vactory_decoupled_webform_install();
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
   * Anonymous JSON:API requests do not return webform configuration.
   */
  public function testAnonymousJsonApi(): void {
    $url = getenv('DTT_BASE_URL') . '/fr/api/webform/webform';
    $response = \Drupal::httpClient()->get($url, [
      'headers' => ['Accept' => 'application/vnd.api+json'],
      'http_errors' => FALSE,
    ]);
    $this->assertSame(200, $response->getStatusCode());
    $body = json_decode((string) $response->getBody(), TRUE);
    $this->assertSame([], $body['data']);
  }

}
