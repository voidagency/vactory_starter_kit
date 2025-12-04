<?php

namespace Drupal\vactory_decoupled\Tests\Functional;

use Drupal\Tests\vactory_core\Functional\VactoryExistingSiteBase;

/**
 * Test l'affichage et la soumission d’un webform - decoupled.
 *
 * @group vactory_decoupled
 */
class WebformNormalizerTest extends VactoryExistingSiteBase {

  /**
   * {@inheritdoc}
   */
  protected array $modulesToInstall = [
    'webform',
    'vactory_decoupled',
    'vactory_decoupled_webform',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create and log in an admin user using DTT helper.
    $this->admin = $this->createUser([], NULL, TRUE);
    $this->drupalLogin($this->admin);

    $this->normalizer = $this->container->get('vactory.webform.normalizer');
  }

  /**
   * Test a basic webform and validation.
   */
  public function testBasicWebformAndValidation(): void {
    // Créer un webform de test.
    $webform_settings = [
      'id' => 'test_webform_validation',
      'title' => 'Test webform validation',
      'elements' => [
        'message' => [
          '#type' => 'textfield',
          '#title' => 'Message',
          '#required' => TRUE,
          '#pattern' => '/^[a-z]+ [a-z]+$/',
          '#pattern_error' => '2 mots, uniquement lettres minuscules, séparés par un espace',
          '#placeholder' => 'Write text',
          '#description' => 'This is a text',
          "#default_value" => "my content",
        ],
        'number' => [
          '#type' => 'number',
          '#title' => 'Number',
          '#min' => 7,
          '#max' => 10,
          '#placeholder' => 'Write number',
          '#description' => 'This is a number',
          "#default_value" => 8,
        ],
      ],
    ];
    $webform = $this->createWebform($webform_settings);

    $elements = $this->normalizer->normalize($webform->id());

    // Vérifier l'existence des champs.
    foreach (['message', 'number'] as $field) {
      // Check validation property exists.
      $this->assertArrayHasKey($field, $elements, "Le champ \"$field\" doit exister dans le webform normalisé.");
      $this->assertArrayHasKey('validation', $elements[$field], "Le champ \"$field\" doit avoir une propriété \"validation\".");

      // Check properties: mapping and value.
      $properties_to_check = [
        '#title' => 'label',
        '#placeholder' => 'placeholder',
        '#description' => 'helperText',
        '#default_value' => 'default_value',
      ];

      foreach ($properties_to_check as $key => $value) {
        $this->assertArrayHasKey($value, $elements[$field], "Le champ \"$field\" doit avoir une propriété \"$value\".");
        $expected_value = $webform_settings['elements'][$field][$key];
        $this->assertEquals($expected_value, $elements[$field][$value]);
      }
    }

    // Vérifier validations.
    $this->assertValidation($elements['message'], [
      'required' => TRUE,
      'pattern' => '/^[a-z]+ [a-z]+$/',
      'patternError' => '2 mots, uniquement lettres minuscules, séparés par un espace',
    ], 'message');

    $this->assertValidation($elements['number'], [
      'min' => 7,
      'max' => 10,
    ], 'number');

  }

  /**
   * Helper to assert validation properties.
   */
  private function assertValidation(array $element, array $expected, string $fieldName): void {
    foreach ($expected as $key => $value) {
      $this->assertArrayHasKey($key, $element['validation'], sprintf(
        'Le champ "%s" doit avoir la validation "%s".', $fieldName, $key
      ));
      $this->assertSame($value, $element['validation'][$key], sprintf(
        'La valeur de la validation "%s" pour le champ "%s" est incorrecte.', $key, $fieldName
      ));
    }
  }

