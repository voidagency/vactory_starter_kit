<?php

/**
 * @file
 * Builds placeholder replacement tokens for calendar related data.
 */

use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Url;
use Drupal\Component\Datetime\DateTimePlus;

/**
 * Implements hook_token_info().
 */
function vactory_calendar_token_info() {
  $types = [
    'name' => t('Calendar Tokens'),
    'description' => t('Tokens related to calendar.'),
  // 'needs-data' => 'calendar_slot',
  ];

  $calendar['rdv-title'] = [
    'name' => t('RDV Title'),
    'description' => t('The RDV Title'),
  ];
  $calendar['rdv-owner'] = [
    'name' => t('RDV owner'),
    'description' => t('The RDV Owner'),
  ];

  $calendar['rdv-owner-fname'] = [
    'name' => t('RDV owner First name'),
    'description' => t('RDV owner First name'),
  ];

  $calendar['rdv-owner-lname'] = [
    'name' => t('RDV owner last name'),
    'description' => t('RDV owner last name'),
  ];

  $calendar['rdv-owner-username'] = [
    'name' => t('RDV owner username'),
    'description' => t('RDV owner username'),
  ];

  $calendar['rdv-owner-civilite'] = [
    'name' => t('RDV owner civilité'),
    'description' => t('RDV owner civilité'),
  ];
  $calendar['rdv-invited'] = [
    'name' => t('RDV invité'),
    'description' => t('Invited People'),
  ];

  $calendar['rdv-invited-fname'] = [
    'name' => t('RDV invité first name'),
    'description' => t('RDV invité first name'),
  ];
  $calendar['rdv-invited-lname'] = [
    'name' => t('RDV invité last name'),
    'description' => t('RDV invité last name'),
  ];
  $calendar['rdv-invited-username'] = [
    'name' => t('RDV invited username'),
    'description' => t('RDV invited username'),
  ];
  $calendar['rdv-invited-civilite'] = [
    'name' => t('RDV invited civilité'),
    'description' => t('RDV invited civilité'),
  ];

  $calendar['rdv-time'] = [
    'name' => t('Timing'),
    'description' => t('RDV timing'),
  ];

  $calendar['rdv-table'] = [
    'name' => t('Table'),
    'description' => t('RDV table'),
  ];

  $calendar['agenda-list'] = [
    'name' => t('Lien du Listing des RDV'),
    'description' => t('Lien du Listing des RDV'),
  ];
  $calendar['link-google-calendar'] = [
    'name' => t('Lien pour ajouter le RDV au Calendrier Google'),
    'description' => t('Lien pour ajouter le RDV au Calendrier Google'),
  ];

  $calendar['rdv-responsable'] = [
    'name' => t('Responsable du RDV'),
    'description' => t('RDV responsable'),
  ];
  return [
    'types' => ['vactory_calendar' => $types],
    'tokens' => ['vactory_calendar' => $calendar],
  ];
}

/**
 * Implements hook_tokens().
 */
