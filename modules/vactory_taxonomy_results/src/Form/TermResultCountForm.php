<?php

namespace Drupal\vactory_taxonomy_results\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the termresultscount entity edit forms.
 */
class TermResultCountForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {

    $entity = $this->getEntity();
    $result = $entity->save();
    $link = $entity->toLink($this->t('View'))->toRenderable();

    $message_arguments = ['%label' => $this->entity->label()];
    $logger_arguments = $message_arguments + ['link' => render($link)];

    if ($result == SAVED_NEW) {
      $this->messenger()->addStatus($this->t('New termresultscount %label has been created.', $message_arguments));
      $this->logger('vactory_taxonomy_results')->notice('Created new termresultscount %label', $logger_arguments);
    }
    else {
      $this->messenger()->addStatus($this->t('The termresultscount %label has been updated.', $message_arguments));
      $this->logger('vactory_taxonomy_results')->notice('Updated new termresultscount %label.', $logger_arguments);
    }

    $form_state->setRedirect('entity.term_result_count.canonical', ['term_result_count' => $entity->id()]);
  }

}
