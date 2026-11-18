<?php

namespace Drupal\Tests\vactory_amp\ExistingSite;

use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\amp\Routing\AmpContext;
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
   * AMP display state to restore: NULL (created by the test) or its status.
   *
   * @var bool|null
   */
  protected $displayStatus;

  /**
   * Original amp.settings data, or NULL when the config did not exist.
   *
   * @var array|null
   */
  protected $ampSettings;

  /**
   * Whether setUp() changed the site (tearDown() restores only then).
   *
   * @var bool
   */
  protected $prepared = FALSE;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // AMP is enabled for a content type by enabling its "amp" view display.
    $display = EntityViewDisplay::load('node.vactory_page.amp');
    $this->displayStatus = $display?->status();
    $display ??= EntityViewDisplay::create([
      'targetEntityType' => 'node',
      'bundle' => 'vactory_page',
      'mode' => 'amp',
    ]);
    $display->setStatus(TRUE)->save();
    // The amp module ships no amp.settings: tearDown() deletes it.
    $config = \Drupal::configFactory()->getEditable('amp.settings');
    $this->ampSettings = $config->isNew() ? NULL : $config->getRawData();
    $config->set('amp_everywhere', TRUE)->save();
    $this->prepared = TRUE;
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    $purge = in_array('vactory_amp', $this->modulesToCleanup, TRUE);
    if ($this->prepared) {
      $display = EntityViewDisplay::load('node.vactory_page.amp');
      if ($this->displayStatus === NULL) {
        $display?->delete();
      }
      else {
        $display?->setStatus($this->displayStatus)->save();
      }
      $config = \Drupal::configFactory()->getEditable('amp.settings');
      if ($this->ampSettings === NULL) {
        $config->delete();
      }
      else {
        $config->setData($this->ampSettings)->save();
      }
    }
    parent::tearDown();
    // Once vactory_amp is uninstalled, its base field waits for cron to be
    // purged; until then the module cannot be installed and uninstalled
    // again.
    if ($purge) {
      field_purge_batch(1000, 'node-exclude_from_amp');
    }
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
    // vactory_amp.services.yml passes amp's five constructor arguments: a new
    // argument, even optional, must be added there too.
    $parameters = (new \ReflectionMethod(AmpContext::class, '__construct'))->getParameters();
    $this->assertSame(
      [
        'configFactory',
        'themeManager',
        'entityTypeInfo',
        'routeMatch',
        'adminContext',
      ],
      array_map(fn($parameter) => $parameter->getName(), $parameters)
    );

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
    foreach ([FALSE => TRUE, TRUE => FALSE] as $excluded => $expected) {
      $node = $this->createPage((bool) $excluded);
      $display = EntityViewDisplay::collectRenderDisplay($node, 'full');
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

    // An excluded node rendered without head links (in a view, as a
    // referenced entity...) must not raise an error.
    $build = ['#view_mode' => 'full', '#attached' => []];
    $node = $this->createPage(TRUE);
    \Drupal::moduleHandler()->alter('entity_view', $build, $node, $display);
    $this->assertArrayNotHasKey('html_head_link', $build['#attached']);
  }

}
