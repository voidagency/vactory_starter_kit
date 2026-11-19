<?php

namespace Drupal\Tests\vactory_google_places\Kernel;

use Drupal\Core\Form\FormState;
use Drupal\KernelTests\KernelTestBase;
use Drupal\vactory_google_places\Plugin\views\filter\VactoryGooglePlacesString;
use Drupal\views\Entity\View;
use Drupal\views\Views;
use Drupal\views_autocomplete_filters\Plugin\views\filter\ViewsAutocompleteFiltersCombine;
use Drupal\views_autocomplete_filters\Plugin\views\filter\ViewsAutocompleteFiltersString;

/**
 * Our use of drupal/views_autocomplete_filters.
 *
 * The module swaps the class of the core "string" / "combine" filters for its
 * autocomplete classes; vactory_google_places swaps "string" again for its
 * subclass. Views picks the handler from views data, so this class swap is
 * what makes our filter run. Run this test on a candidate version before
 * upgrading the module.
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
    $this->installConfig(['node', 'views']);
  }

  /**
   * String filters use our class, built on the module's autocomplete class.
   */
  public function testFilterClasses(): void {
    $manager = $this->container->get('plugin.manager.views.filter');
    $string = $manager->getDefinition('string')['class'];
    $this->assertSame(VactoryGooglePlacesString::class, $string);
    $this->assertTrue(is_a($string, ViewsAutocompleteFiltersString::class, TRUE));
    $this->assertSame(ViewsAutocompleteFiltersCombine::class, $manager->getDefinition('combine')['class']);

    // Autocomplete options our views configs use.
    $filter = $manager->createInstance('string', []);
    $options = (new \ReflectionMethod($filter, 'defineOptions'))->invoke($filter);
    $keys = [
      'autocomplete_filter',
      'autocomplete_field',
      'autocomplete_dependent',
    ];
    foreach ($keys as $key) {
      $this->assertArrayHasKey($key, $options['expose']['contains'], "Option expose.$key");
    }
    $this->assertArrayHasKey('vactory_google_places', $options);
  }

  /**
   * An exposed string filter gets the autocomplete and our Google Places UI.
   */
  public function testExposedFilterInView(): void {
    View::create([
      'id' => 'test_vaf',
      'base_table' => 'node_field_data',
      'display' => [
        'default' => [
          'id' => 'default',
          'display_plugin' => 'default',
          'display_options' => [
            'filters' => [
              'title' => [
                'id' => 'title',
                'table' => 'node_field_data',
                'field' => 'title',
                'plugin_id' => 'string',
                'operator' => 'contains',
                'exposed' => TRUE,
                'vactory_google_places' => 1,
                'expose' => [
                  'identifier' => 'title',
                  'label' => 'Title',
                  'operator_id' => 'title_op',
                  'autocomplete_filter' => 1,
                  'autocomplete_field' => 'title',
                ],
              ],
            ],
          ],
        ],
      ],
    ])->save();
    $view = Views::getView('test_vaf');
    $view->setDisplay('default');
    $view->initHandlers();
    $this->assertInstanceOf(VactoryGooglePlacesString::class, $view->filter['title']);

    $form = [];
    $form_state = (new FormState())->set('exposed', TRUE);
    $view->filter['title']->valueForm($form, $form_state);
    $this->assertContains('vactory-google-places', $form['value']['#attributes']['class'] ?? []);
    $this->assertContains('vactory_google_places/autocomplete', $form['#attached']['library'] ?? []);
    // The autocomplete route set by views_autocomplete_filters exists.
    $route = $form['value']['#autocomplete_route_name'] ?? NULL;
    $this->assertNotEmpty($route);
    $this->container->get('router.route_provider')->getRouteByName($route);
  }

  /**
   * The module's JavaScript no longer uses jQuery.once() (removed in D10).
   *
   * Version 1.7 needs the profile patch "D10: jquery once is removed": this
   * fails if the patch is not applied by the project.
   */
  public function testJavascriptIsDrupal10Ready(): void {
    $path = $this->container->get('extension.list.module')->getPath('views_autocomplete_filters');
    $files = glob(DRUPAL_ROOT . '/' . $path . '/js/*.js') ?: [];
    $this->assertNotEmpty($files);
    foreach ($files as $file) {
      $this->assertDoesNotMatchRegularExpression('/\.(once|removeOnce)\(/', file_get_contents($file), basename($file) . ' calls jQuery.once().');
    }
  }

}
