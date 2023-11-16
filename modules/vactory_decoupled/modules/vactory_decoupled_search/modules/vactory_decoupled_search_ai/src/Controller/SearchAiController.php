<?php

namespace Drupal\vactory_decoupled_search_ai\Controller;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Url;
use Drupal\metatag\MetatagManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\search_api_pinecone\Plugin\search_api\backend\SearchApiPineconeBackend;
use Drupal\openai\Utility\StringHelper;
use OpenAI\Client;
use Drupal\openai_embeddings\Http\PineconeClient;
use Drupal\node\NodeInterface;
use Drupal\Core\Entity\EntityRepository;

/**
 * Class SearchController
 *
 * @package Drupal\vactory_decoupled_search\Controller
 */
class SearchAiController extends ControllerBase
{

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  const EMBEDDING_MODEL = 'text-embedding-ada-002';

  const PINECONE_THRESHOLD = 0.78;

  const PINECONE_TOPK = 4;

  protected $aiClient;
  
  protected $pineconeClient;

  protected $entityRepository;

  /**
   * SearchController constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   * @param \Drupal\Core\Language\LanguageManagerInterface $languageManager
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager,
                              LanguageManagerInterface $languageManager,
                              Client $aiClient,
                              PineconeClient $pineconeClient,
                              EntityRepository $entityRepository) {
    $this->entityTypeManager = $entityTypeManager;
    $this->languageManager = $languageManager;
    $this->aiClient = $aiClient;
    $this->pineconeClient = $pineconeClient;
    $this->entityRepository = $entityRepository;
  }

  /**
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *
   * @return \Drupal\Core\Controller\ControllerBase|static
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('language_manager'),
      $container->get('openai.client'),
      $container->get('openai_embeddings.pinecone_client'),
      $container->get('entity.repository')
    );
  }


  /**
   * Output Search result.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   */
  public function index(Request $request) {
    $query = StringHelper::prepareText($request->query->get('q'), [], 1024);
    
    if (empty($query)) {
      return new JsonResponse([
        'resources' => [],
        'status' => 400
      ]);
    }

    $index = $this->entityTypeManager
      ->getStorage('search_api_index')
      ->load('ai_search');
    $backend = $index->getServerInstance()->getBackend();
    assert($backend instanceof SearchApiPineconeBackend);
    $namespace = $backend->getNamespace($index);
    $score_threshold = self::PINECONE_THRESHOLD;

    // Create the embedding for the latest question.
    try {
      $response = $this->aiClient->embeddings()->create([
        'model' => self::EMBEDDING_MODEL,
        'input' => $query,
      ])->toArray();
      $query_embedding = $response['data'][0]['embedding'] ?? NULL;
      if (!$query_embedding) {
        return new JsonResponse([
          'message' => 'Unexpected embedding response.',
          'status' => 400
        ]);
      }
    }
    catch (\Exception $exception) {
      return new JsonResponse([
        'message' => "Query embedding exception: {$exception->getMessage()}",
        'status' => 400
      ]);
    }
    $langCode = $this->languageManager->getCurrentLanguage()->getId();
    $filters['language'] = ['$eq' => $langCode];
    // Find the best matches from pinecone.
    $results = [];
    try {
      $response = $this->pineconeClient->query(
        $query_embedding,
        self::PINECONE_TOPK,
        TRUE,
        FALSE,
        $filters,
        '',
      );
      $result = json_decode($response->getBody()->getContents(), flags: JSON_THROW_ON_ERROR);
      if (empty($result->matches)) {
        return new JsonResponse([
          'resources' => [],
          'count' => 0,
          'status' => 200
        ]);
      }
      foreach ($result->matches as $match) {
        if ($match->score < $score_threshold) {
          continue;
        }
        $entity = $index->loadItem($match->metadata->item_id)->getValue();
        if ($entity instanceof NodeInterface) {
          $transEntity = $this->entityRepository->getTranslationFromContext($entity, $langCode);
          if (isset($transEntity)) {
            $item['label'] = $transEntity->label();
            $item['score'] = $match->score;
            $item['url'] = $transEntity->toUrl()->toString();
            if ($transEntity->hasField('node_summary')) {
              $item['summary'] = $transEntity->get('node_summary')->value;
            }
            $results[] = $item;
            $item = [];
          }
        }
      }
    }
    catch (\Exception $exception) {
      return new JsonResponse([
        'message' => "Pinecone query exception: {$exception->getMessage()}",
        'status' => 400
      ]);
    }

    return new JsonResponse([
      'resources' => $results,
      'count' => count($results),
      'status' => 200,
    ]);
  }

}
