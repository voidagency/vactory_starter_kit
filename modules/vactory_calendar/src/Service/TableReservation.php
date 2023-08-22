<?php

namespace Drupal\vactory_calendar\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\Core\Utility\Token;
use Drupal\symfony_mailer\EmailFactory;
use Drupal\symfony_mailer\LegacyMailerHelper;
use Drupal\symfony_mailer\Mailer;
use Drupal\vactory_calendar\Entity\CalendarSlot;
use Drupal\vactory_calendar\Entity\CalendarSlotInterface;

/**
 * Calendar utility service.
 */
class TableReservation implements TableReservationInterface {

  use StringTranslationTrait;

  use MailAPI;
  /**
   * The logger channel factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected LoggerChannelFactoryInterface $logger;

  /**
   * @var string
   */
  protected string $language;

  /**
   * Config Object.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  private ConfigFactoryInterface $configFactory;

  /**
   * Language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  private LanguageManagerInterface $languageManager;

  /**
   * The mail manager.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  private EmailFactory $mailer;

  /**
   * Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private EntityTypeManagerInterface $entityTypeManager;


  /**
   * @var \Drupal\Core\Utility\Token
   */
  private Token $token;

  private $legacyMailer;
  private $symfonyMailer;

  /**
   * @var string[]
   */
  private array $notificationTypes;

  /**
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   * @param \Drupal\Core\Language\LanguageManagerInterface $languageManager
   * @param \Drupal\symfony_mailer\EmailFactory $mailer
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger
   * @param \Drupal\Core\Utility\Token $token
   * @param \Drupal\symfony_mailer\LegacyMailerHelper $legacyMailer
   * @param \Drupal\symfony_mailer\Mailer $symfonyMailer
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager,
                              ConfigFactoryInterface $configFactory,
                              LanguageManagerInterface $languageManager,
                              EmailFactory $mailer,
                              LoggerChannelFactoryInterface $logger,
                              Token $token,
                              LegacyMailerHelper $legacyMailer, Mailer $symfonyMailer) {
    $this->configFactory = $configFactory;
    $this->languageManager = $languageManager;
    $this->logger = $logger;
    $this->mailer = $mailer;
    $this->entityTypeManager = $entityTypeManager;
    $this->language = $this->languageManager->getCurrentLanguage()->getId();
    $this->token = $token;
    $this->legacyMailer = $legacyMailer;
    $this->symfonyMailer = $symfonyMailer;
    $this->notificationTypes = [
      self::SEND_INVITATION => $this->t('Demande de prise de Rendez-vous'),
      self::SEND_CONFIRMATION => $this->t("Confirmation d'un Rendez-vous"),
      self::SEND_ANNULATION => $this->t('Rendez-vous annulée'),
      self::TABLE_RESERVED => $this->t('Table réservée pour votre Rendez-vous'),
    ];

  }

  /**
   * @param $from
   * @param $end
   *
   * @return false|null
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  private function getNextAvailable($from, $end) {
    $query = $this->query();
    // RDV time is after the last .
    $upper_bound = $query->andConditionGroup()->condition('field_last_lock_end', $from, '<', $this->language)
      ->condition('field_last_lock_end', $end, '<', $this->language);

    $lower_bound = $query->andConditionGroup()->condition('field_last_lock_begin', $from, '>', $this->language)
      ->condition('field_last_lock_begin', $end, '>', $this->language);
    $empty_table = $query->andConditionGroup()->notExists('field_last_lock_end')->notExists('field_last_lock_begin');

    $orGroup = $query->orConditionGroup();

    $orGroup->condition($lower_bound)->condition($upper_bound)->condition($empty_table);
    $availableQuery = $query->condition($orGroup)
                    ->range(0, 1)
                    ->execute();

    if (count($availableQuery) > 0) {
      return reset($availableQuery);
    }

    return NULL;
  }

  /**
   * @param $event
   *
   * @return bool
   */
  public function assignTable($event) {

    try {
      if (($event instanceof CalendarSlot) && empty($event->get('field_table_du_rdv')
          ?->getValue())) {
        $begin = $event->get('start_time')->value;
        $ending = $event->get('end_time')->value;
        $table = $this->getNextAvailable($begin, $ending);
        if ($table) {
          $event->set('field_table_du_rdv', $table);
          /** @var \Drupal\taxonomy\Entity\Term $entityTable */
          $entity = $this->entityTypeManager->getStorage('taxonomy_term')->load($table);

          $reservedStart = $entity?->get('field_last_lock_begin')->value;
          $reservedEnd = $entity?->get('field_last_lock_end')->value;

//          Greedy matching to set left/right interval to the table.
//          We assume that the reservation starts at min time and end at max time.
//          Downside => The interval in between is never checked.
          $lower_bound = $this->getMinMaxDatetime(new DrupalDateTime($reservedStart), new DrupalDateTime($begin));
          $lower_bound = $lower_bound['min'];
          $upped_bound = $this->getMinMaxDatetime(new DrupalDateTime($reservedEnd), new DrupalDateTime($ending));
          $upped_bound = $upped_bound['max'];

          $entity?->set('field_last_lock_begin', $lower_bound->format('Y-m-d\TH:i:s'));
          $entity?->set('field_last_lock_end', $upped_bound->format('Y-m-d\TH:i:s'));

          $rdv_id = $event->id();
          $this->logger->get('Calendar')->notice("Table $table is reserved for $rdv_id !");
          return (bool) $entity?->save();
        }
      }

    } catch (\Exception $e) {
      $this->logger->get('Calendar')->error($e->getMessage());
      return FALSE;
    }
  }

