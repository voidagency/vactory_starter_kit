<?php

namespace Drupal\vactory_dynamic_field_dummy\Commands;

use Drupal\Component\Serialization\Exception\InvalidDataTypeException;
use Drupal\Component\Serialization\Yaml;
use Drush\Commands\DrushCommands;
use Drupal\vactory_dynamic_field\WidgetsManager;
use Symfony\Component\Finder\Finder;

/**
 * A Drush commandfile.
 */
class DynamicFieldDummyCommands extends DrushCommands {

  /**
   * The custom module path suffix.
   */
  const CUSTOM_MODULES_PATH = "modules/custom";

  const SETTINGS_FILE_NAME = "settings.yml";

  /**
   * The vactory provider manager.
   *
   * @var \Drupal\vactory_dynamic_field\WidgetsManager
   */
  protected $vactoryProviderManager;

  /**
   * Constructs a DynamicFieldGeneratorCommands object.
   */
  public function __construct(
    WidgetsManager $vactoryProviderManager
  ) {
    $this->vactoryProviderManager = $vactoryProviderManager;
  }

  /**
   * Adding examples section for templates.
   *
   * @command df-settings-examples-section
   * @aliases vses
   */
  public function templatesGeneratorCommand($options = ['df' => NULL]) {
    $this->output()
      ->writeln('Adding examples section for templates has started');
    $single_df = $options['df'];

    if ($single_df) {
      $path = $this->vactoryProviderManager->getWidgetsPath($single_df);
      if ($path) {
        $settings_file_path = $path . "/" . $this->getWidgetName($single_df) . "/" . self::SETTINGS_FILE_NAME;
        if (file_exists($settings_file_path)) {
          $contents = file_get_contents($settings_file_path);
          $this->updateTwigFile($contents, $settings_file_path);
        }
        else {
          $this->logger()
            ->error(dt("The $single_df dynamic field does not exist."));
        }
      }
    }
    else {
      $finder = new Finder();
      $widget_list = $this->vactoryProviderManager->getWidgetsList();
      foreach ($widget_list as $widgets) {
        foreach ($widgets as $key => $widget) {
          $path = $this->vactoryProviderManager->getWidgetsPath($key);
          if (str_starts_with($path, self::CUSTOM_MODULES_PATH)) {
            $this->output()
              ->writeln('Add examples section for template settings : [' . $key . '] 🚀');
            // Find settings.yml files.
            $finder->depth('== 1')
              ->files()
              ->name(self::SETTINGS_FILE_NAME)
              ->in($path);
            // Load settings.yml files.
            foreach ($finder as $file) {
              $settings_file_path = $file->getRealPath();
              $contents = $file->getContents();
              $this->updateTwigFile($contents, $settings_file_path);
            }
          }
        }
      }
    }

    $this->logger()
      ->success(dt("Congrats! 🎉 Settings have been updated successfully. Don't forget to push the updated settings to the repo."));
  }

  /**
   * Creating the template twig file.
   */
  private function updateTwigFile($contents, $settings_file_path): void {
    // Decode YAML file.
    try {
      $data = Yaml::decode($contents) ?: [];
      // Prepare the examples section.
      $this->prepareSettingsExampleSection($data);
      $yaml_content = Yaml::encode($data);
      file_put_contents($settings_file_path, $yaml_content);
    }
    catch (InvalidDataTypeException $e) {
      $this->logger()
        ->error(dt("The $settings_file_path contains invalid YAML"));
    }
  }

  /**
   * Prepare settings example section.
   */
  private function prepareSettingsExampleSection(&$data) {
    $multiple = $data['multiple'] ?? FALSE;
    $limit = $multiple ? $data['limit'] ?? 3 : 1;
    $fields = $data['fields'] ?? [];
    $extraFields = $data['extra_fields'] ?? [];
    $data['examples'] = [];
    $fields_example = [];
    foreach ($fields as $field_key => $field) {
      if (str_starts_with($field_key, "group_")) {
        foreach ($field as $key => $value) {
          if (isset($value['type'])) {
            $fields_example[$field_key][$key] = "";
          }
        }
        continue;
      }
      if (isset($field['type'])) {
        $fields_example[$field_key] = "";
      }
    }

    foreach ($extraFields as $field_key => $field) {
      if (str_starts_with($field_key, "group_")) {
        foreach ($field as $key => $value) {
          if (isset($value['type'])) {
            $data['examples']['extra_fields'][$field_key][$key] = "";
          }
        }
        continue;
      }
      if (isset($field['type'])) {
        $data['examples']['extra_fields'][$field_key] = "";
      }
    }
    if ($multiple) {
      for ($i = 0; $i < $limit; $i++) {
        $data['examples']['fields'][$i] = $fields_example;
      }
    }
    else {
      $data['examples']['fields'][0] = $fields_example;
    }
  }

  /**
   * Get the widget name.
   */
  private function getWidgetName($key) {
    $parts = explode(":", $key);
    return $parts[1] ?? NULL;
  }

}
