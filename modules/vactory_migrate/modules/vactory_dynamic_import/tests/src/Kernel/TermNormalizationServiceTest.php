<?php

declare(strict_types=1);

namespace Drupal\Tests\vactory_dynamic_import\Kernel;

use Drupal\vactory_dynamic_import\Service\TermNormalizationService;
use Drupal\vactory_dynamic_import\Form\DynamicImportExecute;
use Drupal\KernelTests\KernelTestBase;

/**
 * @coversDefaultClass \Drupal\vactory_dynamic_import\Service\TermNormalizationService
 * @group              vactory_dynamic_import
 */
class TermNormalizationServiceTest extends KernelTestBase
{

    /**
     * The mocked ban IP manager.
     *
     * @var \Drupal\ban\BanIpManagerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $banManager;

    /**
     * The tested TermNormalization service.
     *
     * @var \Drupal\vactory_dynamic_import\Service\TermNormalizationService
     */
    protected $termNormalization;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->termNormalization = new TermNormalizationService(\Drupal::service('transliteration'));
    }

    /**
     * Tests a banned IP.
     */
    public function testValidCSVContainsNoErrors()
    {
        $delimiter = ",";
        $file_path = "profiles/contrib/vactory_starter_kit/modules/vactory_migrate/modules/vactory_dynamic_import/tests/artifacts/TestVDI_0.csv";
    
        $object = new DynamicImportExecute();

        // Use reflection to access the protected method
        $trimCsvHeaderMethod = new \ReflectionMethod(DynamicImportExecute::class, 'trimCsvHeader');
        $trimCsvHeaderMethod->setAccessible(true);
    
        $getCsvHeaderMethod = new \ReflectionMethod(DynamicImportExecute::class, 'getCsvHeader');
        $getCsvHeaderMethod->setAccessible(true);

        // Call the method
        $trimCsvHeaderMethod->invoke($object, $file_path, $delimiter);  // Pass any required parameters
        $header = $getCsvHeaderMethod->invoke($object, $file_path, $delimiter);  // Pass any required parameters

        $response = $this->termNormalization->validateTerms($file_path, $header, $delimiter);
        dump($response);

        $this->assertEquals(["status" => true, "errors" => [], "term_fields" => true], $response);
    }

    /**
     * Test case for detecting accent differences.
     */
    public function testAccentsDifference(): void
    {
        $term1 = 'café';
        $term2 = 'café';

        // Access the protected method using Reflection
        $reflection = new \ReflectionClass($this->termNormalization);
        $method = $reflection->getMethod('identifyDifferences');
        $method->setAccessible(true);

        // Invoke the protected method
        $differences = $method->invoke($this->termNormalization, $term1, $term2);

        dump('testAccentsDifference : ', $differences);
        $this->assertNotContains('accents', $differences, 'Accents difference is detected.');
    }

    /**
     * Test case for detecting word boundaries.
     */
    public function testWordBoundariesDifference(): void
    {
        $term1 = 'Garage Agree';
        $term2 = 'Garage Agree';

        // Access the protected method using Reflection
        $reflection = new \ReflectionClass($this->termNormalization);
        $method = $reflection->getMethod('hasWordBoundaryDifference');
        $method->setAccessible(true);

        // Invoke the protected method
        $differences = $method->invoke($this->termNormalization, $term1, $term2);

        dump('testWordBoundariesDifference value : ', $differences);
        $this->assertFalse($differences, 'Word boundaries difference is detected.');
    }

    /**
     * Test case for detecting missing characters.
     */
    public function testMissingCharactersDifference(): void
    {
        $term1 = 'agreement';
        $term2 = 'agreement';

        // Access the protected method using Reflection
        $reflection = new \ReflectionClass($this->termNormalization);
        $method = $reflection->getMethod('hasMissingCharacterDifference');
        $method->setAccessible(true);

        // Invoke the protected method
        $differences = $method->invoke($this->termNormalization, $term1, $term2);

        dump('testMissingCharactersDifference value : ', $differences);
        // Assert the result is false
        $this->assertFalse($differences, 'Missing Character difference is detected.');
    }
  
    /**
     * Test case for detecting case differences.
     */
    public function testCaseDifference(): void
    {
        $term1 = 'Garage';
        $term2 = 'Garage';

        // Access the protected method using Reflection
        $reflection = new \ReflectionClass($this->termNormalization);
        $method = $reflection->getMethod('identifyDifferences');
        $method->setAccessible(true);

        // Invoke the protected method
        $differences = $method->invoke($this->termNormalization, $term1, $term2);

        dump('testCaseDifference : ', $differences);
        $this->assertNotContains('case', $differences, 'Case difference is detected.');
    }

  
}
