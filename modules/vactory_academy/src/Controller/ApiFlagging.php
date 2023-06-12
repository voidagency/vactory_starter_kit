<?php

namespace Drupal\vactory_academy\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\flag\FlagServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Serializer;

/**
 * Class ApiFlagging.
 */
class ApiFlagging extends ControllerBase
{

  /**
   * The flag service.
   *
   * @var \Drupal\flag\FlagServiceInterface
   */
  protected $flagService;

  /**
   * The serializer.
   *
   * @var \Symfony\Component\Serializer\Serializer
   */
  protected $serializer;

  /**
   * The available serialization formats.
   *
   * @var array
   */
  protected $serializerFormats = [];

  /**
   * Constructs a new ApiFlagging object.
   */
  public function __construct(Serializer $serializer, array $serializer_formats, FlagServiceInterface $flag)
  {
    $this->serializer = $serializer;
    $this->serializerFormats = $serializer_formats;
    $this->flagService = $flag;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container)
  {
    if ($container->hasParameter('serializer.formats') && $container->has('serializer')) {
      $serializer = $container->get('serializer');
      $formats = $container->getParameter('serializer.formats');
    } else {
      $formats = ['json'];
      $encoders = [new JsonEncoder()];
      $serializer = new Serializer([], $encoders);
    }

    return new static(
      $serializer,
      $formats,
      $container->get('flag')
    );
  }

  /**
   * flagging.
   */
  public function flag(Request $request)
  {
    $format = $this->getRequestFormat($request);
    $content = $request->getContent();
    $flagData = $this->serializer->decode($content, $format);

    $flag = $this->flagService->getFlagById($flagData['flag_id']);

    $flaggableEntityTypeId = $flag->getFlaggableEntityTypeId();
    $entity = \Drupal::entityTypeManager()
      ->getStorage($flaggableEntityTypeId)
      ->load($flagData['entity_id']);
    try {
      /** @var \Drupal\flag\Entity\Flagging $flagging */
      $flagging = $this->flagService->flag($flag, $entity);
    } catch (\LogicException $e) {
      $message = $e->getMessage();
      return new JsonResponse([
        'error_message' => $message,
      ], 400);
    }

    return new JsonResponse([
      'message' => 'flag success',
      'flagging_uuid' => $flagging->uuid(),
      'flagging_id' => $flagging->id(),
      'flag_id' => $flagging->getFlagId(),
    ]);
  }

  /**
   * unflagging.
   */
  public function unFlag(Request $request)
  {
    $format = $this->getRequestFormat($request);
    $content = $request->getContent();
    $unFlagData = $this->serializer->decode($content, $format);
    $flag = $this->flagService->getFlagById($unFlagData['flag_id']);
    $flaggableEntityTypeId = $flag->getFlaggableEntityTypeId();

    $entity = \Drupal::entityTypeManager()
      ->getStorage($flaggableEntityTypeId)
      ->load($unFlagData['entity_id']);

    try {
      $this->flagService->unflag($flag, $entity);
    } catch (\LogicException $e) {
      $message = $e->getMessage();
      return new JsonResponse([
        'error_message' => $message,
      ], 400);
    }

    return new JsonResponse([
      'message' => 'unflag success',
    ]);
  }

  /**
   * Gets the format of the current request.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return string
   *   The format of the request.
   */
  protected function getRequestFormat(Request $request)
  {
    $format = $request->getRequestFormat();
    if (!in_array($format, $this->serializerFormats)) {
      throw new BadRequestHttpException("Unrecognized format: $format.");
    }
    return $format;
  }
}
