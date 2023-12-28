<?php

namespace Drupal\vactory_onesignal\Controller;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Vactory onesignal controller.
 */
class VactoryOnesignalController extends ControllerBase {

  /**
   * Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->entityTypeManager = $container->get('entity_type.manager');
    return $instance;
  }

  /**
   * Add device to user.
   */
  public function addDeviceIdToUser(Request $request) {
    $data = Json::decode($request->getContent());
    if (!isset($data['device_id'])) {
      return new JsonResponse([
        'message' => 'Missing device_id POST param',
        'status' => 400,
      ], 400);
    }
    if (empty($data['device_id'])) {
      return new JsonResponse([
        'message' => 'device_id POST param should not be empty',
        'status' => 400,
      ], 400);
    }
    $user = $this->entityTypeManager->getStorage('user')
      ->load(\Drupal::currentUser()->id());
    if (!$user) {
      return new JsonResponse([
        'message' => 'Session has been destroyed or user not found',
        'status' => 400,
      ], 400);
    }
    $user_devices = $user->get('field_user_device_ids')->getValue() ?? [];
    $user_devices = array_map(fn($el) => $el['value'], $user_devices);
    $user_devices[] = $data['device_id'];
    $user_devices = array_unique($user_devices);
    try {
      $user->set('field_user_device_ids', $user_devices)
        ->save();
      return new JsonResponse([
        'message' => 'Devise ID has been successfully saved',
        'status' => 200,
      ], 200);
    }
    catch (\Exception $e) {
      return new JsonResponse([
        'message' => 'A problem has been occurred while trying adding device ID to user',
        'status' => 500,
      ], 500);
    }
  }

}
