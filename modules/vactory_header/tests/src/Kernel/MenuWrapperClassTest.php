<?php

namespace Drupal\Tests\vactory_header\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * The "Wrapper Class" of menu links in the menu templates (Redmine #71732).
 *
 * Since menu_link_attributes 1.10, drush updb renames the attribute
 * "wrapper_class" to "wrapper-class" on every menu link. The templates read
 * the new name and fall back to the old one.
 *
 * @group vactory_header
 */
class MenuWrapperClassTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system'];

  /**
   * Templates and the macro that renders a menu level.
   */
  public static function templates(): array {
    $header = 'modules/vactory_header/templates/';
    $toolbox = 'modules/vactory_toolbox/templates/';
    $cases = [
      'FullMegaMenuMacro' => ['FullMegaMenuMacro', 'FullMenuMacro'],
      'MegaMenuMacro' => ['MegaMenuMacro', 'MegaMenuMacro'],
      'MenuMacro' => ['MenuMacro', 'MenuMacro'],
      'MobileMenuMacro' => ['MobileMenuMacro', 'MobileMenuMacro'],
      'header1' => ['block--vactory-header1', 'menuMacro'],
      'header3' => ['block--vactory-header3', 'menuMacro'],
      'header4' => ['block--vactory-header4', 'navigationMainMacro'],
      'header5' => ['block--vactory-header5', 'menuMacro'],
    ];
    $data = [];
    foreach ($cases as $key => [$name, $macro]) {
      $data[$key] = [$header . $name . '.html.twig', $macro];
    }
    $toolbox_file = $toolbox . 'vactory-toolbox-block.html.twig';
    $data['toolbox'] = [$toolbox_file, 'menuMacro'];
    return $data;
  }

  /**
   * The wrapper class is rendered under its new and its old name.
   *
   * @dataProvider templates
   */
  public function testWrapperClassRendered(string $file, string $macro): void {
    foreach (['wrapper-class', 'wrapper_class'] as $attribute) {
      $html = $this->renderMacro($file, $macro, $attribute);
      $this->assertStringContainsString('probe-wrapper', $html, "$file renders the \"$attribute\" attribute.");
    }
  }

  /**
   * Renders the macros of a template with one menu link.
   */
  protected function renderMacro(string $file, string $macro, string $attribute): string {
    $source = file_get_contents(dirname(__DIR__, 5) . '/' . $file);
    // Only the macro definitions: the rest of the template needs the block.
    preg_match_all('/\{%-?\s*macro\b.*?\{%-?\s*endmacro\s*-?%\}/s', $source, $matches);
    $template = implode("\n", $matches[0]) . "{% import _self as m %}{{ m.$macro(menu, 1) }}";
    $menu = [
      [
        'text' => 'Link',
        'title' => 'Link',
        'url' => '/',
        'active' => FALSE,
        'active_trail' => FALSE,
        'submenu' => [],
        'below' => [],
        'fields' => [],
        'options' => [
          'attributes' => [$attribute => 'probe-wrapper', 'class' => ''],
        ],
      ],
    ];
    // Plain Twig: functions and filters of other modules (vactory_render, t…)
    // are neutral here, the test only checks how the attribute is read.
    $twig = new Environment(new ArrayLoader(['menu' => $template]), ['autoescape' => 'html']);
    $twig->registerUndefinedFunctionCallback(fn(string $name) => new TwigFunction($name, fn() => ''));
    $twig->registerUndefinedFilterCallback(fn(string $name) => new TwigFilter($name, fn($value) => $value));
    return $twig->render('menu', ['menu' => $menu]);
  }

}
