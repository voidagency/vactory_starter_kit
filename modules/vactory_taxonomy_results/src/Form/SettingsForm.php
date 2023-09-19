<?php

namespace Drupal\vactory_taxonomy_results\Form;

use Drupal\Core\Entity\ContentEntityType;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provide the taxonomy results setting form.
 *
 * @package Drupal\vactory_taxonomy_results\Form
 */
class SettingsForm extends ConfigFormBase {

  /**
   * Entity type manager service.
   *
   * @var EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->entityTypeManager = $container->get('entity_type.manager');
    return $instance;
  }

  /**
   * Gets the configuration names that will be editable.
   *
   * @return array
   *   An array of configuration object names that are editable if called in
   *   conjunction with the trait's config() method.
   */
  protected function getEditableConfigNames() {
    return ['vactory_taxonomy_results.settings'];
  }

  /**
   * Returns a unique string identifying the form.
   *
   * The returned ID should be a unique string that can be a valid PHP function
   * name, since it's used in hook implementation names such as
   * hook_form_FORM_ID_alter().
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'vactory_taxonomy_results_settings';
  }

  /**
   * {@inheritDoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('vactory_taxonomy_results.settings');
    $entity_type_definitions = $this->entityTypeManager->getDefinitions();
    $entity_type_definitions = array_filter($entity_type_definitions, function ($definition) {
      return $definition instanceof ContentEntityType && !in_array($definition->id(), vactory_taxonomy_results_excluded_entities());
    });

    $form = parent::buildForm($form, $form_state);
    $form['settings_tab'] = [
      '#type' => 'vertical_tabs',
    ];

    $bundle_info = \Drupal::service('entity_type.bundle.info');
    $enabled_bundles = $config->get('enabled_bundles');
    foreach ($entity_type_definitions as $entity_type_id => $entity_type_definition) {
      $form[$entity_type_id] = [
        '#type' => 'details',
        '#title' => $entity_type_definition->getLabel(),
        '#group' => 'settings_tab',
        '#tree' => TRUE,
      ];
      $available_bundles = $bundle_info->getBundleInfo($entity_type_id);
      foreach ($available_bundles as $bundle => $infos) {
        $bundle_label = $infos['label'] ?? $bundle;
        $form[$entity_type_id][$bundle] = [
          '#type' => 'details',
          '#title' => $bundle_label,
        ];
        $form[$entity_type_id][$bundle]['enable_result_count'] = [
          '#type' => 'checkbox',
          '#title' => $this->t("Enable taxonomy results count"),
          '#description' => $this->t("If checked the results of all taxonomy fields of {$bundle_label} bundle will be calculated"),
          '#default_value' => isset($enabled_bundles[$entity_type_id]) && in_array($bundle, $enabled_bundles[$entity_type_id]),
        ];
      }
    }

    $form['calculate_results'] = [
      '#type' => 'checkbox',
      '#title' => $this->t("Calculate taxonomy term results after saving configuration"),
      '#description' => $this->t("If checked existing term results count entities will be cleaned and taxonomy fields of enabled bundles related results will be calculated"),
      '#default_value' => isset($enabled_bundles[$entity_type_id]) && in_array($bundle, $enabled_bundles[$entity_type_id]),
    ];

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $config = $this->config('vactory_taxonomy_results.settings');
    $entity_type_definitions = $this->entityTypeManager->getDefinitions();
    $entity_type_definitions = array_filter($entity_type_definitions, function ($definition) {
      return $definition instanceof ContentEntityType && !in_array($definition->id(), vactory_taxonomy_results_excluded_entities());
    });
    $enabled_bundles = [];
    $bundle_info = \Drupal::service('entity_type.bundle.info');
    foreach ($entity_type_definitions as $entity_type_id => $entity_type_definition) {
      if (isset($values[$entity_type_id])) {
        $available_bundles = $bundle_info->getBundleInfo($entity_type_id);
        foreach ($available_bundles as $bundle => $infos) {
          if (isset($values[$entity_type_id][$bundle]) && $values[$entity_type_id][$bundle]['enable_result_count']) {
            $enabled_bundles[$entity_type_id][] = $bundle;
          }
        }
      }
    }
    $config->set('enabled_bundles', $enabled_bundles)
      ->save();
    parent::submitForm($form, $form_state);

    if (isset($values['calculate_results']) && $values['calculate_results']) {
      $config = $this->config('vactory_taxonomy_results.settings');
      $enabled_bundles = $config->get('enabled_bundles');
      // Clear existing term results count entities.
      $ids = $this->entityTypeManager->getStorage('term_result_count')
        ->getQuery()
        ->execute();
      $operations = [];
      if (!empty($ids)) {
        $ids_chunk = array_chunk($ids, 100);
        foreach ($ids_chunk as $ids) {
          $operations[] = [
            'vactory_taxonomy_results_cleaner_batch',
            [$ids],
          ];
        }
      }

      // Recalculate taxonomy term result count.
      $languages = \Drupal::languageManager()->getLanguages();
      $langcodes = array_map(function ($language) {
        return $language->getId();
      }, $languages);
      foreach ($enabled_bundles as $entity_type_id => $bundles) {
        $status = $this->entityTypeManager->getDefinition($entity_type_id)->getKey('status');
        $status = !$status ? $this->entityTypeManager->getDefinition($entity_type_id)->getKey('published') : $status;
        $type = $this->entityTypeManager->getDefinition($entity_type_id)->getKey('bundle');
        $query = $this->entityTypeManager->getStorage($entity_type_id)
          ->getQuery();
        if (!empty($type)) {
          $query->condition($type, $bundles, 'IN');
        }
        if (!empty($status)) {
          $query->condition($status, 1);
        }
        $ids = $query->execute();
        if (!empty($ids)) {
          $ids_chunk = array_chunk($ids, 50);
          foreach ($ids_chunk as $ids ) {
            $operations[] = [
              'vactory_taxonomy_results_count_batch',
              [$ids, $entity_type_id, $langcodes],
            ];
          }
        }
      }
      if (!empty($operations)) {
        $batch = [
          'title'      => "Calculate taxonomy term result count",
          'operations' => $operations,
          'finished'   => 'vactory_taxonomy_results_count_finish',
        ];
        batch_set($batch);
      }
    }
  }

}

