<?php

namespace Drupal\vactory_sondage\Form;

use Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\vactory_sondage\Event\VactorySondageVoteEvent;
use Drupal\vactory_sondage\Services\SondageManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Sondage form class.
 */
class SondageForm extends FormBase {

  /**
   * Sondage manager service.
   *
   * @var \Drupal\vactory_sondage\Services\SondageManager
   */
  protected $sondageManager;

  /**
   * Renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Event dispatcher service.
   *
   * @var \Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher
   */
  protected $eventDispatcher;

  /**
   * {@inheritDoc}
   */
  public function __construct(SondageManager $sondageManager, RendererInterface $renderer, ContainerAwareEventDispatcher $eventDispatcher) {
    $this->sondageManager = $sondageManager;
    $this->renderer = $renderer;
    $this->eventDispatcher = $eventDispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Instantiates this form class.
    return new static(
    // Load the service required to construct this class.
      $container->get('vactory_sondage.manager'),
      $container->get('renderer'),
      $container->get('event_dispatcher')
    );
  }

  /**
   * {@inheritDoc}
   */
  public function getFormId() {
    return 'vactory_sondage_form';
  }

  /**
   * {@inheritDoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $extra_data = []) {
    if (empty($extra_data['sondage_options'])) {
      $form['markup'] = 'No sondage option has been founded';
      return $form;
    }
    $entity_type = $extra_data['entity_type'];
    $entity_id = $extra_data['entity_id'];
    $entity = \Drupal::entityTypeManager()->getStorage($entity_type)
      ->load($entity_id);
    if ($entity) {
      $form['entity'] = $entity;
      $current_user = \Drupal::currentUser();
      $form_state->set('entity', $entity);
      $ajax_wrapper_id = 'vactory_sondage_block_' . $extra_data['entity_id'];
      $form['#prefix'] = '<div id="' . $ajax_wrapper_id . '">';
      $form['#suffix'] = '</div>';
      $storage_results = $entity->get('field_sondage_results')->value;
      $storage_results = isset($storage_results) && !empty($storage_results) ? $storage_results : '[]';
      $storage_results = json_decode($storage_results, TRUE);
      if (!empty($storage_results) && in_array($current_user->id(), $storage_results['all_votters'])) {
        $options_statistics = $this->sondageManager->getStatistics($entity);
        return [
          '#theme' => 'vactory_sondage_state',
          '#statistics' => [
            'options' => $options_statistics,
            'votes_count' => count($storage_results['all_votters']),
            'method' => 'replace',
          ],
        ];
      }
      $sondage_options = array_map(function ($option) {
        $option = [
          '#theme' => 'vactory_sondage_radio_option',
          '#option' => $option,
        ];
        return $this->renderer->renderPlain($option);
      }, $extra_data['sondage_options']);
      $type = $extra_data['sondage_options']['option_1']['type'];
      $form['sondage_options_' . $entity_id] = [
        '#type' => 'radios',
        '#options' => $sondage_options,
        '#required' => TRUE,
        '#prefix' => '<div class="sondage-option type-' . $type . '">',
        '#suffix' => '</div>',
      ];
      $form['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Voter'),
        '#ajax' => [
          'callback' => [$this, 'voteAjaxCallback'],
          'wrapper' => $ajax_wrapper_id,
        ],
        '#states' => [
          'invisible' => [
            'input[name="sondage_options_' . $entity_id . '"]' => ['checked' => FALSE],
          ],
        ],
      ];
      $form['#attached']['library'][] = 'vactory_sondage/style';
    }
    return $form;

  }

  /**
   * {@inheritDoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $form_state->set('validated', TRUE);
    $entity = $form_state->get('entity');
    $voted_option_value = $form_state->getValue('sondage_options_' . $entity->id());
    if (empty($voted_option_value)) {
      $form_state->setError($form['sondage_options_' . $entity->id()], $this->t('Veuillez choisir une option'));
      $form_state->set('validated', FALSE);
    }
  }

  /**
   * {@inheritDoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritDoc}
   */
  public function voteAjaxCallback(array &$form, FormStateInterface $form_state) {
    $form_state->setRebuild();
    if ($form_state->get('validated')) {
      $current_user = \Drupal::currentUser();
      $user = \Drupal::entityTypeManager()->getStorage('user')
        ->load($current_user->id());
      $entity = $form_state->get('entity');
      $voted_option_value = $form_state->getValue('sondage_options_' . $entity->id());
      $storage_results = $entity->get('field_sondage_results')->value;
      $storage_results = isset($storage_results) && !empty($storage_results) ? $storage_results : '[]';
      $storage_results = Json::decode($storage_results);
      $count = isset($storage_results[$voted_option_value]) ? $storage_results[$voted_option_value]['count'] + 1 : 1;
      $storage_results[$voted_option_value]['count'] = $count;
      $storage_results[$voted_option_value]['votters'][] = $current_user->id();
      $storage_results['all_votters'][] = $current_user->id();
      $event = new VactorySondageVoteEvent($entity, $user);
      $this->eventDispatcher->dispatch(VactorySondageVoteEvent::EVENT_NAME, $event);
      $entity->set('field_sondage_results', Json::encode($storage_results));
      $entity->save();
      $response = new AjaxResponse();
      $options_statistics = $this->sondageManager->getStatistics($entity);
      $vote_statistics = [
        '#theme' => 'vactory_sondage_state',
        '#statistics' => $options_statistics,
      ];
      $vote_statistics = $this->renderer->render($vote_statistics);
      $response->addCommand(new ReplaceCommand('#vactory_sondage_block_' . $entity->id(), $vote_statistics));
      return $response;
    }
    return $form;
  }

}
