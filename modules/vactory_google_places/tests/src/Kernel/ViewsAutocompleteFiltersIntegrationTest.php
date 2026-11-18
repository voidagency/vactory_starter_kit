<?php

namespace Drupal\Tests\vactory_google_places\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\vactory_google_places\Plugin\views\filter\VactoryGooglePlacesString;
use Drupal\views_autocomplete_filters\Plugin\views\filter\ViewsAutocompleteFiltersCombine;
use Drupal\views_autocomplete_filters\Plugin\views\filter\ViewsAutocompleteFiltersString;

/**
 * Our use of drupal/views_autocomplete_filters.
 *
 * Our vactory_google_places filter extends its string filter, and the views
 * of the profile use its autocomplete on string, combine and fulltext filters.
 * Run this test on a candidate version before upgrading the module.
 *
 * @group vactory_google_places
 * @group dependencies
 */
class ViewsAutocompleteFiltersIntegrationTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'node',
    'field',
    'text',
    'views',
    'views_autocomplete_filters',
    'vactory_google_places',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
  }

  /**
   * The module gives autocomplete to the core string/combine filters.
   *
   * Our views (locator, smart search, FAQ) rely on it: views_autocomplete_
   * filters replaces the class of the core filter plugins (it no longer ships
   * its own plugin ids since 1.x).
   */
  public function testCoreFiltersGetAutocomplete(): void {
    $manager = $this->container->get('plugin.manager.views.filter');
    $this->assertSame(ViewsAutocompleteFiltersString::class, $manager->getDefinition('string')['class']);
    $this->assertSame(ViewsAutocompleteFiltersCombine::class, $manager->getDefinition('combine')['class']);
    // Autocomplete route used by the exposed filters.
    $this->container->get('router.route_provider')->getRouteByName('viewsfilters.autocomplete');
  }

  /**
   * Our filter plugin builds on the module's string filter.
   */
  public function testFilterPlugin(): void {
    $filter = $this->container->get('plugin.manager.views.filter')
      ->createInstance('vactory_google_places', []);
    $this->assertInstanceOf(VactoryGooglePlacesString::class, $filter);
    $this->assertInstanceOf(ViewsAutocompleteFiltersString::class, $filter);
    $options = (new \ReflectionMethod($filter, 'defineOptions'))->invoke($filter);
    $this->assertArrayHasKey('vactory_google_places', $options);
    // Options of the parent we rely on.
    $this->assertArrayHasKey('expose', $options);
  }

  /**
   * The module's JavaScript no longer uses jQuery.once() (removed in D10).
   *
   * 1.7 needs the profile patch "D10: jquery once is removed": this fails if
   * the patch is not applied by the project.
   */
  public function testJavascriptIsDrupal10Ready(): void {
    $path = $this->container->get('extension.list.module')->getPath('views_autocomplete_filters');
    foreach (glob(DRUPAL_ROOT . '/' . $path . '/js/*.js') as $file) {
      $this->assertDoesNotMatchRegularExpression('/\)\s*\.once\(/', file_get_contents($file), basename($file) . ' calls jQuery.once().');
    }
  }

}
