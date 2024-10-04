<?php

namespace Drupal\vactory_help_center\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\path_alias\AliasManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Config form.
 */
class HelpCenterConfigForm extends ConfigFormBase {

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The alias manager.
   *
   * @var \Drupal\path_alias\AliasManagerInterface
   */
  protected $aliasManager;

  /**
   * Constructs a new HelpCenterConfigForm object.
   *
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\path_alias\AliasManagerInterface $alias_manager
   *   The alias manager.
   */
  public function __construct(LanguageManagerInterface $language_manager, AliasManagerInterface $alias_manager) {
    $this->languageManager = $language_manager;
    $this->aliasManager = $alias_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('language_manager'),
      $container->get('path_alias.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['vactory_help_center.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'help_center_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('vactory_help_center.settings');
    $form = parent::buildForm($form, $form_state);

    $form['help_center_node'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Help Center Node'),
      '#description' => $this->t('Enter the node path (e.g., /node/123)'),
      '#default_value' => $config->get('help_center_node'),
      '#required' => TRUE,
    ];
    $form['actions']['submit']['#value'] = $this->t('Rebuild router');

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $node_path = $form_state->getValue('help_center_node');
    if (!preg_match('/^\/node\/\d+$/', $node_path)) {
      $form_state->setErrorByName('help_center_node', $this->t('Invalid node path. It should be in the format /node/xx where xx is a number.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $node_path = $form_state->getValue('help_center_node');
    $config = $this->config('vactory_help_center.settings');
    $config->set('help_center_node', $node_path)->save();

    // Get aliases for all active languages.
    $languages = $this->languageManager->getLanguages();
    $aliases = [];
    foreach ($languages as $langcode => $language) {
      $alias = $this->aliasManager->getAliasByPath($node_path, $langcode);
      if ($alias !== $node_path) {
        $aliases[$langcode] = $alias;
      }
    }
    $config->set('help_center_aliases', $aliases)->save();

    // Delete existing help center routes.
    $max_depth = $this->getTaxonomyMaxDepth();
    $existing_routes = \Drupal::entityTypeManager()->getStorage('vactory_route')->loadByProperties([
      'path' => $node_path,
    ]);
    foreach ($existing_routes as $existing_route) {
      $existing_route->delete();
    }

    // Generate new routes.
    foreach ($aliases as $langcode => $alias) {
      for ($depth = 1; $depth <= $max_depth; $depth++) {
        $path_parts = [];
        for ($i = 1; $i <= $depth; $i++) {
          $path_parts[] = "{help_center_item_$i}";
        }
        $route = \Drupal::entityTypeManager()->getStorage('vactory_route')->create([
          'id' => "help_center_level_{$depth}_{$langcode}",
          'label' => "Help center level {$depth}",
          'path' => $node_path,
          'alias' => $alias . '/' . implode('/', $path_parts),
        ]);
        $route->save();
      }
    }

    parent::submitForm($form, $form_state);
  }

  /**
   * Get taxonomy max depth.
   */
  private function getTaxonomyMaxDepth() {
    $terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree('vactory_help_center', 0, NULL, TRUE);
    $max_depth = 0;

    foreach ($terms as $term) {
      $parents = \Drupal::service('entity_type.manager')->getStorage('taxonomy_term')->loadParents($term->id());
      $depth = 1;

      while (!empty($parents)) {
        $depth++;
        $parent = reset($parents);
        $parents = \Drupal::service('entity_type.manager')->getStorage('taxonomy_term')->loadParents($parent->id());
      }

      $max_depth = max($max_depth, $depth);
    }

    return $max_depth;
  }

}
