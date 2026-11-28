<?php

namespace Drupal\vactory_mailchimp_newsletter\Commands;

use Drupal\vactory_mailchimp_newsletter\Drush\Commands\SendNewslettersCommands as DrushSendNewslettersCommands;

/**
 * Former location of the send_newsletters command.
 *
 * Kept until 3.4 for code that calls the newsletters_send.commands service
 * or this class; the command itself is declared in
 * \Drupal\vactory_mailchimp_newsletter\Drush\Commands\SendNewslettersCommands.
 */
class SendNewslettersCommands extends DrushSendNewslettersCommands {

}
