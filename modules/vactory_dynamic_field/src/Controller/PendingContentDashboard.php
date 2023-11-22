<?php

namespace Drupal\vactory_dynamic_field\Controller;

use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ExtensionPathResolver;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\path_alias\AliasManagerInterface;
use Drupal\vactory_dynamic_field\PendingContentManager;
use Drupal\vactory_dynamic_field\WidgetsManager;
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
   * The plugin manager.
   *
   * @var \Drupal\vactory_dynamic_field\WidgetsManager
   */
  protected $widgetsManager;

  /**
   * Extension path resolver service.
   *
   * @var \Drupal\Core\Extension\ExtensionPathResolver
   */
  protected $extensionPathResolver;

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
   * Widgets list.
   *
   * @var array
   */
  protected $widgetsList;

  /**
   * {@inheritDoc}
   */
  public function __construct(
    PendingContentManager $pendingContentManager,
    EntityTypeManagerInterface $entityTypeManager,
    EntityRepositoryInterface $entityRepository,
    AliasManagerInterface $aliasManager,
    LanguageManagerInterface $languageManager,
    FormBuilder $formBuilder,
    WidgetsManager $widgetsManager,
    ExtensionPathResolver $extensionPathResolver
  ) {
    $this->pendingContentManager = $pendingContentManager;
    $this->entityTypeManager = $entityTypeManager;
    $this->entityRepository = $entityRepository;
    $this->aliasManager = $aliasManager;
    $this->languageManager = $languageManager;
    $this->formBuilder = $formBuilder;
    $this->extensionPathResolver = $extensionPathResolver;
    $this->widgetsManager = $widgetsManager;
    $this->widgetsList = $this->widgetsManager->getModalWidgetsList();
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
      $container->get('form_builder'),
      $container->get('vactory_dynamic_field.vactory_provider_manager'),
      $container->get('extension.path.resolver')
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
    if ($pending_content_count + $resolved_content_count === 0) {
      $pourcentage = '100%';
    }
    else {
      $pourcentage = round(($resolved_content_count * 100) / ($resolved_content_count + $pending_content_count));
      $pourcentage .= '%';
    }
    $default_langcode = $this->languageManager->getDefaultLanguage()->getId();
    $languages = $this->languageManager->getLanguages();
    $result = [];
    foreach ($content as $info) {
      $entity_type = $info['entity_type'];
      $entity_id = $info['entity_id'];
      $paragraph_id = $info['paragraph_id'];
      $entity = $this->entityTypeManager->getStorage($info['entity_type'])
        ->load($info['entity_id']);
      if ($default_langcode !== $info['langcode']) {
        $entity = $this->entityRepository->getTranslationFromContext($entity, $info['langcode']);
      }
      $info['alias'] = "/{$info['langcode']}/block/{$entity->id()}?destination=/{$info['langcode']}/admin/content/pending";
      if ($entity_type === 'node') {
        $info['alias'] = '/' . $info['langcode'] . $this->aliasManager->getAliasByPath('/node/' . $info['entity_id'], $info['langcode']);
      }
      $info['entity'] = $entity;
      $info['title'] = $info['entity_type'] === 'node' ? $entity->get('title')->value : $entity->get('info')->value;
      $info['language'] = $languages[$info['langcode']]->getName();
      $info['edit_link'] = $info['alias'];
      $settings = $this->widgetsManager->loadSettings($info['widget_id']);
      $category = $settings['category'] ?? 'Others';
      $widget = $this->widgetsList[$category][$info['widget_id']];
      $screenshot = $widget['screenshot'] ?? $this->extensionPathResolver->getPath('module', 'vactory_dynamic_field') . '/images/undefined-screenshot.jpg';
      $file_url_generator = \Drupal::service('file_url_generator');
      $undefined_screenshot = $this->extensionPathResolver->getPath('module', 'vactory_dynamic_field') . '/images/undefined-screenshot.jpg';
      $info['widget_screen'] = empty($screenshot) ? $file_url_generator->generateAbsoluteString($undefined_screenshot) : $screenshot;
      if ($entity_type === 'node') {
        $info['edit_link'] = "/{$info['langcode']}/paragraphs_edit/node/{$info['entity_id']}/paragraphs/{$info['paragraph_id']}/edit?destination=/{$info['langcode']}/admin/content/pending";
        $result[$entity_id]['entity_type'] = $entity_type;
        $result[$entity_id]['title'] = $info['title'];
        $result[$entity_id]['alias'] = $info['alias'];
        $result[$entity_id]['content'][$paragraph_id]['fields'][] = $info['field_label'];
        $result[$entity_id]['content'][$paragraph_id]['screenshot'] = $info['widget_screen'];
        $result[$entity_id]['content'][$paragraph_id]['widget_name'] = $info['widget_name'];
        $result[$entity_id]['content'][$paragraph_id]['language'] = $info['language'];
        $result[$entity_id]['content'][$paragraph_id]['edit_link'] = $info['edit_link'];
      }
      else {
        $result[$entity_id]['entity_type'] = $entity_type;
        $result[$entity_id]['title'] = $info['title'];
        $result[$entity_id]['fields'][] = $info['field_label'];
        $result[$entity_id]['widget_name'] = $info['widget_name'];
        $result[$entity_id]['edit_link'] = $info['edit_link'];
        $result[$entity_id]['language'] = $info['language'];
        $result[$entity_id]['screenshot'] = $info['widget_screen'];
        $result[$entity_id][] = [
          'entity_type' => $entity_type,
          'content' => $info,
        ];
      }
    }

    // Get filter form using the form builder.
    $filter_form = $this->formBuilder->getForm('Drupal\vactory_dynamic_field\Form\PendingContentFilterForm');
    return [
      '#theme' => 'vactory_dynamic_pending_content',
      '#content' => $result,
      '#pourcentage' => $pourcentage,
      '#filter_form' => $filter_form,
    ];
  }

}