  /**
   * Test term elements.
   */
  public function testWebformTermElements(): void {

    $vocabulary = $this->createVocabularyType([
      'vid' => 'tags_test',
      'name' => 'Tags de test',
    ]);

    $term1 = $this->createTerm($vocabulary, [
      'name' => 'tag 1',
    ]);

    $term2 = $this->createTerm($vocabulary, [
      'name' => 'tag 2',
    ]);

    $webform_settings = [
      'id' => 'test_webform_term_elements',
      'title' => 'Test webform term elements',
      'elements' => [
        'checkbox_term' => [
          '#type' => 'webform_term_checkboxes',
          '#title' => 'Checkbox term',
          '#vocabulary' => $vocabulary->id(),
        ],
        'select_term' => [
          '#type' => 'webform_term_select',
          '#title' => 'Select term',
          '#vocabulary' => $vocabulary->id(),
          "#empty_option" => "default",
        ],
      ],
    ];

    // Créer un webform de test.
    $webform = $this->createWebform($webform_settings);

    $elements = $this->normalizer->normalize($webform->id());

    // Vérifier l'existence des champs.
    foreach (['checkbox_term', 'select_term'] as $field) {
      $this->assertArrayHasKey($field, $elements, "Le champ \"$field\" doit exister dans le webform normalisé.");
      $this->assertArrayHasKey('options', $elements[$field], "Le champ \"$field\" doit avoir une propriété \"options\".");
      $this->assertIsArray($elements[$field]['options'], "Le champ \"$field\" doit avoir un array des options.");
      $expected_options_count = [
        'checkbox_term' => 2,
        'select_term' => 3,
      ];
      $expected_count = $expected_options_count[$field];
      $this->assertCount($expected_count, $elements[$field]['options'], "Le champ \"$field\" doit avoir exactement $expected_count options.");

      // Vérifier que les options correspondent aux deux termes créés.
      $expectedOptions = [
        [
          'value' => $term1->id(),
          'label' => $term1->label(),
        ],
        [
          'value' => $term2->id(),
          'label' => $term2->label(),
        ],
      ];

      if ($field == 'select_term') {
        $expected_empty_option = [
          'value' => '',
          'label' => $webform_settings['elements'][$field]['#empty_option'],
        ];
        array_unshift($expectedOptions, $expected_empty_option);
      }

      $this->assertEquals(
        $expectedOptions,
        $elements[$field]['options'],
        "Les options du champ \"$field\" doivent correspondre exactement aux termes créés."
      );
    }

  }

