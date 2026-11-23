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
   * Node voted on by the test.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $node;

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    // Remove the votes before votingapi is uninstalled.
    if ($this->node) {
      $storage = \Drupal::entityTypeManager()->getStorage('vote');
      $storage->delete($storage->loadByProperties([
        'entity_type' => 'node',
        'entity_id' => $this->node->id(),
      ]));
    }
    parent::tearDown();
  }

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
   * Vote and undo only answer POST, with the CSRF header for a session.
   */
  public function testVoteRoutesRequirePost(): void {
    $this->node = $this->createNode([
      'type' => 'vactory_page',
      'title' => 'Rated page',
      'status' => 1,
      'moderation_state' => 'published',
    ]);
    $this->loginAs($this->createUser([
      'can vote entity',
      'Undo entity vote',
      'access content',
    ]));
    $vote_url = $this->baseUrl . '/api/rate/node/' . $this->node->id() . '/fivestar/4';
    $undo_url = $this->baseUrl . '/api/rate/undo/node/' . $this->node->id();
    $token = $this->drupalGet($this->baseUrl . '/session/token');

    // A read request does not vote.
    $this->drupalGet($vote_url);
    $this->assertSession()->statusCodeEquals(405);
    $this->assertSame(0, $this->countVotes());

    // A session request without the CSRF header is refused.
    $this->assertSame(403, $this->post($vote_url));
    $this->assertSame(0, $this->countVotes());

    $this->assertSame(200, $this->post($vote_url, $token));
    $vote = json_decode($this->getSession()->getPage()->getContent(), TRUE);
    $this->assertTrue($vote['status']);
    $this->assertSame(1, $this->countVotes());

    // Same rules to undo the vote.
    $this->drupalGet($undo_url);
    $this->assertSession()->statusCodeEquals(405);
    $this->assertSame(403, $this->post($undo_url));
    $this->assertSame(1, $this->countVotes());

    $this->assertSame(200, $this->post($undo_url, $token));
    $this->assertSame(0, $this->countVotes());
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

  /**
   * Sends a POST request with the session of the logged in user.
   *
   * @param string $url
   *   Absolute URL.
   * @param string|null $token
   *   Value of the X-CSRF-Token header, none when NULL.
   *
   * @return int
   *   The response status code.
   */
  protected function post(string $url, ?string $token = NULL): int {
    $server = $token === NULL ? [] : ['HTTP_X_CSRF_TOKEN' => $token];
    $this->getSession()->getDriver()->getClient()->request('POST', $url, [], [], $server);
    return (int) $this->getSession()->getStatusCode();
  }

  /**
   * Counts the votes stored for the test node.
   */
  protected function countVotes(): int {
    $storage = \Drupal::entityTypeManager()->getStorage('vote');
    $storage->resetCache();
    return count($storage->loadByProperties([
      'entity_type' => 'node',
      'entity_id' => $this->node->id(),
    ]));
  }

}
