<?php

namespace Drupal\vactory_frequent_searches\Controller;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Database;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\language\ConfigurableLanguageManager;

/**
 * Class FrequentSearchesController.
 * @package Drupal\vactory_frequent_searches\Controller
 */
class FrequentSearchesController extends ControllerBase {

  /**
   * Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Current language code.
   *
   * @var string
   */
  protected $langcode;

  /**
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  public function __construct(EntityTypeManagerInterface $entityTypeManager, ConfigurableLanguageManager $languageManager) {
    $this->entityTypeManager = $entityTypeManager;
    $this->langcode = $languageManager->getCurrentLanguage()->getId();
    $this->database = Database::getConnection();
  }

  /**
   * Fetch Only Searches with results From the database.
   */
  public function fetchKeywordsWithResultsFromDatabase($limit = NULL) {
    $query = "SELECT * FROM vactory_frequent_searches where total_results > 0 AND language = '" . $this->langcode ."' ORDER BY numfound DESC";
    if (isset($limit)) {
      $query = $query . " LIMIT " . $limit;
    }
    $searches = $this->database->query($query);
    if (isset($searches) and !empty($searches)) {
      $result = [];
      foreach ($searches as $key => $search) {
        $result[$key]['id'] = $search->qid;
        $result[$key]['s_name'] = $search->s_name;
        $result[$key]['numfound'] = $search->numfound;
        $result[$key]['keywords'] = $search->keywords;
        $result[$key]['language'] = $search->language;
        $result[$key]['timestamp'] = $search->timestamp;
        $result[$key]['i_name'] = $search->i_name;
        $result[$key]['total_results'] = $search->total_results;
      }
      return $result;
    }
    else {
      return [];
    }
  }

  /**
   * Fetch Only Searches without results From the database.
   */
  public function fetchKeywordsWithNoResultsFromDatabase($limit = NULL) {
    $query = "SELECT * FROM vactory_frequent_searches where total_results = 0 AND language = '" . $this->langcode ."' ORDER BY numfound DESC";
    if (isset($limit)) {
      $query = $query . " LIMIT " . $limit;
    }
    $searches = $this->database->query($query);
    if (isset($searches) and !empty($searches)) {
      $result = [];
      foreach ($searches as $key => $search) {
        $result[$key]['id'] = $search->qid;
        $result[$key]['s_name'] = $search->s_name;
        $result[$key]['numfound'] = $search->numfound;
        $result[$key]['keywords'] = $search->keywords;
        $result[$key]['language'] = $search->language;
        $result[$key]['timestamp'] = $search->timestamp;
        $result[$key]['i_name'] = $search->i_name;
      }
      return $result;
    }
    else {
      return [];
    }
  }

  /**
   * Return the number of the keywords with nomber of searches is 1.
   */
  public function getCountOfKeywordsWithoutResults($lang_id = NULL) {
    $row_numbers = $this->database->select('vactory_frequent_searches', 's')
      ->condition('s.total_results', 0, '=');
    if (isset($lang_id)) {
      $row_numbers = $row_numbers->condition('s.language', $lang_id, "=");
    }
    $row_numbers = $row_numbers->countQuery()
      ->execute()
      ->fetchField();
    return $row_numbers;
  }

  /**
   * Get total of keywords.
   */
  public function getCountOfKeywords($lang_id = NULL) {
    $row_numbers = $this->database->select('vactory_frequent_searches', 's')
      ->condition('s.total_results', 0, '>');
    if (isset($lang_id)) {
      $row_numbers = $row_numbers->condition('s.language', $lang_id, "=");
    }
    $row_numbers = $row_numbers->countQuery()
      ->execute()
      ->fetchField();
    return $row_numbers;
  }

  /**
   * Clear all Unuseless Keywords.
   */
  public function clearDatabaseFromUnuselessKeywords() {
    $this->database->delete('vactory_frequent_searches')
      ->condition('numfound', 1, '<=')
      ->execute();
  }

  /**
   * Delete keywords from database.
   */
  public function deleteKeywordsFromDatabase($id) {
    $this->database->delete('vactory_frequent_searches')
      ->condition('qid', (int) $id, '=')
      ->execute();
  }

  /**
   * Update keywords in database.
   */
  public function updateKeywordById($id, $keyword, $num) {
    $this->database->update('vactory_frequent_searches')
      ->fields([
        'keywords' => $keyword,
        'numfound' => $num,
      ])
      ->condition('qid', (int) $id, '=')
      ->execute();
    Cache::invalidateTags(['vactory_frequent_searches']);
  }

  /**
   * Update keyword By Keywords in database.
   */
  public function updateKeyword($output, $language, $total, $indexId) {
    $this->database->query(
      "update vactory_frequent_searches set numfound = numfound + 1, total_results=:total where keywords= :keyword and language= :lang and i_name= :index",
      [
        ':keyword' => $output,
        ':lang' => $language,
        ':total' => $total,
        ':index' => $indexId,
      ]
    );
    Cache::invalidateTags(['vactory_frequent_searches']);
  }

  /**
   * Add keyword to database.
   */
  public function addKeywordToDatabasa($keyword, $count, $lang, $index, $total) {
    $this->database->insert('vactory_frequent_searches')
      ->fields([
        'keywords' => $keyword,
        'numfound' => $count,
        'language' => $lang,
        'i_name' => $index,
        's_name' => '',
        'timestamp' => time(),
        'total_results' => $total,
      ])
      ->execute();
    Cache::invalidateTags(['vactory_frequent_searches']);
  }

  /**
   * Check if keyword exist on the db.
   */
  public function isExistsKeyword($output, $index, $language) {
    $query_db = $this->database->select('vactory_frequent_searches', 's')
      ->condition('s.keywords', $output, "=")
      ->condition('s.i_name', $index, '=')
      ->condition('s.language', $language, "=");
    $num_rows = $query_db->countQuery()->execute()->fetchField();
    if ($num_rows > 0) {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

}
