<?php

namespace Drupal\Tests\vactory_rate\ExistingSite;

use Drupal\Core\Session\AccountInterface;
use Drupal\Tests\vactory_core\Functional\VactoryExistingSiteBase;

/**
 * Access rules of the vactory_rate routes, checked over HTTP.
 *
 * @group vactory_rate
 */
class RateRouteAccessTest extends VactoryExistingSiteBase {

  /**
   * {@inheritdoc}
   */
  protected array $modulesToInstall = ['votingapi', 'rate', 'vactory_rate'];

  /**
   * The settings form is reserved to site configuration administrators.
   */
  public function testSettingsFormAccess(): void {
    $url = $this->baseUrl . '/admin/config/rate/add-entity';

    $this->drupalGet($url);
    $this->assertSession()->statusCodeEquals(403);

    $this->loginAs($this->createUser(['access content']));
    $this->drupalGet($url);
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalLogout();

    $this->loginAs($this->createUser(['administer site configuration']));
    $this->drupalGet($url);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->fieldExists('content_types[vactory_page]');
  }

  /**
   * Logs in with a one-time login link.
   *
   * Same as drupalLogin(), which cannot be used here: on a decoupled site the
   * login form and the user page it lands on may redirect to the front, and
   * the front is not there during the tests.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Account created by createUser().
   */
  protected function loginAs(AccountInterface $account): void {
    $this->drupalGet(user_pass_reset_url($account) . '/login', [
      'query' => ['destination' => 'session/token'],
    ]);
    $cookie = \Drupal::service('session_configuration')->getOptions(\Drupal::request())['name'];
    // @see \Drupal\Tests\UiHelperTrait::drupalUserIsLoggedIn()
    $account->sessionId = $this->getSession()->getCookie($cookie);
    $this->assertTrue($this->drupalUserIsLoggedIn($account));
    $this->loggedInUser = $account;
  }

}
