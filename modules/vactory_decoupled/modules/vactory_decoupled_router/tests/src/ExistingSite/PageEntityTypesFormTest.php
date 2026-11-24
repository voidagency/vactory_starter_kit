<?php

namespace Drupal\Tests\vactory_decoupled_router\ExistingSite;

use Drupal\Core\Form\FormState;
use Drupal\Core\Session\AnonymousUserSession;
use Drupal\Core\Site\Settings;
use Drupal\Core\Url;
use Drupal\vactory_decoupled_router\Form\PageEntityTypesForm;
use weitzman\DrupalTestTraits\ExistingSiteBase;

/**
 * Tests the form of the page entity types of the router.
 *
 * @coversDefaultClass \Drupal\vactory_decoupled_router\Form\PageEntityTypesForm
 * @group vactory_decoupled_router
 */
class PageEntityTypesFormTest extends ExistingSiteBase {

  /**
   * Name of the config.
   */
  protected const CONFIG = 'vactory_decoupled_router.settings';

  /**
   * The config before the test (NULL when it does not exist).
   *
   * @var array|null
   */
  protected $originalConfig;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $config = \Drupal::config(self::CONFIG);
    $this->originalConfig = $config->isNew() ? NULL : $config->getRawData();
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    $config = \Drupal::configFactory()->getEditable(self::CONFIG);
    if ($this->originalConfig === NULL) {
      $config->delete();
    }
    else {
      $config->setData($this->originalConfig)->save();
    }
    parent::tearDown();
  }

  /**
   * Builds the form, without the form builder.
   *
   * The handlers of the form are called directly: nothing depends on the
   * route of the current request.
   */
  protected function buildForm(): array {
    $form_object = \Drupal::classResolver(PageEntityTypesForm::class);
    return $form_object->buildForm([], new FormState());
  }

  /**
   * Validates then submits the form with the given checked types.
   *
   * @return \Drupal\Core\Form\FormState
   *   The form state after the submission.
   */
  protected function submit(array $checked): FormState {
    $form_object = \Drupal::classResolver(PageEntityTypesForm::class);
    $form = $form_object->buildForm([], new FormState());
    $options = array_fill_keys(array_keys($form['page_entity_types']['#options']), 0);
    $form_state = new FormState();
    $form_state->setValues([
      'page_entity_types' => array_combine($checked, $checked) + $options,
    ]);
    $form_object->validateForm($form, $form_state);
    if (!$form_state->getErrors()) {
      $form_object->submitForm($form, $form_state);
    }
    return $form_state;
  }

  /**
   * The form asks for "administer site configuration".
   *
   * @covers ::buildForm
   */
  public function testAccess(): void {
    $url = Url::fromRoute('vactory_decoupled_router.page_entity_types');
    $this->assertSame('admin/config/system/vactory_router/page-entity-types', $url->getInternalPath());
    $this->assertFalse($url->access(new AnonymousUserSession()));
    $this->assertFalse($url->access($this->createUser(['access content'])));
    $this->assertTrue($url->access($this->createUser(['administer site configuration'])));
  }

  /**
   * The checkboxes: content entity types that have a canonical page.
   *
   * @covers ::buildForm
   * @covers ::getEntityTypeOptions
   */
  public function testOptionsAndDefaultValue(): void {
    \Drupal::configFactory()->getEditable(self::CONFIG)->delete();
    $form = $this->buildForm();
    $element = $form['page_entity_types'];
    $this->assertSame('checkboxes', $element['#type']);
    $this->assertTrue($element['#required']);
    foreach (['node', 'taxonomy_term', 'user'] as $entity_type) {
      $this->assertArrayHasKey($entity_type, $element['#options']);
    }
    // No config entity type, no content entity type without canonical page.
    foreach (['node_type', 'vactory_route', 'path_alias'] as $entity_type) {
      $this->assertArrayNotHasKey($entity_type, $element['#options']);
    }
    // Without the config (site not updated yet): node.
    $this->assertSame(['node'], array_values($element['#default_value']));

    \Drupal::configFactory()->getEditable(self::CONFIG)
      ->set('page_entity_types', ['node', 'taxonomy_term', 'removed_type'])
      ->save();
    $form = $this->buildForm();
    $this->assertSame(['node', 'taxonomy_term'], array_values($form['page_entity_types']['#default_value']));
  }

  /**
   * A warning tells when the list is defined in settings.php.
   *
   * @covers ::buildForm
   */
  public function testSettingsOverrideWarning(): void {
    $original = Settings::getAll();
    $messenger = \Drupal::messenger();
    $messenger->deleteAll();
    $this->buildForm();
    $this->assertSame([], $messenger->deleteAll());

    new Settings(['vactory_decoupled_router_page_entity_types' => ['node']] + $original);
    try {
      $this->buildForm();
    }
    finally {
      new Settings($original);
    }
    $messages = $messenger->deleteAll();
    $this->assertCount(1, $messages['warning'] ?? []);
    $this->assertStringContainsString('settings.php', (string) $messages['warning'][0]);
  }

  /**
   * The checked types are saved as a list.
   *
   * @covers ::validateForm
   * @covers ::submitForm
   */
  public function testSubmit(): void {
    $form_state = $this->submit(['node', 'taxonomy_term']);
    $this->assertSame([], $form_state->getErrors());
    $this->assertSame(['node', 'taxonomy_term'], \Drupal::config(self::CONFIG)->get('page_entity_types'));

    $form_state = $this->submit(['node']);
    $this->assertSame([], $form_state->getErrors());
    $this->assertSame(['node'], \Drupal::config(self::CONFIG)->get('page_entity_types'));

    // An empty list is refused: the router would not resolve any page.
    $form_state = $this->submit([]);
    $this->assertArrayHasKey('page_entity_types', $form_state->getErrors());
    $this->assertSame(['node'], \Drupal::config(self::CONFIG)->get('page_entity_types'));
  }

}
