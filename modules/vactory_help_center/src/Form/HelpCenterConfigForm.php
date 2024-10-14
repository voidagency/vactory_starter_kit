<?php

namespace Drupal\vactory_help_center\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\path_alias\AliasManagerInterface;
use Drupal\vactory_help_center\Services\HelpCenterHelper;
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
   * Help center helper.
   *
   * @var \Drupal\vactory_help_center\Services\HelpCenterHelper
   */
  protected $helpCenterHelper;

  /**
   * Constructs a new HelpCenterConfigForm object.
   */
  public function __construct(LanguageManagerInterface $language_manager, AliasManagerInterface $alias_manager, HelpCenterHelper $helpCenterHelper) {
    $this->languageManager = $language_manager;
    $this->aliasManager = $alias_manager;
    $this->helpCenterHelper = $helpCenterHelper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('language_manager'),
      $container->get('path_alias.manager'),
      $container->get('vactory_help_center.helper')
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

    $form['help_center_search_node'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Help Center Search Node'),
      '#description' => $this->t('Enter the search node path (e.g., /node/456)'),
      '#default_value' => $config->get('help_center_search_node'),
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
    $nod_search_path = $form_state->getValue('help_center_search_node');
    if (!preg_match('/^\/node\/\d+$/', $nod_search_path)) {
      $form_state->setErrorByName('help_center_search_node', $this->t('Invalid node search path. It should be in the format /node/xx where xx is a number.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $node_path = $form_state->getValue('help_center_node');
    $node_search_path = $form_state->getValue('help_center_search_node');
    $config = $this->config('vactory_help_center.settings');
    $config->set('help_center_node', $node_path)
      ->set('help_center_search_node', $node_search_path)
      ->save();

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

    $this->helpCenterHelper->generateRouters();

    parent::submitForm($form, $form_state);
  }

}
