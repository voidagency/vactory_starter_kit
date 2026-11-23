<?php

namespace Drupal\Tests\vactory_quiz\Kernel;

use Drupal\Component\Serialization\Yaml;
use Drupal\Core\Extension\Dependency;
use Drupal\Core\Form\FormState;
use Drupal\KernelTests\KernelTestBase;
use Drupal\views\Entity\View;
use Drupal\views\Plugin\views\filter\StringFilter;
use Drupal\views\Views;

/**
 * The module declares what its shipped view uses (Redmine #71362).
 *
 * The "title" filter of the "vactory_quiz" view enables the autocomplete of
 * views_autocomplete_filters. Views picks the handler of a filter from views
 * data, not from the plugin_id of the config: without that module the view
 * still runs, with a plain text filter and no autocomplete.
 *
 * @group vactory_quiz
 * @group dependencies
 */
class QuizViewDependenciesTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'node',
    'field',
    'text',
    'filter',
    'views',
  ];

  /**
   * The shipped view also has options of modules this test does not enable.
   *
   * @var bool
   */
  protected $strictConfigSchema = FALSE;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installConfig(['node', 'views']);
  }

  /**
   * Returns the path of the module.
   */
  protected function modulePath(): string {
    return dirname(__DIR__, 3);
  }

  /**
   * Returns the shipped view.
   */
  protected function shippedView(): array {
    return Yaml::decode(file_get_contents($this->modulePath() . '/config/install/views.view.vactory_quiz.yml'));
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
   * Saves the shipped view and returns the handler of its "title" filter.
   */
  protected function titleFilter(): StringFilter {
    $config = $this->shippedView();
    // The style and row plugins are not the subject of this test.
    unset($config['display']['default']['display_options']['style'], $config['display']['default']['display_options']['row']);
    $config['dependencies'] = [];
    View::create($config)->save();
    $view = Views::getView('vactory_quiz');
    $view->setDisplay('default');
    $view->initHandlers();
    return $view->filter['title'];
  }

  /**
   * Builds the exposed value form of a filter.
   */
  protected function valueForm(StringFilter $filter): array {
    $form = [];
    $form_state = (new FormState())->set('exposed', TRUE);
    $filter->buildExposedForm($form, $form_state);
    return $form;
  }

  /**
   * Module dependencies of the shipped config are declared.
   */
  public function testConfigModuleDependenciesAreDeclared(): void {
    $declared = array_merge($this->declaredModules(), ['vactory_quiz']);
    $files = glob($this->modulePath() . '/config/install/*.yml');
    $this->assertNotEmpty($files);
    foreach ($files as $file) {
      $config = Yaml::decode(file_get_contents($file));
      foreach ($config['dependencies']['module'] ?? [] as $module) {
        $this->assertContains($module, $declared, basename($file) . " depends on $module.");
      }
    }
  }

  /**
   * Modules whose options the shipped view uses are declared.
   */
  public function testViewOptionsComeFromDeclaredModules(): void {
    $declared = $this->declaredModules();
    $filters = $this->shippedView()['display']['default']['display_options']['filters'];
    $checked = 0;
    foreach ($filters as $id => $filter) {
      if (!empty($filter['expose']['autocomplete_filter'])) {
        $checked++;
        $this->assertContains('views_autocomplete_filters', $declared, "Filter $id uses the autocomplete of views_autocomplete_filters.");
      }
      // Option and filter plugin of vactory_google_places.
      if (array_key_exists('vactory_google_places', $filter) || ($filter['plugin_id'] ?? '') === 'vactory_google_places') {
        $this->assertContains('vactory_google_places', $declared, "Filter $id refers to vactory_google_places.");
      }
    }
    $this->assertGreaterThan(0, $checked, 'The view has an autocomplete filter.');
  }

  /**
   * With the declared module, the title filter gets its autocomplete.
   */
  public function testTitleFilterAutocomplete(): void {
    $this->enableModules(['views_autocomplete_filters']);
    $form = $this->valueForm($this->titleFilter());

    // The route name differs between the versions of the module.
    $route = $form['title']['#autocomplete_route_name'] ?? NULL;
    $this->assertNotEmpty($route);
    $this->assertSame('vactory_quiz', $form['title']['#autocomplete_route_parameters']['view_name'] ?? NULL);
    $this->container->get('router.route_provider')->getRouteByName($route);
  }

  /**
   * Without it the view still works: plain text filter, no autocomplete.
   */
  public function testViewRunsWithoutAutocompleteModule(): void {
    $filter = $this->titleFilter();
    $this->assertSame(StringFilter::class, get_class($filter));
    $form = $this->valueForm($filter);
    $this->assertSame('textfield', $form['title']['#type']);
    $this->assertArrayNotHasKey('#autocomplete_route_name', $form['title']);

    $filter->view->execute();
    $this->assertSame([], $filter->view->result);
  }

}
