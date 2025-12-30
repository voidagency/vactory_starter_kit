<?php

namespace Drupal\vactory_checklist\Plugin\Checklist;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Site\Settings;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Vérifie si des modules de développement sont activés.
 *
 * @ChecklistPlugin(
 *   id = "dev_modules_check",
 *   label = @Translation("Modules de développement"),
 *   description = @Translation("Vérifie si des modules de développement sont activés"),
 *   category = "modules"
 * )
 */
class DevModulesCheck extends ChecklistBase implements ContainerFactoryPluginInterface {

  /**
   * Le gestionnaire de modules.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructeur.
   */
  public function __construct(
    array $configuration,
          $plugin_id,
          $plugin_definition,
    ModuleHandlerInterface $module_handler
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function runCheck() {

    $enabled_dev_modules = [];

    $dev_modules = Settings::get('config_exclude_modules', []);

    foreach ($dev_modules as $module) {
      if ($this->moduleHandler->moduleExists($module)) {
        $enabled_dev_modules[] = $module;
      }
    }
    $status = empty($enabled_dev_modules);
    $message = $status
      ? $this->t('Les modules de DEV ne sont pas installés')
      : $this->t('@count module de DEV est installé', ['@count' => count($enabled_dev_modules)]);

    $details = [];
    foreach ($enabled_dev_modules as $module) {
      $details[] = [
        'module' => $module,
      ];
    }

    return [
      'status' => $status,
      'message' => $message,
      'details' => $details,
    ];
  }

}
