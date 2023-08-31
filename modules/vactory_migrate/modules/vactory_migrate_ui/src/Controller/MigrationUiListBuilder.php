<?php

namespace Drupal\vactory_migrate_ui\Controller;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;
use Drupal\migrate_tools\Controller\MigrationListBuilder;

/**
 * Provides a listing of migration entities in a given group.
 *
 * @package Drupal\vactory_migrate_ui\Controller
 *
 * @ingroup vactory_migrate_ui
 */
class MigrationUiListBuilder extends MigrationListBuilder {

  /**
   * Build header function.
   */
  public function buildHeader(): array {
    $header['modify'] = $this->t('Modify');
    return parent::buildHeader() + $header;
  }

  /**
   * Build row function.
   */
  public function buildRow(EntityInterface $migration_entity): array {
    $migration = $this->migrationPluginManager->createInstance($migration_entity->id());
    $migration_group = $migration_entity->get('migration_group');
    if (!$migration_group) {
      $migration_group = 'default';
    }
    try {
      $row['modify']['data'] = [
        '#type' => 'dropbutton',
        '#links' => [
          'simple_form' => [
            'title' => $this->t('modify'),
            'url' => Url::fromRoute('vactory_migrate_ui.modify', [
              'migration_group' => $migration_group,
              'migration' => $migration->id(),
            ]),
          ],
        ],
      ];
    }
    catch (\Exception $e) {
      $row['modify'] = $this->t('N/A');
    }
    return parent::buildRow($migration_entity) + $row;
  }

}
