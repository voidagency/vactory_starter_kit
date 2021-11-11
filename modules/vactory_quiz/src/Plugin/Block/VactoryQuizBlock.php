<?php

namespace Drupal\vactory_quiz\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManager;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\node\NodeInterface;
use Drupal\path_alias\AliasManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Vactory quiz block.
 *
 * @Block(
 *   id="vactory_quiz_block",
 *   admin_label=@Translation("Vactory Quiz Block"),
 *   category="Vactory",
 * )
 */
class VactoryQuizBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTypeManager;

  /**
   * Current Path service.
   *
   * @var string
   */
  private $currentPath;

  /**
   * Language manager service.
   *
   * @var \Drupal\Core\Language\LanguageManager
   */
  private $languageManager;

  /**
   * {@inheritDoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManagerInterface $entityTypeManager,
    CurrentPathStack $currentPathManager,
    AliasManager $aliasManager,
    LanguageManager $languageManager
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entityTypeManager;
    $current_path = $currentPathManager->getPath();
    $this->languageManager = $languageManager;
    $this->currentPath = $aliasManager->getAliasByPath($current_path);
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('path.current'),
      $container->get('path_alias.manager'),
      $container->get('language_manager')
    );
  }

  /**
   * {@inheritDoc}
   */
  public function build() {
    $langcode = $this->languageManager->getCurrentLanguage()->getId();
    $quiz_id = $this->configuration['quiz'];
    if ($quiz_id) {
      $quiz = $this->entityTypeManager->getStorage('node')
        ->load($quiz_id);
      if ($quiz instanceof NodeInterface && $quiz->bundle() === 'vactory_quiz') {
        return [
          '#theme' => 'vactory_quiz_block',
          '#content' => [
            'quiz' => $quiz_id,
            'current_path' => '/' . $langcode . $this->currentPath,
          ],
        ];
      }
    }
    return [];
  }

  /**
   * {@inheritDoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);
    $quiz = NULL;
    if ($this->configuration['quiz']) {
      $quiz = $this->entityTypeManager->getStorage('node')
        ->load($this->configuration['quiz']);
    }

    $form['quiz'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('Quiz'),
      '#target_type' => 'node',
      '#selection_handler' => 'default:node',
      '#selection_settings' => [
        'target_bundles' => ['vactory_quiz'],
        'status' => 1,
      ],
      '#default_value' => $quiz,
      '#description' => $this->t('Chosissez le quiz concerné'),
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritDoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['quiz'] = $form_state->getValue('quiz');
  }

  /**
   * {@inheritDoc}
   */
  public function getCacheTags() {
    return ['vactory_quiz:settings'];
  }

}
