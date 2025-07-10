<?php

namespace Drupal\vactory_checklist\Plugin\Checklist;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Vérifie la présence du field_vactory_meta_tags sur les types de contenu.
 *
 * @ChecklistPlugin(
 *   id = "meta_tags_field_check",
 *   label = @Translation("Vérification des champs Meta Tags"),
 *   description = @Translation("Vérifie la présence et le type du champ field_vactory_meta_tags sur les types de contenu"),
 *   category = "content"
 * )
 */
class MetaTagsFieldCheck extends ChecklistBase implements ContainerFactoryPluginInterface {

  use StringTranslationTrait;

  /**
   * Le gestionnaire de champs d'entité.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Le gestionnaire de types de bundles.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * Constructeur.
   */
  public function __construct(
    array $configuration,
          $plugin_id,
          $plugin_definition,
    EntityFieldManagerInterface $entity_field_manager,
    EntityTypeBundleInfoInterface $entity_type_bundle_info
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityFieldManager = $entity_field_manager;
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_field.manager'),
      $container->get('entity_type.bundle.info')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function runCheck() {
    $content_types_without_field = [];
    $content_types_with_incorrect_field = [];

    $content_types = $this->entityTypeBundleInfo->getBundleInfo('node');

    foreach ($content_types as $content_type => $info) {
      $field_definitions = $this->entityFieldManager->getFieldDefinitions('node', $content_type);

      if (!isset($field_definitions['field_vactory_meta_tags'])) {
        $content_types_without_field[] = [
          'type' => $content_type,
          'label' => $info['label'] ?? $content_type,
        ];
        continue;
      }

      $field_definition = $field_definitions['field_vactory_meta_tags'];
      $field_type = $field_definition->getType();

      // Vérifier si le type de champ est correct (metatag)
      if ($field_type !== 'metatag') {
        $content_types_with_incorrect_field[] = [
          'type' => $content_type,
          'label' => $info['label'] ?? $content_type,
          'current_type' => $field_type,
        ];
      }
    }

    $status = empty($content_types_without_field) && empty($content_types_with_incorrect_field);

    if ($status) {
      $message = $this->t('Tous les types de contenu ont le champ field_vactory_meta_tags correct');
    }
    else {
      $message = $this->t('Problèmes détectés avec le champ field_vactory_meta_tags');
    }

    $details = [];

    foreach ($content_types_without_field as $type_info) {
      $details[] = [
        'content_type' => $type_info['label'],
        'issue' => $this->t('Champ manquant'),
      ];
    }

    foreach ($content_types_with_incorrect_field as $type_info) {
      $details[] = [
        'content_type' => $type_info['label'],
        'issue' => $this->t('Type de champ incorrect'),
      ];
    }

    return [
      'status' => $status,
      'message' => $message,
      'details' => $details,
    ];
  }

}
