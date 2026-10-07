<?php

namespace Drupal\vactory_webform_typesafe\Plugin\WebformHandler;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\vactory_webform_typesafe\Service\QuestionDefinition;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configures classification for one Webform.
 *
 * @WebformHandler(
 *   id = "vactory_typesafe",
 *   label = @Translation("TypeSafe classification"),
 *   category = @Translation("AI"),
 *   description = @Translation("Classify selected submission fields in the background."),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_SINGLE,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 *   submission = \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_REQUIRED
 * )
 */
final class TypeSafeClassificationHandler extends WebformHandlerBase {

  /**
   * The analysis coordinator.
   *
   * @var \Drupal\vactory_webform_typesafe\Service\AnalysisManager
   */
  protected $analysisManager;
  /**
   * The selected-field input builder.
   *
   * @var \Drupal\vactory_webform_typesafe\Service\SubmissionInputBuilder
   */
  protected $inputBuilder;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->analysisManager = $container->get('vactory_webform_typesafe.manager');
    $instance->inputBuilder = $container->get('vactory_webform_typesafe.input');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'fields' => [],
      'automatic' => FALSE,
      'on_update' => FALSE,
      'model' => '',
      'questions' => json_encode(QuestionDefinition::defaults(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['notice'] = [
      '#markup' => $this->t('Les champs sélectionnés sont envoyés à TypeSafe. Sélectionnez uniquement les textes nécessaires à la classification. Les fichiers, mots de passe et champs composites sont exclus.'),
    ];
    $form['fields'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Champs à analyser'),
      '#options' => $this->inputBuilder->eligibleFields($this->getWebform()),
      '#default_value' => $this->configuration['fields'],
      '#required' => TRUE,
    ];
    $form['automatic'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Classifier automatiquement les nouvelles soumissions complètes'),
      '#default_value' => $this->configuration['automatic'],
    ];
    $form['on_update'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Relancer la classification lorsque les champs sélectionnés changent'),
      '#default_value' => $this->configuration['on_update'],
    ];
    $form['model'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Modèle spécifique'),
      '#default_value' => $this->configuration['model'],
      '#description' => $this->t('Laissez vide pour utiliser le modèle défini dans la configuration générale.'),
    ];
    $questions = json_decode($this->configuration['questions'], TRUE) ?: [];
    $rows = [];
    foreach ($questions as $id => $question) {
      $rows[] = ['id' => $id] + $question;
    }
    // Two blank slots allow adding questions without editing JSON or code.
    $rows = array_slice(array_merge($rows, [[], []]), 0, 20);
    $form['question_rows'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Questions de classification'),
      '#tree' => TRUE,
      '#parents' => ['settings', 'question_rows'],
      '#description' => $this->t('Configurez des questions indépendantes. Laissez les emplacements inutilisés vides. Enregistrez pour ajouter des emplacements supplémentaires (20 questions maximum).'),
    ];
    foreach ($rows as $index => $q) {
      $row = [
        '#type' => 'details',
        '#title' => $q['label'] ?? $this->t('Ajouter une question'),
        '#open' => FALSE,
      ];
      $row['id'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Identifiant technique'),
        '#default_value' => $q['id'] ?? '',
        '#maxlength' => 64,
        '#description' => $this->t('Utilisez des lettres minuscules, des chiffres et des tirets bas. Commencez par une lettre.'),
      ];
      $row['label'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Libellé'),
        '#default_value' => $q['label'] ?? '',
      ];
      $row['type'] = [
        '#type' => 'select',
        '#title' => $this->t('Type de question'),
        '#options' => [
          'choice' => $this->t('Choix — une catégorie'),
          'score' => $this->t('Score — niveaux ordonnés'),
          'noul' => $this->t('Noul — probabilité d’une affirmation'),
        ],
        '#default_value' => $q['type'] ?? 'choice',
      ];
      $row['instructions'] = [
        '#type' => 'textarea',
        '#title' => $this->t('Consigne ou affirmation'),
        '#rows' => 3,
        '#default_value' => $q['instructions'] ?? '',
      ];
      $criteria = [];
      foreach ($q['criteria'] ?? [] as $key => $description) {
        $criteria[] = ($q['type'] === 'choice' ? $key . ' | ' : '') . $description;
      }
      $row['criteria'] = [
        '#type' => 'textarea',
        '#title' => $this->t('Catégories ou niveaux de score'),
        '#rows' => 5,
        '#default_value' => implode("\n", $criteria),
        '#description' => $this->t('Choix : une ligne par catégorie, au format identifiant | description. Score : une description par ligne, du niveau le plus faible au plus élevé (2 à 10 niveaux). Noul : laissez vide.'),
      ];
      foreach ([
        'confidence_threshold' => [
          'Seuil de confiance (Choix/Score)',
          0.75,
        ],
        'threshold' => ['Seuil de décision (Noul)', 0.5],
        'review_min' => ['Borne minimale de vérification (Noul)', 0.35],
        'review_max' => ['Borne maximale de vérification (Noul)', 0.65],
      ] as $key => [$label, $default]) {
        $row[$key] = [
          '#type' => 'number',
          '#title' => $label,
          '#min' => 0,
          '#max' => 1,
          '#step' => 0.01,
          '#default_value' => $q[$key] ?? $default,
        ];
      }
      $row['remove'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Supprimer cette question'),
      ];
      $form['question_rows'][$index] = $row;
    }
    return $this->setSettingsParents($form);
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::validateConfigurationForm($form, $form_state);
    try {
      $questions = [];
      foreach ($form_state->getValue('question_rows', []) as $row) {
        if (!empty($row['remove'])) {
          continue;
        }
        $id = trim($row['id']);
        if ($id === '' && trim($row['label']) === '' && trim($row['instructions']) === '') {
          continue;
        }
        if (isset($questions[$id])) {
          throw new \InvalidArgumentException('Duplicate question ID.');
        }
        $question = [
          'label' => trim($row['label']),
          'type' => $row['type'],
          'instructions' => trim($row['instructions']),
        ];
        if ($row['type'] !== 'noul') {
          $question['criteria'] = [];
          foreach (preg_split('/\R/', trim($row['criteria'])) as $line) {
            if (trim($line) === '') {
              continue;
            }
            if ($row['type'] === 'choice') {
              $parts = array_map('trim', explode('|', $line, 2));
              if (count($parts) !== 2 || isset($question['criteria'][$parts[0]])) {
                throw new \InvalidArgumentException('Invalid or duplicate category.');
              }
              $question['criteria'][$parts[0]] = $parts[1];
            }
            else {
              $question['criteria'][] = trim($line);
            }
          }
          $question['confidence_threshold'] = (float) $row['confidence_threshold'];
        }
        else {
          foreach (['threshold', 'review_min', 'review_max'] as $key) {
            $question[$key] = (float) $row[$key];
          }
        }
        $questions[$id] = $question;
      }
      $json = json_encode($questions, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
      QuestionDefinition::parse($json);
      $form_state->setValue('questions', $json);
    }
    catch (\Throwable) {
      $form_state->setErrorByName('question_rows', $this->t('Invalid question definitions. Check unique machine names, labels, instructions, criteria and thresholds.'));
    }
    if (!array_filter($form_state->getValue('fields', []))) {
      $form_state->setErrorByName('fields', $this->t('Select at least one eligible text field.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    foreach ([
      'fields',
      'automatic',
      'on_update',
      'model',
      'questions',
    ] as $key) {
      $this->configuration[$key] = $form_state->getValue($key);
    }
    $this->configuration['fields'] = array_values(array_filter($this->configuration['fields']));
    $this->configuration['automatic'] = (bool) $this->configuration['automatic'];
    $this->configuration['on_update'] = (bool) $this->configuration['on_update'];
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(WebformSubmissionInterface $webform_submission, $update = TRUE) {
    $original = $webform_submission->original ?? NULL;
    $new_completed = !$update || ($original && $original->isDraft());
    if (($new_completed && $this->configuration['automatic']) || (!$new_completed && $this->configuration['on_update'])) {
      try {
        $this->analysisManager->enqueue($webform_submission);
      }
      catch (\Throwable) {
        // A classification failure must never prevent saving the submission.
        $this->getLogger()->error('TypeSafe could not queue submission @sid. Check the configured input limit and questions.', [
          '@sid' => $webform_submission->id(),
        ]);
      }
    }
  }

}
