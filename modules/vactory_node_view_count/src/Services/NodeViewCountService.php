<?php

namespace Drupal\vactory_node_view_count\Services;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Class NodeViewCountService
 * @package Drupal\vactory_node_view_count\Services
 */
class NodeViewCountService {

    /**
     * @param $content_type
     * @param $field_name
     * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
     * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
     * @throws \Drupal\Core\Entity\EntityStorageException
     */
    public function createNodeViewCountField($content_type, $field_name) {
        $field = FieldConfig::loadByName('node', $content_type, $field_name);
        if (empty($field)) {
            $field_storage = FieldStorageConfig::loadByName('node', $field_name);
            if (empty($field_storage)) {
                $field_storage = FieldStorageConfig::create([
                    'field_name' => $field_name,
                    'entity_type' => 'node',
                    'type' => 'integer',
                    'cardinality' => 1,
                ]);
                $field_storage->save();
            }
            $field = FieldConfig::create([
                'field_storage' => $field_storage,
                'bundle' => $content_type,
                'label' => t('Node view count'),
                'default_value' => [
                    'value' => 0,
                ],
            ]);
            $field->save();
            /* @var \Drupal\Core\Entity\Entity\EntityFormDisplay */
            $entity_form_display = \Drupal::entityTypeManager()
                ->getStorage('entity_form_display')
                ->load('node.' . $content_type . '.default');
            if (!$entity_form_display) {
                $values = [
                    'targetEntityType' => 'node',
                    'bundle' => $content_type,
                    'mode' => 'default',
                    'status' => TRUE,
                ];
                \Drupal::entityTypeManager()
                    ->getStorage('entity_form_display')
                    ->create($values);
            }
            $entity_form_display->setComponent($field_name, [
                'type' => 'text_textfield',
                'region' => 'hidden',
            ])->save();
            /* @var \Drupal\Core\Entity\Entity\EntityViewDisplay */
            $entity_view_display = \Drupal::entityTypeManager()
                ->getStorage('entity_view_display')
                ->load('node.' . $content_type . '.default');
            if (!$entity_view_display) {
                $values = [
                    'targetEntityType' => 'node',
                    'bundle' => $content_type,
                    'mode' => 'default',
                    'status' => TRUE,
                ];
                \Drupal::entityTypeManager()
                    ->getStorage('entity_view_display')
                    ->create($values);
            }
            $entity_view_display->setComponent($field_name, [
                'label' => 'hidden',
            ])->save();
        }
    }

    /**
     * @param $content_type
     * @param $field_name
     */
    public function removeNodeViewCountField($content_type, $field_name) {
        $field = FieldConfig::loadByName('node', $content_type, $field_name);
        if (!empty($field)) {
            $field->delete();
        }
    }

}