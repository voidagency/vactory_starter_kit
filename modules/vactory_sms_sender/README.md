### Vactory SMS Sender

Provide a flexible setting form to add different SMS APIs configurations,
the module also provide a service which you could use in your custom modules to send SMS:

    // A valid SMS Destination.
    $to = '2120700712177';
    $message = 'Hello world! This message is from Vactory SMS sender.';
    // User the service to send SMS.
    $sms_sender_manager = \Drupal::service('vactory_sms_sender.manager');
    // The method sendSms() returns TRUE if SMS has been sent successfully.
    // And it returns FALSE otherwise.
    $response = $sms_sender_manager->sendSms($to, $message);
    if ($response) {
      \Drupal::messenger()->addStatus(
        $this->t('SMS has been sent successfully')
      );
    }
    else {
      \Drupal::messenger()->addStatus(
        $this->t('An error occured while sending SMS, please try later')
      );
    }

#### Settings
On the settings form you would find two colored submit buttons:
* Configure according to Infobip: By clicking this button the module
will be preconfigured accoding to Infobip SMS service provider.
* Configure according to Twilio: By clicking this button the module
    will be preconfigured accoding to Twilio SMS service provider.

Of course you could configure the module according to another API provider
settings by fillup the module settings form manually.

The module has a submodule vactory_sms_sender_example you could enable it
to test the module configuration by sending an SMS.

#### Maintainers
Brahim KHOUY <b.khouy@void.fr>
