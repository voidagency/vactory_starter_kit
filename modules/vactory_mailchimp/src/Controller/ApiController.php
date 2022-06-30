<?php

namespace Drupal\vactory_mailchimp\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Component\Utility\EmailValidatorInterface;
use Drupal\Core\Messenger\MessengerInterface;

class ApiController extends ControllerBase
{

  const ERROR_MISSING_ID = 1;
  const ERROR_MISSING_EMAIL = 2;
  const ERROR_INVALID_EMAIL = 3;
  const ERROR_EMAIL_ALREADY_EXISTS = 4;
  const ERROR_SOMETHING_WENT_WRONG = 5;

  /**
   * EntityTypeManagerInterface.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * EmailValidatorInterface.
   *
   * @var \Drupal\Component\Utility\EmailValidatorInterface
   */
  protected $emailValidator;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    MessengerInterface $messenger,
    EmailValidatorInterface $emailValidator
  )
  {
    $this->entityTypeManager = $entityTypeManager;
    $this->messenger = $messenger;
    $this->emailValidator = $emailValidator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container)
  {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('messenger'),
      $container->get('email.validator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function send(Request $request)
  {
    $form_data = $request->request->all();

    // Basic check for list ID.
    if (empty($form_data['id'])) {
      return new JsonResponse([
        'status' => 'error',
        'type' => 'system',
        'code' => static::ERROR_MISSING_ID,
        'message' => t('Missing id'),
      ], 400);
    }

    if (empty($form_data['email'])) {
      return new JsonResponse([
        'status' => 'error',
        'type' => 'system',
        'code' => static::ERROR_MISSING_EMAIL,
        'message' => t('Missing email'),
      ], 400);
    }

    $list_id = $form_data['id'];
    $email = $form_data['email'];

    // Validate email.
    if (!$this->emailValidator->isValid($email)) {
      return new JsonResponse([
        'status' => 'error',
        'type' => 'field',
        'field' => 'email',
        'code' => static::ERROR_INVALID_EMAIL,
        'message' => t('Invalid email'),
      ], 400);
    }

    $is_subscribed = mailchimp_is_subscribed($list_id, $email);
    if ($is_subscribed) {
      return new JsonResponse([
        'status' => 'error',
        'type' => 'field',
        'field' => 'email',
        'code' => static::ERROR_EMAIL_ALREADY_EXISTS,
        'message' => t('Email already exists'),
      ], 400);
    }


    $result = mailchimp_subscribe($list_id, $email);

    if (!$result) {
      return new JsonResponse([
        'status' => 'error',
        'type' => 'system',
        'code' => static::ERROR_SOMETHING_WENT_WRONG,
        'message' => t('Something went wrong'),
      ], 400);
    }

    $response = [
      'status' => 'success',
      'email' => $email,
      'messages' => t('You have successfully subscribed')
    ];

    return new JsonResponse($response, 201);
  }
}
