<?php

namespace Drupal\vactory_decoupled\Tests\Functional;

use weitzman\DrupalTestTraits\ExistingSiteBase;
use Drupal\user\Entity\User;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\node\Entity\Node;
use Drupal\webform\Entity\Webform;
use Drupal\Core\Url;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use GuzzleHttp\ClientInterface;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\taxonomy\Entity\Term;

/**
 * Test l'affichage et la soumission d’un webform - decoupled.
 *
 * @group vactory_decoupled
 */
class WebformElementsTest extends ExistingSiteBase {

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
   * @var \Drupal\paragraphs\Entity\Paragraph
   */
  protected Paragraph $paragraphMultiStepForm;

  /**
   * Paragraph user for testing.
   *
   * @var \Drupal\webform\Entity\Webform
   */
  protected Webform $webform;

  /**
   * WebForm multi step for testing.
   *
   * @var \Drupal\webform\Entity\Webform
   */
  protected ?Webform $webformMultiStep;

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
   * Track modules installed during the test.
   *
   * @var string[]
   */
  protected array $modulesInstalledDuringTest = [];

  /**
   * Webform decoupled service.
   *
   * @var \Drupal\vactory_decoupled_webform\Webform
   */
  protected $webformService;

  /**
   * Test vocabulary for select/check elements.
   *
   * @var \Drupal\taxonomy\Entity\Vocabulary|null
   */
  protected ?Vocabulary $vocabulary = NULL;

  /**
   * Stores the expected options for taxonomy term-based webform elements.
   *
   * @var array
   */
  protected array $termOptions = [];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Assurer que les modules sont installés.
    $this->ensureModuleInstalled('webform');
    $this->ensureModuleInstalled('vactory_decoupled');
    $this->ensureModuleInstalled('vactory_decoupled_webform');
    $this->ensureModuleInstalled('captcha');
    $this->ensureModuleInstalled('recaptcha');

    // Retrieve core services.
    $this->entityTypeManager = \Drupal::entityTypeManager();
    $this->httpClient = \Drupal::httpClient();

    // Create and log in an admin user using DTT helper.
    $this->admin = $this->createUser([], NULL, TRUE);
    $this->drupalLogin($this->admin);

    // Create Multi Step Form.
    $this->webform_multi_step = $this->createMultiStepWebform();

    // Création du webform normal.
    $this->webform = $this->createWebform();

