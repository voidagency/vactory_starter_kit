<?php

namespace Drupal\vactory_decoupled\Functional;

use Drupal\Tests\vactory_core\Functional\VactoryExistingSiteBase;

/**
 * Validate decoupled nodes metatags.
 *
 * @group vactory_decoupled
 */
class MetaTagsTest extends VactoryExistingSiteBase {

  /**
   * {@inheritDoc}
   */
  protected function setUp(): void {

    parent::setUp();

    // Create and log in an admin user using DTT helper.
    $adminUser = $this->createUser([], NULL, TRUE);
    $this->drupalLogin($adminUser);
  }

  /**
   * Test meta tags for normal page.
   */
  public function testMetaTags(): void {
    // Create a node of type vactory_page using DTT helper.
    $node = $this->createNode([
      'type' => 'vactory_page',
      'title' => 'Test MetaTags Page',
      'status' => 1,
    ]);

    $langcode = $node->language()->getId();
    $alias = $this->container->get('path_alias.manager')->getAliasByPath('/node/' . $node->id(), $langcode);

    $json = $this->fetchNodeJsonApi($node, $langcode);

    // Assert internal_metatag is present.
    $this->assertArrayHasKey('internal_metatag', $json['data']['attributes']);

    $metatags = $json['data']['attributes']['internal_metatag'];

    // Vérifier chaque méta-tag.
    foreach ($metatags as $tag) {
      if (isset($tag['id'])) {
        // Vérifier que chaque méta-tag a soit href soit content.
        $hasHref = isset($tag['attributes']['href']) && !empty($tag['attributes']['href']);
        $hasContent = isset($tag['attributes']['content']) && !empty($tag['attributes']['content']);

        $this->assertTrue($hasHref || $hasContent, "Le méta-tag '{$tag['id']}' doit avoir un attribut 'href' ou 'content' avec une valeur.");

        // Vérification spécifique pour canonical_url.
        if ($tag['id'] === 'canonical_url') {
          $expected = "{$this->baseUrl}/{$langcode}{$alias}";
          $this->assertEquals($expected, $tag['attributes']['href']);
        }
      }
    }
  }

  /**
   * Test meta tags for home page.
   */
  public function testHomepageMetaTags(): void {
    // Get the homepage path.
    $frontPath = $this->configFactory->get('system.site')->get('page.front');

    if (preg_match('#^/node/(\d+)$#', $frontPath, $matches)) {
      $nid = $matches[1];
      $node = \Drupal::entityTypeManager()->getStorage('node')->load($nid);
      $langcode = $node->language()->getId();
      $this->assertNotNull($node, 'Homepage node exists.');
      $this->assertEquals('vactory_page', $node->bundle(), 'Homepage is a vactory_page.');

      $json = $this->fetchNodeJsonApi($node, $langcode);

      $this->assertArrayHasKey('internal_metatag', $json['data']['attributes']);

      $metatags = $json['data']['attributes']['internal_metatag'];

      $found = FALSE;
      foreach ($metatags as $tag) {
        if (!empty($tag['id']) && $tag['id'] === 'canonical_url') {
          $found = TRUE;
          $expected = $this->baseUrl . "/{$langcode}";
          $this->assertEquals($expected, $tag['attributes']['href']);
          break;
        }
      }
      $this->assertTrue($found, 'canonical_url metatag exists on homepage.');
    }
    else {
      $this->fail("Homepage path is not a node: $frontPath");
    }
  }

}