  /**
   * @return array|int
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @deprecated Since availability now depends on time.
   */
  public function countAvailable() {
    return $this->query()->condition('status', 1, '=', $this->language)->count()->execute();
  }

  /**
   * @param $type
   * @param null $event
   *
   * @return bool
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function notify($type, $event = NULL) {
    $message = $this->configFactory->get('vactory_calendar.settings')->getRawData()[$type];
    $from = $this->configFactory->get('system.site')->get('mail') ;
    $localManager = $this->entityTypeManager->getStorage('user');
    $params = [];
    if ($event instanceof CalendarSlot) {
      $invited = $event?->get('invited_user_id')->getValue();
      $invited = array_map(static function ($el) use ($localManager) {
        $id = $el['target_id'] ?? NULL;
        return $id ? $localManager->load($id)?->getEmail() : '';
      }, $invited);
      $owner = $event?->getOwner()->getEmail();
      $destination = array_merge([$owner], $invited);
      $params['subject'] = $this->notificationTypes[$type]->__toString();


      $body = ['#markup' =>  Markup::create($this->token->replace($message, ['calendar_slot' => $event]))];

      $params['from'] = $from;
      $params['headers']['Sender'] = $from;
      $params['headers']['From'] = $from;
//      First Try using Mailjet .
      $delivered = $this->sendMailjet($params['subject'], $body, $from, $destination);
//      Formatting destination to match Drupal mailer.
//      Attempt to use Mailgun.
      if (!$delivered) {
        $delivered = $this->sendMailGun($params['subject'], $body, $from, $destination);
      }
//      Fallback to SMTP or default Drupal Mailer.
      if (!$delivered) {
        $destination = implode(',', $destination);
        $mailObject = $this->mailer->newTypedEmail('vactory_calendar', $type);
        $params['to'] = $destination;

        $this->legacyMailer->emailFromArray($mailObject, $params);

        $mailObject->setBody($body);
        $delivered = $this->symfonyMailer->send($mailObject);

      }
      return !empty($delivered);
    }
    return FALSE;
  }

  /**
   * @return \Drupal\Core\Entity\Query\QueryInterface
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  private function query() {
    return $this->entityTypeManager->getStorage('taxonomy_term')->getQuery()->condition('vid', 'vactory_event_table');
  }

  /**
   * @param $id
   * @param null $slot
   *
   * @return bool|null
   */
  public function freeTable($id, $slot = NULL) {
    try {
      if ($id) {
        $table = $this->entityTypeManager->getStorage('taxonomy_term')->load($id);
        if ($slot instanceof CalendarSlotInterface) {
//          In case the table is time limit matches the limits of the cancelled event.
//          Still needs logic so we abort this for now.
//          $begin = $slot->get('start_time')->value;
//          $ending = $slot->get('end_time')->value;
//
//          $reservedStart = $table?->get('field_last_lock_begin')->value;
//          $reservedEnd = $table?->get('field_last_lock_end')->value;
//
//          if ($this->compareDatetime(new DrupalDateTime($reservedEnd), new DrupalDateTime($ending)) === 0) {
//            $table?->set('field_last_lock_end', NULL);
//          }
//          if ($this->compareDatetime(new DrupalDateTime($reservedStart), new DrupalDateTime($begin)) === 0) {
//            $table?->set('field_last_lock_begin', NULL);
//          }
          $slot_id = $slot->id();
          $this->logger->get('Calendar')->notice("Slot $slot_id Canceled Table $id must be Freed!");
          return TRUE;
        }
//        Otherwise just set it to NULL cuz logically it's the cronjob that's doing the clean.
        else {
          $table?->set('field_last_lock_begin', NULL);
          $table?->set('field_last_lock_end', NULL);
        }
        $this->logger->get('Calendar')->notice("Table $id is Free now");
        return (bool) $table?->save();
      }
    } catch (\Exception $e) {
      $this->logger->get('Calendar')->error($e->getMessage());
      return FALSE;
    }
    return FALSE;
  }

