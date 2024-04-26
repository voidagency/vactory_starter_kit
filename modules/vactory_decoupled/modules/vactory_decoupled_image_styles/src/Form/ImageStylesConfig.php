<?php

namespace Drupal\vactory_decoupled_image_styles\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an admin form for images style settings.
 */
class ImageStylesConfig extends ConfigFormBase {

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Constructs a \Drupal\system\ConfigFormBase object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($config_factory);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['vactory_decoupled_image_styles.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'vactory_decoupled_image_styles_admin_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config('vactory_decoupled_image_styles.settings');

    $options = [];
    $styles = $this->entityTypeManager->getStorage('image_style')->loadMultiple();
    foreach ($styles as $name => $style) {
      $options[$name] = $style->label();
    }

    $form['image_styles'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Image styles'),
      '#description' => $this->t('Select image styles to expose. If none are selected, all styles are exposed.'),
      '#options' => $options,
      '#default_value' => (is_array($config->get('image_styles'))) ? $config->get('image_styles') : [],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    parent::submitForm($form, $form_state);

    $this->config('vactory_decoupled_image_styles.settings')
      ->set('image_styles', $form_state->getValue('image_styles'))
      ->save();
  }

}
