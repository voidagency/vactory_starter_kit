<?php

namespace Drupal\Tests\vactory_quiz\Kernel;

use Drupal\Component\Serialization\Yaml;
use Drupal\Core\Config\FileStorage;
use Drupal\Core\Config\UnmetDependenciesException;
use Drupal\Core\Extension\Dependency;
use Drupal\KernelTests\KernelTestBase;

/**
 * The shipped config installs with the declared modules (Redmine #71388).
 *
 * Drupal refuses to install a module whose config/install has a dependency
 * that is not met. Config that needs something no declared module provides
 * (a view mode of the profile, an optional module) is in config/optional:
 * Drupal installs it once its dependencies are met.
 *
 * @group vactory_quiz
 * @group dependencies
 */
class QuizConfigInstallTest extends KernelTestBase {

  /**
   * Config of the profile, which no module provides.
   */
  const CARD_VIEW_MODE = 'core.entity_view_mode.node.card';

  /**
   * Shipped config that depends on the view mode of the profile.
   */
  const CARD_DISPLAY = 'core.entity_view_display.node.vactory_quiz.card';

  /**
   * Shipped config of an optional module.
   */
  const JSONAPI_RESOURCE = 'jsonapi_extras.jsonapi_resource_config.node--vactory_quiz';

  /**
   * Declared modules whose own dependencies this test does not install.
   *
   * The test installs the config they provide to vactory_quiz instead.
   */
  const HEAVY_MODULES = ['vactory_core', 'vactory_field'];

  /**
   * Modules required by the modules declared in vactory_quiz.info.yml.
   *
   * @var string[]
   */
  protected static $modules = [
    'system',
    'user',
    'filter',
    'file',
    'image',
    'media',
    'path_alias',
    'views',
    'better_exposed_filters',
  ];

