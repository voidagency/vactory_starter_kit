<?php

namespace Drupal\vactory_user\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Class ResetCheckController.
 *
 * @package Drupal\vactory_user\Controller
 */
class ResetCheckController extends ControllerBase {

  /**
   * Add confirmation message then redirect to user login.
   */
  public function checkReset() {
    \Drupal::messenger()->addStatus(t('Further instructions have been sent to your email address.'), TRUE);
    return $this->redirect('user.login');
  }

}
