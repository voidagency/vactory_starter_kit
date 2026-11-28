<?php

namespace Drupal\Tests\vactory_core\ExistingSite;

use Drupal\Core\Config\Schema\SchemaCheckTrait;
use Drupal\Core\Serialization\Yaml;
use weitzman\DrupalTestTraits\ExistingSiteBase;

/**
 * Checks the config shipped by the profile against its schema.
 *
 * Every file of config/install and config/optional, in the profile and in
 * its enabled modules, is checked against the schema of the site. A value
 * of the wrong type (an integer for a boolean, for example) fails the test.
 * A key without schema fails only if it is not listed in the baseline file
 * tests/fixtures/shipped-config-missing-schema.txt.
 *
 * @group vactory_core
 */
class ShippedConfigSchemaTest extends ExistingSiteBase {

  use SchemaCheckTrait;

  /**
   * Shipped config has no value of the wrong type, and no new missing schema.
   */
  public function testShippedConfigMatchesSchema(): void {
    $typed_config = \Drupal::service('config.typed');
    $root = \Drupal::service('extension.list.profile')->getPath('vactory_starter_kit');
    $dirs = [$root];
    foreach (\Drupal::moduleHandler()->getModuleList() as $module) {
      if (str_starts_with($module->getPath(), $root . '/')) {
        $dirs[] = $module->getPath();
      }
    }

    $baseline = $this->baseline();
    $checked = 0;
    $type_errors = [];
    $new_missing = [];
    foreach ($dirs as $dir) {
      foreach (['install', 'optional'] as $sub) {
        foreach (glob("$dir/config/$sub/*.yml") ?: [] as $file) {
          $name = basename($file, '.yml');
          if (!$typed_config->hasConfigSchema($name)) {
            continue;
          }
          $checked++;
          $data = Yaml::decode(file_get_contents($file));
          $result = $this->checkConfigSchema($typed_config, $name, $data);
          if (!is_array($result)) {
            continue;
          }
          $relative = substr($file, strlen($root) + 1);
          foreach ($result as $key => $error) {
            $line = "$relative | $key";
            if ($error !== 'missing schema') {
              $type_errors[] = "$line | $error";
            }
            elseif (!isset($baseline[$line])) {
              $new_missing[] = $line;
            }
          }
        }
      }
    }

    $this->assertGreaterThan(0, $checked, 'No shipped config was checked.');
    $this->assertSame([], $type_errors, 'Shipped config values must match the type of their schema.');
    $this->assertSame([], $new_missing, 'Shipped config keys without schema must be fixed, or listed in tests/fixtures/shipped-config-missing-schema.txt with a ticket.');
  }

  /**
   * Returns the known keys without schema, keyed by "file | key".
   */
  private function baseline(): array {
    $file = dirname(__DIR__, 2) . '/fixtures/shipped-config-missing-schema.txt';
    $lines = array_filter(array_map('trim', file($file)), fn ($line) => $line !== '' && !str_starts_with($line, '#'));
    return array_fill_keys($lines, TRUE);
  }

}
