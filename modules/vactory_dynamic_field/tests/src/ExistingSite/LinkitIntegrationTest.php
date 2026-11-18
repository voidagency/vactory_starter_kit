<?php

namespace Drupal\Tests\vactory_dynamic_field\ExistingSite;

use Drupal\Component\Utility\NestedArray;
use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\linkit\Controller\AutocompleteController;
use Drupal\linkit\Entity\Profile;
use Drupal\Tests\vactory_dynamic_field\Form\LinkitStructuresTestForm;
use Drupal\vactory_dynamic_field\Element\UrlExtendedElement;
use Symfony\Component\HttpFoundation\Request;
use weitzman\DrupalTestTraits\ExistingSiteBase;

/**
 * Our use of drupal/linkit.
 *
 * Linkit 7.0.13 broke the autocomplete of the Dynamic Fields URL element
 * (#64112), and 7.0.16 was downgraded to 7.0.12 (#69237). Run this test on a
 * candidate version before unpinning linkit.
 *
 * @group vactory_dynamic_field
 * @group dependencies
 */
class LinkitIntegrationTest extends ExistingSiteBase {

  /**
   * Node used as link target.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $target;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->target = $this->createNode([
      'type' => 'vactory_page',
      'title' => 'Linkit target ' . $this->randomMachineName(8),
      'status' => 1,
      'path' => ['alias' => '/linkit-target-' . strtolower($this->randomMachineName(8))],
    ]);
  }

  /**
   * The autocomplete used by the Dynamic Fields returns our content.
   *
   * Called in-process as a logged-in editor (linkit >= 7.0.16 requires a
   * logged-in user on this route).
   */
  public function testAutocomplete(): void {
    $editor = $this->createUser();
    $editor->addRole('webmaster');
    $editor->save();
    // The route stays reachable for back office editors.
    $this->assertTrue(\Drupal::service('access_manager')->checkNamedRoute('linkit.autocomplete', ['linkit_profile_id' => 'default'], $editor));
    $switcher = \Drupal::service('account_switcher');
    $switcher->switchTo($editor);
    try {
      $request = Request::create('/linkit/autocomplete/default', 'GET', ['q' => $this->target->getTitle()]);
      $controller = \Drupal::classResolver(AutocompleteController::class);
      $response = $controller->autocomplete($request, Profile::load('default'));
    }
    finally {
      $switcher->switchBack();
    }
    $data = json_decode($response->getContent(), TRUE);
    $this->assertIsArray($data, 'The autocomplete returns JSON.');
    $labels = array_column($data['suggestions'] ?? $data, 'label');
    $this->assertContains($this->target->getTitle(), $labels);
    // Route contract used by the URL element.
    $route = \Drupal::service('router.route_provider')->getRouteByName('linkit.autocomplete');
    $this->assertSame('/linkit/autocomplete/{linkit_profile_id}', $route->getPath());
  }

  /**
   * The linkit input has the selector linkit's JavaScript requires (#64112).
   */
  public function testLinkitSelectorContract(): void {
    $form_object = new class() extends FormBase {

      /**
       * {@inheritdoc}
       */
      public function getFormId() {
        return 'vactory_dynamic_field_linkit_test';
      }

      /**
       * {@inheritdoc}
       */
      public function buildForm(array $form, FormStateInterface $form_state) {
        $form['link'] = [
          '#type' => 'url_extended',
          '#title' => 'Link',
          '#default_value' => ['url' => '', 'title' => ''],
        ];
        return $form;
      }

      /**
       * {@inheritdoc}
       */
      public function submitForm(array &$form, FormStateInterface $form_state) {
      }

    };
    $editor = $this->createUser();
    $editor->addRole('webmaster');
    $editor->save();
    $switcher = \Drupal::service('account_switcher');
    $switcher->switchTo($editor);
    try {
      $form = \Drupal::formBuilder()->getForm($form_object);
    }
    finally {
      $switcher->switchBack();
    }
    // Linkit enabled its autocomplete on the input.
    $this->assertContains('form-linkit-autocomplete', $form['link']['url']['#attributes']['class'] ?? []);
    $this->assertNotEmpty($form['link']['url']['#attributes']['data-autocomplete-path'] ?? NULL);
    $this->assertContains('linkit/linkit.autocomplete', $form['link']['url']['#attached']['library'] ?? []);
    $url_selector = $form['link']['url']['#attributes']['data-drupal-selector'];
    $title_selector = $form['link']['title']['#attributes']['data-drupal-selector'];
    $this->assertStringEndsWith('-uri', $url_selector);
    // Selectors stay paired: "<base>-uri" / "<base>-title".
    $this->assertSame(substr($url_selector, 0, -4) . '-title', $title_selector);
  }

