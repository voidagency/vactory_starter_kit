<?php

namespace Drupal\vactory_core\Entity;

use Drupal\paragraphs\Entity\Paragraph;

/**
 * {@inheritdoc}
 */
class VactoryParagraph extends Paragraph {

  /**
   * {@inheritdoc}
   */
  public function getSummary(array $options = []) {
    // Return title instead of normal summary.
    $title_field_name = 'field_vactory_title';
    if (array_key_exists($title_field_name, $this->getFieldDefinitions()) && $this->get($title_field_name)) {
      return $this->get($title_field_name)->getString();
    }
    else {
      return parent::getSummary($options);
    }
  }
}