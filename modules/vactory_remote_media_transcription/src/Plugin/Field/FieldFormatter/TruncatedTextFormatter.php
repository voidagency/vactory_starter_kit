<?php

namespace Drupal\vactory_remote_media_transcription\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FormatterInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'truncated_text' formatter.
 *
 * @FieldFormatter(
 *   id = "truncated_text",
 *   label = @Translation("Truncated text"),
 *   field_types = {
 *     "text",
 *     "text_long",
 *     "text_with_summary",
 *   }
 * )
 */
class TruncatedTextFormatter extends FormatterBase implements FormatterInterface {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'character_limit' => 50,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form['character_limit'] = [
      '#type' => 'number',
      '#title' => $this->t('Character limit'),
      '#default_value' => $this->getSetting('character_limit'),
      '#description' => $this->t('Set the maximum number of characters to display.'),
      '#required' => TRUE,
      '#min' => 1,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    return [
      $this->t('Character limit: @limit', ['@limit' => $this->getSetting('character_limit')]),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $character_limit = $this->getSetting('character_limit');

    foreach ($items as $delta => $item) {
      // Use Unicode::truncate to truncate text safely.
      $text = Unicode::truncate($item->value, $character_limit, TRUE, TRUE);

      // Sanitize the text for XSS protection.
      $elements[$delta] = [
        '#markup' => htmlspecialchars($text, ENT_QUOTES, 'UTF-8'),
      ];
    }

    return $elements;
  }
  
}
