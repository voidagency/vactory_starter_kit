<?php

namespace Drupal\Tests\vactory_core\ExistingSite;

use Drupal\Component\Serialization\Yaml;
use Drupal\consumers\Entity\Consumer;
use Drupal\jquery_colorpicker\Plugin\Field\FieldWidget\JQueryColorpickerWidget;
use Drupal\lang_dropdown\Form\LanguageDropdownForm;
use Drupal\theme_switcher\Form\ThemeSwitcherRuleForm;
use Drupal\theme_switcher\Theme\ThemeSwitcherNegotiator;
use Drupal\vactory_decoupled_theme_switcher\Form\ThemeSwitcherRuleForm as VactoryThemeSwitcherRuleForm;
use weitzman\DrupalTestTraits\ExistingSiteBase;

/**
 * Contracts between the profile and its contrib dependencies.
 *
 * Run on a site built from the profile, and on a candidate version of a
 * dependency before upgrading it: a renamed plugin, service or class breaks
 * our configuration or code silently.
 *
 * @group vactory_core
 * @group dependencies
 */
class ProfileDependencyContractsTest extends ExistingSiteBase {

  /**
   * Known broken components, with their ticket.
   */
  const KNOWN_ISSUES = [
    // #71163: widget that no longer exists.
    'node.vactory_business.default:vcc',
  ];

  /**
   * Returns the shipped config of the profile and of its enabled modules.
   *
   * Keyed by "<module>/<install|optional>/<file>".
   */
  protected function shippedConfig(string $pattern): array {
    $profile = \Drupal::installProfile();
    $profile_path = \Drupal::service('extension.list.profile')->getPath($profile);
    $directories = [$profile => $profile_path];
    $module_list = \Drupal::service('extension.list.module');
    foreach (array_keys(\Drupal::moduleHandler()->getModuleList()) as $module) {
      $path = $module_list->getPath($module);
      if (str_starts_with($path, $profile_path . '/')) {
        $directories[$module] = $path;
      }
    }
    $configs = [];
    foreach ($directories as $module => $path) {
      foreach (['install', 'optional'] as $type) {
        foreach (glob(DRUPAL_ROOT . "/$path/config/$type/$pattern") ?: [] as $file) {
          $configs["$module/$type/" . basename($file)] = Yaml::decode(file_get_contents($file)) ?? [];
        }
      }
    }
    return $configs;
  }

  /**
   * Returns the module dependencies of a config that are not enabled.
   */
  protected function missingModules(array $config): array {
    return array_values(array_filter(
      $config['dependencies']['module'] ?? [],
      fn ($module) => !\Drupal::moduleHandler()->moduleExists($module)
    ));
  }

  /**
   * Plugins referenced by the profile's shipped config exist.
   *
   * Field types and their target entity types, widgets, formatters,
   * field_group formats, block plugins, Views display/style/row plugins.
   * Optional config whose dependencies are missing is not installed and is
   * skipped; install config with missing dependencies is reported.
   */
  public function testShippedConfigPluginsExist(): void {
    $field_types = \Drupal::service('plugin.manager.field.field_type');
    $entity_type_manager = \Drupal::entityTypeManager();
    $problems = [];
    $checked = 0;
    $check = function (bool $exists, string $what) use (&$problems, &$checked) {
      $checked++;
      if (!$exists) {
        $problems[] = $what;
      }
    };
    $configs = [];
    $patterns = [
      'field.storage.*.yml',
      'core.entity_form_display.*.yml',
      'core.entity_view_display.*.yml',
      'block.block.*.yml',
      'views.view.*.yml',
    ];
    foreach ($patterns as $pattern) {
      foreach ($this->shippedConfig($pattern) as $file => $config) {
        $missing = $this->missingModules($config);
        if ($missing) {
          if (str_contains($file, '/install/')) {
            $problems[] = "$file: install config depends on disabled module(s) " . implode(', ', $missing);
          }
          continue;
        }
        $configs[$file] = $config;
      }
    }

    foreach ($configs as $file => $config) {
      $name = basename($file, '.yml');
      if (str_starts_with($name, 'field.storage.')) {
        $check($field_types->hasDefinition($config['type']), "$file: field type {$config['type']}");
        if (!empty($config['settings']['target_type'])) {
          $check($entity_type_manager->hasDefinition($config['settings']['target_type']), "$file: target type {$config['settings']['target_type']}");
        }
      }
      elseif (preg_match('/^core\.entity_(form|view)_display\.(.+)$/', $name, $m)) {
        $manager = \Drupal::service($m[1] === 'form' ? 'plugin.manager.field.widget' : 'plugin.manager.field.formatter');
        foreach ($config['content'] ?? [] as $field => $component) {
          if (!empty($component['type']) && !in_array("{$m[2]}:$field", self::KNOWN_ISSUES, TRUE)) {
            $check($manager->hasDefinition($component['type']), "$file: $field uses {$component['type']}");
          }
        }
        if (\Drupal::hasService('plugin.manager.field_group.formatters')) {
          foreach ($config['third_party_settings']['field_group'] ?? [] as $group => $settings) {
            $check(\Drupal::service('plugin.manager.field_group.formatters')->hasDefinition($settings['format_type']), "$file: field group $group uses {$settings['format_type']}");
          }
        }
      }
      elseif (str_starts_with($name, 'block.block.')) {
        $check(\Drupal::service('plugin.manager.block')->hasDefinition($config['plugin']), "$file: block plugin {$config['plugin']}");
      }
      elseif (str_starts_with($name, 'views.view.')) {
        foreach ($config['display'] ?? [] as $display_id => $display) {
          $check(\Drupal::service('plugin.manager.views.display')->hasDefinition($display['display_plugin']), "$file: display $display_id plugin {$display['display_plugin']}");
          foreach (['style', 'row'] as $type) {
            if (!empty($display['display_options'][$type]['type'])) {
              $plugin = $display['display_options'][$type]['type'];
              $check(\Drupal::service("plugin.manager.views.$type")->hasDefinition($plugin), "$file: display $display_id $type $plugin");
            }
          }
        }
      }
    }
    $this->assertGreaterThan(100, $checked, 'Shipped config was scanned.');
    $this->assertSame([], $problems);
  }

