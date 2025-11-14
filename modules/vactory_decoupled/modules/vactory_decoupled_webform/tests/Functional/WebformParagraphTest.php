<?php

namespace Drupal\vactory_decoupled\Tests\Functional;

use Drupal\Tests\vactory_core\Functional\VactoryExistingSiteBase;
use Drupal\webform\Entity\Webform;
use Drupal\webform\WebformInterface;
use Drupal\webform\Entity\WebformSubmission;
use Drupal\Core\Url;
use Drupal\Component\Serialization\Json;
use GuzzleHttp\ClientInterface;

/**
 * Test l'affichage et la soumission d’un webform - decoupled.
 *
 * @group vactory_decoupled
 */
class WebformParagraphTest extends VactoryExistingSiteBase {

  /**
   * {@inheritDoc}
   */
  protected array $modulesToInstall = [
    'webform',
    'vactory_decoupled',
    'vactory_decoupled_webform',
  ];

  /**
   * Default collection setting (webform case).
   */
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
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

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
    $widget_id = $this->createVolatileDf(self::DEFAULT_COLLECTION_SETTING, $df_name);

    // Create paragraph.
    $paragraph = $this->createParagraph([
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
    $params = ['include' => 'field_vactory_paragraphs'];
    $data = $this->fetchNodeJsonApi($node, $langcode, 200, $params);

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
  }

  /**
   * Teste la soumission du webform valide.
   */
  public function testWebformSubmission(): void {
    $email = 'void_tester@void.com';
    $json = $this->submitWebform([
      'webform_id' => $this->webform->id(),
      'email' => $email,
      'in_draft' => 'false',
    ], 200);

    $this->assertArrayHasKey('sid', $json, 'Un SID doit être retourné.');
    $this->assertNotEmpty($json['sid'], 'Le SID ne doit pas être vide.');

    $submission = WebformSubmission::load($json['sid']);
    $submission_data = $submission->getData();
    $this->assertNotNull($submission, 'La soumission doit exister.');
    $this->assertEquals(
      $email,
      $submission_data['email'],
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
    // Return back to open status.
    $this->webform->setStatus(WebformInterface::STATUS_OPEN)->save();
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
    if (isset($this->webform) && $this->webform) {
      $this->webform->delete();
    }
    parent::tearDown();
  }

}
