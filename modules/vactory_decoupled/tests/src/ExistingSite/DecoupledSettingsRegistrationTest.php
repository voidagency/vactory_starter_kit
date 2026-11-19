<?php

namespace Drupal\Tests\vactory_decoupled\ExistingSite;

use Drupal\Core\Form\FormState;
use Drupal\user\Entity\Role;
use Drupal\vactory_decoupled\Form\DecoupledSettingsForm;
use weitzman\DrupalTestTraits\ExistingSiteBase;

/**
 * Tests the "Registration" tab of the decoupled settings form.
 *
 * @coversDefaultClass \Drupal\vactory_decoupled\Form\DecoupledSettingsForm
 * @group vactory_decoupled
 */
class DecoupledSettingsRegistrationTest extends ExistingSiteBase {

  /**
   * The settings before the test, restored in tearDown().
   *
   * @var array
   */
  protected $originalSettings;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->originalSettings = \Drupal::config('vactory_decoupled.settings')->getRawData();
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    \Drupal::configFactory()->getEditable('vactory_decoupled.settings')
      ->setData($this->originalSettings)
      ->save();
    parent::tearDown();
  }

  /**
   * Builds the form as the given account.
   */
  protected function buildFormAs($account): array {
    $switcher = \Drupal::service('account_switcher');
    $switcher->switchTo($account);
    try {
      $form_object = \Drupal::classResolver(DecoupledSettingsForm::class);
      return \Drupal::formBuilder()->getForm($form_object);
    }
    finally {
      $switcher->switchBack();
    }
  }

  /**
   * The tab is hidden without "administer permissions".
   *
   * @covers ::buildForm
   */
  public function testHiddenWithoutAdministerPermissions(): void {
    $role = $this->createRole(['administer site configuration']);
    $user = $this->createUser();
    $user->addRole($role);
    $user->save();
    $form = $this->buildFormAs($user);
    $this->assertFalse($form['registration']['#access']);
  }

  /**
   * Only grantable roles are offered, and the choice is saved.
   *
   * @covers ::buildForm
   * @covers ::submitForm
   */
  public function testOptionsAndSubmit(): void {
    $member = $this->createRole([]);
    $admin = $this->createUser([], NULL, TRUE);
    $form = $this->buildFormAs($admin);
    $this->assertTrue($form['registration']['#access']);
    $options = array_keys($form['registration']['registration_default_roles']['#options']);
    $this->assertContains($member, $options);
    foreach (Role::loadMultiple() as $id => $role) {
      if ($role->isAdmin() || in_array($id, ['anonymous', 'authenticated'], TRUE)) {
        $this->assertNotContains($id, $options, "Role $id must not be offered.");
      }
    }

    // Submit the whole form as the admin, keeping the current values.
    $switcher = \Drupal::service('account_switcher');
    $switcher->switchTo($admin);
    try {
      $config = \Drupal::config('vactory_decoupled.settings');
      $form_state = (new FormState())->setValues([
        'routes' => $config->get('routes'),
        'cache_excluded_types' => $config->get('cache_excluded_types') ?? [],
        'auth_roles_excluded' => $config->get('auth_roles_excluded') ?? [],
        'iframe_paths' => $config->get('iframe_excluded_paths'),
        'registration_default_roles' => [$member => $member],
      ]);
      \Drupal::formBuilder()->submitForm(DecoupledSettingsForm::class, $form_state);
    }
    finally {
      $switcher->switchBack();
    }
    $this->assertSame([], $form_state->getErrors());
    $this->assertSame([$member], \Drupal::config('vactory_decoupled.settings')->get('registration_default_roles'));
    $this->markEntityForCleanup(Role::load($member));
  }

}
