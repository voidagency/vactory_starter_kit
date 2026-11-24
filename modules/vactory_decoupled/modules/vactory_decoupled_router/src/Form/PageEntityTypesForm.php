<?php

namespace Drupal\vactory_decoupled_router\Form;

use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Site\Settings;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The entity types the router resolves as pages.
 */
class PageEntityTypesForm extends ConfigFormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->setEntityTypeManager($container->get('entity_type.manager'));
    return $instance;
  }

  /**
   * Sets the entity type manager.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function setEntityTypeManager(EntityTypeManagerInterface $entity_type_manager): void {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['vactory_decoupled_router.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'vactory_decoupled_router_page_entity_types_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $options = $this->getEntityTypeOptions();
    $selected = $this->config('vactory_decoupled_router.settings')->get('page_entity_types') ?? ['node'];
    if (Settings::get('vactory_decoupled_router_page_entity_types') !== NULL) {
      $this->messenger()->addWarning($this->t('The list is defined in settings.php (@setting): the value of this form is not used.', [
        '@setting' => "\$settings['vactory_decoupled_router_page_entity_types']",
      ]));
    }
    $form['page_entity_types'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Entity types resolved as pages'),
      '#description' => $this->t('The router (/api/router) answers with the entity only for these types. A path to an entity of another type (for example /taxonomy/term/1 when taxonomy terms are not checked) gets the error page with a 404 status. Check only the types the front displays as pages.'),
      '#options' => $options,
      '#default_value' => array_intersect($selected, array_keys($options)),
      '#required' => TRUE,
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $selected = array_keys(array_filter($form_state->getValue('page_entity_types')));
    $this->config('vactory_decoupled_router.settings')
      ->set('page_entity_types', $selected)
      ->save();
    parent::submitForm($form, $form_state);
  }

  /**
   * The content entity types that have a canonical page.
   *
   * @return array
   *   The labels, keyed by entity type ID.
   */
  protected function getEntityTypeOptions(): array {
    $options = [];
    foreach ($this->entityTypeManager->getDefinitions() as $id => $definition) {
      if ($definition instanceof ContentEntityTypeInterface && $definition->hasLinkTemplate('canonical')) {
        $options[$id] = $this->t('@label (@id)', [
          '@label' => $definition->getLabel(),
          '@id' => $id,
        ]);
      }
    }
    asort($options);
    return $options;
  }

}
