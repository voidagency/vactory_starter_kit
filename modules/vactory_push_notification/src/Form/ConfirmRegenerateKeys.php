<?php

namespace Drupal\vactory_push_notification\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\vactory_push_notification\KeysHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Confirmation for auth keys regeneration.
 */
class ConfirmRegenerateKeys extends ConfirmFormBase {

  /**
   * @var \Drupal\vactory_push_notification\KeysHelper
   */
  protected $keysHelper;

  /**
   * ConfirmRegenerateKeys constructor.
   *
   * @param \Drupal\vactory_push_notification\KeysHelper $keys_helper
   *   The keys helper.
   */
  public function __construct(KeysHelper $keys_helper) {
    $this->keysHelper = $keys_helper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('vactory_push_notification.keys_helper')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'vactory_push_notification_regenerate_keys';
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('vactory_push_notification.settings');
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to regenerate keys ?');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('If you will regenerate keys then already subscribed clients may not receive the notifications from your site.<br>Do you want to continue ?');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->keysHelper
      ->generateKeys()
      ->save();
    $this->messenger()->addStatus($this->t('Public and private keys have been regenerated.'));
    $form_state->setRedirect('vactory_push_notification.settings');
  }

}
