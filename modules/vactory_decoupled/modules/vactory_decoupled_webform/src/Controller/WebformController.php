<?php

namespace Drupal\vactory_decoupled_webform\Controller;

use Drupal\captcha\Constants\CaptchaConstants;
use Drupal\Component\Utility\Crypt;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Session\AccountProxy;
use Drupal\Core\Url;
use Drupal\vactory_core\Services\VactoryDevTools;
use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformSubmissionForm;
use Drupal\webform\WebformSubmissionInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Decoupled webform controller.
 */
class WebformController extends ControllerBase {

  /**
   * Current user account.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Vactory Dev Tools.
   *
   * @var \Drupal\vactory_core\Services\VactoryDevTools
   */
  protected $vactoryDevTools;

  /**
   * WebformController constructor.
   */
  public function __construct(AccountProxy $accountProxy, VactoryDevTools $vactoryDevTools) {
    $this->currentUser = $accountProxy->getAccount();
    $this->vactoryDevTools = $vactoryDevTools;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user'),
      $container->get('vactory_core.tools')
    );
  }

  const ELEMENT_TO_SKIP = [
    'sid',
    'current_page',
    'webform_id',
    'entityType',
    'entityId',
    'in_draft',
  ];

  /**
   * {@inheritdoc}
   */
  public function index(Request $request) {
    $webform_data = $request->request->all();

    // Basic check for webform ID.
    if (empty($webform_data['webform_id'])) {
      return new JsonResponse([
        'error' => [
          'code'    => '400',
          'message' => 'Missing webform id',
        ],
      ], 400);
    }

    $entity_type = NULL;
    $entity_id = NULL;

    if (!empty($request->query->get('entityType')) && !empty($request->query->get('entityId'))) {
      $entity_type = $request->query->get('entityType');
      $entity_id = $request->query->get('entityId');
    }

    // Check for a valid webform.
    $webform = Webform::load($webform_data['webform_id']);
    if (!$webform) {
      return new JsonResponse([
        'error' => [
          'message' => 'Invalid webform_id value.',
        ],
      ], 400);
    }

    // Check if webform is open.
    $is_open = WebformSubmissionForm::isOpen($webform);

    if ($is_open !== TRUE) {
      return new JsonResponse([
        'error' => [
          'message' => 'This webform is closed, or too many submissions have been made.',
        ],
      ], 400);
    }

    $error_message = [];
    \Drupal::moduleHandler()
      ->alter('decoupled_webform_data_presubmit', $webform_data, $error_message);
    if (!empty($error_message)) {
      return new JsonResponse($error_message, $error_message['code'] ?? 400);
    }
    if (isset($webform_data['sid']) && !empty($webform_data['sid'])) {
      $webform_submission = WebformSubmission::load($webform_data['sid']);
      $webform_submission->setCurrentPage($webform_data['current_page'] ?? NULL);
      $webform_submission->set('in_draft', $webform_data['in_draft'] == 'true');

      foreach ($webform_data as $element => $data) {
        if (!in_array($element, self::ELEMENT_TO_SKIP)) {
          if (isset($data) && !empty($data)) {
            $webform_submission->setElementData($element, $data);
          }
        }
      }
    }
    else {
      // Convert to webform values format.
      $values = [
        'in_draft'     => $webform_data['in_draft'] == 'true',
        'current_page' => $webform_data['current_page'] ?? NULL,
        'uid'          => \Drupal::currentUser()->id(),
        'uri'          => '/_webform/submit' . $webform_data['webform_id'],
        'entity_type'  => $entity_type,
        'entity_id'    => $entity_id,
        // Check if remote IP address should be stored.
        'remote_addr'  => $webform->hasRemoteAddr() ? $request->getClientIp() : '',
        'webform_id'   => $webform_data['webform_id'],
      ];
      $values['data'] = $webform_data;

      // Don't submit webform ID.
      unset($values['data']['webform_id']);

      // Don't submit entity data.
      unset($values['data']['entityType']);
      unset($values['data']['entityId']);
      $webform_submission = WebformSubmission::create($values);
    }

    $webform_submission = WebformSubmissionForm::submitWebformSubmission($webform_submission);
    // Check if submit was successful.
    if ($webform_submission instanceof WebformSubmissionInterface) {
      $datalayer_handler_enabled = $this->isHandlerEnabled($webform, 'vactory_datalayer_handler');
      $datalayer = NULL;
      if ($datalayer_handler_enabled) {
        $submission = WebformSubmission::load($webform_submission->id());
        $datalayer = $submission->get('datalayer')->value;
      }
      return new JsonResponse([
        'sid' => $webform_submission->id(),
        'crypted_sid' => $this->vactoryDevTools->encrypt('vactory_tender' . $webform_submission->id()),
        'settings' => self::getWhitelistedSettings($webform),
        'datalayer' => isset($datalayer) ? json_decode($datalayer, TRUE) : [],
      ]);
    }
    else {
      // Return validation errors.
      return new JsonResponse([
        'error' => $webform_submission,
      ], 400);
    }
  }

  /**
   * Get white listed settings.
   */
  private static function getWhitelistedSettings(WebformInterface $webform) {
    $whitelist = [
      'confirmation_url',
      'confirmation_type',
      'confirmation_message',
      'confirmation_title',
      'confirmation_back',
      'confirmation_back_label',
    ];

    $settings = $webform->getSettings();
    $tempstore = \Drupal::service('tempstore.private');
    $response_data = $tempstore->get('webform.response_data');
    $response_data = $response_data->get('response_data');

    if (isset($settings['confirmation_url']) && !empty($settings['confirmation_url'])) {
      $front_uri = \Drupal::config('system.site')->get('page.front');
      if ($front_uri === $settings['confirmation_url'] || $settings['confirmation_url'] === "<front>") {
        $settings['confirmation_url'] = Url::fromRoute('<front>')->toString();
      }
      else {
        $settings['confirmation_url'] = Url::fromUserInput($settings['confirmation_url'])
          ->toString();
      }
      $settings['confirmation_url'] = str_replace('/backend', '', $settings['confirmation_url']);
    }

    return array_merge(array_intersect_key($settings, array_flip($whitelist)), ['response_data' => $response_data],
    );
  }

  /**
   * Generates math captcha.
   */
  public function generateCaptchaMath($webform_id) {

    $num1 = rand(1, 10);
    $num2 = rand(1, 10);

    $captcha_sid = \Drupal::database()->insert('captcha_sessions')->fields([
      'uid'        => $this->currentUser->id(),
      'sid'        => session_id(),
      'ip_address' => \Drupal::request()->getClientIp(),
      'timestamp'  => \Drupal::time()->getRequestTime(),
      'form_id'    => $webform_id,
      'solution'   => $num1 + $num2,
      'status'     => CaptchaConstants::CAPTCHA_STATUS_UNSOLVED,
      'attempts'   => 0,
      'token'      => Crypt::randomBytesBase64(),
    ])->execute();

    if (isset($captcha_sid)) {
      return new JsonResponse([
        'csid' => $captcha_sid,
        'num1' => $num1,
        'num2' => $num2,
      ]);
    }
    else {
      return new JsonResponse([
        'error' => t('Cannot generate captcha', [], ['context', '_FRONTEND']),
      ], 400);
    }

  }

  /**
   * Checks if a webform has a specific handler.
   */
  private function isHandlerEnabled($webform, $handler_id) {
    $handlers = $webform->getHandlers(NULL, TRUE);
    foreach ($handlers as $handler) {
      if ($handler->getPluginId() == $handler_id) {
        return TRUE;
      }
    }
    return FALSE;
  }

}
