<?php

namespace Drupal\vactory_decoupled\Tests\Functional;

use weitzman\DrupalTestTraits\ExistingSiteBase;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\webform\Entity\Webform;
use Drupal\webform\WebformInterface;
use Drupal\webform\Entity\WebformSubmission;
use Drupal\Core\Url;
use Drupal\Component\Serialization\Json;
use GuzzleHttp\ClientInterface;
use Drupal\Core\Serialization\Yaml;

/**
 * Test l'affichage et la soumission d’un webform - decoupled.
 *
 * @group vactory_decoupled
 */
class WebformParagraphTest extends ExistingSiteBase {

  const DF_CREATOR_MODULE = 'vactory_dynamic_field_volatile';

  const DEFAULT_COLLECTION_SETTING = [
    'name' => 'Simple webform',
    'multiple' => FALSE,
    'category' => 'Formulaires',
    'enabled' => TRUE,
    'fields' => [
      'webform' => [
        'type' => 'webform_decoupled',
        'label' => 'Formulaire',
        'options' => [
          '#required' => TRUE,
        ],
      ],
    ],
  ];

  /**
   * Track created DF files for cleanup.
   *
   * @var array
   */
  protected $createdDfFiles = [];

  /**
   * Paragraph user for testing.
   *
   * @var \Drupal\webform\Entity\Webform
   */
  protected Webform $webform;

  /**
   * Client Interface.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected ClientInterface $httpClient;

  /**
   * Track modules installed during the test.
   *
   * @var string[]
   */
  protected array $modulesInstalledDuringTest = [];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Assurer que les modules sont installés.
    $this->ensureModuleInstalled('webform');
    $this->ensureModuleInstalled('vactory_decoupled');
    $this->ensureModuleInstalled('vactory_decoupled_webform');
    $this->ensureModuleInstalled(self::DF_CREATOR_MODULE);

    // Retrieve core services.
    $this->httpClient = \Drupal::httpClient();

    // Create and log in an admin user using DTT helper.
    $admin = $this->createUser([], NULL, TRUE);
    $this->drupalLogin($admin);

