<?php

namespace Drupal\vactory_form_autosave\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Returns responses for Vactory Form Autosave routes.
 */
class VactoryFormAutosaveController extends ControllerBase {

  /**
   * Database connection service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Vactory form autosave manager.
   *
   * @var \Drupal\vactory_form_autosave\Services\VactoryFormAutosaveManager
   */
  protected $formAutosaveManager;

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->database = $container->get('database');
    $instance->formAutosaveManager = $container->get('vactory_form_autosave.manager');
    return $instance;
  }

  /**
   * Update form draft endpoint.
   */
  public function updateFormDraft() {
    $submitted = \Drupal::request()->request->all();
    if (!empty($submitted) && isset($submitted['formId']) && isset($submitted['formData'])) {
      $session = \Drupal::request()->getSession();
      if ($session && method_exists($session, 'getId') && !empty($session->getId())) {
        $session_id = $session->getId();
        $current_user = \Drupal::currentUser();
        $form_id = $submitted['formId'];
        $data = $submitted['formData'];
        if (!is_string($data)) {
          return new JsonResponse(['error' => 'Bad type of data param, expected string type'], 400);
        }
        $code = $this->formAutosaveManager->updateFormDraft($data, $form_id, $current_user, $session_id);
        switch ($code) {
          case 1:
            $message = 'Draft has been successfully created';
            break;
          case 2:
            $message = 'Draft has been successfully updated';
            break;
          default:
            $message = '';
        }
        return new JsonResponse(['message' => $message]);

      }
    }
    return new JsonResponse([]);
  }

  /**
   * Get form draft.
   */
  public function getFormDraft() {
    $submitted = \Drupal::request()->request->all();
    if (!empty($submitted) && isset($submitted['formId'])) {
      $session = \Drupal::request()->getSession();
      if ($session && method_exists($session, 'getId') && !empty($session->getId())) {
        $session_id = $session->getId();
        $current_user = \Drupal::currentUser();
        $form_id = $submitted['formId'];
        $data = $this->formAutosaveManager->getFormDraft($form_id, $current_user, $session_id);
        return new JsonResponse($data);
      }
    }
    return FALSE;
  }

}