  /**
   * Skips the test when a module is not enabled on the site.
   */
  protected function requireModules(string ...$modules): void {
    foreach ($modules as $module) {
      if (!\Drupal::moduleHandler()->moduleExists($module)) {
        $this->markTestSkipped("$module is not enabled.");
      }
    }
  }

  /**
   * The jquery_colorpicker fork: widget altered by vactory_core, DF element.
   */
  public function testJqueryColorpicker(): void {
    $this->requireModules('jquery_colorpicker');
    $this->assertTrue(class_exists(JQueryColorpickerWidget::class));
    $this->assertTrue(\Drupal::service('element_info')->hasDefinition('jquery_colorpicker'));
  }

  /**
   * Consumers + simple_oauth: OneTimeToken reads a client's Drupal entity.
   */
  public function testConsumers(): void {
    $this->requireModules('consumers', 'simple_oauth');
    $consumer = Consumer::create([
      'label' => 'Contract test',
      'client_id' => 'contract_' . $this->randomMachineName(8),
    ]);
    $consumer->save();
    $this->markEntityForCleanup($consumer);
    $client = \Drupal::service('simple_oauth.repositories.client')->getClientEntity($consumer->getClientId());
    $this->assertNotNull($client);
    $this->assertSame($consumer->id(), $client->getDrupalEntity()->id());
  }

  /**
   * Lang_dropdown: block used by the headers, form altered by vactory_core.
   */
  public function testLangDropdown(): void {
    $this->requireModules('lang_dropdown');
    $this->assertTrue(\Drupal::service('plugin.manager.block')->hasDefinition('language_dropdown_block:language_interface'));
    $this->assertTrue(class_exists(LanguageDropdownForm::class));
    $form = (new \ReflectionClass(LanguageDropdownForm::class))->newInstanceWithoutConstructor();
    $this->assertStringStartsWith('lang_dropdown_form', $form->getFormId());
  }

  /**
   * Csv_serialization / views_data_export: CSV export of appointments.
   */
  public function testCsvExport(): void {
    $this->requireModules('csv_serialization');
    $serializer = \Drupal::service('serializer');
    $this->assertTrue($serializer->supportsEncoding('csv'));
    $rows = [['name' => 'Jane', 'city' => 'Rabat']];
    $this->assertStringContainsString('Jane', $serializer->encode($rows, 'csv'));
  }

  /**
   * Coffee: admin quick navigation data route.
   */
  public function testCoffee(): void {
    $this->requireModules('coffee');
    $this->assertNotEmpty(\Drupal::service('router.route_provider')->getRoutesByNames(['coffee.get_data']));
  }

  /**
   * Theme_switcher: our rule form and our redefinition of its negotiator.
   */
  public function testThemeSwitcher(): void {
    $this->requireModules('theme_switcher', 'vactory_decoupled_theme_switcher');
    $this->assertTrue(is_a(VactoryThemeSwitcherRuleForm::class, ThemeSwitcherRuleForm::class, TRUE));
    $form_class = \Drupal::entityTypeManager()->getDefinition('theme_switcher_rule')->getFormClass('add');
    $this->assertSame(VactoryThemeSwitcherRuleForm::class, $form_class);
    // We redefine this service with the contrib class and arguments:
    // instantiating it validates both.
    $this->assertInstanceOf(ThemeSwitcherNegotiator::class, \Drupal::service('theme.negotiator.theme_switcher_negotiator'));
  }

}
