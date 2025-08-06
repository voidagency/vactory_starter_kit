<?php

namespace Drupal\vactory_decoupled\Functional;

use weitzman\DrupalTestTraits\ExistingSiteBase;

/**
 * Validate decoupled nodes metatags.
 *
 * @group vactory_decoupled
 */
class MetaTagsTest extends ExistingSiteBase {

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
    $aliasManager = \Drupal::service('path_alias.manager');
    $alias = $aliasManager->getAliasByPath('/node/' . $node->id(), $langcode);

    $parsedUrl = parse_url($this->baseUrl);

    // Fallbacks in case parts are missing.
    $scheme = $parsedUrl['scheme'] ?? 'http';
    $host = $parsedUrl['host'] ?? 'localhost';
    $port = isset($parsedUrl['port']) ? ':' . $parsedUrl['port'] : '';

    // Full URL (for request context).
    $fullUrl = "{$scheme}://{$host}{$port}/{$langcode}/api/node/vactory_page/{$node->uuid()}";

    // Fetch the node via JSON:API.
    $this->drupalGet($fullUrl);
    // Assert response status.
    $this->assertSession()->statusCodeEquals(200);

    // Decode response.
    $json = json_decode($this->getSession()->getPage()->getContent(), TRUE);

    // Assert internal_metatag is present.
    $this->assertArrayHasKey('internal_metatag', $json['data']['attributes']);

    // Find metatag with id = canonical_url.
    $canonicalExists = FALSE;
    foreach ($json['data']['attributes']['internal_metatag'] as $item) {
      if (isset($item['id']) && $item['id'] === 'canonical_url') {
        $canonicalExists = TRUE;
        $expected = "{$scheme}://{$host}{$port}/{$langcode}{$alias}";
        $this->assertEquals($expected, $item['attributes']['href']);
        break;
      }
    }

    // Assert canonical_url metatag is found.
    $this->assertTrue($canonicalExists, 'canonical_url metatag exists.');
  }

  /**
   * Test meta tags for home page.
   */
  public function testHomepageMetaTags(): void {
    // Get the homepage path.
    $frontPath = \Drupal::config('system.site')->get('page.front');

    if (preg_match('#^/node/(\d+)$#', $frontPath, $matches)) {
      $nid = $matches[1];
      $node = \Drupal::entityTypeManager()->getStorage('node')->load($nid);
      $langcode = $node->language()->getId();
      $this->assertNotNull($node, 'Homepage node exists.');
      $this->assertEquals('vactory_page', $node->bundle(), 'Homepage is a vactory_page.');

      $parsedUrl = parse_url($this->baseUrl);

      // Fallbacks in case parts are missing.
      $scheme = $parsedUrl['scheme'] ?? 'http';
      $host = $parsedUrl['host'] ?? 'localhost';
      $port = isset($parsedUrl['port']) ? ':' . $parsedUrl['port'] : '';

      // Full URL (for request context).
      $fullUrl = "{$scheme}://{$host}{$port}/{$langcode}/api/node/vactory_page/{$node->uuid()}";

      // Fetch the node via JSON:API.
      $this->drupalGet($fullUrl);
      // Assert response status.
      $this->assertSession()->statusCodeEquals(200);

      // Decode response.
      $json = json_decode($this->getSession()->getPage()->getContent(), TRUE);

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
