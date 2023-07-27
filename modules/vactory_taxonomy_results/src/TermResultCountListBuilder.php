<?php

namespace Drupal\vactory_taxonomy_results;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Routing\RedirectDestinationInterface;
use Drupal\social_media_links\Plugin\SocialMediaLinks\Platform\Drupal;
use Drupal\taxonomy\Entity\Term;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a list controller for the termresultscount entity type.
 */
class TermResultCountListBuilder extends EntityListBuilder {

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The redirect destination service.
   *
   * @var \Drupal\Core\Routing\RedirectDestinationInterface
   */
  protected $redirectDestination;

  /**
   * Constructs a new TermResultCountListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage class.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Drupal\Core\Routing\RedirectDestinationInterface $redirect_destination
   *   The redirect destination service.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, DateFormatterInterface $date_formatter, RedirectDestinationInterface $redirect_destination) {
    parent::__construct($entity_type, $storage);
    $this->dateFormatter = $date_formatter;
    $this->redirectDestination = $redirect_destination;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity_type.manager')->getStorage($entity_type->id()),
      $container->get('date.formatter'),
      $container->get('redirect.destination')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build['table'] = parent::render();

    $total = $this->getStorage()
      ->getQuery()
      ->count()
      ->accessCheck(FALSE)
      ->execute();

    $build['summary']['#markup'] = $this->t('Total termresultscounts: @total', ['@total' => $total]);
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('ID');
    $header['tid'] = $this->t('Term');
    $header['vid'] = $this->t('Taxonomy');
    $header['etity_type'] = $this->t('Referenced entity type');
    $header['bundle'] = $this->t('Referenced bundle');
    $header['plugin'] = $this->t('Term result count plugin');
    $header['count'] = $this->t('Results count');
    $header['langcode'] = $this->t('Language');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $language = \Drupal::languageManager()->getCurrentLanguage();
    $langcode = $language->getId();
    $entity = \Drupal::service('entity.repository')
      ->getTranslationFromContext($entity, $langcode);
    $language_name = $language->getName();
    $tid = $entity->get('tid')->value;
    $vid = '';
    if (!empty($tid)) {
      $term = Term::load($tid);
      if ($term) {
        $vid = $term->bundle();
        $tid = $term->getName();
      }
    }
    $row['id'] = $entity->id();
    $row['tid'] = $tid;
    $row['vid'] = $vid;
    $row['etity_type'] = $entity->get('entity_type')->value;
    $row['bundle'] = $entity->get('bundle')->value;
    $row['plugin'] = $entity->get('plugin')->value;
    $row['count'] = $entity->get('count')->value;
    $row['langcode'] = $language_name;
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultOperations(EntityInterface $entity) {
    $operations = parent::getDefaultOperations($entity);
    $destination = $this->redirectDestination->getAsArray();
    foreach ($operations as $key => $operation) {
      $operations[$key]['query'] = $destination;
    }
    return $operations;
  }

}
