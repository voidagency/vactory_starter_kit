<?php

namespace Drupal\vactory_announcements\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Defines a confirmation form to confirm deletion of something by id.
 */
class ConfirmAnnouncementsDeleteForm extends ConfirmFormBase {

  /**
   * ID of the node to delete.
   *
   * @var int
   */
  protected $id;

  /**
   * Node to delete.
   *
   * @var object
   */
  protected $node;

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, string $id = NULL) {
    $form['title'] = [
      '#type' => 'markup',
      '#markup' => '<p>' . $this->getQuestion() . '</p>',
    ];
    $this->id = $id;
    $current_user = \Drupal::currentUser();
    $this->node = Node::load($this->id);
    if ($current_user->isAuthenticated()) {
      if (isset($this->node)) {
        if (isset($this->node->get('uid')->getValue()[0]) && !empty($this->node->get('uid')->getValue()[0]) && ($this->node->get('uid')->getValue()[0]['target_id'] == $current_user->id())) {
          return parent::buildForm($form, $form_state);
        }
      }
    }
    throw new AccessDeniedHttpException();
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->node->delete();
    \Drupal::messenger()->addMessage(t("Votre annonce a été supprimé"));
    $form_state->setRedirect('vactory_announcements.add_announcement');
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() : string {
    return "confirm_delete_form";
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('vactory_announcements.add_announcement');
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Do you want to delete this ad ?');
  }

}
