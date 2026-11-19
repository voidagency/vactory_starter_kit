<?php

namespace Drupal\vactory_webform_typesafe\Form;

use Drupal\Core\Form\FormBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Shared injected dependencies for administrative forms. */
abstract class AnalysisFormBase extends FormBase {

  /**
   * The analysis coordinator.
   *
   * @var \Drupal\vactory_webform_typesafe\Service\AnalysisManager
   */
  protected $manager;
  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entities;
  /**
   * The shared lock backend.
   *
   * @var \Drupal\Core\Lock\LockBackendInterface
   */
  protected $lock;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = new static();
    $instance->manager = $container->get('vactory_webform_typesafe.manager');
    $instance->entities = $container->get('entity_type.manager');
    $instance->lock = $container->get('lock');
    return $instance;
  }

}
