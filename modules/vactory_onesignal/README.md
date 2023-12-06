# Vactory Onesignal
Provides additional custom services to enhance onesignal contrib module

### Installation
`drush en vactory_onesignal -y`

### Configuration
Go to `/admin/config/services/onesignal` and set your Onesignal app ID
and Onesignal REST Api key.

### Fields
The module add new user field "User Device ID" (`field_user_device_ids`)
this field accepts multiple values, it is designed to store user
devices ids in order to send user notifications from Drupal to onesignal.

### Endpoints

* `/api/notifications/device_id/add`: This endpoint serves to post
authenticated user device ID (retrieved form onesignal subscription call)
to Drupal so it would be associated to the user entity.

### Push notification service
The module expose `vactory_onesignal.manager` service, developers
could use onesignalNotifyUsers methode to push notification to Onesignal:

    /**
     * Generate onesignal push notification.
     * 
     * @param array $headings
     *   Notification heading array with translations:
     *   Example: ['en' => 'Welcome', 'fr' => 'Bienvenue', 'ar' => 'مرحبا'].
     * @param array $contents
     *   Notification content array with translations:
     *   Example: ['en' => 'Here notif content',
     *             'fr' => 'Voilà le contenu de notif',
     *             'ar' => 'إليكم محتوى الإشعار'].
     * @param string $redirect_path
     *   Path to redirect user to when he clicks the notif.
     * @param array $drupal_users_ids
     *   Drupal concerned users ids if empty then the notif will be pushed to
     *   all subsribed devices.
     * @param array $subscription_ids
     *   Concerned onesignal subscription ids.
    */
    \Drupal\vactory_onesignal\Services\VactoryOnesignalManager::onesignalNotifyUsers(
        $headings, 
        $contents, 
        $redirect_path = '/', 
        array $drupal_users_ids = [], 
        array $subscription_ids = []
    );

### Test
After enbling and configuring the module you could visit the test form
on: `/admin/config/services/onesignal/test`
Enter the notif title and content then choose a user (the user device ids field
should already been set) and/or enter concerned subscriptions IDs then click 
"Generate & send Notification"

### Maintainers
Brahim KHOUY <b.khouy@void.fr>
