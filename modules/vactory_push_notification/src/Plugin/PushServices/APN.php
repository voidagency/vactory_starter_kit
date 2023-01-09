<?php

namespace Drupal\vactory_push_notification\Plugin\PushServices;

use Drupal\vactory_push_notification\PushServiceBase;
use GuzzleHttp\Psr7\Request;
use Drupal\Core\Form\FormStateInterface;
use Jose\Component\Core\AlgorithmManager;
use Jose\Component\KeyManagement\JWKFactory;
use Jose\Component\Signature\JWSBuilder;
use Jose\Component\Signature\Algorithm\ES256;
use Jose\Component\Signature\Serializer\CompactSerializer;


/**
 * Provides APN send functionality.
 *
 * @PushService(
 *   id = "APN",
 *   title = @Translation("Send notifications to APN."),
 * )
 */
class APN extends PushServiceBase
{

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state)
    {
        $config = $this->config('vactory_push_notification.apn');

        $form['apn'] = [
            '#type' => 'details',
            '#open' => true,
            '#title' => $this->t('APN parameters'),
        ];

        $form['apn']['apn_bundle_identifier'] = [
            '#type' => 'textfield',
            '#title' => $this->t('APN Bundle Identifier'),
            '#default_value' => $config->get('apn_bundle_identifier'),
            '#required' => TRUE,
        ];

        $form['apn']['apn_key_id'] = [
            '#type' => 'textfield',
            '#title' => $this->t('APN Key ID'),
            '#default_value' => $config->get('apn_key_id'),
            '#required' => TRUE,
        ];

        $form['apn']['apn_team'] = [
            '#type' => 'textfield',
            '#title' => $this->t('APN Team ID'),
            '#default_value' => $config->get('apn_team'),
            '#required' => TRUE,
        ];

        $form['apn']['apn_p8_file'] = [
            '#type' => 'textarea',
            '#title' => $this->t('APN P8 File content'),
            '#default_value' => $config->get('apn_p8_file'),
            '#required' => TRUE,
        ];

        $form['apn']['apn_prod_env']  = [
            '#type' => 'checkbox',
            '#title' => $this->t('Enable production'),
            '#default_value' => $config->get('apn_prod_env'),
            '#description' => $this->t('By default we use Sandbox, check this to enable production service.'),
        ];

        return $form;
    }

    /**
     * Form submit callback for confirm keys regeneration.
     */
    public function saveForm(array &$form, FormStateInterface $form_state)
    {
        \Drupal::configFactory()->getEditable('vactory_push_notification.apn')
            ->set('apn_bundle_identifier', $form_state->getValue('apn_bundle_identifier'))
            ->set('apn_key_id', $form_state->getValue('apn_key_id'))
            ->set('apn_team', $form_state->getValue('apn_team'))
            ->set('apn_p8_file', $form_state->getValue('apn_p8_file'))
            ->set('apn_prod_env', $form_state->getValue('apn_prod_env'))
            ->save();

        // \Drupal::messenger()->addStatus($this->t('APN settings have been updated.'));
    }

    /**
     * {@inheritdoc}
     */
    public function getRequest($data)
    {
        // todo: need to cache jwt token.
        // need more info about how this APN token works... expires
        // Should we go atleast with some static func calls ?
        // Or go with some advanced techniques.
        $config = $this->config('vactory_push_notification.apn');
        $secret_key = $config->get('apn_p8_file');
        $teamId = $config->get('apn_team');
        $apn_key_id = $config->get('apn_key_id');
        $package_id = $config->get('apn_bundle_identifier');
        $is_production = (bool) $config->get('apn_prod_env');

        $algorithmManager = new AlgorithmManager([
            new ES256()
        ]);

        $jwk = JWKFactory::createFromKey($secret_key);
        $jwsBuilder = new JWSBuilder($algorithmManager);

        $payload = json_encode([
            'iat' => time(),
            'iss' => $teamId,
        ]);

        $jws = $jwsBuilder
            ->create()
            ->withPayload($payload)
            ->addSignature($jwk, ['alg' => 'ES256', 'kid' => $apn_key_id])
            ->build();

        $serializer = new CompactSerializer();
        $token = $serializer->serialize($jws, 0);

        $serviceUrl = $is_production ? 'https://api.push.apple.com' : 'https://api.sandbox.push.apple.com';
        $serviceUrl .= '/3/device/' . $data['token'];

        $headers = [
            'apns-topic' => $package_id,
            // 'apns-push-type' => 'alert',
            'Authorization' => 'Bearer ' . trim($token)
        ];

        $notification_payload = json_decode($data['payload'], TRUE);

        $content = json_encode([
            'aps' => [
                'alert' => [
                    "title" => $notification_payload['title'],
                    "body" => $notification_payload['body'],
                    "link" => [
                        "type" => "screen",
                        "param" => [
                            "foo" => "bar",
                            "service" => "APN",
                        ],
                        "path" => "NotFound"
                    ],
                ]
            ]
        ]);

        return new Request('POST', $serviceUrl, $headers, $content, '2.0'); // 2.0 for http/2
    }
}
