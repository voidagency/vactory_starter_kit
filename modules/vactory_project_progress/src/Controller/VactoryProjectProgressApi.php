<?php

namespace Drupal\vactory_project_progress\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Controller TpeMonProjetApi.
 */
class VactoryProjectProgressApi extends ControllerBase {

  /**
   * Entity repository interface.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->entityRepository = $container->get('entity.repository');
    return $instance;
  }

  /**
   * Edit concerned user project progress.
   */
  public function postResult(Request $request) {
    $req = json_decode($request->getContent());
    if (!isset($req) || empty($req)) {
      return new JsonResponse([
        'resources' => $this->t('Empty params!'),
        'req' => $req,
      ], 400);
    }
    if (isset($req->uuid)) {
      $user = $this->entityRepository->loadEntityByUuid('user', $req->uuid);
      if ($user) {
        if (!isset($user->field_project_progress)) {
          return new JsonResponse([
            'message' => "Project field doesn't exist",
          ], 400);
        }
        if (isset($req->data)) {
          $data = json_decode($req->data, TRUE);
          if (empty($data)) {
            return new JsonResponse([
              'message' => 'Invalid data',
            ], 400);
          }
          $user->set('field_project_progress', $req->data);
          $user->save();
          return new JsonResponse([
            'message' => 'Data saved successfully',
          ], 200);
        }
        return new JsonResponse([
          'resources' => $this->t('Data is required!'),
        ], 400);
      }
      return new JsonResponse([
        'resources' => $this->t('Invalid user uuid!'),
      ], 400);
    }
    return new JsonResponse([
      'resources' => $this->t('User uuid is required!'),
      'req' => $req,
    ], 400);
  }

  /**
   * Get concerned user project progress.
   */
  public function getProject(Request $request) {
    $uuid = $request->query->get('uuid');
    if (isset($uuid) && !empty($uuid)) {
      $user = $this->entityRepository->loadEntityByUuid('user', $uuid);
      if ($user) {
        if (!isset($user->field_project_progress)) {
          return new JsonResponse([
            'message' => "Project field doesn't exist",
          ], 400);
        }
        return new JsonResponse([
          'projectData' => $user->field_project_progress->value,
        ], 200);
      }
      return new JsonResponse([
        'resources' => $this->t('Invalid user uuid!'),
      ], 400);
    }
    return new JsonResponse([
      'resources' => $this->t('Empty params!'),
    ], 400);
  }

}
