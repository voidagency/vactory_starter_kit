<?php

namespace Drupal\Tests\vactory_core\ExistingSite;

use Drupal\Component\Serialization\Yaml;
use Drupal\consumers\Entity\Consumer;
use Drupal\jquery_colorpicker\Plugin\Field\FieldWidget\JQueryColorpickerWidget;
use Drupal\theme_switcher\Form\ThemeSwitcherRuleForm;
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
   * Returns the profile's shipped config files matching a pattern.
   */
  protected function shippedConfig(string $pattern): array {
    $root = dirname(__DIR__, 5);
    $dirs = '{config,modules/*/config,modules/*/modules/*/config,modules/*/modules/*/modules/*/config}';
    $files = glob($root . '/' . $dirs . '/{install,optional}/' . $pattern, GLOB_BRACE) ?: [];
    $configs = [];
    foreach ($files as $file) {
      $configs[str_replace($root . '/', '', $file)] = Yaml::decode(file_get_contents($file)) ?? [];
    }
    return $configs;
  }

  /**
   * Returns TRUE when the config would be installed on this site.
   *
   * The module shipping the config and every module it depends on must be
   * enabled.
   */
  protected function dependenciesMet(array $config, string $file): bool {
    $module_handler = \Drupal::moduleHandler();
    if (preg_match('#(?:^|/)([a-z0-9_]+)/config/(?:install|optional)/#', $file, $matches)
      && $matches[1] !== 'modules' && !$module_handler->moduleExists($matches[1])) {
      return FALSE;
    }
    foreach ($config['dependencies']['module'] ?? [] as $module) {
      if (!$module_handler->moduleExists($module)) {
        return FALSE;
      }
    }
    return TRUE;
  }

  /**
   * Widgets, formatters and field types used by shipped config exist.
   */
  public function testShippedConfigPluginsExist(): void {
    $field_types = \Drupal::service('plugin.manager.field.field_type');
    $missing = [];
    $checked = 0;

    foreach ($this->shippedConfig('field.storage.*.yml') as $file => $config) {
      if ($this->dependenciesMet($config, $file) && !empty($config['type'])) {
        $checked++;
        if (!$field_types->hasDefinition($config['type'])) {
          $missing[] = "$file: field type {$config['type']}";
        }
      }
    }
    $displays = [
      'core.entity_form_display.*.yml' => \Drupal::service('plugin.manager.field.widget'),
      'core.entity_view_display.*.yml' => \Drupal::service('plugin.manager.field.formatter'),
    ];
    foreach ($displays as $pattern => $manager) {
      foreach ($this->shippedConfig($pattern) as $file => $config) {
        if (!$this->dependenciesMet($config, $file)) {
          continue;
        }
        foreach ($config['content'] ?? [] as $field => $component) {
          if (!empty($component['type'])) {
            $checked++;
            if (!$manager->hasDefinition($component['type'])) {
              $missing[] = "$file: $field uses {$component['type']}";
            }
          }
        }
      }
    }
    $this->assertGreaterThan(100, $checked, 'Shipped config was scanned.');
    $this->assertSame([], $missing);
  }

  /**
   * Code-level contracts with specific dependencies.
   */
  public function testCodeContracts(): void {
    $container = \Drupal::getContainer();

    // The jquery_colorpicker voidagency fork: vactory_core alters its widget,
    // Dynamic Fields use its form element.
    $this->assertTrue(class_exists(JQueryColorpickerWidget::class));
    $this->assertTrue($container->get('element_info')->hasDefinition('jquery_colorpicker'));

    // Consumers + simple_oauth: OneTimeToken reads the Drupal entity of an
    // OAuth client.
    $consumer = Consumer::create([
      'label' => 'Contract test',
      'client_id' => 'contract_' . strtolower($this->randomMachineName(8)),
    ]);
    $consumer->save();
    $this->markEntityForCleanup($consumer);
    $client = $container->get('simple_oauth.repositories.client')->getClientEntity($consumer->getClientId());
    $this->assertSame($consumer->id(), $client->getDrupalEntity()->id());

    // Lang_dropdown: vactory_core alters "lang_dropdown_form".
    $this->assertTrue($container->get('plugin.manager.block')->hasDefinition('language_dropdown_block:language_interface'));

    // Csv_serialization: CSV export of vactory_appointment's view.
    $this->assertTrue($container->get('serializer')->supportsEncoding('csv'));
    $rows = [['name' => 'Jane', 'city' => 'Rabat']];
    $csv = $container->get('serializer')->encode($rows, 'csv');
    $this->assertStringContainsString('Jane', $csv);

    // Coffee: admin quick navigation data route.
    $this->assertNotEmpty($container->get('router.route_provider')->getRoutesByNames(['coffee.get_data']));

    // Theme_switcher: our rule form extends theirs, our module uses their
    // negotiator service.
    $this->assertTrue(is_a(VactoryThemeSwitcherRuleForm::class, ThemeSwitcherRuleForm::class, TRUE));
    $form_class = \Drupal::entityTypeManager()->getDefinition('theme_switcher_rule')->getFormClass('add');
    $this->assertSame(VactoryThemeSwitcherRuleForm::class, $form_class);
    $this->assertTrue($container->has('theme.negotiator.theme_switcher_negotiator'));
  }

}
