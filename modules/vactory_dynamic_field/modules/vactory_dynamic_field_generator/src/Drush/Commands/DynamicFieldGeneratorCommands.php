<?php

namespace Drupal\vactory_dynamic_field_generator\Drush\Commands;

use Drush\Commands\DrushCommands;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\vactory_dynamic_field\WidgetsManager;
use Drupal\vactory_dynamic_field_generator\VactoryGeneratorUtils;
use Drupal\vactory_dynamic_field_generator\Generators\GeneratorFactory;

/**
 * A Drush commandfile.
 */
final class DynamicFieldGeneratorCommands extends DrushCommands {

  /**
   * The twig template file name.
   */
  const TEMPLATE_FILE_NAME = "template.html.twig";

  /**
   * The custom module path suffix.
   */
  const CUSTOM_MODULES_PATH = "modules/custom";

  /**
   * Constructs a DynamicFieldGeneratorCommands object.
   */
  public function __construct(
    private readonly WidgetsManager $vactoryProviderManager,
    private readonly VactoryGeneratorUtils $vactoryGeneratorUtils,
    private readonly GeneratorFactory $generatorFactory,
  ) {
    parent::__construct();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('vactory_dynamic_field.vactory_provider_manager'),
      $container->get('vactory_dynamic_field_generator.utils'),
      $container->get('vactory_dynamic_field_generator.factory.generate'),
    );
  }

  /**
   * Clear expired notifications data.
   *
   * @command templates-generator
   * @aliases tg
   */
  public function templatesGeneratorCommand($options = ['option-name' => 'default']) {
    $this->output()
      ->writeln('Generating custom dynamic fields templates has started');
    $widget_list = $this->vactoryProviderManager->getWidgetsList();
    foreach ($widget_list as $widgets) {
      foreach ($widgets as $key => $widget) {
        $path = $this->vactoryProviderManager->getWidgetsPath($key);
        if (str_starts_with($path, self::CUSTOM_MODULES_PATH)) {
          $widget_name = $this->vactoryGeneratorUtils->getWidgetName($key);
          if (isset($widget_name)) {
            $path = $path . DIRECTORY_SEPARATOR . $widget_name;
            $template_file = $path . DIRECTORY_SEPARATOR . self::TEMPLATE_FILE_NAME;
            if (file_exists($template_file)) {
              if ($this->vactoryGeneratorUtils->isFileEmpty($template_file)) {
                $this->output()
                  ->writeln('Generating template for : [' . $key . '] 🚀');
                $html = $this->prepareTemplateHtmlContent($widget);
                $this->createTwigFile($template_file, $html);
              }
            }
            else {
              $this->output()
                ->writeln('Generating template for : [' . $key . '] 🚀');
              $html = $this->prepareTemplateHtmlContent($widget);
              $this->createTwigFile($template_file, $html);
            }
          }
        }
      }
    }
    $this->logger()
      ->success(dt("Congrats! 🎉 Templates have been generated successfully. Don't forget to push the templates to the repo."));
  }

  /**
   * Prepare template html content.
   */
  private function prepareTemplateHtmlContent($settings) {
    $html = '';
    $multiple = $settings['multiple'] ?? FALSE;
    $fields = $settings['fields'] ?? [];
    $extraFields = $settings['extra_fields'] ?? [];
    $html_content = $this->prepareFieldsHtml($fields, $multiple, FALSE);
    $extra_fields_html_content = $this->prepareFieldsHtml($extraFields, FALSE, TRUE);
    return $extra_fields_html_content . PHP_EOL . $html_content;
  }

  /**
   * Prepare the template html content.
   */
  public function prepareFieldsHtml($fields, $multiple = FALSE, $isExtraFields = FALSE) {
    $html = '';
    foreach ($fields as $key => $field) {
      $generator = $this->generatorFactory->getGenerator($field['type'] ?? "");
      if (isset($generator)) {
        $html .= $generator->generate($key, $field, $multiple, $isExtraFields);
      }
    }
    if ($multiple) {
      $html = $this->vactoryGeneratorUtils->multipleWrapper($html);
    }
    return $html;
  }

  /**
   * Creating the template twig file.
   */
  public function createTwigFile(string $path, $content = ''): void {
    file_put_contents($path, $content);
  }

}
