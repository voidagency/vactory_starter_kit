<?php

namespace Drupal\vactory_checklist\Plugin\Checklist;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\vactory_dynamic_field\WidgetsManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Vérifie l'existence et le contenu des templates Twig pour les DF.
 *
 * @ChecklistPlugin(
 *   id = "widget_twig_template_check",
 *   label = @Translation("Vérification des templates Twig des DF"),
 *   description = @Translation("Vérifie que tous les widgets ont leur template Twig existant et non vide"),
 *   category = "dynamic_fields"
 * )
 */
class WidgetTwigTemplateCheck extends ChecklistBase implements ContainerFactoryPluginInterface {

  use StringTranslationTrait;

  /**
   * Constantes définissant les chemins et noms de fichiers.
   */
  private const TEMPLATE_FILE_NAME = "template.html.twig";

  /**
   * Le gestionnaire de widgets.
   *
   * @var \Drupal\vactory_dynamic_field\WidgetsManager
   */
  protected $widgetsManager;

  /**
   * Constructeur.
   */
  public function __construct(
    array $configuration,
          $plugin_id,
          $plugin_definition,
    WidgetsManager $widgets_manager
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->widgetsManager = $widgets_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('vactory_dynamic_field.vactory_provider_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function runCheck() {
    $widgets_without_template = [];
    $empty_template_widgets = [];

    $widget_list = $this->widgetsManager->getWidgetsList();

    foreach ($widget_list as $widgets) {
      foreach ($widgets as $key => $widget) {
        $path = $this->widgetsManager->getWidgetsPath($key);

        $widget_name = $this->getWidgetName($key);

        if ($widget_name) {
          $template_path = $path . DIRECTORY_SEPARATOR . $widget_name . DIRECTORY_SEPARATOR . self::TEMPLATE_FILE_NAME;

          if (!file_exists($template_path)) {
            $widgets_without_template[] = $key;
          }

          elseif (filesize($template_path) === 0) {
            $empty_template_widgets[] = $key;
          }
        }
      }
    }

    $status = empty($widgets_without_template) && empty($empty_template_widgets);

    $details = [];
    foreach ($widgets_without_template as $widget) {
      $details[] = [
        'issue' => $this->t('Template manquant'),
        'widget' => $widget,
      ];
    }
    foreach ($empty_template_widgets as $widget) {
      $details[] = [
        'issue' => $this->t('Template vide'),
        'widget' => $widget,
      ];
    }

    $message = $status
      ? $this->t('Tous les widgets ont leurs templates Twig')
      : $this->t('Certains widgets ont des problèmes avec leurs templates');

    return [
      'status' => $status,
      'message' => $message,
      'details' => $details,
    ];
  }

  /**
   * Extrait le nom du widget à partir de sa clé.
   *
   * @param string $key
   *   La clé du widget.
   *
   * @return string|null
   *   Le nom du widget ou null si impossible à extraire.
   */
  private function getWidgetName(string $key): ?string {
    $parts = explode(":", $key);
    return $parts[1] ?? NULL;
  }

}
