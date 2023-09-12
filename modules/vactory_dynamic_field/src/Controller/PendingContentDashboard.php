<?php

namespace Drupal\vactory_dynamic_field\Controller;

use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\path_alias\AliasManagerInterface;
use Drupal\vactory_dynamic_field\PendingContentManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormBuilder;

/**
 * Pending Content Dashboard class.
 */
class PendingContentDashboard extends ControllerBase {

  /**
   * Pending content Manager.
   *
   * @var \Drupal\vactory_dynamic_field\PendingContentManager
   */
  protected $pendingContentManager;

  /**
   * Entity type Manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Entity repository Manager.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * Path alias manager.
   *
   * @var \Drupal\path_alias\AliasManagerInterface
   */
  protected $aliasManager;

  /**
   * Language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilder
   */
  protected $formBuilder;

  /**
   * {@inheritDoc}
   */
  public function __construct(
    PendingContentManager $pendingContentManager,
    EntityTypeManagerInterface $entityTypeManager,
    EntityRepositoryInterface $entityRepository,
    AliasManagerInterface $aliasManager,
    LanguageManagerInterface $languageManager,
    FormBuilder $formBuilder
  ) {
    $this->pendingContentManager = $pendingContentManager;
    $this->entityTypeManager = $entityTypeManager;
    $this->entityRepository = $entityRepository;
    $this->aliasManager = $aliasManager;
    $this->languageManager = $languageManager;
    $this->formBuilder = $formBuilder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('df_pending_content.manager'),
      $container->get('entity_type.manager'),
      $container->get('entity.repository'),
      $container->get('path_alias.manager'),
      $container->get('language_manager'),
      $container->get('form_builder')
    );
  }

  /**
   * Callback for opening the modal form.
   */
  public function dashboard() {
    // Get filter query params.
    $query_params = \Drupal::request()->query->all();
    $filters = array_filter($query_params, fn($el) => !empty($el));
    // Get filtered content.
    $content = $this->pendingContentManager->getAllPendingContent($filters);
    // Get all pending content.
    $pending_content_count = $this->pendingContentManager->getPendingContentCount();
    $resolved_content_count = $this->pendingContentManager->getResolvedContentCount();
    // Pourcentage d'avancement.
    if ($resolved_content_count + $resolved_content_count === 0) {
      $pourcentage = '100%';
    }
    else {
      $pourcentage = round(($resolved_content_count * 100) / ($resolved_content_count + $pending_content_count));
      $pourcentage .= '%';
    }
    $default_langcode = $this->languageManager->getDefaultLanguage()->getId();
    $languages = $this->languageManager->getLanguages();
    foreach ($content as &$info) {
      $entity = $this->entityTypeManager->getStorage($info['entity_type'])
        ->load($info['entity_id']);
      if ($default_langcode !== $info['langcode']) {
        $entity = $this->entityRepository->getTranslationFromContext($entity, $info['langcode']);
      }
      $info['alias'] = "/{$info['langcode']}/block/{$entity->id()}?destination=/{$info['langcode']}/admin/content/pending";
      if ($info['entity_type'] === 'node') {
        $info['alias'] = '/' . $info['langcode'] . $this->aliasManager->getAliasByPath('/node/' . $info['entity_id'], $info['langcode']);
      }
      $info['entity'] = $entity;
      $info['title'] = $info['entity_type'] === 'node' ? $entity->get('title')->value : $entity->get('info')->value;
      $info['language'] = $languages[$info['langcode']]->getName();
      $info['edit_link'] = $info['alias'];
      if ($info['entity_type'] === 'node') {
        $info['edit_link'] = "/{$info['langcode']}/paragraphs_edit/node/{$info['entity_id']}/paragraphs/{$info['paragraph_id']}/edit?destination=/{$info['langcode']}/admin/content/pending";
      }
    }
    // Get filter form using the form builder.
    $filter_form = $this->formBuilder->getForm('Drupal\vactory_dynamic_field\Form\PendingContentFilterForm');
    return [
      '#theme' => 'vactory_dynamic_pending_content',
      '#content' => $content,
      '#pourcentage' => $pourcentage,
      '#filter_form' => $filter_form,
    ];
  }

}
