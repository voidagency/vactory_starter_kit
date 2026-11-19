<?php

namespace Drupal\Tests\vactory_decoupled\Kernel;

use Drupal\Component\Serialization\Yaml;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\jsonapi\JsonApiResource\JsonApiDocumentTopLevel;
use Drupal\jsonapi\JsonApiResource\LinkCollection;
use Drupal\jsonapi\JsonApiResource\NullIncludedData;
use Drupal\jsonapi\JsonApiResource\ResourceObjectData;
use Drupal\jsonapi_user_resources\Events\RegistrationEvent;
use Drupal\jsonapi_user_resources\Events\UserResourcesEvents;
use Drupal\KernelTests\KernelTestBase;
use Drupal\user\Entity\Role;
use Drupal\user\Entity\User;
use Drupal\user\UserInterface;
use Drupal\vactory_decoupled\EventSubscriber\UserRegistrationRolesSubscriber;

/**
 * Tests the subscriber that drops roles from JSON:API registrations.
 *
 * The vactory_decoupled module has too many dependencies to be installed in
 * a Kernel test: its subscriber service is registered from the module's own
 * services.yml, so a missing service definition fails here too.
 *
 * @coversDefaultClass \Drupal\vactory_decoupled\EventSubscriber\UserRegistrationRolesSubscriber
 * @group vactory_decoupled
 */
class UserRegistrationRolesSubscriberTest extends KernelTestBase {

  /**
   * Service id of the subscriber.
   */
  const SERVICE = 'vactory_decoupled.user_registration_roles_subscriber';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'user'];

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container) {
    parent::register($container);
    $services = Yaml::decode(file_get_contents(dirname(__DIR__, 3) . '/vactory_decoupled.services.yml'));
    if (isset($services['services'][self::SERVICE])) {
      $definition = $services['services'][self::SERVICE];
      $container->register(self::SERVICE, $definition['class'])
        ->addTag('event_subscriber');
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    foreach (['editor', 'administrator'] as $id) {
      Role::create(['id' => $id, 'label' => $id])->save();
    }
  }

  /**
   * Builds an unsaved account as the registration resource would.
   */
  protected function createAccount(): UserInterface {
    return User::create([
      'name' => 'registrant',
      'mail' => 'registrant@example.com',
      'pass' => 'secret',
      'status' => 1,
      'langcode' => 'en',
      'roles' => ['editor', 'administrator'],
    ]);
  }

  /**
   * Dispatches a registration event for the account.
   */
  protected function dispatch(UserInterface $account, string $event_name): void {
    $document = new JsonApiDocumentTopLevel(new ResourceObjectData([]), new NullIncludedData(), new LinkCollection([]));
    $this->container->get('event_dispatcher')
      ->dispatch(new RegistrationEvent($account, $document), $event_name);
  }

  /**
   * The service is declared and subscribes to the validate event only.
   *
   * @covers ::getSubscribedEvents
   */
  public function testServiceDefinition(): void {
    $this->assertTrue($this->container->has(self::SERVICE), 'The subscriber service is declared in vactory_decoupled.services.yml.');
    $this->assertInstanceOf(UserRegistrationRolesSubscriber::class, $this->container->get(self::SERVICE));
    $expected = [
      UserResourcesEvents::REGISTRATION_VALIDATE => [
        'onRegistrationValidate',
        1000,
      ],
    ];
    $this->assertSame($expected, UserRegistrationRolesSubscriber::getSubscribedEvents());
  }

  /**
   * Roles are removed on validate, the rest of the account is untouched.
   *
   * @covers ::onRegistrationValidate
   */
  public function testValidateRemovesRoles(): void {
    $account = $this->createAccount();
    $this->dispatch($account, UserResourcesEvents::REGISTRATION_VALIDATE);

    $this->assertSame([], $account->getRoles(TRUE));
    $this->assertTrue($account->isNew());
    $this->assertSame('registrant', $account->getAccountName());
    $this->assertSame('registrant@example.com', $account->getEmail());
    $this->assertSame('secret', $account->getPassword());
    $this->assertTrue($account->isActive());
    $this->assertSame('en', $account->language()->getId());

    // The account saves without any role.
    $account->save();
    $this->assertSame([], User::load($account->id())->getRoles(TRUE));
  }

  /**
   * Other events, including the complete event, leave roles alone.
   *
   * @covers ::getSubscribedEvents
   */
  public function testOtherEventsIgnored(): void {
    $events = [
      UserResourcesEvents::REGISTRATION_COMPLETE,
      UserResourcesEvents::PASSWORD_RESET,
    ];
    foreach ($events as $event_name) {
      $account = $this->createAccount();
      $this->dispatch($account, $event_name);
      $this->assertSame(['editor', 'administrator'], $account->getRoles(TRUE), $event_name);
    }
  }

  /**
   * An account without roles goes through unchanged.
   *
   * @covers ::onRegistrationValidate
   */
  public function testNoRoles(): void {
    $account = User::create(['name' => 'plain', 'mail' => 'plain@example.com']);
    $this->dispatch($account, UserResourcesEvents::REGISTRATION_VALIDATE);
    $this->assertSame([], $account->getRoles(TRUE));
    $this->assertSame('plain', $account->getAccountName());
  }

}
