# Vactory Notifications 

Vactory notifications module enhance Vactory project with a notifications entity
to generate notifications when a new content is created.


## Table of Contents
 * [Installation](#installation)
 * [Configuration](#configuration)
 * [Permissions](#permissions)
 * [Tokens](#tokens)
 * [Maintainers](#maintainers)

## Installation
Enable the module with the following drush command:

    drush en vactory_notifications -y
After enabling the module go to `/admin/config/regional/content-language` and enable
notifications entity content translation.
## Configuration

The module configurtion page is accessible via:

    /admin/config/system/notifications-configuration

#### Content settings
In the content settings you can set the default values of notifications title and
message also you can choose if the notifications should be automatically translated or not.

Use Notification lifetime field to set days number from the notification created date 
after which this notification is deleted in the next cron call, by default it's set to 6 days.

#### Roles settings
In this stage of configuration you can attach to each role the concerned
content types (users who have such role can recieve notifications from enabled
content types of that role)

The module configs are translated on: 

    /admin/config/system/notifications-configuration/translate

## Permissions
The module defines the following permissions:

Administer notifications: Adminisrate notifications configs and content.

Add notifications : Create new notifications.

Edit notifications: Edit existing notifications.

Delete notifications: Delete notifications.

View notifications: View the notifications list, this permission is used to control access
to the notifications view results on notification listing page (`/notifications`).

## Tokens
The module defines its own tokens to create notifications with dynamic title and message
content, just click the link "Brows available tokens" showed in different module config pages,
and in the token name 'Notifications' group choose the needed tokens.

## Clean expired notifications
You can execute the following drush command to clean expired notifications entities:

`drush cen`

You could also add this command to your crontab conf file.

## Loom demo video
https://www.loom.com/share/a8b79aa700d14d7cb9af3f46a29cfeaf

## Maintainers
Brahim KHOUY
<b.khouy@void.fr>
