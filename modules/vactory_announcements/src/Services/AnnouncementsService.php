<?php

namespace Drupal\vactory_announcements\Services;

use Drupal\Core\Config\ConfigManager;
use Drupal\Core\Link;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\taxonomy\Entity\Term;

/**
 * Class AnnouncementsService Mail sending service.
 */
class AnnouncementsService {

  use MessengerTrait;
  use StringTranslationTrait;

  /**
   * The logger channel factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $logger;

  /**
   * User Service.
   *
   * @var \Drupal\mailsystem\MailsystemManager
   */
  protected $mailManager;

  /**
   * Config manager.
   *
   * @var \Drupal\Core\Config\ConfigManager
   */
  protected $configManager;

  /**
   * Constructs a new AnnouncementsService.
   *
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger
   *   The logger channel factory.
   * @param \Drupal\Core\Mail\MailManagerInterface $mail_manager
   *   Mail manager.
   * @param \Drupal\Core\Config\ConfigManager $config_manager
   *   Config manager.
   */
  public function __construct(LoggerChannelFactoryInterface $logger, MailManagerInterface $mail_manager, ConfigManager $config_manager) {
    $this->logger = $logger;
    $this->mailManager = $mail_manager;
    $this->configManager = $config_manager;
  }

  /**
   * Function to send mail.
   *
   * @param string $subject
   *   Subject.
   * @param string $to_mail
   *   Destination mail.
   * @param string $mail_body
   *   Data (If empty, fallback to config's default mail body).
   *
   * @return bool
   *   Send mail.
   */
  public function sendMail($subject, $to_mail, $mail_body = '') {

    /** @var \Drupal\Core\Config\ConfigManager $confiManager */
    $confiManagerFactory = $this->configManager->getConfigFactory();
    $langcode = $confiManagerFactory->get('system.site')->get('langcode');

    // Mail.
    $module = 'vactory_announcements';
    $key = 'vactory_announcements_mail_service';
    $to = $to_mail;
    $reply = FALSE;
    $send = TRUE;
    $params['message'] = $mail_body;
    $params['subject'] = $subject;
    $params['options']['title'] = $subject;

    /** @var  /Drupal\Core\Mail\MailManager $mailManager */
    $mailManager = $this->mailManager;
    try {
      $mailManager->mail($module, $key, $to, $langcode, $params, $reply, $send);
      return TRUE;
    }
    catch (\Exception $e) {
      \Drupal::logger('vactory_announcements')
        ->error(t("Erreur lors de l'envoi de mail :") . $e->getMessage());
    }
    return FALSE;
  }

  /**
   * Function to get Parameter value.
   */
  public function getParamValue($param, $node) {
    $replacement = '';
    switch ($param) {
      case '!name':
        $replacement = isset($node->get('field_vactory_name')->getValue()[0]) ? $node->get('field_vactory_name')->getValue()[0]['value'] : '';
        break;

      case '!link_moderate':
        $replacement = Link::fromTextAndUrl(t('Cliquer ici'), Url::fromRoute('entity.node.edit_form', ['node' => $node->id()], ['absolute' => TRUE]))->toString()->getGeneratedLink();
        break;

      case '!link_annonce':
        $replacement = Link::fromTextAndUrl(t('Cliquer ici'), Url::fromRoute('entity.node.canonical', ['node' => $node->id()], ['absolute' => TRUE]))->toString()->getGeneratedLink();
        break;

      case '!link_delete':
        $replacement = Link::fromTextAndUrl(t('Cliquer ici'), Url::fromRoute('vactory_announcements.annonce_delete', ['id' => $node->id()], ['absolute' => TRUE]))->toString()->getGeneratedLink();
        break;

      case '!site_name':
        $replacement = \Drupal::config('system.site')->get('name');
        break;

      case '!period_validity':
        $tid = isset($node->get('field_ad_display')->getValue()[0]) ? $node->get('field_ad_display')->getValue()[0]['target_id'] : '';
        $term = Term::load($tid);
        $replacement = isset($term->get('name')->getValue()[0]) ? $term->get('name')->getValue()[0]['value'] : '';
        break;

      case '!date_end':
        $replacement = isset($node->get('field_event_date_end')->getValue()[0]) ? $node->get('field_event_date_end')->getValue()[0]['value'] : '';
        break;

      case '!date_start':
        $replacement = isset($node->get('field_event_date_start')->getValue()[0]) ? $node->get('field_event_date_start')->getValue()[0]['value'] : '';
        break;

      case '!title':
        $replacement = isset($node->get('title')->getValue()[0]) ? $node->get('title')->getValue()[0]['value'] : '';
        break;

      case '!country':
        $replacement = isset($node->get('field_country')->getValue()[0]) ? $node->get('field_country')->getValue()[0]['value'] : '';
        break;

      case '!site':
        $replacement = isset($node->get('field_site')->getValue()[0]) ? $node->get('field_site')->getValue()[0]['value'] : '';
        break;

      case '!body':
        $replacement = isset($node->get('field_ad_content')->getValue()[0]) ? $node->get('field_ad_content')->getValue()[0]['value'] : '';
        break;

      case '!facebook':
        $replacement = isset($node->get('field_facebook_account')->getValue()[0]) ? $node->get('field_facebook_account')->getValue()[0]['value'] : '';
        break;

      case '!twitter':
        $replacement = isset($node->get('field_twitter_account')->getValue()[0]) ? $node->get('field_twitter_account')->getValue()[0]['value'] : '';
        break;

      case '!phone':
        $replacement = isset($node->get('field_vactory_phone')->getValue()[0]) ? $node->get('field_vactory_phone')->getValue()[0]['value'] : '';
        break;

      case '!mail':
        $replacement = isset($node->get('field_vactory_email')->getValue()[0]) ? $node->get('field_vactory_email')->getValue()[0]['value'] : '';
        break;
    }
    return $replacement;
  }

}
