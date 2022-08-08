<?php

namespace Drupal\Tests\vactory_dynamic_field\Kernel;

use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\user\Entity\User;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Core\Language\LanguageInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Tests node normalizer functionality.
 *
 * @group Entity
 */
class NodeNormalizerTest extends EntityKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['node', 'language', 'taxonomy', 'system', 'field', 'text', 'entity_reference', 'image', 'vactory_dynamic_field'];
  // public static $modules = ['node', 'paragraphs', 'my_module'];


  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
    $this->installEntitySchema('taxonomy_term');

    // Create the node bundles required for testing.
    $type = NodeType::create([
      'type' => 'page',
      'name' => 'page',
    ]);
    $type->save();

    // Add a vocabulary so we can test different view modes.
    $vocabulary = Vocabulary::create([
      'name' => $this->randomMachineName(),
      'description' => $this->randomMachineName(),
      'vid' => 'taxonomy',
      'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
      'help' => '',
    ]);
    $vocabulary->save();

     // Create a field.
     $handler_settings = [
      'target_bundles' => [
        $vocabulary->id() => $vocabulary->id(),
      ],
      'auto_create' => TRUE,
    ];
     // Add the term field.
     FieldStorageConfig::create([
      'field_name' => 'field_term',
      'type' => 'entity_reference',
      'entity_type' => 'node',
      'cardinality' => 1,
      'settings' => [
        'target_type' => 'taxonomy_term',
      ],
    ])->save();
    FieldConfig::create([
      'field_name' => 'field_term',
      'entity_type' => 'node',
      'bundle' => 'page',
      'label' => 'Terms',
      'settings' => [
        'handler' => 'default',
        'handler_settings' => $handler_settings,
      ],
    ])->save();
  }

  /**
   * Tests node owner functionality.
   */
  public function testOwner() {
    // $user = $this->createUser();

    // $container = \Drupal::getContainer();
    // $container->get('current_user')->setAccount($user);

    $term = Term::create([
      'name' => 'Events',
      'description' => $this->randomMachineName(),
      'vid' => 'taxonomy',
      'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
    ]);
    $term->save();

    // echo "Term id :" . $term->id();

    // Create a test node.
    $node = Node::create([
      'type' => 'page',
      'title' => $this->randomMachineName(),
      'field_term' => [$term->id()],
      'language' => 'en',
    ]);
    $node->save();

    $result = \Drupal::service('vactory.views.to_api')->normalizeNode($node, [
      "fields" => [
        "field_term" => "theme"
      ],
      "image_styles" => []
    ]);

    $this->assertSame($result, [
      "theme" => [
        "id" => 1,
        "label" => "Events"
      ]
    ]);
  }

}
