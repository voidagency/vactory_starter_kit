<?php

namespace Drupal\Tests\vactory_core\Unit;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\StringTextareaWidget;
use Drupal\Core\Field\Plugin\Field\FieldWidget\StringTextfieldWidget;
use Drupal\Core\Form\FormState;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Theme\ActiveTheme;
use Drupal\Core\Theme\ThemeManagerInterface;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the textfield_counter settings attached to text widgets.
 *
 * The JavaScript of textfield_counter iterates "key" with forEach() and uses
 * each value as a CSS class selector: "key" has to be encoded as a JSON list,
 * and two fields with different maximum lengths cannot share a class
 * (Redmine #71387).
 *
 * @group vactory_core
 */
class TextfieldCounterWidgetAlterTest extends UnitTestCase {

  /**
   * The name of the active theme.
   *
   * @var string
   */
  protected $activeTheme = 'claro';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    require_once dirname(__DIR__, 3) . '/vactory_core.module';
    $theme_manager = $this->createMock(ThemeManagerInterface::class);
    $theme_manager->method('getActiveTheme')->willReturnCallback(function () {
      return new ActiveTheme(['name' => $this->activeTheme]);
    });
    $container = new ContainerBuilder();
    $container->set('theme.manager', $theme_manager);
    $container->set('config.factory', $this->getConfigFactoryStub([
      'system.theme' => ['admin' => 'claro'],
    ]));
    \Drupal::setContainer($container);
  }

  /**
   * Builds a widget element and passes it to the alter hook of vactory_core.
   *
   * @param string $field_name
   *   The name of the field.
   * @param int $delta
   *   The delta of the element.
   * @param int $maxlength
   *   The maximum length of the element.
   * @param string $widget_class
   *   The class of the widget.
   * @param bool $nested
   *   TRUE when the input is the "value" child, as core widgets build it.
   *
   * @return array
   *   The input element (the "value" child when nested).
   */
  protected function alterElement(string $field_name, int $delta, int $maxlength, string $widget_class = StringTextfieldWidget::class, bool $nested = TRUE): array {
    $definition = $this->createMock(FieldDefinitionInterface::class);
    $definition->method('getName')->willReturn($field_name);
    $items = $this->createMock(FieldItemListInterface::class);
    $items->method('getFieldDefinition')->willReturn($definition);
    $element = ['#delta' => $delta];
    if ($nested) {
      $element['value'] = ['#type' => 'textfield', '#maxlength' => $maxlength];
    }
    else {
      $element += ['#type' => 'textfield', '#maxlength' => $maxlength];
    }
    $context = [
      'widget' => $this->createMock($widget_class),
      'items' => $items,
      'delta' => $delta,
    ];
    vactory_core_field_widget_single_element_form_alter($element, new FormState(), $context);
    return $nested ? $element['value'] : $element;
  }

  /**
   * Returns the settings of the page, as the browser receives them.
   *
   * @param array $elements
   *   The input elements of the form.
   *
   * @return object
   *   The decoded JSON of drupalSettings.textfieldCounter.
   */
  protected function pageSettings(array $elements): object {
    $attached = [];
    foreach ($elements as $element) {
      // The renderer merges the attachments of every element of the page.
      $attached = BubbleableMetadata::mergeAttachments($attached, $element['#attached']);
    }
    $this->assertContains('textfield_counter/counter', $attached['library']);
    return json_decode(json_encode($attached['drupalSettings']['textfieldCounter']));
  }

  /**
   * Asserts that every element is bound to valid settings of its own.
   *
   * @param array $elements
   *   The input elements of the form.
   * @param int[] $maxlengths
   *   The expected maximum length of each element, in the same order.
   */
  protected function assertCounters(array $elements, array $maxlengths): void {
    $settings = $this->pageSettings($elements);
    foreach (array_values($elements) as $index => $element) {
      $classes = $element['#attributes']['class'];
      $key = $element['#attributes']['data-field-definition-id'];
      $this->assertContains('textfield-counter-element', $classes);
      $this->assertContains($key, $classes);
      $this->assertTrue(isset($settings->{$key}), "The class '$key' has settings.");
      // The counter of an element is the one of every class it matches.
      foreach (array_intersect($classes, array_keys(get_object_vars($settings))) as $class) {
        $this->assertSame($maxlengths[$index], $settings->{$class}->maxlength, "Element $index keeps its own maximum length.");
      }
    }
    foreach ($settings as $name => $field_settings) {
      // The JavaScript calls fieldSettings.key.forEach().
      $this->assertIsArray($field_settings->key, "The key of '$name' is encoded as a JSON list.");
      $this->assertSame([$name], $field_settings->key);
      $this->assertMatchesRegularExpression('/^-?[_a-zA-Z][_a-zA-Z0-9-]*$/', $name, "'$name' is a valid CSS class.");
      $this->assertTrue($field_settings->preventSubmit);
    }
  }

  /**
   * Every delta of a multiple value field gets a counter.
   */
  public function testMultipleValueFieldKeysAreLists(): void {
    $elements = [];
    foreach ([0, 1, 2] as $delta) {
      $elements[] = $this->alterElement('field_vactory_subtitle', $delta, 120);
    }
    $this->assertCounters($elements, [120, 120, 120]);
  }

  /**
   * Two fields of the same form keep their own maximum length.
   */
  public function testFieldsOfTheSameFormKeepTheirOwnMaxlength(): void {
    $elements = [
      $this->alterElement('title', 0, 255),
      $this->alterElement('field_vactory_excerpt', 0, 80, StringTextareaWidget::class),
      $this->alterElement('field_vactory_subtitle', 0, 120),
      $this->alterElement('field_vactory_subtitle', 1, 120),
      // The same field in another bundle, with another maximum length.
      $this->alterElement('field_vactory_excerpt', 0, 300, StringTextareaWidget::class),
    ];
    $this->assertCounters($elements, [255, 80, 120, 120, 300]);
  }

  /**
   * An element without "value" child gets the counter on itself.
   */
  public function testElementWithoutValueChild(): void {
    $elements = [
      $this->alterElement('field_a', 1, 60, StringTextfieldWidget::class, FALSE),
      $this->alterElement('field_b', 1, 90, StringTextfieldWidget::class, FALSE),
    ];
    $this->assertCounters($elements, [60, 90]);
  }

  /**
   * The function still works when called without field name.
   */
  public function testDirectCallWithoutFieldName(): void {
    $elements = [];
    foreach ([[0, 60], [1, 60], [1, 90]] as [$delta, $maxlength]) {
      $element = ['#delta' => $delta, '#maxlength' => $maxlength];
      addCounterToTextElement($element);
      $elements[] = $element;
    }
    $this->assertCounters($elements, [60, 60, 90]);
  }

  /**
   * No counter outside of the administration theme.
   */
  public function testNoCounterOutsideOfAdminTheme(): void {
    $this->activeTheme = 'vactory';
    $element = $this->alterElement('title', 0, 255);
    $this->assertSame(['#type' => 'textfield', '#maxlength' => 255], $element);
  }

}
