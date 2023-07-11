<?php

namespace Drupal\vactory_migrate_plugin\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\MigrateSkipRowException;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;



/**
 *
 * Use this plugin to get the new Id based on the legacy Id.
 * This plugin supports all content entities (node, term, user, ...).
 *
 * Example:
 *
 * @code
 *
 * process:
 *  tid:
 *    plugin: translation_legacy_id
 *    entity: taxonomy_term
 *    bundle: type_pub
 *    mapping_field: legacy_id
 *    bundle_key: vid
 *    source: id
 *
 * @endcode
 *
 * @MigrateProcessPlugin(
 *   id = "translation_legacy_id"
 * )
 */
class TranslationLegacyId extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {

    $id = $value;
    $entity = $this->configuration['entity'];
    $bundle = $this->configuration['bundle'];
    $mapping_field = $this->configuration['mapping_field'];
    $bundle_key = $this->configuration['bundle_key'];


    if (isset($id) && isset($entity) && isset($bundle) && isset($mapping_field) && isset($bundle_key)) {
      $query = \Drupal::entityTypeManager()->getStorage($entity)->getQuery();
      $query->condition($bundle_key, $bundle);
      $query->condition($mapping_field, $id);
      $query->accessCheck(FALSE);

      $ids = $query->execute();

      if (count($ids) == 1) {
        $result = reset($ids);
        return $result;
      }
    }

    throw new MigrateSkipRowException();
  }

}
