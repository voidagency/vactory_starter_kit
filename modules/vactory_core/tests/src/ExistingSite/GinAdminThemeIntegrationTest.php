<?php

namespace Drupal\Tests\vactory_core\ExistingSite;

use weitzman\DrupalTestTraits\ExistingSiteBase;

/**
 * Gin as administration theme: back-office pages render.
 *
 * Projects use gin, required by the profile, as their administration theme.
 * Catches a gin or gin_toolbar version that breaks the back-office pages
 * (server error, theme not applied).
 *
 * @group vactory_core
 */
class GinAdminThemeIntegrationTest extends ExistingSiteBase {

  /**
   * The administration theme before the test.
   *
   * @var string|null
   */
  private ?string $originalAdminTheme = NULL;

  /**
   * Whether the test installed gin itself (then uninstalls it).
   *
   * @var bool
   */
  private bool $installedGin = FALSE;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $themes = \Drupal::service('extension.list.theme')->getList();
    if (!isset($themes['gin'])) {
      $this->markTestSkipped('gin is required by the profile.');
    }
    if (!\Drupal::service('theme_handler')->themeExists('gin')) {
      \Drupal::service('theme_installer')->install(['gin']);
      $this->installedGin = TRUE;
    }
    $config = \Drupal::configFactory()->getEditable('system.theme');
    $this->originalAdminTheme = $config->get('admin');
    $config->set('admin', 'gin')->save();
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    if ($this->originalAdminTheme !== NULL) {
      \Drupal::configFactory()->getEditable('system.theme')
        ->set('admin', $this->originalAdminTheme)
        ->save();
    }
    if ($this->installedGin) {
      \Drupal::service('theme_installer')->uninstall(['gin']);
    }
    parent::tearDown();
  }

  /**
   * Content overview and node form render in gin, without server error.
   */
  public function testBackOfficePagesRender(): void {
    $account = $this->createUser([], NULL, TRUE);
    // drupalLogin() needs the login form, not served on a decoupled site.
    $this->drupalGet(user_pass_reset_url($account) . '/login');

    foreach (['/admin/content', '/node/add'] as $path) {
      $this->drupalGet($path);
      $this->assertSame(200, $this->getSession()->getStatusCode(), "$path answers 200.");
      $html = $this->getSession()->getPage()->getContent();
      $this->assertStringContainsString('/themes/contrib/gin/', $html, "$path is rendered with gin.");
    }
  }

}