    // Save webform.
    $this->webform->save();
  }

  /**
   * Teste l’affichage du webform dans un paragraphe.
   */
  public function testDecoupledWebform(): void {
    // Create 2 paragraphs : Vactory_Component.
    $this->paragraph = $this->createWebformParagraph($this->webform);
    $this->paragraphMultiStepForm = $this->createWebformParagraph($this->webform_multi_step);
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
        [
          'target_id' => $this->paragraphMultiStepForm->id(),
          'target_revision_id' => $paragraphStorage->getLatestRevisionId($this->paragraphMultiStepForm->id()),
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
    $this->assertEquals('vactory_decoupled_webform:webform', $component['widget_id'], 'Widget ID should be vactory_decoupled_webform:webform.');

    $this->assertJson($component['widget_data'], 'Widget data should be valid JSON.');

    // Décoder widget_data pour tester son contenu.
    $widgetData = Json::decode($component['widget_data']);

    // Vérifier que le webform ID correspond.
    $this->assertEquals($this->webform->id(), $widgetData['components'][0]['webform']['id'], 'Webform ID should match the test form.');

    $elements = $widgetData['components'][0]['webform']['elements'];
    // Webform Exepected Elements.
    $expectedElements = [
      'name' => ['type' => 'text', 'required' => TRUE],
      'email' => [
        'type' => 'text',
        'required' => TRUE,
        'pattern' => '/^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}$/i',
      ],
      'subject' => ['type' => 'text', 'required' => TRUE],
      'message' => [
        'type' => 'textArea',
        'states' => TRUE,
        'minText' => 'Minimum age is 1',
        'maxText' => 'Maximum age is 120',
      ],

      // Phone fields.
      'phone_number' => ['type' => 'text', 'htmlInputType' => 'tel'],
      'numero_telephone' => [
        'type' => 'text',
        'htmlInputType' => 'tel',
        'international' => TRUE,
        'international_initial_country' => 'MA',
        'international_preferred_countries' => 'array',
      ],

      // Captchas.
      'captcha' => [
        'type' => 'captcha',
        'captcha_type' => 'captcha/Math',
        'required' => TRUE,
      ],
      'recaptcha' => [
        'type' => 'captcha',
        'captcha_type' => 'recaptcha/reCAPTCHA',
        'required' => TRUE,
      ],

      // Number, URL, password, date.
      'age' => [
        'type' => 'number',
        'validation_min' => 1,
        'validation_max' => 120,
      ],
      'website' => ['type' => 'text'],
      'password' => ['type' => 'password', 'required' => TRUE],
      'birth_date' => [
        'type' => 'date',
        'required' => TRUE,
        'dateMin' => '1900-01-01',
        'dateMax' => '2025-09-24',
      ],

      // Time, radios, terms of service.
      'appointment_time' => ['type' => 'time'],
      'gender' => ['type' => 'radios', 'options_count' => 3],
      'terms_of_service' => ['type' => 'checkbox', 'required' => TRUE],
      'i_agree_to_terms' => ['type' => 'checkbox', 'required' => TRUE],

      // Raw HTML / bio.
      'bio' => ['type' => 'rawhtml', 'format' => 'basic_html'],

      // Range, email confirm, select other.
      'satisfaction' => [
        'type' => 'range',
        'validation_min' => 1,
        'validation_max' => 5,
      ],
      'email_confirm' => ['type' => 'text', 'sameAs' => 'email'],
      'select_other' => [
        'type' => 'webform_select_other',
        'options_count' => 2,
      ],

      // Radios other.
      'radios_other' => ['type' => 'radios', 'options_count' => 2],

      // Hidden field.
      'hidden_field' => ['type' => 'text', 'htmlInputType' => 'hidden'],

      // Scale.
      'scale' => [
        'type' => 'webform_scale',
        'validation_min' => 1,
        'validation_max' => 10,
      ],

      // Options display / all / none.
      'test_select' => [
        'type' => 'select',
        'optionsDisplay' => 'select',
        'options_count' => 4,
      ],
      'test_check' => [
        'type' => 'checkboxes',
        'optionsAll' => 4,
        'optionsNone' => 0,
        'options_count' => 4,
      ],

      // Buttons.
      'buttons' => ['type' => 'webform_actions'],

      // File upload.
      'profile_picture' => [
        'type' => 'upload',
        'extensionsClean' => 'gif jpg jpeg png',
        'filePreview' => FALSE,
      ],
      'document_file' => [
        'type' => 'upload',
        'isMultiple' => TRUE,
        'validation_maxFiles' => 3,
      ],

      // Containers, Fieldsets, Details, Sections.
      'container_1' => [
        'type' => 'container',
        'childs' => ['field_b' => ['type' => 'text']],
      ],
      'fieldset_1' => [
        'type' => 'fieldset',
        'childs' => ['field_c' => ['type' => 'text']],
      ],
      'details_1' => [
        'type' => 'details',
        'childs' => ['field_d' => ['type' => 'text']],
      ],
      'section_1' => [
        'type' => 'webform_section',
        'childs' => ['field_e' => ['type' => 'text']],
      ],

      // Flexbox container.
      'flexbox_1' => [
        'type' => 'webform_flexbox',
        'childs' => [
          'first_name' => ['type' => 'text', 'flex' => 1],
          'last_name' => ['type' => 'text', 'flex' => 1],
        ],
        'flexTotal' => 2,
      ],
      // Term selects & checkboxes.
      'webform_term_select' => [
        'type' => 'select',
        'label' => 'Select a Term',
        'validation' => ['required' => TRUE],
        'options' => $this->termOptions,
        'isMultiple' => FALSE,
      ],
      'webform_term_checkboxes' => [
        'type' => 'checkboxes',
        'label' => 'Select Multiple Terms',
        'validation' => ['required' => TRUE],
        'options' => $this->termOptions,
        'isMultiple' => TRUE,
      ],
    ];

    foreach ($expectedElements as $field => $rules) {
      $this->assertArrayHasKey($field, $elements, "Field '$field' should exist in the webform elements.");
      $element = $elements[$field];

      $this->assertFieldType($field, $rules, $element);
      $this->assertFieldRequired($field, $rules, $element);
      $this->assertFieldPattern($field, $rules, $element);
      $this->assertFieldMinMax($field, $rules, $element);
      $this->assertFieldSameAs($field, $rules, $element);
      $this->assertFieldHtmlInputType($field, $rules, $element);
      $this->assertFileUpload($field, $rules, $element);
      $this->assertOptions($field, $rules, $element);
      $this->assertDateRules($field, $rules, $element);
      $this->assertMinMaxText($field, $rules, $element);
      $this->assertChildren($field, $rules, $element);
      $this->assertFlexbox($field, $rules, $element);
      $this->assertTermElements($field, $rules, $element);
    }

    // Vérifier les elements de Multi step Form.
    $componentMultiStep = $data['included'][1]['attributes']['field_vactory_component'];

    // Décoder widget_data pour tester son contenu.
    $widgetDataMultiStep = Json::decode($componentMultiStep['widget_data']);
    $elementsMultiStep = $widgetDataMultiStep['components'][0]['webform']['elements'];

    $this->assertMultiStepWebform($widgetDataMultiStep);

    // Prepare date for the submission.
    $submissionData = [
      'webform_id' => $this->webform_multi_step->id(),
      'name' => 'Testeur',
      'last_name' => 'Testeur',
      'document_cv' => [],
      'captcha_sid' => '',
      'captcha_response' => '',
      'in_draft' => TRUE,
    ];

    // Enable draft on the webform.
    $this->webform_multi_step->setSetting('draft', 'authenticated');
    $this->webform_multi_step->save();

    // Submit the form.
    $json = $this->submitWebform($submissionData, 200);

    $this->assertArrayHasKey('sid', $json, 'Un SID doit être retourné.');
    $this->assertNotEmpty($json['sid'], 'Le SID ne doit pas être vide.');
  }

  /**
   * Assert that a field has the expected type.
   */
  private function assertFieldType(string $field, array $rules, array $element): void {
    if (isset($rules['type']) && isset($element['type'])) {
      $this->assertEquals($rules['type'], $element['type'], "Field '$field' type should match.");
    }
  }

  /**
   * Assert that a field is marked as required in its validation rules.
   */
  private function assertFieldRequired(string $field, array $rules, array $element): void {
    if (isset($rules['required'])) {
      $this->assertArrayHasKey('validation', $element, "Field '$field' is missing 'validation'.");
      $this->assertArrayHasKey('required', $element['validation'], "Field '$field' validation missing 'required'.");
      $this->assertTrue($element['validation']['required'], "Field '$field' should be required.");
    }
  }

  /**
   * Assert that a field has the correct regex validation pattern.
   */
  private function assertFieldPattern(string $field, array $rules, array $element): void {
    if (isset($rules['pattern'])) {
      $this->assertArrayHasKey('validation', $element, "Field '$field' is missing 'validation'.");
      $this->assertArrayHasKey('pattern', $element['validation'], "Field '$field' validation missing 'pattern'.");
      $this->assertEquals($rules['pattern'], $element['validation']['pattern'], "Field '$field' pattern should match.");
    }
  }

  /**
   * Assert that a field has correct min/max validation rules.
   */
  private function assertFieldMinMax(string $field, array $rules, array $element): void {
    if (isset($rules['validation_min']) && isset($element['validation']['min'])) {
      $this->assertEquals($rules['validation_min'], $element['validation']['min'], "Field '$field' min validation should match.");
    }
    if (isset($rules['validation_max']) && isset($element['validation']['max'])) {
      $this->assertEquals($rules['validation_max'], $element['validation']['max'], "Field '$field' max validation should match.");
    }
  }

  /**
   * Assert that a field respects "sameAs" validation (e.g. confirm password).
   */
  private function assertFieldSameAs(string $field, array $rules, array $element): void {
    if (isset($rules['sameAs']) && isset($element['validation']['sameAs'])) {
      $this->assertEquals($rules['sameAs'], $element['validation']['sameAs'], "Field '$field' should match sameAs rule.");
    }
  }

  /**
   * Assert that a field has the expected HTML input type.
   */
  private function assertFieldHtmlInputType(string $field, array $rules, array $element): void {
    if (isset($rules['htmlInputType']) && isset($element['htmlInputType'])) {
      $this->assertEquals($rules['htmlInputType'], $element['htmlInputType'], "Field '$field' htmlInputType should match.");
    }
  }

  /**
   * Assert file upload rules such as extensions, multiple files etc.
   */
  private function assertFileUpload(string $field, array $rules, array $element): void {
    if (isset($rules['extensionsClean']) && isset($element['extensionsClean'])) {
      $this->assertEquals($rules['extensionsClean'], $element['extensionsClean'], "Field '$field' allowed extensions should match.");
    }
    if (isset($rules['isMultiple']) && isset($element['isMultiple'])) {
      $this->assertEquals($rules['isMultiple'], $element['isMultiple'], "Field '$field' multiple file setting should match.");
    }
    if (isset($rules['filePreview']) && isset($element['filePreview'])) {
      $this->assertEquals($rules['filePreview'], $element['filePreview'], "Field '$field' file preview setting should match.");
    }
    if (isset($rules['validation_maxFiles']) && isset($element['validation']['maxFiles'])) {
      $this->assertEquals($rules['validation_maxFiles'], $element['validation']['maxFiles'], "Field '$field' max files should match.");
    }
  }

  /**
   * Assert select/radio/checkbox options and related settings.
   */
  private function assertOptions(string $field, array $rules, array $element): void {
    if (isset($rules['options_count']) && isset($element['options'])) {
      $this->assertCount($rules['options_count'], $element['options'], "Field '$field' options count should match.");
    }
    if (isset($rules['optionsDisplay']) && isset($element['optionsDisplay'])) {
      $this->assertEquals($rules['optionsDisplay'], $element['optionsDisplay'], "Field '$field' optionsDisplay should match.");
    }
    if (isset($rules['optionsAll']) && isset($element['optionsAll'])) {
      $this->assertEquals($rules['optionsAll'], $element['optionsAll'], "Field '$field' optionsAll should match.");
    }
    if (isset($rules['optionsNone']) && isset($element['optionsNone'])) {
      $this->assertEquals($rules['optionsNone'], $element['optionsNone'], "Field '$field' optionsNone should match.");
    }
  }

  /**
   * Assert date fields have correct minimum and maximum date limits.
   */
  private function assertDateRules(string $field, array $rules, array $element): void {
    if (isset($rules['dateMin']) && isset($element['dateMin'])) {
      $this->assertEquals($rules['dateMin'], $element['dateMin'], "Field '$field' dateMin should match.");
    }
    if (isset($rules['dateMax']) && isset($element['dateMax'])) {
      $this->assertEquals($rules['dateMax'], $element['dateMax'], "Field '$field' dateMax should match.");
    }
  }

  /**
   * Assert minimum and maximum text length constraints.
   */
  private function assertMinMaxText(string $field, array $rules, array $element): void {
    if (isset($rules['minText']) && isset($element['attributes']['minText'])) {
      $this->assertEquals($rules['minText'], $element['attributes']['minText'], "Field '$field' minText should match.");
    }
    if (isset($rules['maxText']) && isset($element['attributes']['maxText'])) {
      $this->assertEquals($rules['maxText'], $element['attributes']['maxText'], "Field '$field' maxText should match.");
    }
  }

  /**
   * Assert that a field's child elements exist and match expected properties.
   */
  private function assertChildren(string $field, array $rules, array $element): void {
    if (isset($rules['childs']) && is_array($rules['childs']) && isset($element['childs'])) {
      foreach ($rules['childs'] as $childField => $childRules) {
        $this->assertArrayHasKey($childField, $element['childs'], "Child field '$childField' should exist in '$field'.");
        foreach ($childRules as $childKey => $childValue) {
          if (isset($element['childs'][$childField][$childKey])) {
            $this->assertEquals($childValue, $element['childs'][$childField][$childKey], "Child field '$childField' property '$childKey' should match.");
          }
        }
      }
    }
  }

  /**
   * Assert flexbox-specific rules such as total flex columns.
   */
  private function assertFlexbox(string $field, array $rules, array $element): void {
    if (isset($rules['flexTotal']) && isset($element['flexTotal'])) {
      $this->assertEquals($rules['flexTotal'], $element['flexTotal'], "Field '$field' flexTotal should match.");
    }
  }

  /**
   * Assert that a term select or checkboxes exist and have the correct options.
   *
   * @param string $field
   *   The field machine name.
   * @param array $rules
   *   Rules containing expected options.
   * @param array $element
   *   The webform element array.
   */
  private function assertTermElements(string $field, array $rules, array $element): void {

    if (isset($rules['type']) && isset($element['type'])) {
      $this->assertEquals($rules['type'], $element['type'], "Field '$field' type should match.");
    }
    // Vérifie les options (les valeurs, pas les labels).
    if (isset($rules['options'], $element['options'])) {
      // Valeurs attendues.
      $expectedValues = array_keys($rules['options']);

      // Valeurs réelles.
      $actualValues = array_column($element['options'], 'value');

      $this->assertEquals(
        $expectedValues,
        $actualValues,
        "Field '$field' option values should match."
      );

      // Vérifie les labels des enfants si breadcrumb est désactivé.
      if (empty($element['#breadcrumb'])) {
        foreach ($element['options'] as $index => $option) {
          // Enfants aux indices pairs (à partir de 0, donc 1, 3, 5).
          if ($index % 2 === 1) {
            $this->assertStringStartsWith(
              '-',
              $option['label'],
              "Field '$field' child label '{$option['label']}' should start with '-'"
            );
          }
        }
      }
    }
  }

  /**
   * Assert structure and elements of a multi-step webform.
   *
   * @param array $widgetDataMultiStep
   *   The decoded widget data from the webform component.
   */
  protected function assertMultiStepWebform(array $widgetDataMultiStep): void {
    $elementsMultiStep = $widgetDataMultiStep['components'][0]['webform']['elements'];
    $pages = $elementsMultiStep['pages'];

    // Ensure pages exist.
    $this->assertArrayHasKey('page_1', $pages);
    $this->assertArrayHasKey('page_2', $pages);
    $this->assertArrayHasKey('page_3', $pages);

    // Check elements in page_1.
    $this->assertArrayHasKey('name', $pages['page_1']['childs']);
    $this->assertEquals('text', $pages['page_1']['childs']['name']['type']);

    // Check elements in page_2.
    $this->assertArrayHasKey('last_name', $pages['page_2']['childs']);
    $this->assertTrue($pages['page_2']['childs']['last_name']['validation']['required']);

    // Check elements in page_3.
    $this->assertArrayHasKey('document_cv', $pages['page_3']['childs']);
    $this->assertEquals('upload', $pages['page_3']['childs']['document_cv']['type']);
    $this->assertEquals(2, $pages['page_3']['childs']['document_cv']['validation']['maxFiles']);
    $this->assertEquals('pdf', $pages['page_3']['childs']['document_cv']['extensionsClean']);

    // Check captcha in page_3.
    $this->assertArrayHasKey('captcha', $pages['page_3']['childs']);
    $this->assertEquals('captcha/Math', $pages['page_3']['childs']['captcha']['captcha_type']);

    // Verify that "webform_preview" exists.
    $this->assertArrayHasKey('webform_preview', $pages);

    // Verify that the sub-keys preview and wizard exist.
    $this->assertArrayHasKey('preview', $pages['webform_preview']);
    $this->assertArrayHasKey('wizard', $pages['webform_preview']);
  }

  /**
   * Create a Vactory webform paragraph for the given webform entity.
   */
  private function createWebformParagraph($webform) {
    $paragraph = Paragraph::create([
      'type' => 'vactory_component',
      'field_vactory_component' => [
        'widget_id' => 'vactory_decoupled_webform:webform',
        'widget_data' => json_encode([
          [
            'webform' => [
              'id' => $webform->id(),
              'style' => '',
            ],
          ],
        ]),
      ],
    ]);

    $paragraph->save();
    return $paragraph;
  }

  /**
   * Create a fully defined webform for testing.
   *
   * @return \Drupal\webform\Entity\Webform
   *   The created webform entity.
   */
  protected function createWebform(): Webform {

    // Create taxonomy for test select and checkboxes.
    $vocabulary = $this->vocabulary = $this->createTestVocabulary('tags', 'Tags');
    // Prepare term options from the test vocabulary.
    $terms = $this->createTestTerms('tags', [
      'Parent A' => ['Term A'],
      'Parent B' => ['Term B'],
      'Parent C' => ['Term C'],
    ]);
    $this->termOptions = [];
    foreach ($terms as $term) {
      $this->termOptions[$term->id()] = $term->getName();
    }

    $webform = Webform::create([
      'id' => 'contact_phpunit_test',
      'title' => 'Contact Webform PHPUnit',
      'elements' => [
        'name' => [
          '#type' => 'textfield',
          '#title' => 'Your Name',
          '#required' => TRUE,
          '#description' => 'Some description',
          '#description_display' => 'before',
          '#required_error' => 'Name is required',
          '#default_value' => '[current-user:display-name]',
          '#wrapper_attributes' => ['class' => ['NameWrapperClass']],
          '#attributes' => ['class' => ['NameItemClass']],
          '#title_display' => 'after',
          '#other__title' => 'Other Name',
          '#placeholder' => 'Enter your full name',
          '#readonly' => FALSE,
        ],
        'email' => [
          '#type' => 'email',
          '#title' => 'Your Email',
          '#default_value' => '[current-user:mail]',
          '#required' => TRUE,
        ],
        'subject' => [
          '#type' => 'textfield',
          '#title' => 'Subject',
          '#required' => TRUE,
          '#test' => 'Testing contact webform from [site:name]',
        ],
        'message' => [
          '#type' => 'textarea',
          '#title' => 'Message',
          '#states' => [
            'visible' => [
              ':input[name="test_check[Option 1]"]' => ['checked' => TRUE],
            ],
          ],
          '#min_text' => 'Minimum age is 1',
          '#max_text' => 'Maximum age is 120',
          '#test' => 'Please ignore this email.',
        ],
        'phone_number' => [
          '#type' => 'tel',
          '#title' => 'Phone Number Simple',
        ],
        'numero_telephone' => [
          '#type' => 'tel',
          '#title' => 'Phone Number International',
          '#autocomplete' => '',
          '#international' => TRUE,
          '#international_initial_country' => 'MA',
        ],
        'test_select' => [
          '#type' => 'select',
          '#title' => 'test select',
          '#options' => [
            'Option 1' => 'Option 1',
            'Option 2' => 'Option 2',
            'Option 3' => 'Option 3',
          ],
          '#options_display' => 'select',
          '#empty_option' => '- Select an option -',
          '#empty_value' => '',
          '#default_value' => 'Option 1',
        ],
        'test_check' => [
          '#type' => 'checkboxes',
          '#title' => 'Test check',
          '#options' => [
            'Option 1' => 'Option 1',
            'Option 2' => 'Option 2',
            'Option 3' => 'Option 3',
          ],
          '#default_value' => ['Option 1'],
          '#options_all' => 4,
          '#options_none' => 0,
        ],
        'i_agree_to_terms' => [
          '#type' => 'checkbox',
          '#title' => 'I agree to the terms and conditions',
          '#required' => TRUE,
        ],
        'captcha' => [
          '#type' => 'captcha',
          '#captcha_type' => 'captcha/Math',
          '#captcha_title' => 'this is Question title',
          '#captcha_description' => 'this is Question description',
        ],
        'recaptcha' => [
          '#type' => 'captcha',
          '#captcha_type' => 'recaptcha/reCAPTCHA',
        ],
        'age' => [
          '#type' => 'number',
          '#title' => 'Your age',
          '#required' => TRUE,
          '#min' => 1,
          '#max' => 120,
        ],
        'website' => [
          '#type' => 'url',
          '#title' => 'Your website',
        ],
        'password' => [
          '#type' => 'password',
          '#title' => 'Password',
          '#required' => TRUE,
        ],
        'birth_date' => [
          '#type' => 'date',
          '#title' => 'Birth date',
          '#required' => TRUE,
          '#date_date_min' => '1900-01-01',
          '#date_date_max' => '2025-09-24',
          '#wrapper_attributes' => ['class' => []],
        ],
        'appointment_time' => [
          '#type' => 'webform_time',
          '#title' => 'Preferred appointment time',
        ],
        'gender' => [
          '#type' => 'radios',
          '#title' => 'Gender',
          '#options' => [
            'male' => 'Male',
            'female' => 'Female',
            'other' => 'Other',
          ],
        ],
        'terms_of_service' => [
          '#type' => 'webform_terms_of_service',
          '#title' => 'Agree to Terms',
          '#required' => TRUE,
        ],
        'profile_picture' => [
          '#type' => 'webform_image_file',
          '#title' => 'Profile Picture',
          '#upload_validators' => ['file_validate_extensions' => ['png jpg jpeg']],
        ],
        'bio' => [
          '#type' => 'processed_text',
          '#title' => 'Biography',
          '#format' => 'basic_html',
          '#text' => '<p>Write your biography here.</p>',
        ],
        'satisfaction' => [
          '#type' => 'range',
          '#title' => 'Satisfaction level',
          '#min' => 1,
          '#max' => 5,
          '#step' => 1,
        ],
        'email_confirm' => [
          '#type' => 'webform_email_confirm',
          '#title' => 'Confirm Email',
        ],
        'select_other' => [
          '#type' => 'webform_select_other',
          '#title' => 'Select with Other',
          '#options' => ['Option A' => 'A', 'Option B' => 'B'],
        ],
        'webform_term_select' => [
          '#type' => 'webform_term_select',
          '#title' => 'Select a Term',
          '#vocabulary' => $vocabulary->id(),
          '#breadcrumb' => '',
          '#required' => TRUE,
        ],
        'webform_term_checkboxes' => [
          '#type' => 'webform_term_checkboxes',
          '#title' => 'Select Multiple Terms',
          '#vocabulary' => $vocabulary->id(),
          '#breadcrumb' => '',
          '#required' => TRUE,
        ],
        'radios_other' => [
          '#type' => 'webform_radios_other',
          '#title' => 'Choose One',
          '#options' => ['Yes' => 'yes', 'No' => 'no'],
        ],
        'document_file' => [
          '#multiple' => 3,
          '#type' => 'webform_document_file',
          '#title' => 'Upload document',
          '#upload_validators' => ['file_validate_extensions' => ['pdf doc docx']],
          '#default_file' => DRUPAL_ROOT . '/profiles/contrib/vactory_starter_kit/modules/vactory_decoupled/modules/vactory_decoupled_webform/assets/defaultfile.pdf',
          '#max_files' => 3,
          '#max_filesize' => 5242880,
        ],
        'hidden_field' => [
          '#type' => 'hidden',
          '#value' => 'secret',
        ],
        'scale' => [
          '#type' => 'webform_scale',
          '#title' => 'Rate this',
          '#min' => 1,
          '#max' => 10,
        ],
        'flexbox_1' => [
          '#type' => 'webform_flexbox',
          '#title' => 'Flexbox Container',
          '#align_items' => 'center',
          '#description' => 'Conteneur flexbox pour tester les layouts',
          'first_name' => [
            '#type' => 'textfield',
            '#title' => 'First Name',
            '#required' => TRUE,
          ],
          'last_name' => [
            '#type' => 'textfield',
            '#title' => 'Last Name',
            '#required' => TRUE,
          ],
        ],
        'container_1' => [
          '#type' => 'container',
          '#title' => 'Container Example',
          'field_b' => [
            '#type' => 'textfield',
            '#title' => 'Field B',
          ],
        ],
        'fieldset_1' => [
          '#type' => 'fieldset',
          '#title' => 'Fieldset Example',
          'field_c' => [
            '#type' => 'textfield',
            '#title' => 'Field C',
          ],
        ],
        'details_1' => [
          '#type' => 'details',
          '#title' => 'Details Example',
          'field_d' => [
            '#type' => 'textfield',
            '#title' => 'Field D',
          ],
        ],
        'section_1' => [
          '#type' => 'webform_section',
          '#title' => 'Section Example',
          'field_e' => [
            '#type' => 'textfield',
            '#title' => 'Field E',
          ],
        ],
        'actions' => [
          '#type' => 'webform_actions',
          'submit' => [
            '#type' => 'submit',
            '#value' => 'Send message',
          ],
        ],
      ],
    ]);
    // Save the webform.
    $webform->save();

    return $webform;
  }

  /**
   * Create the multi-step webform programmatically based on YAML export.
   *
   * @return \Drupal\webform\Entity\Webform
   *   The created webform entity.
   */
  protected function createMultiStepWebform(): Webform {
    $webform_multi_step_id = 'multi_step_form_test' . time();

    $this->webformMultiStep = Webform::create([
      'id' => $webform_multi_step_id,
      'title' => 'Multi Step Form Test PHP UNIT',
      'description' => '',
      'status' => Webform::STATUS_OPEN,
      'elements' => [
        'page_1' => [
          '#type' => 'webform_wizard_page',
          '#title' => 'Page 1',
          '#icon' => 'pattern',
          'name' => [
            '#type' => 'textfield',
            '#title' => 'Name',
          ],
        ],
        'page_2' => [
          '#type' => 'webform_wizard_page',
          '#title' => 'Page 2',
          '#icon' => 'shield-check-solid',
          'last_name' => [
            '#type' => 'textfield',
            '#title' => 'Last name',
            '#required' => TRUE,
          ],
        ],
        'page_3' => [
          '#type' => 'webform_wizard_page',
          '#title' => 'Page 3',
          '#icon' => 'home',
          'document_cv' => [
            '#type' => 'webform_document_file',
            '#title' => 'Document CV',
            '#multiple' => 2,
            '#file_extensions' => 'pdf',
          ],
          'captcha' => [
            '#type' => 'captcha',
            '#captcha_type' => 'captcha/Math',
          ],
        ],
      ],
    ]);

    $this->webformMultiStep->save();

    return $this->webformMultiStep;
  }

  /**
   * Create a vocabulary for test.
   */
  protected function createTestVocabulary(string $vid = 'tags', string $name = 'Tags'): Vocabulary {
    $vocabulary = Vocabulary::load($vid);
    if (!$vocabulary) {
      $vocabulary = Vocabulary::create([
        'vid' => $vid,
        'description' => '',
        'name' => $name,
        'hierarchy' => 0,
      ]);
      $vocabulary->save();
    }
    return $vocabulary;
  }

  /**
   * Create Terms Taxonomy for test with optional parent-child hierarchy.
   */
  protected function createTestTerms(string $vocabulary_id = 'tags', array $terms_tree = []): array {
    $created_terms = [];

    // Load the vocabulary.
    $vocabulary = \Drupal::entityTypeManager()
      ->getStorage('taxonomy_vocabulary')
      ->load($vocabulary_id);

    if (!$vocabulary) {
      throw new \Exception("Vocabulary '$vocabulary_id' does not exist.");
    }

    // First, create parent terms.
    foreach ($terms_tree as $parent_name => $children) {
      // Check if the parent term already exists.
      $parent_terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')
        ->loadByProperties([
          'name' => $parent_name,
          'vid' => $vocabulary_id,
        ]);

      if ($parent_terms) {
        $parent_term = reset($parent_terms);
      }
      else {
        $parent_term = Term::create([
          'name' => $parent_name,
          'vid' => $vocabulary_id,
        ]);
        $parent_term->save();
      }

      $created_terms[$parent_name] = $parent_term;

      // Create child terms if provided.
      foreach ($children as $child_name) {
        $child_terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')
          ->loadByProperties([
            'name' => $child_name,
            'vid' => $vocabulary_id,
          ]);

        if ($child_terms) {
          $child_term = reset($child_terms);
        }
        else {
          $child_term = Term::create([
            'name' => $child_name,
            'vid' => $vocabulary_id,
            'parent' => [$parent_term->id()],
          ]);
          $child_term->save();
        }

        $created_terms[$child_name] = $child_term;
      }
    }

    return $created_terms;
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
    if (isset($this->webformMultiStep) && $this->webformMultiStep) {
      $this->webformMultiStep->delete();
    }
    // Delete vocabulary created for the test.
    if (isset($this->vocabulary) && $this->vocabulary) {
      $this->vocabulary->delete();
    }
    // Désinstaller les modules installés pendant le test.
    if (!empty($this->modulesInstalledDuringTest)) {
      $moduleInstaller = \Drupal::service('module_installer');
      $moduleInstaller->uninstall($this->modulesInstalledDuringTest);
    }
    parent::tearDown();
  }

}
