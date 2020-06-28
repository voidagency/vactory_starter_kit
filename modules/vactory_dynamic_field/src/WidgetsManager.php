<?php

namespace Drupal\vactory_dynamic_field;

use Drupal\Component\Plugin\Mapper\MapperInterface;
use Drupal\Component\Serialization\Exception\InvalidDataTypeException;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Component\Serialization\Yaml;
use Symfony\Component\Finder\Finder;

/**
 * Gathers the provider plugins.
 */
class WidgetsManager extends DefaultPluginManager implements WidgetsManagerInterface, MapperInterface {

  /**
   * {@inheritdoc}
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/vactory_dynamic_field/Platform', $namespaces, $module_handler,
      'Drupal\vactory_dynamic_field\VactoryDynamicFieldPluginInterface',
      'Drupal\vactory_dynamic_field\Annotation\PlatformProvider'
    );
    $this->alterInfo('vactory_dynamic_field_info');
  }

  /**
   * {@inheritdoc}
   */
  protected function getPluginsList() {
    $options = [];
    foreach ($this->getDefinitions() as $id => $definition) {
      $plugin = $this->createInstance($id);
      $options[$definition['id']] = $plugin;
    }
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function loadWidgetById($uuid) {
    $widget = $this->loadSettings($uuid);

    return $widget;
  }

  /**
   * {@inheritdoc}
   */
  public function getWidgetsList() {
    // @todo: refactor with getModalWidgetsList().
    // @todo: cache.
    $widgets = [];
    $plugins = $this->getPluginsList();

    foreach ($plugins as $platform_id => $platform) {
      $widgets[$platform_id] = [];
    }

    foreach ($plugins as $platform_id => $platform) {
      $widget_path = $platform->getWidgetsPath();
      $finder = new Finder();

      // Find settings.yml files.
      $finder->depth('== 1')
        ->files()
        ->name('settings.yml')
        ->in($widget_path);

      // Load settings.yml files.
      foreach ($finder as $file) {
        $widget_id = $file->getRelativePath();
        $settings_file_path = $file->getRealPath();
        $contents = $file->getContents();
        $screenshot_path = $file->getPath() . '/screenshot.png';
        $screenshot_path_fallback = $file->getPath() . '/screenshoot.png';
        $static_widget_path = $file->getPath() . '/static.html.twig';

        // Decode YAML file.
        try {
          $data = Yaml::decode($contents) ?: [];
        }
        catch (InvalidDataTypeException $e) {
          throw new \Exception("The $settings_file_path contains invalid YAML", 0, $e);
        }

        // Add screenshoot.
        $data['screenshot'] = FALSE;
        if (file_exists($screenshot_path)) {
          $data['screenshot'] = file_create_url($screenshot_path);
        }
        elseif (file_exists($screenshot_path_fallback)) {
          $data['screenshot'] = file_create_url($screenshot_path_fallback);
        }

        // Add static widget - demo content.
        $data['static_widget'] = FALSE;
        if (file_exists($static_widget_path)) {
          $data['static_widget'] = $static_widget_path;
        }

        // Keep only enabled widgets.
        if (isset($data['enabled']) && $data['enabled'] === TRUE) {
          $widgets[$platform_id][$data['uuid']] = $data;
        }
      }
    }

    return $widgets;
  }

  /**
   * {@inheritdoc}
   */
  public function getModalWidgetsList($allowedProviders = []) {
    // @todo: cache.
    $widgets = [];
    $plugins = $this->getPluginsList();

    foreach ($plugins as $platform_id => $platform) {

      // Add only widgets from allowed providers.
      if (!empty($allowedProviders) && !array_key_exists($platform_id, $allowedProviders)) {
        continue;
      }

      $widget_path = $platform->getWidgetsPath();
      $finder = new Finder();

      // Find settings.yml files.
      $finder->depth('== 1')
        ->files()
        ->name('settings.yml')
        ->in($widget_path);

      // Load settings.yml files.
      foreach ($finder as $file) {
        $widget_id = $file->getRelativePath();
        $settings_file_path = $file->getRealPath();
        $contents = $file->getContents();
        $screenshot_path = $file->getPath() . '/screenshot.png';
        $screenshot_path_fallback = $file->getPath() . '/screenshoot.png';
        $static_widget_path = $file->getPath() . '/static.html.twig';

        // Decode YAML file.
        try {
          $data = Yaml::decode($contents) ?: [];
        }
        catch (InvalidDataTypeException $e) {
          throw new \Exception("The $settings_file_path contains invalid YAML", 0, $e);
        }

        // Add screenshoot.
        $data['screenshot'] = FALSE;
        if (file_exists($screenshot_path)) {
          $data['screenshot'] = file_create_url($screenshot_path);
        }
        elseif (file_exists($screenshot_path_fallback)) {
          $data['screenshot'] = file_create_url($screenshot_path_fallback);
        }

        // Add static widget - demo content.
        $data['static_widget'] = FALSE;
        if (file_exists($static_widget_path)) {
          $data['static_widget'] = $static_widget_path;
        }

        // Add widget id.
        $data['uuid'] = $platform_id . ':' . $widget_id;

        // Keep only enabled widgets.
        if (isset($data['enabled']) && $data['enabled'] === TRUE) {
          $widgets[$data['category']][$data['uuid']] = $data;
        }
      }
    }

    $widgets_alter = $widgets;
    unset($widgets_alter['Froala']);
    unset($widgets_alter['Content']);
    $widgets['Content'] = isset($widgets['Content']) ? $widgets['Content'] : [];
    $widgets_alter['Content'] = isset($widgets['Froala']) && is_array($widgets['Froala']) ? array_merge($widgets['Content'], $widgets['Froala']) : $widgets['Content'];

    foreach ($widgets_alter as $catgeory => &$items) {
      $id = 1;
      foreach ($items as &$item) {
        $old_name = $item['name'];
        $new_name = preg_replace('/([0-9]+) - /', '', $old_name);

        $item['name'] = $id . ' - ' . $new_name;
        $id++;
      }
    }

    return $widgets_alter;
  }

  /**
   * {@inheritdoc}
   */
  public function loadSettings($uuid) {
    list($plugin_id, $id) = explode(':', $uuid);
    $plugin = $this->createInstance($plugin_id);

    $widget_path = $plugin->getWidgetsPath();
    $settings_path = $widget_path . '/' . $id . '/settings.yml';
    $screenshot_url = $widget_path . '/' . $id . '/screenshot.png';
    $screenshot_url_fallback = $widget_path . '/' . $id . '/screenshoot.png';
    $static_widget_path = $widget_path . '/' . $id . '/static.html.twig';

    try {
      $data = Yaml::decode(file_get_contents($settings_path)) ?: [];
    }
    catch (InvalidDataTypeException $e) {
      \Drupal::logger('vactory_dynamic_field')
        ->notice("The $settings_path contains invalid YAML: " . $e->getMessage());
      return [];
    }

    // Add screenshoot.
    $data['screenshot'] = FALSE;
    if (file_exists($screenshot_url)) {
      $data['screenshot'] = file_create_url($screenshot_url);
    }
    elseif (file_exists($screenshot_url_fallback)) {
      $data['screenshot'] = file_create_url($screenshot_url_fallback);
    }

    // Add static widget - demo content.
    $data['static_widget'] = FALSE;
    if (file_exists($static_widget_path)) {
      $data['static_widget'] = $static_widget_path;
    }

    // Add widget id.
    $data['uuid'] = $uuid;

    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function getWidgetsPath($uuid) {
    list($plugin_id, $id) = explode(':', $uuid);
    $plugin = $this->createInstance($plugin_id);

    $widget_path = $plugin->getWidgetsPath();

    return $widget_path;
  }

  /**
   * Get Plugin provider it's module name.
   *
   * @param string $plugin_id
   *   Plugin ID Provider.
   *
   * @return array|null
   *   Plugin definition.
   */
  protected function getPluginProvider($plugin_id) {
    return $this->getDefinition($plugin_id);
  }

  /**
   * Get an options list suitable for form elements for provider selection.
   *
   * @return array
   *   An array of options keyed by plugin ID with label values.
   */
  public function getProvidersOptionList() {
    $options = [];
    foreach ($this->getDefinitions() as $definition) {
      $options[$definition['id']] = $definition['title'];
    }
    return $options;
  }

}