  /**
   * Linkit selectors in every Dynamic Fields structure (#64112).
   *
   * Reproduces the manual test of PR #912: with linkit >= 7.0.13 and without
   * the "-uri" selector, only the first link of a form got an autocomplete
   * and the others none. The elements are built by the Dynamic Fields
   * FormWidgetTrait::getFormElement(), with the #parents the modal form gives
   * (components[extra_field|delta][group][field]); "Add one more" is a real
   * AJAX rebuild adding components[2].
   */
  public function testLinkitSelectorsInDynamicFieldStructures(): void {
    $form_object = new LinkitStructuresTestForm();
    $editor = $this->createUser();
    $editor->addRole('webmaster');
    $editor->save();
    $switcher = \Drupal::service('account_switcher');
    $switcher->switchTo($editor);
    try {
      $form_state = new FormState();
      $form = \Drupal::formBuilder()->buildForm($form_object, $form_state);
      // "Add one more": AJAX rebuild with one more item.
      $form_state->set('items', 3);
      $form_state->setRequestMethod('POST');
      $form_state->setRebuild();
      Html::setIsAjax(TRUE);
      $rebuilt = \Drupal::formBuilder()->rebuildForm($form_object->getFormId(), $form_state, $form);
    }
    finally {
      Html::setIsAjax(FALSE);
      $switcher->switchBack();
    }
    $builds = [
      'initial' => [$form, LinkitStructuresTestForm::structure(2)],
      'rebuild' => [$rebuilt, LinkitStructuresTestForm::structure(3)],
    ];
    $selectors = [];
    foreach ($builds as $build => [$built_form, $structure]) {
      foreach ($structure as $parents) {
        $element = NestedArray::getValue($built_form, $parents);
        $label = $build . ': ' . implode('.', $parents);
        $url = $element['url'];
        $selector = $url['#attributes']['data-drupal-selector'];
        $expected = 'edit-' . str_replace('_', '-', implode('-', $parents)) . '-uri';
        $this->assertSame($expected, $selector, $label);
        $this->assertSame(substr($expected, 0, -4) . '-title', $element['title']['#attributes']['data-drupal-selector'], $label);
        // Every input gets linkit's autocomplete, not only the first one.
        $this->assertContains('form-linkit-autocomplete', $url['#attributes']['class'] ?? [], $label);
        $this->assertNotEmpty($url['#attributes']['data-autocomplete-path'] ?? NULL, $label);
        // The submitted value key is unchanged.
        $this->assertStringEndsWith('[url]', $url['#name'], $label);
        $selectors[$build][implode('.', $parents)] = $selector;
      }
      $this->assertSame(array_values(array_unique($selectors[$build])), array_values($selectors[$build]), $build . ': selectors are unique.');
    }
    // The AJAX rebuild keeps the existing selectors and adds the new item's.
    $this->assertSame($selectors['initial'], array_intersect_key($selectors['rebuild'], $selectors['initial']));
    $this->assertSame('edit-components-2-link-uri', $selectors['rebuild']['components.2.link']);
  }

  /**
   * The Dynamic Fields URL element is built on linkit's form element.
   */
  public function testUrlExtendedElement(): void {
    $element = [
      '#type' => 'url_extended',
      '#required' => FALSE,
      '#default_value' => ['url' => '', 'title' => ''],
      '#parents' => ['link'],
      '#array_parents' => ['link'],
    ];
    $form = [];
    $element = UrlExtendedElement::processUrlExtended($element, new FormState(), $form);
    $this->assertSame('linkit', $element['url']['#type']);
    $this->assertSame('linkit.autocomplete', $element['url']['#autocomplete_route_name']);
    // The element type and the route still exist in linkit.
    $this->assertTrue(\Drupal::service('element_info')->hasDefinition('linkit'));
    \Drupal::service('router.route_provider')->getRouteByName('linkit.autocomplete');
  }

  /**
   * The linkit filter turns /node/ID links into the content alias (#52782).
   */
  public function testFilterConvertsToAlias(): void {
    $html = sprintf(
      '<a data-entity-type="node" data-entity-uuid="%s" data-entity-substitution="canonical" href="/node/%d">Target</a>',
      $this->target->uuid(),
      $this->target->id()
    );
    $output = (string) check_markup($html, 'full_html');
    $this->assertStringContainsString($this->target->toUrl()->toString(), $output);
    $this->assertStringContainsString('linkit-target-', $output);
  }

}
