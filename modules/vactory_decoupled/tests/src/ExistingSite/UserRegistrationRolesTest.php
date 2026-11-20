<?php

namespace Drupal\Tests\vactory_decoupled\ExistingSite;

use Drupal\Core\Url;
use Drupal\user\Entity\Role;
use Drupal\user\Entity\User;
use Drupal\user\UserInterface;
use weitzman\DrupalTestTraits\ExistingSiteBase;

/**
 * Roles sent to the JSON:API registration resource are not kept.
 *
 * The jsonapi_user_resources module maps the whole payload onto the new
 * account without field access checks, so an anonymous POST could otherwise
 * register itself as administrator.
 *
 * @see \Drupal\vactory_decoupled\EventSubscriber\UserRegistrationRolesSubscriber
 *
 * @group vactory_decoupled
 */
class UserRegistrationRolesTest extends ExistingSiteBase {

  /**
   * Original "user.settings:register" value, when it was changed.
   *
   * @var string|null
   */
  protected $originalRegister;

  /**
   * Name of the account registered by the test.
   *
   * @var string
   */
  protected $name;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->name = 'reg_roles_' . $this->randomMachineName(8);
    $config = \Drupal::configFactory()->getEditable('user.settings');
    if ($config->get('register') === UserInterface::REGISTER_ADMINISTRATORS_ONLY) {
      $this->originalRegister = $config->get('register');
      $config->set('register', UserInterface::REGISTER_VISITORS_ADMINISTRATIVE_APPROVAL)->save();
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    // Delete the account even if an assertion failed before it was marked.
    $accounts = \Drupal::entityTypeManager()->getStorage('user')
      ->loadByProperties(['name' => $this->name]);
    foreach ($accounts as $account) {
      $account->delete();
    }
    if ($this->originalRegister !== NULL) {
      \Drupal::configFactory()->getEditable('user.settings')
        ->set('register', $this->originalRegister)->save();
    }
    parent::tearDown();
  }

  /**
   * An anonymous registration asking for roles gets none.
   */
  public function testRolesInPayloadAreDropped(): void {
    $requested = array_filter(
      [Role::load('administrator'), Role::load('webmaster')],
    );
    if (!$requested) {
      $role = Role::create([
        'id' => strtolower($this->randomMachineName()),
        'label' => 'Registration test',
      ]);
      $role->save();
      $this->markEntityForCleanup($role);
      $requested = [$role];
    }

    $attributes = [
      'name' => $this->name,
      'mail' => $this->name . '@example.com',
    ];
    // Fill the site's own required text fields (first name, last name...).
    $resource_type = \Drupal::service('jsonapi.resource_type.repository')->get('user', 'user');
    $definitions = \Drupal::service('entity_field.manager')->getFieldDefinitions('user', 'user');
    foreach ($definitions as $field_name => $definition) {
      if ($definition->isRequired() && !$definition->getFieldStorageDefinition()->isBaseField()
        && in_array($definition->getType(), ['string', 'string_long'], TRUE)) {
        $attributes[$resource_type->getPublicName($field_name)] = 'Test';
      }
    }
    if (!\Drupal::config('user.settings')->get('verify_mail')) {
      $attributes['pass'] = ['value' => $this->randomString(16) . 'aA1!'];
    }
    $relationships = [];
    foreach ($requested as $role) {
      $relationships[] = [
        'type' => 'user_role--user_role',
        'id' => $role->uuid(),
      ];
    }
    $payload = [
      'data' => [
        'type' => 'user--user',
        'attributes' => $attributes,
        'relationships' => ['roles' => ['data' => $relationships]],
      ],
    ];

    $url = Url::fromRoute('jsonapi_user_resources.registration', [], ['absolute' => TRUE])->toString();
    $response = \Drupal::httpClient()->post($url, [
      'headers' => [
        'Accept' => 'application/vnd.api+json',
        'Content-Type' => 'application/vnd.api+json',
      ],
      'body' => json_encode($payload),
      'http_errors' => FALSE,
    ]);
    $this->assertSame(201, $response->getStatusCode(), (string) $response->getBody());

    \Drupal::entityTypeManager()->getStorage('user')->resetCache();
    $accounts = \Drupal::entityTypeManager()->getStorage('user')
      ->loadByProperties(['name' => $this->name]);
    $this->assertCount(1, $accounts);
    $account = reset($accounts);
    assert($account instanceof User);
    $this->assertSame([], $account->getRoles(TRUE), 'No role from the payload is kept.');
    $this->assertSame($this->name . '@example.com', $account->getEmail());
  }

}
