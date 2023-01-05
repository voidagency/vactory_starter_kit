<?php

namespace Drupal\vactory_push_notification\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\ConfirmFormHelper;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\vactory_push_notification\Entity\Subscription;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Clear notification subscribers.
 */
class ConfirmClearSubscribers extends ConfirmFormBase {

  const BATCH_DELETE_SIZE = 500;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $storage;

  /**
   * ConfirmClearSubscribers constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_manager
   *   The entity type manager.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(EntityTypeManagerInterface $entity_manager) {
    $this->storage = $entity_manager->getStorage('vactory_wpn_subscription');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $c) {
    return new static(
      $c->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'vactory_push_notification_confirm_clear_subscribers';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $count = $this->storage
      ->getQuery()
      ->count()
      ->execute();

    if ($count == 0) {
      $form['description'] = [
        '#markup' => $this->t('No notification subscriptions found.'),
      ];
      $form['actions'] = ['#type' => 'actions'];
      $form['actions']['cancel'] = ConfirmFormHelper::buildCancelLink($this, $this->getRequest());;
      return $form;
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $query = $this->storage->getQuery();

    $start = 0;

    // @TODO: reimplement with batch() ?
    while ($ids = $query->range($start, self::BATCH_DELETE_SIZE)->execute()) {
      $subscriptions = Subscription::loadMultiple($ids);
      $this->storage->delete($subscriptions);
      $start += self::BATCH_DELETE_SIZE;
    }

    $this->messenger()->addStatus($this->t('Subscriptions have been cleared.'));
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Do you want to delete all the subscriptions ?');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('After that users cannot get notifications from your site and they must resubscribe to receive notifications again.');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('view.vactory_push_notification_subscriptions.page_1');
  }
}
