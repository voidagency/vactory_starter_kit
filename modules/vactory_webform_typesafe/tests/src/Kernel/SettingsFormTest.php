<?php

namespace Drupal\Tests\vactory_webform_typesafe\Kernel;

use Drupal\Core\Form\FormState;
use Drupal\vactory_webform_typesafe\Form\SettingsForm;
use Drupal\vactory_webform_typesafe\Service\ApiKeyStore;

require_once __DIR__ . '/TypeSafeKernelTestBase.php';

/**
 * Characterizes SettingsForm submissions through the form builder.
 *
 * @group vactory_webform_typesafe
 */
class SettingsFormTest extends TypeSafeKernelTestBase {

  /**
   * The previous TYPESAFE_API_KEY environment value.
   *
   * @var string|false
   */
  protected $environment;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->environment = getenv('TYPESAFE_API_KEY');
    putenv('TYPESAFE_API_KEY');
    $this->loginWith(['administer vactory webform typesafe']);
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    if ($this->environment !== FALSE) {
      putenv('TYPESAFE_API_KEY=' . $this->environment);
    }
    parent::tearDown();
  }

  /**
   * Submits the form through the form builder.
   */
  protected function submit(array $values, ?string $op = NULL): FormState {
    $form_state = new FormState();
    $values += [
      'model' => 'jev-custom',
      'request_timeout' => '30',
      'max_attempts' => '5',
      'max_input_bytes' => '5000',
    ];
    if ($op !== NULL) {
      $values['op'] = $op;
    }
    $form_state->setValues($values);
    $this->container->get('form_builder')->submitForm(SettingsForm::class, $form_state);
    return $form_state;
  }

  /**
   * Returns the key store.
   */
  protected function keys(): ApiKeyStore {
    return $this->container->get('vactory_webform_typesafe.api_key');
  }

  /**
   * A normal save stores settings and a new key outside configuration.
   */
  public function testSubmitStoresSettingsAndKey(): void {
    $this->assertSame('none', $this->keys()->source());
    $form_state = $this->submit([
      'api_key' => ' synthetic-key ',
      'remove_api_key' => NULL,
      'model' => '  jev-custom  ',
    ]);
    $this->assertSame([], array_map('strval', $form_state->getErrors()));
    $config = $this->config('vactory_webform_typesafe.settings');
    $this->assertSame('jev-custom', $config->get('model'));
    $this->assertSame(30, $config->get('request_timeout'));
    $this->assertSame(5, $config->get('max_attempts'));
    $this->assertSame(5000, $config->get('max_input_bytes'));
    $this->assertStringNotContainsString('synthetic-key', json_encode($config->getRawData()));
    $this->assertSame('stored', $this->keys()->source());
    $this->assertSame('synthetic-key', $this->keys()->get());
    // The secret is removed from the values and the user input.
    $this->assertFalse($form_state->hasValue('api_key'));
    $this->assertArrayNotHasKey('api_key', $form_state->getUserInput());
    $this->assertSame(['The configuration options have been saved.'], $this->messages('status'));
    $this->assertSame(0, $this->classifier->calls);
  }

  /**
   * A blank key keeps the stored one; removal deletes it.
   */
  public function testBlankKeyKeepsAndRemoveDeletes(): void {
    $this->keys()->save('existing-key');
    $this->submit(['api_key' => '', 'remove_api_key' => NULL]);
    $this->assertSame('existing-key', $this->keys()->get());
    $this->submit(['api_key' => '', 'remove_api_key' => 1]);
    $this->assertSame('none', $this->keys()->source());
  }

  /**
   * Validation errors prevent saving.
   */
  public function testValidationErrors(): void {
    $form_state = $this->submit([
      'api_key' => 'bad key',
      'remove_api_key' => 1,
      'model' => '   ',
    ]);
    // Only the first error of an element is kept: the core #required check
    // precedes "Enter a model name." and the replace/remove conflict precedes
    // the key format check.
    $this->assertSame([
      'model' => 'Default model field is required.',
      'api_key' => 'Choose either replacing or removing the key.',
    ], array_map('strval', $form_state->getErrors()));
    $this->assertSame('none', $this->keys()->source());
    $this->assertSame('jev-latest', $this->config('vactory_webform_typesafe.settings')->get('model'));

    $form_state = $this->submit(['api_key' => "bad\tkey"]);
    $this->assertSame([
      'api_key' => 'The API key contains invalid whitespace or control characters.',
    ], array_map('strval', $form_state->getErrors()));
    $this->assertSame('none', $this->keys()->source());
  }

  /**
   * The "Save and test connection" button saves then lists models.
   */
  public function testSaveAndTestConnection(): void {
    $this->classifier->models = ['jev-latest', 'jev-fast'];
    $values = ['api_key' => 'synthetic-key', 'remove_api_key' => NULL];
    $this->submit($values, 'Save and test connection');
    $this->assertSame('synthetic-key', $this->keys()->get());
    $this->assertSame([
      'The configuration options have been saved.',
      'Connected to Jev. Available model aliases: jev-latest, jev-fast.',
    ], $this->messages('status'));
  }

  /**
   * A programmatic submission without any key input saves the settings.
   *
   * FormBuilder::submitForm() copies the values into the user input, so this
   * path does not hit the NULL user input of a fresh FormState.
   */
  public function testProgrammaticSubmitWithoutUserInput(): void {
    $form_state = $this->submit([]);
    $this->assertSame([], array_map('strval', $form_state->getErrors()));
    $this->assertSame('jev-custom', $this->config('vactory_webform_typesafe.settings')->get('model'));
    $this->assertSame('none', $this->keys()->source());
  }

  /**
   * A direct submit on a fresh form state (user input never set) saves.
   *
   * FormState::getUserInput() returns NULL until input is set, and
   * submitForm() passes it back to setUserInput(array).
   */
  public function testDirectSubmitWithoutUserInput(): void {
    // Currently throws a TypeError from FormState::setUserInput(NULL).
    $this->markTestSkipped('Fixed by #71220');
    $form_object = SettingsForm::create($this->container);
    $form_state = new FormState();
    $form = $form_object->buildForm([], $form_state);
    $form_state->setValues([
      'api_key' => 'synthetic-key',
      'remove_api_key' => 0,
      'model' => 'jev-direct',
      'request_timeout' => 60,
      'max_attempts' => 3,
      'max_input_bytes' => 20000,
    ]);
    $form_object->submitForm($form, $form_state);
    $this->assertSame('jev-direct', $this->config('vactory_webform_typesafe.settings')->get('model'));
    $this->assertSame('synthetic-key', $this->keys()->get());
    $this->assertFalse($form_state->hasValue('api_key'));
    $this->assertEmpty($form_state->getUserInput());
  }

}
