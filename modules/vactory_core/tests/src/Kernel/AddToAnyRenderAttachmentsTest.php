<?php

namespace Drupal\Tests\vactory_core\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * AddToAny script attached by the rendered buttons, not by every page.
 *
 * Renders the AddToAny templates through the renderer and collects the page
 * attachments the way core does (Redmine #66851).
 *
 * @group vactory_core
 */
class AddToAnyRenderAttachmentsTest extends KernelTestBase {

  /**
   * Library of the AddToAny module that loads its external script.
   */
  const LIBRARY = 'addtoany/addtoany.front';

  /**
   * Key of the inline configuration script in the HTML head.
   */
  const HEAD_KEY = 'addtoany-js';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'path_alias',
    'node',
    'token',
    'addtoany',
    'twig_tweak',
    'simplify_menu',
    'vactory_core',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['addtoany']);
  }

  /**
   * Renders AddToAny buttons and returns the bubbled metadata.
   *
   * @param string $theme_hook
   *   The theme hook of the buttons.
   *
   * @return array
   *   The rendered element, with its "#attached" and "#cache" keys.
   */
  protected function renderButtons(string $theme_hook): array {
    $build = [
      '#theme' => $theme_hook,
      '#addtoany_html' => '<a class="a2a_button_facebook"></a>',
      '#buttons_size' => 32,
    ];
    $markup = (string) $this->container->get('renderer')->renderRoot($build);
    $this->assertStringContainsString('a2a_kit', $markup);
    $this->assertStringContainsString('a2a_button_facebook', $markup);
    return $build;
  }

  /**
   * The theme hooks that render AddToAny buttons.
   *
   * @return array
   *   One theme hook per case.
   */
  public static function themeHookProvider(): array {
    return [
      'share buttons' => ['addtoany_standard'],
      'follow buttons' => ['addtoany_follow'],
    ];
  }

  /**
   * Every template of the AddToAny module is covered by the data provider.
   */
  public function testEveryButtonTemplateIsCovered(): void {
    $hooks = array_keys(addtoany_theme());
    sort($hooks);
    $this->assertSame(['addtoany_follow', 'addtoany_standard'], $hooks);
  }

  /**
   * Rendered buttons bubble the script and its configuration.
   *
   * @dataProvider themeHookProvider
   */
  public function testRenderedButtonsAttachScript(string $theme_hook): void {
    $build = $this->renderButtons($theme_hook);

    $this->assertContains(self::LIBRARY, $build['#attached']['library']);
    $head = array_column($build['#attached']['html_head'], 0, 1);
    $this->assertArrayHasKey(self::HEAD_KEY, $head);
    // Same inline configuration as addtoany_page_attachments().
    $upstream = [];
    addtoany_page_attachments($upstream);
    $upstream_head = array_column($upstream['#attached']['html_head'], 0, 1);
    $this->assertSame((string) $upstream_head[self::HEAD_KEY]['#value'], (string) $head[self::HEAD_KEY]['#value']);
    // A change of the AddToAny settings invalidates the cached buttons, and
    // without tokens the buttons do not vary by route.
    $this->assertContains('config:addtoany.settings', $build['#cache']['tags']);
    $this->assertNotContains('route', $build['#cache']['contexts']);
  }

  /**
   * Buttons vary by route only when the additional JavaScript has tokens.
   *
   * @dataProvider themeHookProvider
   */
  public function testTokensVaryByRoute(string $theme_hook): void {
    $settings = $this->config('addtoany.settings');
    $settings->set('additional_js', 'a2a_config.locale="fr";a2a_config.color_main=[1,2];')->save();
    $build = $this->renderButtons($theme_hook);
    $this->assertNotContains('route', $build['#cache']['contexts']);

    $settings->set('additional_js', 'a2a_config.linkname="[node:title]";')->save();
    $build = $this->renderButtons($theme_hook);
    $this->assertContains('route', $build['#cache']['contexts']);
    $this->assertContains(self::LIBRARY, $build['#attached']['library']);
  }

  /**
   * A page without buttons does not load the script.
   */
  public function testPageWithoutButtonsHasNoScript(): void {
    // AddToAny itself attaches the script to every page.
    $upstream = [];
    addtoany_page_attachments($upstream);
    $this->assertContains(self::LIBRARY, $upstream['#attached']['library']);
    $this->assertContains(self::HEAD_KEY, array_column($upstream['#attached']['html_head'], 1));

    // Hook_page_attachments() and its alter hook, invoked as core does.
    $page = [];
    $this->container->get('main_content_renderer.html')->invokePageAttachmentHooks($page);
    $this->assertNotEmpty($page['#attached']['html_head']);
    $this->assertNotContains(self::LIBRARY, $page['#attached']['library'] ?? []);
    $this->assertNotContains(self::HEAD_KEY, array_column($page['#attached']['html_head'], 1));
  }

}