  /**
   * Test upload elements.
   */
  public function testWebformUploadFields(): void {
    // Créer des fichiers de test pour les valeurs par défaut.
    $test_image = $this->createFile([
      'uri' => 'public://test_image.jpg',
      'filename' => 'test_image.jpg',
      'filemime' => 'image/jpeg',
      'filesize' => 1024 * 500,
    ]);

    // Créer un webform avec différents types de champs upload.
    $webform_settings = [
      'id' => 'test_webform_upload',
      'title' => 'Test webform upload',
      'elements' => [
        'single_image' => [
          '#type' => 'webform_image_file',
          '#title' => 'Single Image Upload',
          '#max_filesize' => 5,
          '#file_extensions' => 'png gif',
          '#file_preview' => TRUE,
          '#default_value' => $test_image->id(),
        ],
        'multiple_documents' => [
          '#type' => 'webform_document_file',
          '#title' => 'Multiple Documents Upload',
          '#multiple' => 3,
          '#max_filesize' => 10,
          '#file_extensions' => 'pdf doc docx txt',
        ],

      ],
    ];

    $webform = $this->createWebform($webform_settings);
    $elements = $this->normalizer->normalize($webform->id());

    // Check type upload.
    foreach (['single_image', 'multiple_documents'] as $field) {
      $this->assertEquals('upload', $elements[$field]['type'], "Le champ \"$field\" doit être de type \"upload\".");
    }

    // Check validation maxSizeBytes et maxSizeMb.
    $this->assertEquals(5 * 1024 * 1024, $elements['single_image']['validation']['maxSizeBytes']);
    $this->assertEquals(5, $elements['single_image']['maxSizeMb']);
    $this->assertEquals(10 * 1024 * 1024, $elements['multiple_documents']['validation']['maxSizeBytes']);
    $this->assertEquals(10, $elements['multiple_documents']['maxSizeMb']);

    // Check validation extensions (format avec points).
    $this->assertEquals('.png,.gif', $elements['single_image']['validation']['extensions']);
    $this->assertEquals('png gif', $elements['single_image']['extensionsClean']);
    $this->assertEquals('.pdf,.doc,.docx,.txt', $elements['multiple_documents']['validation']['extensions']);
    $this->assertEquals('pdf doc docx txt', $elements['multiple_documents']['extensionsClean']);

    // Check isMultiple et maxFiles.
    $this->assertFalse($elements['single_image']['isMultiple']);
    $this->assertArrayNotHasKey('maxFiles', $elements['single_image']['validation']);
    $this->assertTrue($elements['multiple_documents']['isMultiple']);
    $this->assertEquals(3, $elements['multiple_documents']['validation']['maxFiles']);

    // Check file preview.
    $this->assertTrue($elements['single_image']['filePreview']);

    // Check structure default_value pour fichier unique.
    $default_single = $elements['single_image']['default_value'];
    $this->assertIsArray($default_single);
    $this->assertEquals($test_image->id(), $default_single['fid']);
    $this->assertEquals('test_image.jpg', $default_single['name']);
    $this->assertEquals('image/jpeg', $default_single['type']);
    $this->assertEquals(1024 * 500, $default_single['size']);
    $this->assertNotEmpty($default_single['previewUrl']);
  }

  /**
   * Test captcha elements.
   */
  public function testWebformCaptchaElements(): void {
    // Créer un webform avec différents types de captcha.
    $webform_settings = [
      'id' => 'test_webform_captcha',
      'title' => 'Test webform captcha',
      'elements' => [
        'captcha_math' => [
          '#type' => 'captcha',
          '#title' => 'Math Captcha',
          '#captcha_type' => 'captcha/Math',
        ],
        'captcha_recaptcha' => [
          '#type' => 'captcha',
          '#title' => 'reCAPTCHA',
          '#captcha_type' => 'recaptcha/reCAPTCHA',
        ],
      ],
    ];

    $webform = $this->createWebform($webform_settings);
    $elements = $this->normalizer->normalize($webform->id());

    // Vérifier l'existence des champs captcha.
    foreach (['captcha_math', 'captcha_recaptcha'] as $field) {
      $this->assertArrayHasKey($field, $elements, "Le champ \"$field\" doit exister dans le webform normalisé.");

      // Vérifier que le type est 'captcha'.
      $this->assertEquals('captcha', $elements[$field]['type'], "Le champ \"$field\" doit être de type \"captcha\".");

      // Vérifier que la validation required est TRUE.
      $this->assertArrayHasKey('validation', $elements[$field], "Le champ \"$field\" doit avoir une propriété \"validation\".");
      $this->assertTrue($elements[$field]['validation']['required'], "Le champ \"$field\" doit avoir validation.required = TRUE.");

      // Vérifier que captcha_type existe.
      $this->assertArrayHasKey('captcha_type', $elements[$field], "Le champ \"$field\" doit avoir une propriété \"captcha_type\".");
    }

    // Vérifier captcha_type pour le captcha Math.
    $this->assertEquals('captcha/Math', $elements['captcha_math']['captcha_type'], "Le champ \"captcha_math\" doit avoir captcha_type = 'captcha/Math'.");

    // Vérifier captcha_type pour le recaptcha.
    $this->assertEquals('recaptcha/reCAPTCHA', $elements['captcha_recaptcha']['captcha_type'], "Le champ \"captcha_recaptcha\" doit avoir captcha_type = 'recaptcha/reCAPTCHA'.");
  }

}
