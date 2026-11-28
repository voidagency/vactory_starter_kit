<?php

namespace Drupal\vactory_mailchimp_newsletter\Drush\Commands;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\vactory_mailchimp_newsletter\Services\CampaignsService;
use Drush\Commands\DrushCommands;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Drush commands of vactory_mailchimp_newsletter.
 *
 * Declared in src/Drush/Commands, the place Drush 12 and 13 look for them.
 */
class SendNewslettersCommands extends DrushCommands {

  /**
   * Constructs a SendNewslettersCommands object.
   */
  public function __construct(
    protected CampaignsService $campaigns,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected LoggerChannelFactoryInterface $loggerChannelFactory,
  ) {
    parent::__construct();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('vactory_mailchimp_newsletter.campaigns.manage'),
      $container->get('entity_type.manager'),
      $container->get('logger.factory'),
    );
  }

  /**
   * Sends Newsletters.
   *
   * @command send_newsletters
   * @aliases send_newsletters
   *
   * @usage send_newsletters
   */
  public function sendNewsletters() {
    $current_time = new \DateTime('now');
    $current_date = $current_time->format('Y-m-d\TH:i:s');
    // Get recipients list id.
    $list_id = $this->campaigns->mailchimpGetInfos()['list_id'];
    // Query to get all published newsletter.
    // Runs from cron/Drush: select every due newsletter, whoever runs it.
    $storage = $this->entityTypeManager->getStorage('node');
    $query = $storage->getQuery()->accessCheck(FALSE);
    $query->condition('type', 'newsletter');
    $query->condition('status', 1);
    $query->condition('field_vactory_datetime', $current_date, '<=');
    $newsletters = $query->execute();

    foreach ($newsletters as $newsletter) {
      // Load newsletter.
      $node = $storage->load($newsletter);
      $recurrence = isset($node->get('field_recurrence')->value) ? $node->get('field_recurrence')->value : '';
      $sending_date = $node->get('field_vactory_datetime')->value;

      // Send newsletter.
      $this->campaigns->mailchimpSendNewsletter($node, $list_id);

      switch ($recurrence) {
        case 'everyday':
          $sending_date = date('Y-m-d\TH:i:s', strtotime($sending_date . ' +1 day'));
          break;

        case 'every_week':
          $sending_date = date('Y-m-d\TH:i:s', strtotime($sending_date . ' +1 week'));
          break;

        case 'every_month':
          $sending_date = date('Y-m-d\TH:i:s', strtotime($sending_date . ' +1 month'));
          break;

        default:
          // Set newsletter to unpublished.
          $node->setUnpublished()->save();
          break;
      }
      $node->set('field_vactory_datetime', $sending_date);
      $node->save();
    }

    $this->loggerChannelFactory->get('bfig_mailchimp')->notice('Sending Newsletters has been completed.');
  }

}
