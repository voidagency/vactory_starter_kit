<?php 

// namespace Drupal\vactory_content_sheets;
namespace Drupal\vactory_content_sheets\Services;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\DatabaseException;
// use Psr\Log\LoggerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;


class ContentService {
  /**
   * The logger channel factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */


  protected $database;
  protected $logger;


  /**
  * Constructs a new AnnouncementsService.
  *
  * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger
  *   The logger channel factory.
  */
  public function __construct(Connection $database, LoggerChannelFactoryInterface $logger) {
    $this->database = $database;
    $this->logger = $logger;
  }

  public function getContent(string $key = '') {
    \Drupal::logger('getContent =')->debug('key: @key', [
              '@key' => print_r($key, TRUE),
            ]);
    $langcode = \Drupal::languageManager()->getCurrentLanguage()->getId();

    try {
      $result = $this->database->select('vactory_content_sheets', 'v')
                               ->fields('v', ['content'])
                               ->condition('key', $key)
                               ->condition('langcode', $langcode)
                               ->execute()
                               ->fetchField();
      return $result ?: NULL;
    } catch (DatabaseException $e) {
      // $this->logger->error('Database error in ContentService: @message', ['@message' => $e->getMessage()]);
      \Drupal::logger('vactory_content_sheets')->error(t("Database error in ContentService :") . $e->getMessage());
      return NULL;
    }
  }
}

