<?php

namespace Drupal\vactory_checklist\Plugin\Checklist;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Vérifie si le logo par défaut a été modifié.
 *
 * @ChecklistPlugin(
 *   id = "vactory_logo_path_check",
 *   label = @Translation("Vérification du logo"),
 *   description = @Translation("Vérifie que le logo par défaut du thème Vactory a été changé"),
 *   category = "theme"
 * )
 */
class VactoryLogoPathCheck extends ChecklistBase implements ContainerFactoryPluginInterface {

  use StringTranslationTrait;

  /**
   * Le service de configuration.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructeur.
   */
  public function __construct(
    array $configuration,
          $plugin_id,
          $plugin_definition,
    ConfigFactoryInterface $config_factory
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function runCheck() {
    $theme_config = $this->configFactory->get('vactory.settings');

    $logo_path = $theme_config->get('logo.path');

    $status = !empty($logo_path) && strpos($logo_path, 'themes/vactory/') !== 0;

    $message = $status
      ? $this->t("Le logo a été personnalisé")
      : $this->t("Le logo par défaut du thème Vactory n'a pas été modifié");

    return [
      'status' => $status,
      'message' => $message,
      'details' => [],
    ];
  }

}
