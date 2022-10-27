<?php

namespace Drupal\vactory_user_migrate\Plugin\migrate\process;
use Drupal\Core\Password\PhpassHashedPassword;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\MigrateSkipRowException;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Hash user password migrate process plugin.
 *
 * @MigrateProcessPlugin(
 *   id = "hash_password"
 * )
 */

class HashPassword extends ProcessPluginBase implements ContainerFactoryPluginInterface {

  /**
   * Password manager service.
   *
   * @var \Drupal\Core\Password\PhpassHashedPassword
   */
  protected $password_manager;

  /**
   * {@inheritDoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    PhpassHashedPassword $password_manager
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->password_manager = $password_manager;
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('password')
    );
  }

  /**
   * {@inheritdoc}
   * @throws MigrateSkipRowException
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (empty($value)) {
      throw new MigrateSkipRowException('User (' . $row->get('Email') . '): Missing password property value');
    }
    return $this->password_manager->hash($value);
  }

}