  /**
   * @param \Drupal\Core\Datetime\DrupalDateTime $datetime1
   * @param \Drupal\Core\Datetime\DrupalDateTime $datetime2
   *
   * @return \Drupal\Core\Datetime\DrupalDateTime[]
   */
  protected function getMinMaxDatetime(DrupalDateTime $datetime1, DrupalDateTime $datetime2) {
    $comparison = $this->compareDatetime($datetime1, $datetime2);

    if ($comparison === -1) {
      $minDatetime = $datetime1;
      $maxDatetime = $datetime2;
    }
    else {
      $minDatetime = $datetime1;
      $maxDatetime = $datetime2;
    }

    return [
      'min' => $minDatetime,
      'max' => $maxDatetime,
    ];
  }

  /**
   * @param \Drupal\Core\Datetime\DrupalDateTime $datetime1
   * @param \Drupal\Core\Datetime\DrupalDateTime $datetime2
   *
   * @return int
   */
  protected function compareDatetime(DrupalDateTime $datetime1, DrupalDateTime $datetime2) {
    $diff = $datetime1->diff($datetime2);

    if ($diff->invert) {
      return 1; // $datetime2 is earlier
    }
    elseif ($diff->invert === 0) {
      return 0; // Both are equal
    }
    else {
      return -1; // $datetime1 is earlier
    }
  }


  /**
   * @param $calendar_slot
   *
   * @return \Drupal\Core\GeneratedUrl|string
   */
  public function googleCalendarLink ($calendar_slot) {
    $begin = new DrupalDateTime($calendar_slot->get('start_time')->value);
    $ending = new DrupalDateTime($calendar_slot->get('end_time')->value);

    $site_name = $this->configFactory->get('system.site')->get('name');
    // Prepare params for google calendar URL.
    $options = [
      'query' => [
        'action' => 'TEMPLATE',
        'text' => $this->t('Rendez-vous chez @site_name - @title', [
          '@title' => $calendar_slot->getName(),
          '@site_name' => $site_name,
        ]),
        'dates' => $begin->format('Ymd\THis') . '/' . $ending->format('Ymd\THis'),
//        'location' => t('Agence') . ' ' . $agency->get('name')->value,
        'sf' => TRUE,
        'output' => 'xml',
      ],
    ];
    $add_to_google_calendar = Url::fromUri("https://www.google.com/calendar/render", $options);
    return $add_to_google_calendar->toString();
  }
}
