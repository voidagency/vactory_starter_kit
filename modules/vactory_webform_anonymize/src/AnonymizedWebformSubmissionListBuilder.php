<?php

namespace Drupal\vactory_webform_anonymize;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\webform\WebformSubmissionListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Extended WebformSubmissionListBuilder with anonymization.
 */
class AnonymizedWebformSubmissionListBuilder extends WebformSubmissionListBuilder {

  /**
   * The Vactory Webform Anonymize Helper.
   *
   * @var \Drupal\vactory_webform_anonymize\Services\AnonymizedWebformSubmissionService
   */
  public $vactoryWebformAnonymizeHelper;

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    $instance = parent::createInstance($container, $entity_type);
    $instance->vactoryWebformAnonymizeHelper = $container->get('vactory_webform_anonymize.helper');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row = parent::buildRow($entity);
    if ($this->vactoryWebformAnonymizeHelper->shouldAnonymize($this->webform)) {
      $row = $this->anonymizeRowData($row);
    }
    return $row;
  }

  /**
   * Anonymize data in a row.
   */
  protected function anonymizeRowData($row) {
    $settings = $this->webform->getThirdPartySetting('vactory_webform_anonymize', 'settings', []);
    foreach ($row['data'] as $key => &$value) {
      if (in_array($key, [
        'operations',
        'sid',
        'uuid',
        'in_draft',
        'sticky',
        'locked',
        'notes',
        'serial',
      ])) {
        continue;
      }
      $value = $this->vactoryWebformAnonymizeHelper->anonymizeRecursive($value, $settings);
    }
    return $row;
  }

}
