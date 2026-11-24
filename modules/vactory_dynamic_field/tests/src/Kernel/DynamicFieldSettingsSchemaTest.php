<?php

namespace Drupal\Tests\vactory_dynamic_field\Kernel;

use Drupal\Component\Serialization\Yaml;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\SchemaCheckTestTrait;

/**
 * The settings of a Dynamic Field have a config schema.
 *
 * Without it, saving a Dynamic Field fails when the config schema is checked
 * strictly ("allowed_providers missing schema").
 *
 * @group vactory_dynamic_field
 */
class DynamicFieldSettingsSchemaTest extends KernelTestBase {

  use SchemaCheckTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'field',
    'entity_test',
    'vactory_dynamic_field',
  ];

  /**
   * The settings shipped by the module are valid and kept.
   *
   * @dataProvider providerShippedSettings
   */
  public function testShippedSettings(string $config_file): void {
    $shipped = Yaml::decode(file_get_contents(dirname(__DIR__, 3) . '/' . $config_file))['settings'];
    FieldStorageConfig::create([
      'field_name' => 'field_df',
      'entity_type' => 'entity_test',
      'type' => 'field_wysiwyg_dynamic',
    ])->save();
    // The strict schema check of the test runs on this save, after Drupal has
    // cast the values to the schema types (an unchecked provider: 0 to "0").
    FieldConfig::create([
      'field_name' => 'field_df',
      'entity_type' => 'entity_test',
      'bundle' => 'entity_test',
      'settings' => $shipped,
    ])->save();
    $this->assertConfigSchemaByName('field.field.entity_test.entity_test.field_df');

    $saved = FieldConfig::load('entity_test.entity_test.field_df')->getSetting('allowed_providers');
    $this->assertSame(array_keys($shipped['allowed_providers']), array_keys($saved));
    // Unchecked providers (0) stay unchecked: the form filters them out.
    $this->assertSame(array_keys(array_filter($shipped['allowed_providers'])), array_keys(array_filter($saved)));
    $this->assertSame('0', $saved['vactory_froala']);
  }

  /**
   * Data provider for testShippedSettings().
   */
  public static function providerShippedSettings(): array {
    return [
      'block component' => ['config/install/field.field.block_content.vactory_block_component.field_dynamic_block_components.yml'],
      'gallery' => ['modules/vactory_dynamic_field_unitegallery/config/install/field.field.node.vactory_gallery.field_vactory_gallery_item.yml'],
    ];
  }

}
