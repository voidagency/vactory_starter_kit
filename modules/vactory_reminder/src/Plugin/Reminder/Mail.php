<?php

namespace Drupal\vactory_reminder\Plugin\Reminder;

use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\vactory_reminder\ReminderInterface;
use Drupal\vactory_reminder\SuspendCurrentItemException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a reminder implementation for sending mails.
 *
 * @Reminder(
 *   id = "mail",
 *   title = "Send mail",
 * )
 */
class Mail extends PluginBase implements ReminderInterface, ContainerFactoryPluginInterface {

  /**
   * Mail manager service.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  private $mailManager;

  /**
   * Language manager service.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  private $languageManager;

  /**
   * {@inheritDoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MailManagerInterface $mailManager, LanguageManagerInterface $languageManager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->mailManager = $mailManager;
    $this->languageManager = $languageManager;
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.mail'),
      $container->get('language_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    if (!isset($data['extra']['message']) || !isset($data['extra']['email']) || !isset($data['extra']['subject'])) {
      $suffix = '';
      $suffix .= !isset($data['extra']['message']) ? 'message' : $suffix;
      $suffix .= !isset($data['extra']['email']) ? (!empty($suffix) ? ', ' : '') . 'email' : $suffix;
      $suffix .= !isset($data['extra']['subject']) ? (!empty($suffix) ? ', ' : '') . 'subject' : $suffix;
      throw new SuspendCurrentItemException('There was a problem sending a mail: Missing ' . $suffix . ' parameter' . serialize($data));
    }

    $to = $data['extra']['email'];
    $langcode = isset($data['extra']['langcode']) ? $data['extra']['langcode'] : $this->languageManager->getDefaultLanguage()->getId();
    $params = [
      'subject' => $data['extra']['subject'],
      'message' => $data['extra']['message'],
    ];

    try {
      $result = $this->mailManager->doMail('vactory_reminder', 'action_send_email', $to, $langcode, $params);
      if (!$result['result']) {
        throw new SuspendCurrentItemException('There was a problem sending a mail: ' . serialize($data));
      }
    }
    catch (\Exception $e) {
      throw new SuspendCurrentItemException($e);
    }
  }

}
