<?php

namespace Drupal\vactory_dynamic_field_generator;

/**
 * The vactory generator utils.
 */
class VactoryGeneratorUtils {

  /**
   * Comment wrapper.
   */
  public function commentWrapper($fieldName, $html) {
    return "{# Start Render field [" . $fieldName . "] #}" . PHP_EOL . $html . "{# End Render field [" . $fieldName . "] #}" . PHP_EOL . PHP_EOL;
  }

  /**
   * Condition wrapper.
   */
  public function conditionWrapper($field, $html) {
    return "{% if " . $field . " is not empty %}" . PHP_EOL . $html . "{% endif %}" . PHP_EOL;
  }

  /**
   * Multiple wrapper.
   */
  public function multipleWrapper($html = '') {
    return "<div>" . PHP_EOL . "{% for item in content %}" . PHP_EOL . $html . PHP_EOL . "{% endfor %}" . PHP_EOL . "</div>";
  }

  /**
   * Get the widget name.
   */
  public function getWidgetName($key) {
    $parts = explode(":", $key);
    return $parts[1] ?? NULL;
  }

  /**
   * Check if the file is empty or not.
   */
  public function isFileEmpty(string $path): bool {
    if (!file_exists($path)) {
      throw new InvalidArgumentException("The file at $path does not exist.");
    }
    $content = file_get_contents($path);
    return trim($content) === '';
  }

}
