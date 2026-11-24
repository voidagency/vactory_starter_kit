<?php

namespace Drupal\Tests\vactory_decoupled_router\Unit;

use Drupal\Component\Transliteration\TransliterationInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\Exception\UndefinedLinkTemplateException;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\PathProcessor\InboundPathProcessorInterface;
use Drupal\Core\Url;
use Drupal\path_alias\AliasManagerInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\vactory_decoupled_router\PageRules;

/**
 * Which paths are the canonical address of an entity, for the router.
 *
 * @coversDefaultClass \Drupal\vactory_decoupled_router\PageRules
 * @group vactory_decoupled_router
 */
class PageRulesCanonicalPathTest extends UnitTestCase {

  /**
   * Builds the rules with a path processor that knows one alias.
   *
   * The processor removes the "en" and "fr" language prefixes, then gives
   * "/node/12" for the "/about" alias and for the front page.
   *
   * @param array $received
   *   Receives the paths given to the processor.
   */
  protected function createRules(array &$received = []): PageRules {
    $processor = $this->createMock(InboundPathProcessorInterface::class);
    $processor->method('processInbound')
      ->willReturnCallback(function ($path) use (&$received) {
        $received[] = $path;
        $path = preg_replace('#^/(en|fr)(?=/|$)#', '', $path) ?: '/';
        return in_array($path, ['/about', '/'], TRUE) ? '/node/12' : $path;
      });
    $config_factory = $this->getConfigFactoryStub([
      'language.negotiation' => [
        'url' => [
          'source' => 'path_prefix',
          'prefixes' => ['en' => 'en', 'fr' => 'fr'],
        ],
      ],
    ]);
    return new PageRules(
      $config_factory,
      $this->createMock(ModuleHandlerInterface::class),
      $this->createMock(AliasManagerInterface::class),
      $this->createMock(TransliterationInterface::class),
      $this->createMock(LanguageManagerInterface::class),
      $processor
    );
  }

  /**
   * An entity whose canonical URL has the given internal path.
   *
   * @param string|null $internal_path
   *   The internal path, without leading slash; NULL for an entity type
   *   without canonical URL.
   */
  protected function createEntity(?string $internal_path): EntityInterface {
    $entity = $this->createMock(EntityInterface::class);
    $to_url = $entity->method('toUrl')->with('canonical');
    if ($internal_path === NULL) {
      $to_url->willThrowException(new UndefinedLinkTemplateException('No link template "canonical".'));
      return $entity;
    }
    $url = $this->createMock(Url::class);
    $url->method('getInternalPath')->willReturn($internal_path);
    $to_url->willReturn($url);
    return $entity;
  }

  /**
   * The canonical path, its alias and the front page; nothing else.
   *
   * @covers ::isCanonicalPath
   * @dataProvider providerPaths
   */
  public function testCanonicalPath(string $path, bool $expected): void {
    $rules = $this->createRules();
    $this->assertSame($expected, $rules->isCanonicalPath($this->createEntity('node/12'), $path));
  }

  /**
   * Data provider for testCanonicalPath().
   */
  public static function providerPaths(): array {
    return [
      'internal path' => ['/node/12', TRUE],
      'internal path with language prefix' => ['/fr/node/12', TRUE],
      'internal path without leading slash' => ['node/12', TRUE],
      'internal path with query string' => ['/node/12?page=2', TRUE],
      'internal path in a URL' => ['https://example.com/en/node/12', TRUE],
      'alias' => ['/about', TRUE],
      'alias with language prefix' => ['/en/about', TRUE],
      'alias with trailing slash' => ['/about/', TRUE],
      'front page' => ['/', TRUE],
      'front page of a language' => ['/fr', TRUE],
      'edit form' => ['/node/12/edit', FALSE],
      'edit form with language prefix' => ['/en/node/12/edit', FALSE],
      'delete form' => ['/node/12/delete', FALSE],
      'revisions' => ['/node/12/revisions', FALSE],
      'latest version' => ['/node/12/latest', FALSE],
      'translations' => ['/node/12/translations', FALSE],
      'upper case' => ['/NODE/12', FALSE],
      'leading zero' => ['/node/012', FALSE],
      'trailing slash' => ['/node/12/', FALSE],
      'trailing slash with language prefix' => ['/en/node/12/', FALSE],
      'another node' => ['/node/123', FALSE],
      'another alias' => ['/about-us', FALSE],
      'JSON:API resource' => ['/api/node/vactory_page/a-uuid', FALSE],
    ];
  }

  /**
   * The rule follows the canonical URL of the entity, whatever its type.
   *
   * @covers ::isCanonicalPath
   */
  public function testOtherEntityTypes(): void {
    $rules = $this->createRules();
    $term = $this->createEntity('taxonomy/term/7');
    $this->assertTrue($rules->isCanonicalPath($term, '/taxonomy/term/7'));
    $this->assertTrue($rules->isCanonicalPath($term, '/fr/taxonomy/term/7'));
    $this->assertFalse($rules->isCanonicalPath($term, '/taxonomy/term/7/edit'));
    $this->assertFalse($rules->isCanonicalPath($term, '/node/12'));

    // An entity type without canonical URL has no address.
    $without = $this->createEntity(NULL);
    $this->assertFalse($rules->isCanonicalPath($without, '/admin/thing/7'));
  }

  /**
   * The path is given to the processor as the router of the core does.
   *
   * @covers ::isCanonicalPath
   */
  public function testPathGivenToTheProcessor(): void {
    $received = [];
    $rules = $this->createRules($received);
    $entity = $this->createEntity('node/12');
    foreach (['/about/', '/', 'https://example.com/en/about?page=2#top'] as $path) {
      $rules->isCanonicalPath($entity, $path);
    }
    $this->assertSame(['/about', '/', '/en/about'], $received);
  }

  /**
   * A value starting with two slashes is not a path of the site.
   *
   * @covers ::isSitePath
   * @dataProvider providerSitePaths
   */
  public function testSitePath(string $path, bool $expected): void {
    $this->assertSame($expected, $this->createRules()->isSitePath($path));
  }

  /**
   * Data provider for testSitePath().
   */
  public static function providerSitePaths(): array {
    return [
      'path' => ['/about', TRUE],
      'front page' => ['/', TRUE],
      'no leading slash' => ['about', TRUE],
      'two slashes inside' => ['/about//us', TRUE],
      'URL with a scheme' => ['https://example.com/about', TRUE],
      'two slashes and a segment' => ['//about', FALSE],
      'two slashes only' => ['//', FALSE],
      'URL without scheme' => ['//example.com/about', FALSE],
    ];
  }

}
