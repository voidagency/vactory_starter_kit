<?php

namespace Drupal\vactory_espace_prive\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\user\Entity\User;
use Drupal\user\Form\UserLoginForm;
use Drupal\user\Form\UserPasswordForm;
use Drupal\user\UserInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class EspacePriveController.
 *
 * @package Drupal\vactory_espace_prive\Controller
 */
class EspacePriveController extends ControllerBase {

  /**
   * Returns Login form.
   */
  public function login() {
    $is_anonymous = \Drupal::currentUser()->isAnonymous();
    if ($is_anonymous) {
      $login_form = \Drupal::formBuilder()->getForm(UserLoginForm::class);
      return [
        '#theme' => 'espace_prive_login',
        '#login_form' => $login_form,
      ];
    }
    return $this->redirect('vactory_espace_prive.cleaned_profile');
  }

  /**
   * Returns Register form.
   */
  public function register() {
    $is_anonymous = \Drupal::currentUser()->isAnonymous();
    if ($is_anonymous) {
      $entity = \Drupal::entityTypeManager()
        ->getStorage('user')
        ->create([]);
      $formObject = \Drupal::entityTypeManager()
        ->getFormObject('user', 'register')
        ->setEntity($entity);
      $registration_form = \Drupal::formBuilder()->getForm($formObject);
      return [
        '#theme' => 'espace_prive_registration',
        '#registration_form' => $registration_form,
      ];
    }
    return $this->redirect('vactory_espace_prive.profile');
  }

  /**
   * Returns Profile edit form for users with administer users permission.
   */
  public function profile($user) {
    $current_user = \Drupal::currentUser();

    $is_anonymous = $current_user->isAnonymous();
    if ($is_anonymous) {
      $profile_url = Url::fromRoute('vactory_espace_prive.profile', ['user' => $user->id()])
        ->toString();
      return $this->redirect('vactory_espace_prive.login', ['destination' => $profile_url]);
    }

    if ($user instanceof UserInterface && !$current_user->isAnonymous()) {
      if ($current_user->hasPermission('administer users')) {
        $formObject = \Drupal::entityTypeManager()
          ->getFormObject('user', 'default')
          ->setEntity($user);
        $profile_form = \Drupal::formBuilder()->getForm($formObject);
        return [
          '#theme' => 'espace_prive_profile',
          '#profile_form' => $profile_form,
        ];
      }
      if ($current_user->id() === $user->id()) {
        return $this->redirect('vactory_espace_prive.cleaned_profile');
      }
    }
    throw new NotFoundHttpException();
  }

  /**
   * Returns Profile edit form for the current user.
   */
  public function cleanedProfile() {
    $current_user = \Drupal::currentUser();
    if (!$current_user->isAnonymous()) {
      $user = \Drupal::service('entity_type.manager')->getStorage('user')
        ->load($current_user->id());
      $formObject = \Drupal::entityTypeManager()
        ->getFormObject('user', 'default')
        ->setEntity($user);
      $profile_form = \Drupal::formBuilder()->getForm($formObject);
      return [
        '#theme' => 'espace_prive_profile',
        '#profile_form' => $profile_form,
      ];
    }
    throw new NotFoundHttpException();
  }

  /**
  * Returns Password Edit Form for the current user
   */
   public function editPassword() {
    $current_user = \Drupal::currentUser();
    if (!$current_user->isAnonymous()) {
      $user = \Drupal::service('entity_type.manager')->getStorage('user')
        ->load($current_user->id());
      $formObject = \Drupal::entityTypeManager()
        ->getFormObject('user', 'default')
        ->setEntity($user);
      $edit_password_form = \Drupal::formBuilder()->getForm($formObject);
      return [
        '#theme' => 'espace_prive_edit_pwd',
        '#edit_password_form' => $edit_password_form,
      ];
    }
    throw new NotFoundHttpException();
   }

  /**
   * Returns email form for reset password.
   */
  public function resetPassword() {
    $current_user = \Drupal::currentUser();
    $is_anonymous = $current_user->isAnonymous();
    if ($is_anonymous) {
      $password_form = \Drupal::formBuilder()->getForm(UserPasswordForm::class);
      return [
        '#theme' => 'espace_prive_password',
        '#password_form' => $password_form,
      ];
    }
    return $this->redirect('vactory_espace_prive.profile',["user" => $current_user->id()]);
  }

  /**
   * Check if user has permission to view other users details.
   */
  public function userView($user) {
    $current_user = \Drupal::currentUser();
    if ($user instanceof UserInterface && !$current_user->isAnonymous() && $current_user->hasPermission('administer users')) {
      return [
        '#theme' => 'user',
        '#user' => $user,
      ];
    }
    if ($current_user->id() === $user->id()) {
      return $this->redirect('vactory_espace_prive.welcome');
    }
    throw new NotFoundHttpException();
  }

  /**
   * Returns profile view page.
   */
  public function welcome() {
    $current_user = \Drupal::currentUser();
    if (!$current_user->isAnonymous()) {
      $user = User::load(\Drupal::currentUser()->id());
      return [
        '#theme' => 'user',
        '#user' => $user,
      ];
    }
    throw new NotFoundHttpException();
  }

}
