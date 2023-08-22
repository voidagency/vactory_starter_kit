# Vactory Calendar

This module enables users to schedule an appointment with another user. After selecting a user from the participants page, users can choose a slot to set the appointment. Once confirmed, an email is dispatched to both users, notifying them of the scheduled meeting.

## Table of Contents
- [Requirements](#requirements)
- [Installation](#installation)
- [Configuration](#configuration)
- [Extends](#extends)
- [API](#api)
- [Todo](#todo)
- [Troubleshooting & FAQ](#troubleshooting-faq)
- [Maintainers](#maintainers)

## Requirements

Dependencies:
  - drupal:content_translation
  - drupal:datetime
  - drupal:field
  - drupal:language
  - drupal:options
  - drupal:path
  - drupal:taxonomy
  - drupal:text
  - drupal:user
  - field_group:field_group
  - jsonapi_extras:jsonapi_defaults
  - jsonapi_extras:jsonapi_extras
  - ultimate_cron:ultimate_cron
  - vactory_decoupled:vactory_decoupled
  - symfony_mailer:symfony_mailer

## Installation

Activate the module using the following Drush command:

    drush en -y vactory_calendar

## Configuration

For end-users:
   > - Once logged in, navigate to `fr/participants/liste` to select a user.
   > - After choosing a user, you'll be redirected to `fr/agenda?target=idUSER` to pick a time slot.
   > - Review your appointments at `/fr/my-agenda`.

For Webmasters:
   > - To set the calendar timings and details, visit `fr/admin/config/system/calendar-configuration`.
   > - Configure the email settings at `fr/admin/config/system/calendar-configuration`. Available tokens can be used to customize the email content.
   > - Modify, delete, or view the list of appointments at `/fr/admin/structure/calendar_slot`.
   > - Pages:
    >> - 'participant_profile' => ['path' => '/account/profil', 'title' => 'Participant Profile']
    >> - 'participant_agenda' => ['path' => '/agenda', 'title' => 'Participant's Agenda']
    >> - 'participants_list' => ['path' => '/participants/liste', 'title' => 'Participants']
        >>> - Template: List of Participants || Category: Calendar utilities
    >> - 'my_agenda' => ['path' => '/my-agenda', 'title' => 'My Agenda']
        >>> - Template: Link with icon || Category: Calendar utilities
            >>>> - Config => Icon ID:calendar || Link title: Access My Agenda || Link URL: /agenda

**NOTE**: Any changes to this module require an export of the feature. Please pay close attention to dependencies and remember to update this file (README.md) whenever there's a change.

## Extends

None

## API

None

## Todo

This module could benefit from improvements and optimizations:
   > - Replace field.field.user.user.field_pays with a pays-type field (vactory).

## Troubleshooting & FAQ

None

## Maintainers

- Hamza HASBI 
  <h.hasbi@void.fr>

- Fahd BOUAICHA 
  <f.bouaicha@void.fr>