  /**
   * The shipped config has options of modules this test does not enable.
   *
   * @var bool
   */
  protected $strictConfigSchema = FALSE;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // The module lives in the profile: it is only discovered when the
    // profile is the install profile.
    $this->setInstallProfile('vactory_starter_kit');
    // Only the modules declared in vactory_quiz.info.yml: config that needs
    // another module is not installable here.
    $this->enableModules(array_diff($this->declaredModules(), self::HEAVY_MODULES));
    $this->enableModules(['vactory_quiz']);
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installEntitySchema('taxonomy_term');
    $this->installEntitySchema('path_alias');
    $this->installConfig(['system', 'field', 'filter', 'node']);
  }

  /**
   * Returns the path of the module.
   */
  protected function modulePath(): string {
    return dirname(__DIR__, 3);
  }

  /**
   * Returns the path of the profile.
   */
  protected function profilePath(): string {
    return dirname($this->modulePath(), 2);
  }

  /**
   * Returns the modules declared in vactory_quiz.info.yml.
   */
  protected function declaredModules(): array {
    $info = Yaml::decode(file_get_contents($this->modulePath() . '/vactory_quiz.info.yml'));
    return array_map(
      fn ($dependency) => Dependency::createFromString($dependency)->getName(),
      $info['dependencies'] ?? []
    );
  }

  /**
   * Returns the names of the config shipped in a directory of the module.
   */
  protected function shippedConfig(string $directory): array {
    return (new FileStorage($this->modulePath() . '/config/' . $directory))->listAll();
  }

  /**
   * Installs the config vactory_field provides to vactory_quiz.
   */
  protected function installVactoryFieldConfig(): void {
    $this->assertContains('vactory_field', $this->declaredModules());
    $storage = new FileStorage($this->profilePath() . '/modules/vactory_field/config/install');
    $names = [
      'taxonomy.vocabulary.tags',
      'field.storage.node.field_vactory_tags',
    ];
    foreach ($names as $name) {
      $data = $storage->read($name);
      $this->assertIsArray($data, "vactory_field provides $name.");
      $entity_type_id = $this->container->get('config.manager')->getEntityTypeIdByName($name);
      $this->container->get('entity_type.manager')->getStorage($entity_type_id)->create($data)->save();
    }
  }

  /**
   * Runs the check "drush en" does before it installs the module.
   */
  protected function checkConfigurationToInstall(): void {
    $this->container->get('config.installer')->checkConfigurationToInstall('module', 'vactory_quiz');
  }

  /**
   * Asserts that a config object exists, or not.
   */
  protected function assertConfigExists(string $name, bool $exists = TRUE): void {
    $this->assertSame($exists, !$this->config($name)->isNew(), $name . ($exists ? ' is installed.' : ' is not installed.'));
  }

  /**
   * Without the config of vactory_field, Drupal refuses the installation.
   */
  public function testInstallNeedsVactoryFieldConfig(): void {
    try {
      $this->checkConfigurationToInstall();
      $this->fail('The tags field needs the storage and the vocabulary of vactory_field.');
    }
    catch (UnmetDependenciesException $e) {
      $this->assertSame([
        'field.field.node.vactory_quiz.field_vactory_tags' => [
          'field.storage.node.field_vactory_tags',
          'taxonomy.vocabulary.tags',
        ],
      ], $e->getConfigObjects());
    }
  }

  /**
   * With the declared modules, config/install has no unmet dependency.
   */
  public function testInstallWithDeclaredModules(): void {
    $this->installVactoryFieldConfig();
    // Neither the profile config nor jsonapi_extras are there.
    $this->assertConfigExists(self::CARD_VIEW_MODE, FALSE);
    $this->assertFalse($this->container->get('module_handler')->moduleExists('jsonapi_extras'));

    $this->checkConfigurationToInstall();
    $this->installConfig(['vactory_quiz']);

    $installed = $this->shippedConfig('install');
    $this->assertNotEmpty($installed);
    foreach ($installed as $name) {
      $this->assertConfigExists($name);
    }
    // Optional config waits for its dependencies.
    $this->assertSame([self::CARD_DISPLAY, self::JSONAPI_RESOURCE], $this->shippedConfig('optional'));
    $this->assertConfigExists(self::CARD_DISPLAY, FALSE);
    $this->assertConfigExists(self::JSONAPI_RESOURCE, FALSE);
  }

  /**
   * The card display is installed once the profile view mode exists.
   */
  public function testCardDisplayFollowsTheProfileViewMode(): void {
    $this->installVactoryFieldConfig();
    $this->installConfig(['vactory_quiz']);
    $this->assertConfigExists(self::CARD_DISPLAY, FALSE);

    // Only the profile provides the view mode: no module can be declared.
    $view_mode = (new FileStorage($this->profilePath() . '/config/install'))->read(self::CARD_VIEW_MODE);
    $this->assertIsArray($view_mode, 'The profile provides the card view mode.');
    $this->container->get('entity_type.manager')->getStorage('entity_view_mode')->create($view_mode)->save();
    $this->container->get('config.installer')->installOptionalConfig();

    $this->assertConfigExists(self::CARD_DISPLAY);
    $this->assertContains(self::CARD_VIEW_MODE, $this->config(self::CARD_DISPLAY)->get('dependencies.config'));
    $this->assertConfigExists(self::JSONAPI_RESOURCE, FALSE);
  }

  /**
   * The JSON:API resource declares the modules it needs.
   */
  public function testJsonapiResourceDependencies(): void {
    $resource = (new FileStorage($this->modulePath() . '/config/optional'))->read(self::JSONAPI_RESOURCE);
    $this->assertIsArray($resource);
    // The provider of the config (jsonapi_extras) is checked by Drupal from
    // its name. The enhancers are plugins of vactory_decoupled.
    $this->assertSame(['vactory_decoupled', 'vactory_quiz'], $resource['dependencies']['enforced']['module']);
    $enhancers = array_unique(array_filter(array_map(
      fn ($field) => $field['enhancer']['id'] ?? '',
      $resource['resourceFields']
    )));
    $this->assertNotEmpty($enhancers);
    $plugins = '';
    foreach (glob($this->profilePath() . '/modules/vactory_decoupled/src/Plugin/jsonapi/FieldEnhancer/*.php') as $file) {
      $plugins .= file_get_contents($file);
    }
    foreach ($enhancers as $enhancer) {
      $this->assertStringContainsString('id = "' . $enhancer . '"', $plugins, "vactory_decoupled provides the enhancer $enhancer.");
    }
    // vactory_decoupled requires jsonapi_extras.
    $info = Yaml::decode(file_get_contents($this->profilePath() . '/modules/vactory_decoupled/vactory_decoupled.info.yml'));
    $this->assertContains('jsonapi_extras', $info['dependencies']);
  }

}
