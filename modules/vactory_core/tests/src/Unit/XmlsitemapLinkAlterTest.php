<?php

namespace Drupal\Tests\vactory_core\Unit;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Tests\UnitTestCase;

/**
 * Tests vactory_core_xmlsitemap_link_alter().
 *
 * @group vactory_core
 */
class XmlsitemapLinkAlterTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    require_once dirname(__DIR__, 3) . '/vactory_core.module';
    $container = new ContainerBuilder();
    $container->set('config.factory', $this->getConfigFactoryStub([
      'system.site' => ['page' => ['front' => '/node/1']],
    ]));
    \Drupal::setContainer($container);
  }

  /**
   * The front page link is disabled and not accessible.
   */
  public function testFrontPageLinkIsExcluded(): void {
    $link = ['loc' => '/node/1', 'status' => 1, 'access' => 1];
    vactory_core_xmlsitemap_link_alter($link, []);
    $this->assertSame(0, $link['status']);
    $this->assertFalse($link['access']);
  }

  /**
   * Other links are left untouched.
   */
  public function testOtherLinkIsKept(): void {
    $link = ['loc' => '/node/2', 'status' => 1, 'access' => 1];
    vactory_core_xmlsitemap_link_alter($link, []);
    $this->assertSame(['loc' => '/node/2', 'status' => 1, 'access' => 1], $link);
  }

}
