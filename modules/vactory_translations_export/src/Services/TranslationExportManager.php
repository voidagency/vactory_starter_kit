<?php

namespace Drupal\vactory_translations_export\Services;

use Drupal\Core\Database\Connection;
use Drupal\Core\Language\LanguageManagerInterface;

/**
 * Translation export manager service.
 */
class TranslationExportManager {

  /**
   * Language manager interface.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;
  /**
   * Language manager interface.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Service constructor.
   */
  public function __construct(LanguageManagerInterface $languageManager, Connection $database) {
    $this->languageManager = $languageManager;
    $this->database = $database;
  }

  /**
   * Get existing translation contexts.
   */
  public function getTranslationContexts() {
    $options = [];
    $results = $this->database->select('locales_source', 'ls')
      ->fields('ls', ['context'])
      ->condition('ls.context', '', '<>')
      ->distinct()
      ->execute()
      ->fetchAll();
    if (!empty($results)) {
      $results = array_map(fn($el) => $el->context, $results);
      $options = array_combine($results, $results);
    }
    return $options;
  }

  /**
   * Get translations data.
   */
  public function getTranslationsData($contexts) {
    $data = [];
    $source_query = $this->database->select('locales_source', 'ls')
      ->fields('ls', ['lid', 'source']);
    if (!empty($contexts)) {
      $source_query->condition('ls.context', array_values($contexts), 'IN');
    }
    $results = $source_query->orderBy('lid', 'DESC')
      ->execute()
      ->fetchAll();
    if (!empty($results)) {
      foreach ($results as $result) {
        $data[$result->lid] = [
          $result->lid,
          $result->source,
        ];
      }
    }
    $languages = $this->languageManager->getLanguages();

    foreach ($languages as $langcode => $language) {
      $query = $this->database->select('locales_source', 'ls')
        ->fields('ls', ['lid'])
        ->fields('lt', ['translation'])
        ->condition('lt.language', $langcode);
      if (!empty($contexts)) {
        $query->condition('ls.context', array_values($contexts), 'IN');
      }
      $query->join('locales_target', 'lt', 'ls.lid=lt.lid');
      $results = $query->execute()->fetchAll();
      if (!empty($results)) {
        foreach ($results as $result) {
          $data[$result->lid][$langcode] = $result->translation;
        }
      }
    }
    $langcodes = array_keys($languages);
    $data = array_map(function ($el) use ($langcodes) {
      $values = [
        // Source string.
        $el[1],
      ];
      foreach ($langcodes as $langcode) {
        $values[] = $el[$langcode] ?? '';
      }
      return $values;
    }, $data);
    return $data;
  }

  /**
   * Update translation.
   */
  public function updateTranslation($source, $translation, $langcode, $context) {
    if (empty($source)) {
      return;
    }
    if ($this->sourceExists($source, $context)) {
      // Update the source translation.
      $sid = $this->getSourceId($source);
      if (empty($sid)) {
        return;
      }
      $this->updateSourceTranslation($sid, $translation, $langcode);
    }
    else {
      // Create new source the source translation.
      $this->createSource($source);
      $this->updateTranslation($source, $translation, $langcode, $context);
    }
  }

  /**
   * Check whether source text exist.
   */
  public function sourceExists($source, $context) {
    $query = $this->database->select('locales_source', 'ls')
      ->condition('ls.source', $source);
    if (!empty($context)) {
      $query->condition('ls.context', $context, 'IN');
    }
    $count = $query->condition('ls.source', $source)
      ->countQuery()
      ->execute()
      ->fetchField();
    return $count > 0;
  }

  /**
   * Check whether translation exist.
   */
  public function translationExists($sid, $langcode) {
    $query = $this->database->select('locales_target', 'lt')
      ->condition('lid', $sid);
    $count = $query->condition('language', $langcode)
      ->countQuery()
      ->execute()
      ->fetchField();
    return $count > 0;
  }

  /**
   * Get source ID.
   */
  public function getSourceId($source) {
    $results = $this->database->select('locales_source', 'ls')
      ->fields('ls', ['lid'])
      ->condition('source', $source)
      ->execute()
      ->fetchAll();
    if (!empty($results)) {
      return reset($results)->lid;
    }
    return NULL;
  }

  /**
   * Update source translation.
   */
  public function updateSourceTranslation($sid, $translation, $langcode) {
    if ($this->translationExists($sid, $langcode)) {
      // Update source translation.
      $this->database->update('locales_target')
        ->fields([
          'translation' => $translation,
        ])
        ->condition('language', $langcode)
        ->condition('lid', $sid)
        ->execute();
    }
    else {
      // Create source translation.
      $this->database->insert('locales_target')
        ->fields([
          'translation' => $translation,
          'lid' => $sid,
          'language' => $langcode,
          'customized' => 1,
        ])
        ->execute();
    }
    return NULL;
  }

  /**
   * Create new source string.
   */
  public function createSource($source) {
    // Create source translation.
    $this->database->insert('locales_source')
      ->fields([
        'source' => $source,
        'context' => '_FRONTEND',
        'version' => 'latest',
      ])
      ->execute();
  }

}
