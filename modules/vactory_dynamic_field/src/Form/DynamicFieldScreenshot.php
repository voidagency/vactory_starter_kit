<?php

namespace Drupal\vactory_dynamic_field\Form;

use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;
use Drupal\vactory_dynamic_field\WidgetsManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provide a setting form for vactory Dynamic Field module.
 */
class DynamicFieldScreenshot extends ConfigFormBase {

  /**
   * The plugin manager.
   *
   * @var \Drupal\vactory_dynamic_field\WidgetsManager
   */
  protected $widgetsManager;

  /**
   * File url generator service.
   *
   * @var \Drupal\Core\File\FileUrlGeneratorInterface
   */
  protected $fileUrlGenerator;

  /**
   * Constructs the form class.
   */
  public function __construct(
    WidgetsManagerInterface $widgets_manager,
    FileUrlGeneratorInterface $fileUrlGenerator,
  ) {
    $this->widgetsManager = $widgets_manager;
    $this->fileUrlGenerator = $fileUrlGenerator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('vactory_dynamic_field.vactory_provider_manager'),
      $container->get('file_url_generator')
    );
  }

  /**
   * Gets the configuration names that will be editable.
   *
   * @return array
   *   An array of configuration object names that are editable if called in
   *   conjunction with the trait's config() method.
   */
  protected function getEditableConfigNames() {
    return ['vactory_dynamic_field.screenshot_settings'];
  }

  /**
   * Returns a unique string identifying the form.
   *
   * The returned ID should be a unique string that can be a valid PHP function
   * name, since it's used in hook implementation names such as
   * hook_form_FORM_ID_alter().
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'vactory_dynamic_field_screenshot_settings';
  }

  /**
   * {@inheritDoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('vactory_dynamic_field.screenshot_settings');
    $form = parent::buildForm($form, $form_state);
    $widgetsList = $this->widgetsManager->getModalWidgetsList();

    $form['tab'] = [
      '#type' => 'horizontal_tabs',
    ];

    foreach ($widgetsList as $category => $widgets) {
      $form[$category] = [
        '#type' => 'details',
        '#title' => ucfirst($category),
        '#group' => 'tab',
      ];

      $form[$category]['templates'] = [
        '#type' => 'container',
        '#attributes' => [
          'style' => 'display: grid;grid-template-columns: repeat(2, minmax(0, 1fr));gap: 10px',
        ],
      ];

      foreach ($widgets as $uuid => $widget) {
        $screenshotConfig = $config->get($uuid)['fid'] ?? NULL;
        $form[$category]['templates'][$uuid] = [
          '#type' => 'container',
          '#attributes' => [
            'style' => 'display:flex;padding: 10px;margin-top: 10px;background-color: #edf0f5;',

          ],
        ];
        $form[$category]['templates'][$uuid]['screenshot'] = [
          '#markup' => '<img src="' . $widget['screenshot'] . '" alt="DF Image" width="600" height="500">',
        ];

        $form[$category]['templates'][$uuid]['wrapper'] = [
          '#type' => 'container',
          '#attributes' => [
            'style' => 'display:flex;justify-content: space-between;flex-direction: column;padding: 10px;margin-top: 10px;background-color: #edf0f5;',
          ],
        ];
        $form[$category]['templates'][$uuid]['wrapper']['name'] = [
          '#type' => 'html_tag',
          '#tag' => 'h2',
          '#value' => $widget['name'],
        ];
        $form[$category]['templates'][$uuid]['wrapper']['widget|' . $uuid] = [
          '#type' => 'managed_file',
          '#title' => $this->t('Screenshot'),
          '#name' => 'csv',
          '#upload_location' => 'public://df-screenshots',
          '#upload_validators' => [
            'file_validate_extensions' => ['png jpg jpeg gif'],
          ],
          '#description' => t("Upload new screenshot for this template"),
          '#default_value' => $screenshotConfig ? [$screenshotConfig] : [],
        ];
      }
    }

    return $form;
  }

  /**
   * {@inheritDoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('vactory_dynamic_field.screenshot_settings');

    $values = $form_state->getValues();
    foreach ($values as $key => $value) {
      if (str_starts_with($key, 'widget|') && !empty($value)) {
        $split = explode('|', $key);
        $templateId = end($split);
        $fid = reset($value);
        if (isset($fid)) {
          $file = File::load($fid);
          $uri = $file->getFileUri();
          if (!$file->isPermanent()) {
            $file->setPermanent();
            try {
              $file->save();
            }
            catch (\Exception $e) {
            }
          }
          $config->set($templateId, [
            'fid' => $fid,
            'uri' => $uri,
          ]);
        }
      }
    }
    $config->save();
  }

}
