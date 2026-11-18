<?php

namespace Drupal\Tests\vactory_decoupled\ExistingSite;

use Drupal\Core\Url;
use weitzman\DrupalTestTraits\ExistingSiteBase;

/**
 * Links of Dynamic Fields as exposed by JSON:API.
 *
 * Reproduces the JSON:API check of the manual test of PR #912 (linkit): the
 * links picked with linkit are stored as /node/ID and must come out as
 * aliases, in every Dynamic Fields structure (multiple items, groups, extra
 * fields) and inside CKEditor text. The template settings are a fixture with
 * the same shape as a widget settings.yml.
 *
 * @group vactory_decoupled
 * @group dependencies
 */
class DynamicFieldLinksNormalizerTest extends ExistingSiteBase {

  /**
   * Template settings fixture.
   */
  const SETTINGS = [
    'uuid' => 'vactory_decoupled_links_test',
    'fields' => [
      'link' => ['type' => 'url_extended', 'label' => 'Link'],
      'group_item' => [
        'g_title' => 'Group',
        'link' => ['type' => 'url_extended', 'label' => 'Group link'],
      ],
      'content' => [
        'type' => 'text_format',
        'label' => 'Content',
        'options' => ['#format' => 'full_html'],
      ],
    ],
    'extra_fields' => [
      'cta' => ['type' => 'url_extended', 'label' => 'CTA'],
    ],
  ];

  /**
   * Creates a published page with an alias.
   */
  protected function createPage(string $name) {
    return $this->createNode([
      'type' => 'vactory_page',
      'title' => $name,
      'status' => 1,
      'moderation_state' => 'published',
    ]);
  }

  /**
   * Builds a stored url_extended value.
   */
  protected function link(string $url): array {
    return ['url' => $url, 'title' => '', 'attributes' => []];
  }

  /**
   * Linkit links come out as aliases in every structure.
   */
  public function testLinksAreAliased(): void {
    $item = $this->createPage('item');
    $group = $this->createPage('group');
    $text = $this->createPage('text');
    $cta = $this->createPage('cta');

    $items = [
      [
        'link' => $this->link('/node/' . $item->id()),
        'group_item' => ['link' => $this->link('/node/' . $group->id())],
        'content' => [
          'value' => '<p><a href="/node/' . $text->id() . '" data-entity-type="node" data-entity-uuid="' . $text->uuid() . '" data-entity-substitution="canonical">See</a></p>',
          'format' => 'full_html',
        ],
      ],
      [
        'link' => $this->link('https://www.example.com/page'),
        'group_item' => ['link' => $this->link('/node/' . $item->id() . '?x=1#a')],
        'content' => ['value' => '<p>Plain</p>', 'format' => 'full_html'],
      ],
    ];
    $extra = ['cta' => $this->link('/node/' . $cta->id())];

    $manager = \Drupal::service('vactory_decoupled.dynamic_field_manager');
    // Initialises the cacheability the formatters write to.
    $manager->process([]);
    foreach ($items as &$component) {
      $manager->applyFormatters(['fields'], self::SETTINGS, $component);
    }
    unset($component);
    $manager->applyFormatters(['extra_fields'], self::SETTINGS, $extra);

    $alias = fn($node) => Url::fromUserInput('/node/' . $node->id())->toString();
    $internal = [
      [$item, $items[0]['link']],
      [$group, $items[0]['group_item']['link']],
      [$cta, $extra['cta']],
    ];
    foreach ($internal as [$node, $value]) {
      $this->assertSame($alias($node), $value['url']);
      // An alias, not the system path stored by linkit.
      $this->assertStringEndsWith(\Drupal::service('path_alias.manager')->getAliasByPath('/node/' . $node->id()), $value['url']);
      $this->assertStringNotContainsString('/node/', $value['url']);
      $this->assertFalse($value['is_external']);
    }
    // External links are kept as is.
    $this->assertSame('https://www.example.com/page', $items[1]['link']['url']);
    $this->assertTrue($items[1]['link']['is_external']);
    // Query and fragment are kept on internal links.
    $this->assertSame($alias($item) . '?x=1#a', $items[1]['group_item']['link']['url']);

    // CKEditor text: the linkit filter turns the link into the alias.
    $html = $items[0]['content']['value']['#text'];
    $this->assertStringContainsString('href="' . $alias($text) . '"', $html);
    $this->assertStringNotContainsString('href="/node/', $html);
    $this->assertSame('<p>Plain</p>', trim($items[1]['content']['value']['#text']));
  }

}
