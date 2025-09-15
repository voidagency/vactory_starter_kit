<?php

namespace Drupal\vactory_decoupled\Tests\Functional;

use weitzman\DrupalTestTraits\ExistingSiteBase;
use Drupal\user\Entity\User;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\node\Entity\Node;
use Drupal\Core\Url;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use GuzzleHttp\ClientInterface;

/**
 * Test l'affichage et la soumission d’un webform décorrélé (decoupled).
 *
 * @group vactory_decoupled
 */
class WebformParagraphTest extends ExistingSiteBase
{

  protected User $admin;
  protected Node $node;
  protected Paragraph $paragraph;
  protected \Drupal\webform\Entity\Webform $webform;
  protected EntityTypeManagerInterface $entityTypeManager;
  protected ClientInterface $httpClient;

  protected function setUp(): void
  {
    parent::setUp();

    $this->entityTypeManager = \Drupal::entityTypeManager();
    $this->httpClient = \Drupal::httpClient();

    // Create and log in an admin user using DTT helper.
    $this->admin = $this->createUser([], NULL, TRUE);
    $this->drupalLogin($this->admin);

    // Create test webform.
    $this->webform = \Drupal\webform\Entity\Webform::create([
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
   * Helper to submit a webform.
   */
  protected function submitWebform(array $submissionData, int $expectedStatus = 200): array
  {
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
   * Teste l’affichage du webform dans un paragraphe décorrélé.
   */
  public function testDecoupledWebform(): void
  {
    // Create the paragraph : Vactory_Component 
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

    $field = $this->paragraph->get('field_vactory_component')->getValue();
    $this->assertNotEmpty($field, 'Le champ field_vactory_component doit exister.');

    $widgetField = $field[0];
    $widgetDataJson = $widgetField['widget_data'] ?? '';
    $this->assertNotEmpty($widgetDataJson, 'widget_data doit être défini.');
    $widgetData = json_decode($widgetDataJson, true);

    $webformId = $widgetData[0]['webform']['id'] ?? null;
    $this->assertNotNull($webformId, 'L’ID du webform doit être défini');
  }

  /**
   * Teste la soumission du webform valide.
   */
  public function testWebformSubmission(): void
  {
    $json = $this->submitWebform([
      'webform_id' => $this->webform->id(),
      'email' => 'void_tester@void.com',
      'in_draft' => 'false',
    ], 200);

    $this->assertArrayHasKey('sid', $json, 'Un SID doit être retourné.');
    $this->assertNotEmpty($json['sid'], 'Le SID ne doit pas être vide.');

    $submission = \Drupal\webform\Entity\WebformSubmission::load($json['sid']);
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
  public function testInvalidEmailSubmission(): void
  {
    $this->submitWebform([
      'webform_id' => $this->webform->id(),
      'email' => 'invalid-email-format',
      'in_draft' => 'false',
    ], 400);
  }

  /**
   * Teste la soumission d’un webform fermé.
   */
  public function testClosedWebformSubmission(): void
  {
    $this->webform->setStatus(\Drupal\webform\WebformInterface::STATUS_CLOSED)->save();

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
  public function testMissingEmailSubmission(): void
  {
    $json = $this->submitWebform([
      'webform_id' => $this->webform->id(),
      'in_draft' => 'false',
    ], 400);

    $this->assertArrayHasKey('error', $json, 'Une erreur doit être retournée.');
    $errorMessage = is_array($json['error']) ? implode(' ', $json['error']) : $json['error'];
    $this->assertStringContainsString('email', $errorMessage, 'Le message doit mentionner le champ requis "email".');
  }

  protected function tearDown(): void
  {
    if (isset($this->paragraph) && $this->paragraph) {
      $this->paragraph->delete();
    }
    if (isset($this->webform) && $this->webform) {
      $this->webform->delete();
    }
    parent::tearDown();
  }
}
