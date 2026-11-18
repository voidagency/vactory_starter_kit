<?php

namespace Drupal\Tests\vactory_amp\ExistingSite;

use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Core\Routing\RouteMatch;
use Drupal\Tests\vactory_core\Functional\VactoryExistingSiteBase;
use Drupal\vactory_amp\Services\VactoryAmpContextDecorator;
use Symfony\Component\Routing\Route;

/**
 * Our use of drupal/amp through vactory_amp.
 *
 * The vactory_amp module extends amp's AmpContext (same constructor, decorated service
 * router.amp_context) and removes the "amphtml" link that amp adds in
 * hook_entity_view_alter() for nodes excluded from AMP. Run this test on a
 * candidate version of amp before upgrading it.
 *
 * @group vactory_amp
 * @group dependencies
 */
class AmpIntegrationTest extends VactoryExistingSiteBase {

  /**
   * {@inheritdoc}
   */
  protected array $modulesToInstall = ['amp', 'vactory_amp'];

  /**
   * AMP display created by the test.
   *
   * @var \Drupal\Core\Entity\Display\EntityViewDisplayInterface|null
   */
  protected $ampDisplay;

  /**
   * Original amp.settings:amp_everywhere value.
   *
   * @var mixed
   */
  protected $everywhere;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // AMP is enabled for a content type by enabling its "amp" view display.
    if (!EntityViewDisplay::load('node.vactory_page.amp')) {
      $this->ampDisplay = EntityViewDisplay::create([
        'targetEntityType' => 'node',
        'bundle' => 'vactory_page',
        'mode' => 'amp',
        'status' => TRUE,
      ]);
      $this->ampDisplay->save();
    }
    $config = \Drupal::configFactory()->getEditable('amp.settings');
    $this->everywhere = $config->get('amp_everywhere');
    $config->set('amp_everywhere', TRUE)->save();
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    $this->ampDisplay?->delete();
    \Drupal::configFactory()->getEditable('amp.settings')
      ->set('amp_everywhere', $this->everywhere)
      ->save();
    parent::tearDown();
    // vactory_amp's base field is purged on cron after uninstall; until then
    // the module cannot be installed and uninstalled again.
    field_purge_batch(1000);
  }

  /**
   * Creates a published page.
   */
  protected function createPage(bool $excluded) {
    return $this->createNode([
      'type' => 'vactory_page',
      'title' => $excluded ? 'Excluded from AMP' : 'AMP page',
      'status' => 1,
      'moderation_state' => 'published',
      'exclude_from_amp' => (int) $excluded,
    ]);
  }

  /**
   * Our decorator replaces amp's context and keeps its behaviour.
   */
  public function testAmpContextDecorator(): void {
    $context = \Drupal::service('router.amp_context');
    $this->assertInstanceOf(VactoryAmpContextDecorator::class, $context);

    $route = new Route('/node/{node}');
    foreach ([FALSE => TRUE, TRUE => FALSE] as $excluded => $expected) {
      $node = $this->createPage((bool) $excluded);
      $this->assertTrue($context->entityIsAmp($node));
      $route_match = new RouteMatch('entity.node.canonical', $route, ['node' => $node]);
      $this->assertSame($expected, $context->isAmpRoute($route_match, NULL, FALSE), $excluded ? 'Excluded node is not AMP.' : 'Node is AMP.');
    }
  }

  /**
   * The "amphtml" link added by amp is removed for excluded nodes.
   */
  public function testAmphtmlLink(): void {
    $display = EntityViewDisplay::collectRenderDisplay($this->createPage(FALSE), 'full');
    foreach ([FALSE => TRUE, TRUE => FALSE] as $excluded => $expected) {
      $node = $this->createPage((bool) $excluded);
      $canonical = $node->toUrl('canonical', ['absolute' => TRUE])->toString();
      $build = [
        '#view_mode' => 'full',
        '#attached' => [
          'html_head_link' => [
            [['rel' => 'canonical', 'href' => $canonical], TRUE],
          ],
        ],
      ];
      // Same order as the view builder: amp adds the link, vactory_amp
      // removes it.
      \Drupal::moduleHandler()->alter('entity_view', $build, $node, $display);
      $rels = array_map(fn($link) => $link[0]['rel'] ?? NULL, $build['#attached']['html_head_link']);
      $this->assertSame($expected, in_array('amphtml', $rels, TRUE), $excluded ? 'No amphtml link for an excluded node.' : 'amphtml link for an AMP node.');
      $this->assertContains('canonical', $rels);
    }

    // A node rendered without head links must not raise a warning.
    $build = ['#view_mode' => 'full', '#attached' => []];
    $node = $this->createPage(TRUE);
    \Drupal::moduleHandler()->alter('entity_view', $build, $node, $display);
    $this->assertEmpty($build['#attached']['html_head_link'] ?? []);
  }

}
