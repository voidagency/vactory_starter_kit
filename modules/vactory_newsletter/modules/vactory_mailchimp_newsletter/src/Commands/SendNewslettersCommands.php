<?php

namespace Drupal\vactory_mailchimp_newsletter\Commands;

use Drupal\node\Entity\Node;
use Drush\Commands\DrushCommands;

/**
 * A Drush commandfile.
 */
class SendNewslettersCommands extends DrushCommands {

  /**
   * {@inheritDoc}
   */
  public function __construct() {
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
    $list_id = \Drupal::service('vactory_mailchimp_newsletter.campaigns.manage')->mailchimpGetInfos()['list_id'];
    // Query to get all published newsletter.
    $query = \Drupal::entityQuery('node');
    $query->condition('type', 'newsletter');
    $query->condition('status', 1);
    $query->condition('field_vactory_datetime', $current_date, '<=');
    $newsletters = $query->execute();

    foreach ($newsletters as $key => $newsletter) {
      // Load newsletter.
      $node = Node::load($newsletter);
      $recurrence = isset($node->get('field_recurrence')->value) ? $node->get('field_recurrence')->value : '';
      $sending_date = $node->get('field_vactory_datetime')->value;

      // Send newsletter.
      \Drupal::service('vactory_mailchimp_newsletter.campaigns.manage')->mailchimpSendNewsletter($node, $list_id);

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

    \Drupal::logger('bfig_mailchimp')->notice('Sending Newsletters has been completed.');
  }

}
