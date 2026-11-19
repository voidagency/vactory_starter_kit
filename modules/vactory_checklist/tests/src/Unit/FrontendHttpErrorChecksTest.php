<?php

namespace Drupal\Tests\vactory_checklist\Unit;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Language\Language;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Site\Settings;
use Drupal\Tests\UnitTestCase;
use Drupal\vactory_checklist\Plugin\Checklist\GoogleConsoleFilesCheck;
use Drupal\vactory_checklist\Plugin\Checklist\GtmCheck;
use Drupal\vactory_checklist\Plugin\Checklist\SitemapHostCheck;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;

/**
 * Tests the checks calling the frontend when the frontend fails.
 *
 * A network error (DNS, connection refused, timeout) must give a failed check
 * like an HTTP error does, not crash the checklist page.
 *
 * @group vactory_checklist
 */
class FrontendHttpErrorChecksTest extends UnitTestCase {

  /**
   * Settings pointing to the frontend.
   *
   * @var \Drupal\Core\Site\Settings
   */
  protected $settings;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->settings = new Settings(['BASE_FRONTEND_URL' => 'https://front.example']);
  }

  /**
   * Provides the frontend failures.
   */
  public static function failureProvider(): array {
    return [
      'network error' => [
        new ConnectException('Could not resolve host: front.example', new Request('GET', 'https://front.example')),
        'Could not resolve host',
      ],
      'http error' => [new Response(404), '404 Not Found'],
    ];
  }

  /**
   * The sitemap check reports the failure.
   *
   * @param \Throwable|\Psr\Http\Message\ResponseInterface $failure
   *   What the frontend returns.
   * @param string $error
   *   Expected part of the reported error.
   *
   * @dataProvider failureProvider
   */
  public function testSitemapHostCheck($failure, string $error): void {
    $language_manager = $this->createMock(LanguageManagerInterface::class);
    $language_manager->method('getLanguages')->willReturn([new Language(['id' => 'fr'])]);
    $container = new ContainerBuilder();
    $container->set('http_client', $this->createHttpClient($failure));
    $container->set('language_manager', $language_manager);
    $container->set('string_translation', $this->getStringTranslationStub());
    \Drupal::setContainer($container);

    $result = (new SitemapHostCheck([], 'sitemap_url_check', []))->runCheck();

    $this->assertFalse($result['status']);
    $this->assertCount(1, $result['details']);
    $this->assertStringContainsString('Impossible de récupérer le sitemap pour la langue fr', (string) $result['details'][0]['error']);
    $this->assertStringContainsString($error, (string) $result['details'][0]['error']);
  }

  /**
   * The Google Search Console files check reports the failure.
   *
   * @param \Throwable|\Psr\Http\Message\ResponseInterface $failure
   *   What the frontend returns.
   * @param string $error
   *   Expected part of the reported error.
   *
   * @dataProvider failureProvider
   */
  public function testGoogleConsoleFilesCheck($failure, string $error): void {
    $check = new GoogleConsoleFilesCheck([], 'google_console_files_check', [], $this->createHttpClient($failure), $this->settings);
    $check->setStringTranslation($this->getStringTranslationStub());

    $result = $check->runCheck();

    $this->assertFalse($result['status']);
    $this->assertStringContainsString('Impossible de récupérer les fichiers depuis le frontend', (string) $result['message']);
    $this->assertStringContainsString($error, $result['details'][0]['error']);
  }

  /**
   * The GTM check reports the failure.
   *
   * @param \Throwable|\Psr\Http\Message\ResponseInterface $failure
   *   What the frontend returns.
   * @param string $error
   *   Expected part of the reported error.
   *
   * @dataProvider failureProvider
   */
  public function testGtmCheck($failure, string $error): void {
    $check = new GtmCheck([], 'gtm_check', [], $this->createHttpClient($failure), $this->settings);

    $result = $check->runCheck();

    $this->assertFalse($result['status']);
    $this->assertStringStartsWith('Failed to fetch frontend page: ', $result['message']);
    $this->assertStringContainsString($error, $result['message']);
  }

  /**
   * Builds an HTTP client whose only response is the given failure.
   *
   * @param \Throwable|\Psr\Http\Message\ResponseInterface $failure
   *   What the frontend returns.
   */
  protected function createHttpClient($failure): Client {
    return new Client(['handler' => HandlerStack::create(new MockHandler([$failure]))]);
  }

}
