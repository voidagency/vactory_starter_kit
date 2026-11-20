<?php

namespace Drupal\Tests\vactory_dynamic_field\Unit;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Markup;
use Drupal\Tests\UnitTestCase;
use Drupal\vactory_dynamic_field\Plugin\Field\FieldWidget\FormWidgetTrait;

/**
 * Tests the textfield_counter settings attached by the Dynamic Fields form.
 *
 * The JavaScript of textfield_counter iterates "key" with forEach() and uses
 * each value as a CSS class selector: "key" has to be encoded as a JSON list
 * of valid class names (Redmine #71223).
 *
 * @group vactory_dynamic_field
 */
class TextfieldCounterSettingsTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $module_handler = $this->createMock(ModuleHandlerInterface::class);
    $module_handler->method('moduleExists')->willReturn(FALSE);
    $container = new ContainerBuilder();
    $container->set('module_handler', $module_handler);
    $container->set('config.factory', $this->getConfigFactoryStub([
      'system.file' => ['default_scheme' => 'public'],
    ]));
    \Drupal::setContainer($container);
  }

  /**
   * Builds elements the way ModalForm does and returns the form.
   *
   * @param array $fields
   *   A list of [type, options, field id, index].
   *
   * @return array
   *   The form, with its "elements" and attachments.
   */
  protected function buildForm(array $fields): array {
    $builder = new class() {

      use FormWidgetTrait {
        getFormElement as public;
      }

    };
    $form = [];
    $form_state = new FormState();
    foreach ($fields as [$type, $options, $field_id, $index]) {
      $form['elements'][] = $this->buildElement($builder, $type, $options, $form, $form_state, $field_id, $index);
    }
    return $form;
  }

  /**
   * Builds one element.
   */
  protected function buildElement(object $builder, string $type, array $options, array &$form, FormStateInterface $form_state, string $field_id, $index): array {
    return $builder->getFormElement($type, Markup::create('Label'), '', $options, $form, $form_state, '', $field_id, $index);
  }

  /**
   * Every "key" is a JSON list of valid CSS classes set on an element.
   */
  public function testCounterKeysAreListsOfValidClasses(): void {
    $form = $this->buildForm([
      // Simple fields of the first, second and third component.
      ['text', [], 'title', 0],
      ['text', [], 'title', 1],
      ['text', [], 'title', 2],
      ['textarea', ['#maxlength' => 400], 'intro_text', 2],
      // Extra field of a group: the index is the name of the field.
      ['text', [], 'group_cta', 'label'],
      // Field of a group: ModalForm passes neither id nor index.
      ['text', [], '', ''],
      // Field whose id is not a valid CSS class.
      ['text', [], '2nd_title', 0],
    ]);

    $settings = $form['#attached']['drupalSettings']['textfieldCounter'];
    $this->assertContains('textfield_counter/counter', $form['#attached']['library']);
    $json = json_decode(json_encode($settings));
    $classes = [];
    foreach ($form['elements'] as $element) {
      $classes = array_merge($classes, $element['#attributes']['class']);
    }
    foreach ($json as $name => $field_settings) {
      $this->assertIsArray($field_settings->key, "The key of '$name' is encoded as a JSON list.");
      $this->assertSame([$name], $field_settings->key);
      $this->assertMatchesRegularExpression('/^-?[_a-zA-Z][_a-zA-Z0-9-]*$/', $name, "'$name' is a valid CSS class.");
      $this->assertContains($name, $classes, "An element has the class '$name'.");
      $this->assertTrue($field_settings->preventSubmit);
    }
    // Every element is bound to the settings of its own class.
    foreach ($form['elements'] as $element) {
      $this->assertContains('textfield-counter-element', $element['#attributes']['class']);
      $this->assertArrayHasKey($element['#attributes']['data-field-definition-id'], $settings);
      $this->assertContains($element['#attributes']['data-field-definition-id'], $element['#attributes']['class']);
    }
    // Class names of valid field ids are unchanged.
    $this->assertSame(255, $settings['title-1']['maxlength']);
    $this->assertSame(255, $settings['title-2']['maxlength']);
    $this->assertSame(400, $settings['intro_text-2']['maxlength']);
    $this->assertSame(255, $settings['group_cta-label']['maxlength']);
  }

  /**
   * Group fields without id get the maximum length of their own element.
   */
  public function testGroupFieldsKeepTheirOwnMaxlength(): void {
    $form = $this->buildForm([
      ['text', [], '', ''],
      ['textarea', ['#maxlength' => 80], '', ''],
      ['text', ['#maxlength' => 80], '', ''],
    ]);

    $settings = $form['#attached']['drupalSettings']['textfieldCounter'];
    $expected = [255, 80, 80];
    foreach ($form['elements'] as $delta => $element) {
      $key = $element['#attributes']['data-field-definition-id'];
      $this->assertSame($expected[$delta], $settings[$key]['maxlength']);
    }
  }

  /**
   * Elements without maximum length get no counter.
   */
  public function testNoCounterWithoutMaxlength(): void {
    $form = $this->buildForm([
      ['textarea', [], 'description', 0],
    ]);

    $this->assertArrayNotHasKey('#attached', $form);
    $this->assertArrayNotHasKey('#attributes', $form['elements'][0]);
  }

}
