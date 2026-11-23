<?php

namespace Drupal\Tests\vactory_decoupled\ExistingSite;

use Drupal\block\Entity\Block;
use Drupal\block_content\Entity\BlockContent;
use weitzman\DrupalTestTraits\ExistingSiteBase;

/**
 * Contract between the profile and block_class.
 *
 * The profile relies on block_class in two places: the classes typed on a
 * block are printed on the block wrapper by Drupal, and they are exposed as
 * typed to the decoupled front by BlocksManager. Both must hold on every
 * block_class 3.x release (3.0 renders from a preprocess, 3.1 from
 * hook_block_view_alter).
 *
 * @group vactory_decoupled
 * @group dependencies
 */
class BlockClassIntegrationTest extends ExistingSiteBase {

  /**
   * Active theme name.
   *
   * @var string
   */
  protected $theme;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->theme = \Drupal::theme()->getActiveTheme()->getName();
  }

  /**
   * Places a block with block_class classes in the first visible region.
   */
  protected function placeBlockWithClasses(string $plugin, string $classes): Block {
    $regions = array_keys(system_region_list($this->theme, 'visible'));
    $block = Block::create([
      'id' => 'vsk_block_class_' . strtolower($this->randomMachineName(8)),
      'theme' => $this->theme,
      'region' => reset($regions),
      'plugin' => $plugin,
      'weight' => 0,
      'settings' => [
        'label' => 'Block class test',
        'label_display' => '0',
      ],
    ]);
    $block->setThirdPartySetting('block_class', 'classes', $classes);
    $block->save();
    $this->markEntityForCleanup($block);
    return $block;
  }

  /**
   * The classes of a block are printed on its HTML wrapper.
   */
  public function testClassesInRenderedBlock(): void {
    $block = $this->placeBlockWithClasses('system_powered_by_block', 'vsk-class-one vsk_class_two');

    $build = \Drupal::entityTypeManager()->getViewBuilder('block')->view($block);
    $html = (string) \Drupal::service('renderer')->renderRoot($build);

    $this->assertMatchesRegularExpression('/class="[^"]*\bvsk-class-one\b[^"]*"/', $html);
    $this->assertMatchesRegularExpression('/class="[^"]*\bvsk_class_two\b[^"]*"/', $html);
  }

  /**
   * Characters outside CSS identifiers are stripped from the HTML class.
   */
  public function testInvalidCharactersCleanedInRenderedBlock(): void {
    $block = $this->placeBlockWithClasses('system_powered_by_block', 'vsk-plain md:vsk-variant');

    $build = \Drupal::entityTypeManager()->getViewBuilder('block')->view($block);
    $html = (string) \Drupal::service('renderer')->renderRoot($build);

    $this->assertMatchesRegularExpression('/class="[^"]*\bvsk-plain\b[^"]*"/', $html);
    $this->assertStringContainsString('mdvsk-variant', $html);
    $this->assertStringNotContainsString('md:vsk-variant', $html);
  }

  /**
   * The classes are exposed as typed in the decoupled blocks output.
   */
  public function testClassesInDecoupledOutput(): void {
    $block_content = BlockContent::create([
      'type' => 'vactory_block_component',
      'info' => 'Block class test ' . $this->randomMachineName(8),
    ]);
    $block_content->save();
    $this->markEntityForCleanup($block_content);

    $with_classes = $this->placeBlockWithClasses('block_content:' . $block_content->uuid(), 'vsk-class-one md:vsk-variant');
    $without_classes = $this->placeBlockWithClasses('block_content:' . $block_content->uuid(), '');

    $node = $this->createNode(['type' => 'vactory_page', 'status' => 1]);

    // The JSON:API subrequest made for the block content emits core
    // deprecations (PHP 8.1+) unrelated to this contract.
    $error_level = error_reporting(E_ALL & ~E_DEPRECATED);
    drupal_static_reset();
    try {
      $blocks = \Drupal::service('vactory_decoupled.blocksManager')->getBlocksByNode($node->id());
    }
    finally {
      drupal_static_reset();
      error_reporting($error_level);
    }

    $classes = array_column($blocks, 'classes', 'id');
    $this->assertArrayHasKey($with_classes->id(), $classes);
    $this->assertSame('vsk-class-one md:vsk-variant', $classes[$with_classes->id()]);
    $this->assertArrayHasKey($without_classes->id(), $classes);
    $this->assertEmpty($classes[$without_classes->id()]);
  }

}
