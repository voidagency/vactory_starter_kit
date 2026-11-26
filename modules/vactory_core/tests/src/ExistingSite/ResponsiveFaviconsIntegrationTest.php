<?php

namespace Drupal\Tests\vactory_core\ExistingSite;

use weitzman\DrupalTestTraits\ExistingSiteBase;

/**
 * Responsive favicons, enabled by the profile: tags reach the page head.
 *
 * Catches a version of the module that stops rendering the configured tags
 * or breaks the pages (the profile moved from 1.x-dev to 4.x). Since 4.x,
 * a tag whose icon file is missing from the favicons folder is dropped.
 *
 * @group vactory_core
 */
class ResponsiveFaviconsIntegrationTest extends ExistingSiteBase {

  /**
   * The module settings before the test, restored afterwards.
   *
   * @var array
   */
  private array $original = [];

  /**
   * Icon file created by the test.
   */
  private const ICON = 'public://favicons/vactory-test-icon-32.png';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    if (!\Drupal::moduleHandler()->moduleExists('responsive_favicons')) {
      $this->markTestSkipped('responsive_favicons is enabled by the profile.');
    }
    $config = \Drupal::configFactory()->getEditable('responsive_favicons.settings');
    $this->original = $config->getRawData();
    // Tags read back by the module from its settings, as an admin enters them.
    $config->set('path_type', 'upload')
      ->set('path', 'favicons')
      ->set('tags', [
        '<link rel="icon" type="image/png" sizes="32x32" href="/vactory-test-icon-32.png">',
        '<link rel="icon" type="image/png" sizes="16x16" href="/vactory-test-missing-16.png">',
      ])
      ->save();
    $directory = 'public://favicons';
    \Drupal::service('file_system')->prepareDirectory($directory, 1);
    file_put_contents(self::ICON, 'PNG');
    drupal_flush_all_caches();
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    if (file_exists(self::ICON)) {
      unlink(self::ICON);
    }
    if ($this->original) {
      \Drupal::configFactory()->getEditable('responsive_favicons.settings')
        ->setData($this->original)
        ->save();
    }
    parent::tearDown();
  }

  /**
   * The configured tags are in the head of a page; the icon route answers.
   */
  public function testTagsAreRendered(): void {
    // Since 4.x, tags are cached by the module: clear after configuring.
    drupal_flush_all_caches();
    $this->drupalGet('<front>');
    $this->assertLessThan(500, $this->getSession()->getStatusCode());
    $html = $this->getSession()->getPage()->getContent();
    $this->assertStringContainsString('vactory-test-icon-32.png', $html, 'The tag of an existing icon is rendered.');
    $this->assertStringNotContainsString('vactory-test-missing-16.png', $html, 'The tag of a missing icon is dropped.');

    $this->drupalGet('/favicon.ico');
    $this->assertLessThan(500, $this->getSession()->getStatusCode(), 'The favicon route does not fail.');
  }

}
