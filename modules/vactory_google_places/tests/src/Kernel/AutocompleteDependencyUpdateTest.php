<?php

namespace Drupal\Tests\vactory_google_places\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\vactory_google_places\Plugin\views\filter\VactoryGooglePlacesString;
use Drupal\views\Plugin\views\filter\StringFilter;
use Drupal\views\Views;

/**
 * A site where views_autocomplete_filters was never enabled (Redmine #71714).
 *
 * Before 3.3.0, vactory_google_places did not declare
 * views_autocomplete_filters, and Drupal does not enable a newly declared
 * dependency on an existing site.
 * Our "string" filter class extends a class of that module: without it, every
 * view with a text filter failed (/admin/content answered 500).
 *
 * @group vactory_google_places
 */
class AutocompleteDependencyUpdateTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   *
   * KernelTestBase does not resolve dependencies: views_autocomplete_filters
   * stays disabled, as on such a site.
   */
  protected static $modules = [
    'system',
    'user',
    'node',
    'field',
    'text',
    'filter',
    'views',
    'vactory_google_places',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installConfig(['filter', 'node', 'views']);
    $this->container->get('module_handler')->loadInclude('vactory_google_places', 'install');
  }

  /**
   * Without the module, text filters keep the core class and views work.
   */
  public function testWithoutTheModuleViewsKeepWorking(): void {
    $this->assertFalse($this->container->get('module_handler')->moduleExists('views_autocomplete_filters'));
    $class = $this->container->get('plugin.manager.views.filter')->getDefinition('string')['class'];
    $this->assertSame(StringFilter::class, $class);

    $view = Views::getView('content');
    $view->setDisplay('page_1');
    $view->initHandlers();
    $this->assertInstanceOf(StringFilter::class, $view->filter['title']);
  }

  /**
   * The update enables the module; text filters then use our class.
   */
  public function testUpdateEnablesTheModule(): void {
    vactory_google_places_update_10001();
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('views_autocomplete_filters'));

    $class = \Drupal::service('plugin.manager.views.filter')->getDefinition('string')['class'];
    $this->assertSame(VactoryGooglePlacesString::class, $class);
    $view = Views::getView('content');
    $view->setDisplay('page_1');
    $view->initHandlers();
    $this->assertInstanceOf(VactoryGooglePlacesString::class, $view->filter['title']);

    // A second run changes nothing and does not fail.
    vactory_google_places_update_10001();
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('views_autocomplete_filters'));
  }

}
