<?php

namespace Drupal\vactory_dynamic_field\Controller;

use Drupal\vactory_dynamic_field\WidgetsManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

// phpcs:disable
if (!function_exists('array_key_first')) {
  function array_key_first(array $arr) {
    foreach ($arr as $key => $unused) {
      return $key;
    }
    return NULL;
  }
}
// phpcs:enable

/**
 * StaticWidgetsListController class.
 */
class StaticWidgetsListController extends ControllerBase {

  /**
   * The plugin manager.
   *
   * @var \Drupal\vactory_dynamic_field\WidgetsManager
   */
  protected $widgetsManager;

  /**
   * Constructs a new StaticWidgetsListController.
   *
   * @param \Drupal\vactory_dynamic_field\WidgetsManagerInterface $widgets_manager
   *   Widget manager.
   */
  public function __construct(WidgetsManagerInterface $widgets_manager) {
    $this->widgetsManager = $widgets_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('vactory_dynamic_field.vactory_provider_manager'));
  }

  /**
   * Widgets List.
   */
  public function widgetsList($cid = '') {
    $widgets_list = $this->widgetsManager->getModalWidgetsList();
    $layout = \Drupal::request()->query->get('layout', 'default');

    if (!empty($cid) && !isset($widgets_list[$cid])) {
      throw new NotFoundHttpException();
    }

    if (!empty($cid) && !isset($widgets_list[$cid])) {
      throw new NotFoundHttpException();
    }

    $widgets_list_filtered = $widgets_list;

    // If empty - Pick first.
    if (empty($cid)) {
      $cid = array_key_first($widgets_list);
    }

    // Filter by category.
    $widgets_list_filtered = [$cid => $widgets_list_filtered[$cid]];

    $hook_theme = ($layout === 'default') ? 'vactory_dynamic_demo_main' : 'vactory_dynamic_demo_preview_main';
    return [
      '#theme'   => $hook_theme,
      '#content' => [
        'templates'          => $widgets_list,
        'templates_filtered' => $widgets_list_filtered,
        'current_cid'        => $cid,
      ],
    ];
  }

  /**
   * Widgets View.
   */
  public function widgetView($wid = '') {
    $widget = $this->widgetsManager->loadWidgetById($wid);

    return [
      '#theme'   => 'vactory_dynamic_demo_template_detail',
      '#content' => [
        'template'          => $widget,
        'image_placeholder' => VACTORY_DYNAMIC_FIELD_V_IMAGE_PLACEHOLDER,
      ],
    ];
  }

}
