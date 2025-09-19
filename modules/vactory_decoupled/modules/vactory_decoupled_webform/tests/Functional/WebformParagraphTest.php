<?php

namespace Drupal\vactory_decoupled\Tests\Functional;

use weitzman\DrupalTestTraits\ExistingSiteBase;
use Drupal\user\Entity\User;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\node\Entity\Node;
use Drupal\webform\Entity\Webform;
use Drupal\webform\WebformInterface;
use Drupal\webform\Entity\WebformSubmission;
use Drupal\Core\Url;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use GuzzleHttp\ClientInterface;

/**
 * Test l'affichage et la soumission d’un webform - decoupled.
 *
 * @group vactory_decoupled
 */
class WebformParagraphTest extends ExistingSiteBase {

  /**
   * Admin password used for authentication.
   *
   * @var \Drupal\user\Entity\User
   */
  protected User $admin;

  /**
   * Node used for testing.
   *
   * @var \Drupal\node\Entity\Node
   */
  protected Node $node;

  /**
   * Paragraph user for testing.
   *
   * @var \Drupal\paragraphs\Entity\Paragraph
   */
  protected Paragraph $paragraph;

  /**
   * Paragraph user for testing.
   *
   * @var \Drupal\webform\Entity\Webform
   */
  protected Webform $webform;

  /**
   * Entity Type Manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Client Interface.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected ClientInterface $httpClient;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Vérifier si les modules nécessaires sont actifs avant de lancer le test.
    $this->assertTrue(
      \Drupal::moduleHandler()->moduleExists('webform'),
      'Le module "webform" doit être activé pour exécuter ce test.'
    );

    $this->assertTrue(
      \Drupal::moduleHandler()->moduleExists('vactory_decoupled'),
      'Le module "vactory_decoupled" doit être activé pour exécuter ce test.'
    );

    $this->assertTrue(
      \Drupal::moduleHandler()->moduleExists('vactory_decoupled_webform'),
      'Le module "vactory_decoupled_webform" doit être activé pour exécuter ce test.'
    );

    // Retrieve core services.
    $this->entityTypeManager = \Drupal::entityTypeManager();
    $this->httpClient = \Drupal::httpClient();

    // Create and log in an admin user using DTT helper.
    $this->admin = $this->createUser([], NULL, TRUE);
    $this->drupalLogin($this->admin);

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
    // Create the paragraph : Vactory_Component.
    $this->paragraph = Paragraph::create([
      'type' => 'vactory_component',
      'field_vactory_component' => [
        'widget_id' => 'vactory_decoupled_webform:webform',
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
    $this->paragraph->save();

    $paragraphStorage = $this->entityTypeManager->getStorage('paragraph');

    // Create the node (Vactory_Page) : with DTT helper.
    $this->node = $this->createNode([
      'type' => 'vactory_page',
      'title' => 'Page_avec_formulaire_PHP_UNIT_' . time(),
      'status' => 1,
      'moderation_state' => 'published',
      'field_vactory_paragraphs' => [
        [
          'target_id' => $this->paragraph->id(),
          'target_revision_id' => $paragraphStorage->getLatestRevisionId($this->paragraph->id()),
        ],
      ],
    ]);

    // Test with json api.
    $langcode = $this->node->language()->getId();
    $parsedUrl = parse_url($this->baseUrl);

    // Fallbacks.
    $scheme = $parsedUrl['scheme'] ?? 'http';
    $host = $parsedUrl['host'] ?? 'localhost';
    $port = isset($parsedUrl['port']) ? ':' . $parsedUrl['port'] : '';

    // URL finale.
    $fullUrl = "{$scheme}://{$host}{$port}/{$langcode}/api/node/vactory_page/{$this->node->uuid()}?include=field_vactory_paragraphs";

    $this->drupalGet($fullUrl);
    $this->assertSession()->statusCodeEquals(200);

    $response = $this->getSession()->getPage()->getContent();
    $data = json_decode($response, TRUE);

    $component = $data['included'][0]['attributes']['field_vactory_component'];

    // Vérifier que le widget_id est correct.
    $this->assertEquals(
      'vactory_decoupled_webform:webform',
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
    $this->assertTrue(
      $widgetData['components'][0]['webform']['elements']['email']['validation']['required'],
      'Email field should be required.'
    );
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
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    if (isset($this->paragraph) && $this->paragraph) {
      $this->paragraph->delete();
    }
    if (isset($this->webform) && $this->webform) {
      $this->webform->delete();
    }
    parent::tearDown();
  }

}
