<?php

namespace Drupal\vactory_calendar\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Renderer;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure Calendar Settings.
 */
class SettingsForm extends ConfigFormBase {
  /**
   * {@inheritdoc}
   */
  private $rendrer;

  /**
   * {@inheritdoc}
   */
  public function __construct(Renderer $rendrer, ConfigFactoryInterface $config) {
    parent::__construct($config);
    $this->rendrer = $rendrer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('renderer'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'vactory_calendar.settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['vactory_calendar.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Get the form configuration object to set default value for each field.
    $config = $this->config('vactory_calendar.settings');

    // Declare necessary tabs.
    $form['tabs'] = [
      '#type' => 'vertical_tabs',
    ];

    $form['mailings'] = [
      '#type' => 'details',
      '#title' => $this->t('Calendar Mailing System'),
      '#group' => 'tabs',

    ];

    $form['time_boundes'] = [
      '#type' => 'details',
      '#title' => $this->t('Calendar Boundries'),
      '#group' => 'tabs',
    ];

    $form['mailjet'] = [
      '#type' => 'details',
      '#title' => $this->t('Mailjet Service API'),
      '#group' => 'tabs',
    ];

    $form['mailgun'] = [
      '#type' => 'details',
      '#title' => $this->t('MailGun Service API Fallback'),
      '#group' => 'tabs',
    ];
    $form['mailjet']['api_key'] = [
      '#title' => $this->t("Mailjet API key"),
      '#type' => 'textfield',
      '#default_value' => empty($config->get('api_key')) ? '' : $config->get('api_key'),
      '#description' => $this->t('Leave Empty to use SMTP config'),
    ];
    $form['mailjet']['api_secret'] = [
      '#title' => $this->t("Mailjet API secret"),
      '#type' => 'textfield',
      '#default_value' => empty($config->get('api_secret')) ? '' : $config->get('api_secret'),
      '#description' => $this->t('Leave Empty to use SMTP config'),
    ];

    $form['mailgun']['mailgun_api_key'] = [
      '#title' => $this->t("MailGun API key"),
      '#type' => 'textfield',
      '#default_value' => empty($config->get('mailgun_api_key')) ? '' : $config->get('mailgun_api_key'),
      '#description' => $this->t('Leave Empty to use SMTP config'),
    ];
    $form['mailgun']['mailgun_api_secret'] = [
      '#title' => $this->t("Mailjet API secret"),
      '#type' => 'textfield',
      '#default_value' => empty($config->get('mailgun_api_secret')) ? '' : $config->get('mailgun_api_secret'),
      '#description' => $this->t('Leave Empty to use SMTP config'),
    ];
    $form['mailgun']['mailgun_domain'] = [
      '#title' => $this->t("MailGun domain"),
      '#type' => 'textfield',
      '#default_value' => empty($config->get('mailgun_domain')) ? '' : $config->get('mailgun_domain'),
      '#description' => $this->t('Leave Empty to use SMTP config'),
    ];

    $form['mailings']['invitation_mail'] = [
      '#type' => 'text_format',
      '#format' => 'email_html',
      '#allowed_formats' => ['email_html'],
      '#title' => $this->t("Contenu du mail d'invitation"),
      '#description' => $this->t('Laissez vide et la notification par email ne sera pas envoyée <br/><br/><br/>'),
      '#attributes' => [
        'class' => ['js-form-item'],
        'placeholder' => $this->t('Saisissez le message à envoyer à la personne qui à reçu une invitation pour un RDV'),
      ],
      '#default_value' => !empty($config->get('invitation_mail')) ? $config->get('invitation_mail') : '',
    ];

    $form['mailings']['confirmation_mail'] = [
      '#type' => 'text_format',
      '#format' => 'email_html',
      '#allowed_formats' => ['email_html'],
      '#title' => $this->t('Contenu du mail de confirmation'),
      '#description' => $this->t('Laissez vide et la notification par email ne sera pas envoyée <br/><br/><br/>'),
      '#attributes' => [
        'class' => ['js-form-item'],
        'placeholder' => $this->t('Saisissez le message à envoyer à la personne après confirmation du RDV'),
      ],
      '#default_value' => !empty($config->get('confirmation_mail')) ? $config->get('confirmation_mail') : '',
    ];

    $form['mailings']['annulation_mail'] = [
      '#type' => 'text_format',
      '#format' => 'email_html',
      '#allowed_formats' => ['email_html'],
      '#title' => $this->t("Contenu du mail d'annulation"),
      '#description' => $this->t('Laissez vide et la notification par email ne sera pas envoyée <br/><br/><br/>'),
      '#attributes' => [
        'class' => ['js-form-item'],
        'placeholder' => $this->t('Saisissez le message à envoyer à la personne après annulation du RDV'),
      ],
      '#default_value' => !empty($config->get('annulation_mail')) ? $config->get('annulation_mail') : '',
    ];

    $form['mailings']['table_reserved'] = [
      '#type' => 'text_format',
      '#format' => 'email_html',
      '#allowed_formats' => ['email_html'],
      '#title' => $this->t("Contenu du mail de la réservation d'une table"),
      '#description' => $this->t('Laissez vide et la notification par email ne sera pas envoyée <br/><br/><br/>'),
      '#attributes' => [
        'class' => ['js-form-item'],
        'placeholder' => $this->t('Saisissez le message à envoyer quand une table est résérvé pour le RDV'),
      ],
      '#default_value' => !empty($config->get('table_reserved')) ? $config->get('table_reserved') : '',
    ];

    $default_time_begin = !empty($config->get('begin')) ? $config->get('begin') : NULL;
    $default_time_end = !empty($config->get('end')) ? $config->get('end') : NULL;

    $form['time_boundes']['begin'] = [
      '#type' => 'datetime',
      '#title' => $this->t('Début des RDVs'),
      '#date_date_element' => 'none',
      '#date_time_element' => 'time',
      '#description' => $this->t('Choisir le temps pour débuter les RDVs <br/><br/><br/>'),
      '#attributes' => [
        'class' => ['js-form-item'],
      ],
      '#default_value' => $default_time_begin ? new DrupalDateTime($default_time_begin) : NULL,
    ];

    $form['time_boundes']['end'] = [
      '#type' => 'datetime',
      '#title' => $this->t('Fin des RDVs'),
      '#date_date_element' => 'none',
      '#date_time_element' => 'time',
      '#description' => $this->t('Choisir le temps pour Clôturer les RDVs <br/><br/><br/>'),
      '#attributes' => [
        'class' => ['js-form-item'],
      ],
      '#default_value' => $default_time_end ? new DrupalDateTime($default_time_end) : NULL,
    ];

    $form['time_boundes']['interval'] = [
      '#type' => 'textfield',
      '#title' => $this->t("Durée d'un RDV en min"),
      '#description' => $this->t("Choisir la Durée d' un RDV en minutes <br/><br/><br/>"),
      '#attributes' => [
        'class' => ['js-form-item'],
      ],
      '#default_value' => !empty($config->get('interval')) ? $config->get('interval') : '',
    ];

    $form['tokens'] = $this->addTokens();

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    /*
     * To do: add some fields validation in case a field
     * need validation before submitting.
     */

    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $leftBound = $form_state->getValue('begin')?->format('H:i:s');
    $rightBound = $form_state->getValue('end')?->format('H:i:s');

    $this->config('vactory_calendar.settings')
      ->set('invitation_mail', $form_state->getValue('invitation_mail')['value'])
      ->set('confirmation_mail', $form_state->getValue('confirmation_mail')['value'])
      ->set('begin', $leftBound)
      ->set('end', $rightBound)
      ->set('interval', $form_state->getValue('interval'))
      ->set('annulation_mail', $form_state->getValue('annulation_mail')['value'])
      ->set('table_reserved', $form_state->getValue('table_reserved')['value'])
      ->set('api_key', $form_state->getValue('api_key'))
      ->set('api_secret', $form_state->getValue('api_secret'))
      ->set('mailgun_api_key', $form_state->getValue('mailgun_api_key'))
      ->set('mailgun_api_secret', $form_state->getValue('mailgun_api_secret'))
      ->set('mailgun_domain', $form_state->getValue('mailgun_domain'))
      ->save();
    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function addTokens() {
    $token_tree = [
      '#theme' => 'token_tree_link',
      '#show_restricted' => TRUE,
      '#weight' => 90,
    ];
    return [
      '#type' => 'markup',
      '#markup' => $this->rendrer->render($token_tree),
    ];
  }

}