function vactory_calendar_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {

  $replacements = [];

  if ($type === 'vactory_calendar' && !empty($data['calendar_slot'])) {
    /** @var \Drupal\vactory_calendar\Entity\CalendarSlotInterface $calendar */
    $calendar = $data['calendar_slot'];

    $entity_manager = \Drupal::service('entity_type.manager');
    $owner = $calendar?->getOwner();
    $invited = $calendar?->get('invited_user_id')->getValue();

    foreach ($tokens as $name => $original) {
      switch ($name) {
        // Basic user account information.
        case 'rdv-owner':
          $replacements[$original] = $owner->getEmail() ?? '';
          break;

        case 'rdv-title':
          $replacements[$original] = $calendar->getName() ?? '';
          break;

        case 'rdv-owner-fname':
          $replacements[$original] = $owner->get('field_first_name')->value ?? '';
          break;

        case 'rdv-owner-lname':
          $replacements[$original] = $owner->get('field_last_name')->value ?? '';
          break;

        case 'rdv-owner-username':
          $replacements[$original] = $owner->getAccountName() ?? '';
          break;
        
        case 'rdv-owner-civilite':
          if ($owner->hasField('field_civilite') && !$owner->get('field_civilite')->isEmpty()) {
            $term_id = $owner->get('field_civilite')->target_id;
            if ($term_id) {
              $term = \Drupal\taxonomy\Entity\Term::load($term_id);
              if ($term) {
                $field_code = $term->get('field_code')->value;
                $replacements[$original] = $field_code;
              } else {
                $replacements[$original] = "Term with id $term_id does not exist";
              }
            } else {
              $replacements[$original] = "field_civilite is empty for this user";
            }
          } else {
            $replacements[$original] = "User does not have field_civilite field or it's empty";
          }
          break;

        case 'rdv-invited':
          $invited_emails = array_map(static function ($el) use ($entity_manager) {
            return $entity_manager->getStorage('user')->load($el['target_id'])?->getEmail() ?? '';
          }, $invited);
          $replacements[$original] = implode(';', $invited_emails);
          break;

        case 'rdv-invited-fname':
          $invited_emails = array_map(static function ($el) use ($entity_manager) {
            return $entity_manager->getStorage('user')->load($el['target_id'])?->get('field_first_name')->value ?? '';
          }, $invited);
          $replacements[$original] = implode(';', $invited_emails);
          break;

        case 'rdv-invited-lname':
          $invited_emails = array_map(static function ($el) use ($entity_manager) {
            return $entity_manager->getStorage('user')->load($el['target_id'])?->get('field_last_name')->value ?? '';
          }, $invited);
          $replacements[$original] = implode(';', $invited_emails);
          break;

        case 'rdv-invited-username':
          $invited_emails = array_map(static function ($el) use ($entity_manager) {
            return $entity_manager->getStorage('user')->load($el['target_id'])?->getAccountName() ?? '';
          }, $invited);
          $replacements[$original] = implode(';', $invited_emails);
          break;

        case 'rdv-invited-civilite':
          // $invited_emails = array_map(static function ($el) use ($entity_manager) {
          //   return $entity_manager->getStorage('user')->load($el['target_id'])?->get('field_civilite')->value ?? '';
          // }, $invited);
          // $replacements[$original] = implode(';', $invited_emails);
          $invited_civilites = array_map(static function ($el) use ($entity_manager) {
            $user = $entity_manager->getStorage('user')->load($el['target_id']);
            if ($user) {
              $term_id = $user->get('field_civilite')->target_id;
              if ($term_id) {
                $term = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($term_id);
                return $term->get('field_code')->value;
              }
            }
            return '';
          }, $invited);
          $replacements[$original] = implode(';', $invited_civilites);
          break; 

        
        case 'rdv-time':
          //it Return it like this : Le 28 June/juin 2023 de 02h04 à 02h11
          $start = $calendar?->get('start_time')->value;
          $end = $calendar?->get('end_time')->value;
          $dateTimeStart = new DateTimePlus($start);
          $dateTimeEnd = new DateTimePlus($end);

          $day = $dateTimeStart->format('j');
          // $month = $dateTimeStart->format('n');
          $year = $dateTimeStart->format('Y');
          $start = $dateTimeStart->format('H\hi');
          $end = $dateTimeEnd->format('H\hi');

          $translatedMonth = \Drupal::service('date.formatter')->format($dateTimeStart->getTimestamp(), 'custom', 'F');
          $translatedDay = t('@day @month @year', ['@day' => $day, '@month' => $translatedMonth, '@year' => $year]);

          $replacements[$original] = t('Le @day de @start à @end', ['@day' => $translatedDay, '@start' => $start, '@end' => $end]);

          // It Return it like this : Le 28-06-2023 de 02:04 à 02:08
          // $start = $calendar?->get('start_time')->value;
          // $end = $calendar?->get('end_time')->value;
          // $dateTimeStart = new DateTime($start);
          // $dateTimeEnd = new DateTime($end);
          // $day = $dateTimeStart->format('d-m-Y');
          //   $start = $dateTimeStart->format('H:i');
          //   $end = $dateTimeEnd->format('H:i');
          //   $replacements[$original] = t('Le @day de @start à @end',
          //     ['@day' => $day, '@start' => $start, '@end' => $end]);
          break;

        case 'rdv-table':
          $table = $calendar?->get('field_table_du_rdv')->getValue() ?? [];
          $id = '';
          if ($table) {
            $id = $table[0]['target_id'] ?? '';
          }

          $table = $entity_manager->getStorage('taxonomy_term')?->load($id)?->getName() ?? '';
          $replacements[$original] = t('Votre table : @table', ['@table' => $table]);
          break;
        case 'agenda-list':
          $route = \Drupal::entityTypeManager()->getStorage('vactory_route')->load('mon_agenda');
          $url = Url::fromUserInput(($route?->get('alias')), ['absolute' => true])->toString();
          $replacements[$original] = $url;
          break;
        case 'link-google-calendar':
          $link = \Drupal::service('vactory_calendar.table_reservation')->googleCalendarLink($calendar);
          $replacements[$original] = $link;
          break;

        case 'rdv-responsable':
          $responsable = $calendar?->get('field_responsable_commercial')->getValue() ?? [];
          $id = '';
          if ($responsable) {
            $id = $responsable[0]['target_id'] ?? '';
          }
          $responsable = $entity_manager->getStorage('user')?->load($id)?->getEmail() ?? '';
          $replacements[$original] = $responsable ? t('Responsable : @mail', ['@mail' => $responsable]) : '';
          break;
      }
    }

  }

  return $replacements;
}