    // Create test webform.
    $this->webform = Webform::create([
      'id' => 'test_formulaire_' . time(),
      'title' => 'Formulaire de test Avec PHP UNIT',
      'elements' => [
        'email' => [
          '#type' => 'email',
          '#title' => 'An email',
          '#required' => TRUE,
        ],
      ],
    ]);
    $this->webform->save();
  }

  /**
   * Teste l’affichage du webform dans un paragraphe.
   */
  public function testDecoupledWebform(): void {
    // Prepare the DF.
    $df_name = 'test-webform';
    $this->writeDfFile(self::DEFAULT_COLLECTION_SETTING, $df_name);
    $widget_id = implode(':', [self::DF_CREATOR_MODULE, $df_name]);

    // Create paragraph.
    $paragraph = Paragraph::create([
      'type' => 'vactory_component',
      'field_vactory_component' => [
        'widget_id' => $widget_id,
        'widget_data' => json_encode([
          [
            'webform' => [
              'id' => $this->webform->id(),
              'style' => '',
            ],
          ],
        ]),
      ],
    ]);
    $paragraph->save();

    // Create the node (Vactory_Page) : with DTT helper.
    $node = $this->createNode([
      'type' => 'vactory_page',
      'title' => 'Page_avec_formulaire_PHP_UNIT_' . time(),
      'status' => 1,
      'moderation_state' => 'published',
      'field_vactory_paragraphs' => [
        [
          'target_id' => $paragraph->id(),
          'target_revision_id' => $paragraph->getRevisionId(),
        ],
      ],
    ]);

    // Test with json api.
    $langcode = $node->language()->getId();
    $parsedUrl = parse_url($this->baseUrl);

    // Fallbacks.
    $scheme = $parsedUrl['scheme'] ?? 'http';
    $host = $parsedUrl['host'] ?? 'localhost';
    $port = isset($parsedUrl['port']) ? ':' . $parsedUrl['port'] : '';

    // URL finale.
    $fullUrl = "{$scheme}://{$host}{$port}/{$langcode}/api/node/vactory_page/{$node->uuid()}?include=field_vactory_paragraphs";

    $this->drupalGet($fullUrl);
    $this->assertSession()->statusCodeEquals(200);

    $response = $this->getSession()->getPage()->getContent();
    $data = json_decode($response, TRUE);

    $component = $data['included'][0]['attributes']['field_vactory_component'];

    // Vérifier que le widget_id est correct.
    $this->assertEquals(
      $widget_id,
      $component['widget_id'],
      'Widget ID should be vactory_decoupled_webform:webform.'
    );

    $this->assertJson($component['widget_data'], 'Widget data should be valid JSON.');

    // Décoder widget_data pour tester son contenu.
    $widgetData = Json::decode($component['widget_data']);

    // Vérifier que le webform ID correspond.
    $this->assertEquals(
      $this->webform->id(),
      $widgetData['components'][0]['webform']['id'],
      'Webform ID should match the test form.'
    );

    // Vérifier qu’un champ email existe avec required=true.
    $elements = $widgetData['components'][0]['webform']['elements'] ?? NULL;
    $this->assertNotNull($elements, 'Webform elements should exist.');

    $this->assertArrayHasKey('email', $elements, 'Email field should exist in elements.');
    $this->assertArrayHasKey('validation', $elements['email'], 'Email field should have validation key.');
    $this->assertArrayHasKey('required', $elements['email']['validation'], 'Email field should have required key.');

    $this->assertTrue(
      $elements['email']['validation']['required'],
      'Email field should be required.'
    );

    // Cleanup.
    $paragraph->delete();
  }

  /**
   * Teste la soumission du webform valide.
   */
  public function testWebformSubmission(): void {
    $json = $this->submitWebform([
      'webform_id' => $this->webform->id(),
      'email' => 'void_tester@void.com',
      'in_draft' => 'false',
    ], 200);

    $this->assertArrayHasKey('sid', $json, 'Un SID doit être retourné.');
    $this->assertNotEmpty($json['sid'], 'Le SID ne doit pas être vide.');

    $submission = WebformSubmission::load($json['sid']);
    $this->assertNotNull($submission, 'La soumission doit exister.');
    $this->assertEquals(
      'void_tester@void.com',
      $submission->getData('email')['email'],
      'Le champ email doit être correctement enregistré.'
    );
  }

  /**
   * Teste la soumission invalide - email incorrect.
   */
  public function testInvalidEmailSubmission(): void {
    $this->submitWebform([
      'webform_id' => $this->webform->id(),
      'email' => 'invalid-email-format',
      'in_draft' => 'false',
    ], 400);
  }

  /**
   * Teste la soumission d’un webform fermé.
   */
  public function testClosedWebformSubmission(): void {
    $this->webform->setStatus(WebformInterface::STATUS_CLOSED)->save();

    $json = $this->submitWebform([
      'webform_id' => $this->webform->id(),
      'email' => 'closed_tester@void.com',
      'in_draft' => 'false',
    ], 400);

    $this->assertArrayHasKey('error', $json, 'Une erreur doit être retournée.');
    $this->assertEquals(
      'This webform is closed, or too many submissions have been made.',
      $json['error']['message'],
      'Le message doit indiquer que le webform est fermé.'
    );
  }

  /**
   * Teste la soumission invalide - champ email manquant.
   */
  public function testMissingEmailSubmission(): void {
    $json = $this->submitWebform([
      'webform_id' => $this->webform->id(),
      'in_draft' => 'false',
    ], 400);

    $this->assertArrayHasKey('error', $json, 'Une erreur doit être retournée.');
    $errorMessage = is_array($json['error']) ? implode(' ', $json['error']) : $json['error'];
    $this->assertStringContainsString('email', $errorMessage, 'Le message doit mentionner le champ requis "email".');
  }

  /**
   * Helper to submit a webform.
   */
  protected function submitWebform(array $submissionData, int $expectedStatus = 200): array {
    $url = Url::fromUserInput('/_webform', ['absolute' => TRUE])->toString();

    $response = $this->httpClient->post($url, [
      'headers' => [
        'Accept' => 'application/json',
        'Content-Type' => 'application/x-www-form-urlencoded',
      ],
      'form_params' => $submissionData,
      'http_errors' => FALSE,
    ]);

    $this->assertEquals($expectedStatus, $response->getStatusCode(), "Expected HTTP $expectedStatus response.");

    return json_decode($response->getBody()->getContents(), TRUE);
  }

  /**
   * Ensure a module is installed and track if we installed it during the test.
   */
  protected function ensureModuleInstalled(string $module_name): void {
    $moduleHandler = \Drupal::service('module_handler');
    $moduleInstaller = \Drupal::service('module_installer');

    if (!$moduleHandler->moduleExists($module_name)) {
      $moduleInstaller->install([$module_name]);
      $this->modulesInstalledDuringTest[] = $module_name;
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    // Clean up created DF files.
    foreach ($this->createdDfFiles as $dfPath) {
      if (file_exists($dfPath)) {
        $this->removeDirectory($dfPath);
      }
    }

    if (isset($this->webform) && $this->webform) {
      $this->webform->delete();
    }
    // Désinstaller les modules installés pendant le test.
    if (!empty($this->modulesInstalledDuringTest)) {
      $moduleInstaller = \Drupal::service('module_installer');
      $moduleInstaller->uninstall($this->modulesInstalledDuringTest);
    }
    parent::tearDown();
  }

  /**
   * Write DF file and track for cleanup.
   *
   * @param array $content
   *   The content to write.
   * @param string $name
   *   The DF name.
   *
   * @return bool
   *   TRUE if file was written successfully.
   */
  protected function writeDfFile(array $content, $name): bool {
    $yaml_config = Yaml::encode($content);
    $dest_uri = 'private://volatile-df';
    $dest_df_uri = $dest_uri . '/' . $name;

    if (!file_exists($dest_df_uri)) {
      mkdir($dest_df_uri, 0777, TRUE);
    }

    $filepath = \Drupal::service('file_system')->realpath($dest_df_uri . '/settings.yml');
    $printed = file_put_contents($filepath, $yaml_config);

    // Track the directory for cleanup.
    $this->createdDfFiles[] = \Drupal::service('file_system')->realpath($dest_df_uri);

    return (bool) $printed;
  }

  /**
   * Recursively remove a directory and its contents.
   *
   * @param string $dir
   *   The directory path to remove.
   */
  protected function removeDirectory(string $dir): void {
    if (!is_dir($dir)) {
      return;
    }

    $files = array_diff(scandir($dir), ['.', '..']);
    foreach ($files as $file) {
      $path = $dir . DIRECTORY_SEPARATOR . $file;
      if (is_dir($path)) {
        $this->removeDirectory($path);
      }
      else {
        unlink($path);
      }
    }
    rmdir($dir);
  }

}
