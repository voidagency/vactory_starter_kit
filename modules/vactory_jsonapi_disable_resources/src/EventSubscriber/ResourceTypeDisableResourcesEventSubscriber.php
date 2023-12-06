<?php

namespace Drupal\vactory_jsonapi_disable_resources\EventSubscriber;

use Drupal\jsonapi\ResourceType\ResourceTypeBuildEvents;
use Drupal\jsonapi\ResourceType\ResourceTypeBuildEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class ResourceTypeDisableResourcesEventSubscriber.
 *
 * Eventsubscriber which disables unwanted JSONAPI resources, fields.
 *
 * @package Drupal\vactory_jsonapi_disable_resources\EventSubscriber
 */
class ResourceTypeDisableResourcesEventSubscriber implements EventSubscriberInterface {

    /**
     * Global disabled fiedls.
     */
    protected $global_disabled_fields = [
        'node' => [
            'vid',
            'promote',
            'sticky',
            'revision_timestamp',
            'revision_log',
            'default_langcode',
            'revision_translation_affected',
            'form_path_alias',
            'node_content_package_exclude',
            'machine_name',
            'mail_subject',
            'mail_message',
            'generate_notification',
            'content_translation_source',
            'content_translation_outdated',
            'field_exclude_from_search'
        ],
    ];

    /**
     * Disabled resources.
     */
    protected $disabled_resources = [];

    /**
     * Disabled fields per resource.
     */
    protected $disabled_fields_per_resource = [];

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      ResourceTypeBuildEvents::BUILD => [
        ['disableResourceType'],
      ],
    ];
  }

  /**
   * Disables unwanted resource types.
   *
   * @param \Drupal\jsonapi\ResourceType\ResourceTypeBuildEvent $event
   *   The build event.
   */
  public function disableResourceType(ResourceTypeBuildEvent $event) {
    \Drupal::moduleHandler()
        ->alter('jsonapi_disable_resources', $this->global_disabled_fields, $this->disabled_resources, $this->disabled_fields_per_resource);
    $resource_type_name = $event->getResourceTypeName();

    // Disabled resources.
    if (in_array($resource_type_name, $this->disabled_resources)) {
        $event->disableResourceType();
    }

    // Disabled fields per resource.
    foreach ($this->disabled_fields_per_resource as $resource => $fields) {
        if ($resource === $resource_type_name) {
            $this->disableFields($event, $fields);
        }
    }
    // Global disabled fields.
    foreach ($this->global_disabled_fields as $entity => $fields) {
        if (strpos($resource_type_name, $entity) !== FALSE) {
            $this->disableFields($event, $fields);
        }
    }
    
  }

  /**
   * Disable fields.
   */
  private function disableFields($event, $fields) {
    foreach ($event->getFields() as $field_name => $field) {
        if (in_array($field_name, $fields)) {
            $event->disableField($field);
        }
    }
  }

}
