<?php

namespace Drupal\vactory_decoupled;

use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\filter\FilterProcessResult;
use Drupal\linkit\SubstitutionManagerInterface;

/**
 * Provides a service to render link.
 *
 */
class Linkit {

  /**
   * The entity repository.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * The substitution manager.
   *
   * @var \Drupal\linkit\SubstitutionManagerInterface
   */
  protected $substitutionManager;

  /**
   * Constructs a LinkitFilter object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository service.
   * @param \Drupal\linkit\SubstitutionManagerInterface $substitution_manager
   *   The substitution manager.
   */
  public function __construct(EntityRepositoryInterface $entity_repository, SubstitutionManagerInterface $substitution_manager) {
    $this->entityRepository = $entity_repository;
    $this->substitutionManager = $substitution_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    $result = new FilterProcessResult($text);

    if (strpos($text, 'data-entity-type') !== FALSE && strpos($text, 'data-entity-uuid') !== FALSE) {
      $dom = Html::load($text);
      $xpath = new \DOMXPath($dom);

      foreach ($xpath->query('//a[@data-entity-type and @data-entity-uuid]') as $element) {
        /** @var \DOMElement $element */
        try {
          // Load the appropriate translation of the linked entity.
          $entity_type = $element->getAttribute('data-entity-type');
          $uuid = $element->getAttribute('data-entity-uuid');

          // Skip empty attributes to prevent loading of non-existing
          // Content type.
          // Check if the linked entity is not a node.
          if ($entity_type != 'node' || $uuid === '') {
            continue;
          }

          $substitution_type = $element->getAttribute('data-entity-substitution') ?? SubstitutionManagerInterface::DEFAULT_SUBSTITUTION;
          $entity = $this->entityRepository->loadEntityByUuid($entity_type, $uuid);
          
          if ($entity) {

            $entity = $this->entityRepository->getTranslationFromContext($entity, $langcode);

            /** @var \Drupal\Core\Url $url */
            $url = $this->substitutionManager
              ->createInstance($substitution_type)
              ->getUrl($entity);
            if (!$url) {
              continue;
            }

            // Parse link href as url, extract query and fragment from it.
            $href_url = parse_url($element->getAttribute('href'));

            if (!empty($href_url["fragment"])) {
              $url->setOption('fragment', $href_url["fragment"]);
            }
            if (!empty($href_url["query"])) {
              $parsed_query = [];
              parse_str($href_url['query'], $parsed_query);
              if (!empty($parsed_query)) {
                $url->setOption('query', $parsed_query);
              }
            }
            $element->setAttribute('href', $url->toString());
          }
        }
        catch (\Exception $e) {
          watchdog_exception('vactory_linkit_DF_filter', $e);
        }
      }

      $result->setProcessedText(Html::serialize($dom));
    }

    return $result;
  }

}
