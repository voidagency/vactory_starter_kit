<?php

namespace Drupal\vactory_content_sheets\Services;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\DatabaseException;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Component\Utility\NestedArray;
use Drupal\vactory_dynamic_field\WidgetsManager;

/**
 * Content service class.
 */
class ContentService {

  /**
   * The logger channel factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $logger;

  /**
   * The database service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The DF manager.
   *
   * @var \Drupal\vactory_dynamic_field\WidgetsManager
   */
  protected $platformProvider;

  const SIMPLE_TEXT_TYPES = [
    'text',
    'textarea',
  ];

  /**
   * Constructs a new AnnouncementsService.
   */
  public function __construct(Connection $database, LoggerChannelFactoryInterface $logger, WidgetsManager $platformProvider) {
    $this->database = $database;
    $this->logger = $logger;
    $this->platformProvider = $platformProvider;
  }

  /**
   * Get content by key.
   */
  public function getContent(string $key = '', string $lang = NULL) {
    \Drupal::logger('getContent =')->debug('key: @key', [
      '@key' => print_r($key, TRUE),
    ]);
    $langcode = isset($lang) ? $lang : \Drupal::languageManager()
      ->getCurrentLanguage()
      ->getId();

    try {
      $result = $this->database->select('vactory_content_sheets', 'v')
        ->fields('v', ['content'])
        ->condition('key', $key)
        ->condition('langcode', $langcode)
        ->execute()
        ->fetchField();
      return $result ?: NULL;
    }
    catch (DatabaseException $e) {
      \Drupal::logger('vactory_content_sheets')
        ->error(t("Database error in ContentService :") . $e->getMessage());
      return NULL;
    }
  }

  /**
   * Extract label/url from text.
   */
  public function extractCTA($text) {
    // Define the pattern to match the CTA label and URL.
    $pattern = '/(?P<label>[^(]+)\((?P<url>[^)]+)\)/';

    // Use preg_match to find the matches
    preg_match($pattern, $text, $matches);
    // Check if there are matches and return the result
    if ($matches && isset($matches['label'], $matches['url'])) {
      $ctaLabel = trim($matches['label']);
      $ctaUrl = trim($matches['url']);
      return ['label' => $ctaLabel, 'url' => $ctaUrl];
    }
    else {
      // Return null if no matches are found.
      return NULL;
    }
  }

  /**
   * Get default value by type.
   */
  public function getDefaultValueByType($text, $type) {
    if (in_array($type, self::SIMPLE_TEXT_TYPES)) {
      if (str_starts_with($text, "tx:")) {
        return $this->getContent($text) ?? $text;
      }
    }

    if ($type === "text_format") {
      if ((str_starts_with($text, 'tx:') || str_starts_with($text, '<p>tx:'))) {
        return $this->getContent($text) ?? $text;
      }
    }

    if ($type === 'url_extended') {
      $url = $text['url'];
      if (str_starts_with($url, 'cta:')) {
        $retrievedContent = $this->getContent($url);
        $retrievedContent = $this->extractCTA($retrievedContent);
        if ($retrievedContent !== NULL) {
          $text['url'] = $retrievedContent['url'];
          $text['title'] = $retrievedContent['label'];
          return $text;
        }
      }
    }

    return NULL;
  }

  /**
   * Replace content sheet regex by real data.
   */
  public function replaceContentSheetRegex($paragraph, $lang = NULL) {
    $skip = ['auto_populate', 'pending_content'];
    $widget_data = $paragraph->get('field_vactory_component')
      ->getValue()[0]['widget_data'];
    $widget_id = $paragraph->get('field_vactory_component')
      ->getValue()[0]['widget_id'];
    $settings = $this->platformProvider->loadSettings($widget_id) ?? [];
    $widget_data = json_decode($widget_data, TRUE);
    foreach ($widget_data as $key => &$component) {
      if (in_array($key, $skip)) {
        continue;
      }
      if ($key == "extra_field") {
        $this->applyFormatters(['extra_fields'], $settings, $component, $lang);
      }
      else {
        $this->applyFormatters(['fields'], $settings, $component, $lang);
      }

    }
    return [
      [
        'widget_id' => $widget_id,
        'widget_data' => json_encode($widget_data),
      ],
    ];
  }

  /**
   * Apply formatters function.
   */
  public function applyFormatters($parent_keys, $settings, &$component, $lang = NULL) {
    foreach ($component as $field_key => &$value) {
      $info = NestedArray::getValue($settings, array_merge((array) $parent_keys, [$field_key]));
      if (isset($info['type'])) {
        if (in_array($info['type'], self::SIMPLE_TEXT_TYPES)) {
          if (str_starts_with($value, 'tx:')) {
            $retrievedContent = $this->getContent($value, $lang);
            if ($retrievedContent) {
              $value = $retrievedContent;
            }
          }
        }

        if ($info['type'] === 'text_format') {
          $text = $value['value'] ?? $value;
          if (str_starts_with($text, 'tx:') || str_starts_with($text, '<p>tx:')) {
            $retrievedContent = $this->getContent($text, $lang);
            if ($retrievedContent) {
              $value['value'] = $retrievedContent;
            }
          }
        }

        if ($info['type'] === 'url_extended') {
          $url = $value['url'];
          if (str_starts_with($url, 'cta:')) {
            $retrievedContent = $this->getContent($url, $lang);
            if ($retrievedContent !== NULL) {
              $retrievedContent = $this->extractCTA($retrievedContent);
              if ($retrievedContent) {
                $value['url'] = $retrievedContent['url'];
                $value['title'] = $retrievedContent['label'];
              }
            }
          }
        }
      }

    }
  }

}
