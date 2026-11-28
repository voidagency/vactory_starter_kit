<?php

namespace Drupal\Tests\vactory_quiz\Kernel;

use Drupal\Core\Session\AccountInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\NodeType;
use Drupal\user\Entity\Role;
use Drupal\Tests\user\Traits\UserCreationTrait;

/**
 * Tests the access to the routes of vactory_quiz.
 *
 * @group vactory_quiz
 */
class QuizRouteAccessTest extends KernelTestBase {

  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'field',
    'text',
    'node',
    'vactory_quiz',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installSchema('system', ['sequences']);
    // Uid 1 is the super user: create it first, so the accounts of the test
    // only have their own permissions.
    $this->createUser();
    // The permissions of the quiz content type exist with the type.
    NodeType::create(['type' => 'vactory_quiz', 'name' => 'Quiz'])->save();
    // As on a site: visitors can read content.
    Role::create(['id' => 'anonymous', 'label' => 'Anonymous'])
      ->grantPermission('access content')
      ->save();
    $this->container->get('router.builder')->rebuild();
  }

  /**
   * Checks the access to a named route.
   */
  protected function canAccess(string $route, AccountInterface $account): bool {
    return $this->container->get('access_manager')->checkNamedRoute($route, [], $account);
  }

  /**
   * The answers dialog is for the editors of quizzes only.
   */
  public function testAnswersModalForm(): void {
    $route = 'vactory_quiz.answers_modal_form';
    $this->assertFalse($this->canAccess($route, $this->container->get('current_user')->getAccount()), 'Anonymous.');
    $this->assertFalse($this->canAccess($route, $this->createUser(['access content'])), 'Authenticated reader.');
    $this->assertTrue($this->canAccess($route, $this->createUser(['create vactory_quiz content'])), 'Quiz creator.');
    $this->assertTrue($this->canAccess($route, $this->createUser(['edit own vactory_quiz content'])), 'Quiz editor (own).');
    $this->assertTrue($this->canAccess($route, $this->createUser(['edit any vactory_quiz content'])), 'Quiz editor (any).');
    $this->assertTrue($this->canAccess($route, $this->createUser(['bypass node access'])), 'Node administrator.');
  }

  /**
   * The quiz history is read and written by a signed-in user only.
   *
   * All anonymous visitors share uid 0: one would read the answers of the
   * others.
   */
  public function testHistoryRoutesNeedAnAccount(): void {
    $anonymous = $this->container->get('current_user')->getAccount();
    $this->assertFalse($this->canAccess('vactory_quiz.get', $anonymous));
    $this->assertFalse($this->canAccess('vactory_quiz.post', $anonymous));
    $reader = $this->createUser(['access content']);
    $this->assertTrue($this->canAccess('vactory_quiz.get', $reader));
    $this->assertTrue($this->canAccess('vactory_quiz.post', $reader));
  }

  /**
   * The paths of the history routes do not change.
   */
  public function testHistoryPaths(): void {
    $routes = $this->container->get('router.route_provider');
    $this->assertSame('/api/quiz_history/get', $routes->getRouteByName('vactory_quiz.get')->getPath());
    $this->assertSame('/quiz_history/post', $routes->getRouteByName('vactory_quiz.post')->getPath());
  }

}
