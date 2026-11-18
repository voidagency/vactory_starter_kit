<?php

namespace Drupal\Tests\vactory_decoupled\ExistingSite;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\pathauto\PathautoState;
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
    'name' => 'Links test',
    'multiple' => TRUE,
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
      'group_extra' => [
        'g_title' => 'Extra group',
        'link' => ['type' => 'url_extended', 'label' => 'Extra group link'],
      ],
    ],
  ];

  /**
   * Aliases of the created pages, keyed by node id.
   *
   * @var string[]
   */
  protected $aliases = [];

  /**
   * Creates a published page with a known alias (pathauto skipped).
   */
  protected function createPage(string $name) {
    $alias = '/df-links-' . strtolower($this->randomMachineName(8)) . '-' . $name;
    $node = $this->createNode([
      'type' => 'vactory_page',
      'title' => $name,
      'status' => 1,
      'moderation_state' => 'published',
      'path' => ['alias' => $alias, 'pathauto' => PathautoState::SKIP],
    ]);
    $this->aliases[$node->id()] = $alias;
    return $node;
  }

  /**
   * Asserts a processed link points to the page alias.
   */
  protected function assertAliased($node, array $value, string $suffix = ''): void {
    $this->assertStringEndsWith($this->aliases[$node->id()] . $suffix, $value['url']);
    $this->assertStringNotContainsString('/node/', $value['url']);
    $this->assertFalse($value['is_external']);
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
        '_weight' => 1,
        'link' => $this->link('/node/' . $item->id()),
        'group_item' => ['link' => $this->link('/node/' . $group->id())],
        'content' => [
          'value' => '<p><a href="/node/' . $text->id() . '" data-entity-type="node" data-entity-uuid="' . $text->uuid() . '" data-entity-substitution="canonical">See</a></p>',
          'format' => 'full_html',
        ],
      ],
      [
        '_weight' => 2,
        'link' => $this->link('https://www.example.com/page'),
        'group_item' => ['link' => $this->link('/node/' . $item->id() . '?x=1#a')],
        'content' => ['value' => '<p>Plain</p>', 'format' => 'full_html'],
      ],
    ];
    $extra = [
      'cta' => $this->link('/node/' . $cta->id()),
      'group_extra' => ['link' => $this->link('/node/' . $group->id())],
    ];

    $manager = \Drupal::service('vactory_decoupled.dynamic_field_manager');
    // Initialises the cacheability the formatters write to.
    $manager->process([]);
    foreach ($items as &$component) {
      $manager->applyFormatters(['fields'], self::SETTINGS, $component);
    }
    unset($component);
    $manager->applyFormatters(['extra_fields'], self::SETTINGS, $extra);

    $this->assertAliased($item, $items[0]['link']);
    $this->assertAliased($group, $items[0]['group_item']['link']);
    $this->assertAliased($cta, $extra['cta']);
    $this->assertAliased($group, $extra['group_extra']['link']);
    // External links are kept as is.
    $this->assertSame('https://www.example.com/page', $items[1]['link']['url']);
    $this->assertTrue($items[1]['link']['is_external']);
    // Query and fragment are kept on internal links.
    $this->assertAliased($item, $items[1]['group_item']['link'], '?x=1#a');

    // CKEditor text: the linkit filter turns the link into the alias.
    $html = $items[0]['content']['value']['#text'];
    $this->assertMatchesRegularExpression('#href="[^"]*' . preg_quote($this->aliases[$text->id()], '#') . '"#', $html);
    $this->assertStringNotContainsString('href="/node/', $html);
    $this->assertStringContainsString('Plain', $items[1]['content']['value']['#text']);
  }

  /**
   * The full process() path with a real widget (link in extra fields).
   *
   * Template "Formulaires > Simple" of vactory_decoupled_webform, used for the
   * manual test of PR #912.
   */
  public function testProcessWithRealWidget(): void {
    $page = $this->createPage('webform');
    $data = [
      'widget_id' => 'vactory_decoupled_webform:webform',
      'widget_data' => json_encode([
        'extra_field' => [
          'title' => 'Form',
          'link' => $this->link('/node/' . $page->id()),
        ],
        0 => ['_weight' => 1, 'webform' => []],
      ]),
    ];
    $result = \Drupal::service('vactory_decoupled.dynamic_field_manager')
      ->process($data, new CacheableMetadata());
    $content = json_decode($result['data']['widget_data'], TRUE);
    $this->assertSame('vactory_decoupled_webform:webform', $content['template']);
    $this->assertAliased($page, $content['extra_field']['link']);
  }

}
