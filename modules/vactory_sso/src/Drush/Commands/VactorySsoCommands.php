<?php

namespace Drupal\vactory_sso\Drush\Commands;

use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\vactory_sso\UnverifiedEmailTracker;
use Drush\Commands\DrushCommands;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Drush commands of vactory_sso.
 */
final class VactorySsoCommands extends DrushCommands {

  /**
   * Constructs a VactorySsoCommands object.
   */
  public function __construct(
    private readonly UnverifiedEmailTracker $tracker,
    private readonly EntityTypeManagerInterface $entityTypeManager,
  ) {
    parent::__construct();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('vactory_sso.unverified_email_tracker'),
      $container->get('entity_type.manager'),
    );
  }

  /**
   * Lists the SSO logins refused because the email is not verified.
   *
   * Since oauth_login_oauth2 3.1.1, the login is refused when the identity
   * provider does not report email_verified = true. Each refused address is
   * listed once, with the Drupal account that has this address.
   *
   * @command vactory-sso:unverified-emails
   * @aliases vsso-ue
   * @option reset Forget the recorded addresses.
   * @field-labels
   *   email: Email
   *   account: Drupal account
   *   claim: email_verified
   *   count: Refused
   *   first: First
   *   last: Last
   * @usage drush vactory-sso:unverified-emails
   *   Lists the refused addresses, most recent first.
   * @usage drush vactory-sso:unverified-emails --reset
   *   Forgets them, after the emails are verified on the identity provider.
   */
  public function unverifiedEmails(
    array $options = ['reset' => FALSE, 'format' => 'table'],
  ): ?RowsOfFields {
    if ($options['reset']) {
      $this->tracker->reset();
      $this->logger()->success('Recorded addresses forgotten.');
      return NULL;
    }
    $entries = $this->tracker->all();
    if (!$entries) {
      $this->logger()->success('No SSO login refused for an unverified email.');
      return NULL;
    }
    $storage = $this->entityTypeManager->getStorage('user');
    $rows = [];
    foreach ($entries as $email => $entry) {
      $ids = $storage->getQuery()->accessCheck(FALSE)->condition('mail', $email)->execute();
      $rows[] = [
        'email' => $email,
        'account' => $ids ? 'uid ' . implode(', ', $ids) : '-',
        'claim' => $entry['claim'],
        'count' => $entry['count'],
        'first' => date('Y-m-d H:i', $entry['first']),
        'last' => date('Y-m-d H:i', $entry['last']),
      ];
    }
    return new RowsOfFields($rows);
  }

}
