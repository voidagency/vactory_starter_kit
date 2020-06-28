<?php

namespace Drupal\vactory_appbanners\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * {@inheritdoc}
 */
class SettingsForm extends ConfigFormBase {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a SettingsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler instance to use.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ModuleHandlerInterface $module_handler) {
    parent::__construct($config_factory);

    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'vactory_appbanners_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'vactory_appbanners.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config('vactory_appbanners.settings');

    $form['general'] = [
      '#type'  => 'fieldset',
      '#title' => $this->t('General Settings'),
    ];

    $form['general']['title'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Title'),
      '#description'   => $this->t('What the title of the app should be in the banner (defaults to &lt;title&gt;)'),
      '#default_value' => $config->get('title'),
    ];

    $form['general']['author'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Author'),
      '#description'   => $this->t('What the author of the app should be in the banner (defaults to &lt;meta name=&quot;author&quot;&gt; or hostname)'),
      '#default_value' => $config->get('author'),
    ];

    $form['general']['price'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Price'),
      '#description'   => $this->t('Price of the app'),
      '#default_value' => $config->get('price'),
    ];

    $form['general']['logo_upload'] = [
      '#type'              => 'managed_file',
      '#title'             => t('App Icon'),
      '#upload_location'   => 'public://vactory_appbanners/',
      '#upload_validators' => [
        'file_validate_is_image' => [],
      ],
      '#default_value'     => $config->get('icon') ? [$config->get('icon')] : [],
      '#description'       => $this->t('The URL of the icon (defaults to &lt;meta name=&quot;apple-touch-icon&quot;&gt;)'),
    ];

    $form['general']['button'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Button Text'),
      '#description'   => $this->t('Text for the install button'),
      '#default_value' => $config->get('button'),
    ];

    $form['general']['url'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('URL'),
      '#description'   => $this->t('The URL for the button. Keep null if you want the button to link to the app store.'),
      '#default_value' => $config->get('url'),
    ];

    $form['general']['days_hidden'] = [
      '#type'          => 'number',
      '#title'         => $this->t('Days Hidden'),
      '#description'   => $this->t('Duration to hide the banner after being closed (0 = always show banner)'),
      '#default_value' => $config->get('days_hidden'),
    ];

    $form['general']['days_reminder'] = [
      '#type'          => 'number',
      '#title'         => $this->t('Days Reminder'),
      '#description'   => $this->t('Duration to hide the banner after "VIEW" is clicked *separate from when the close button is clicked* (0 = always show banner)'),
      '#default_value' => $config->get('days_reminder'),
    ];

    $form['general']['hide_on_install'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Hide on install ?'),
      '#description'   => $this->t('Hide the banner after "VIEW" is clicked.'),
      '#default_value' => $config->get('hide_on_install', TRUE),
    ];

    $form['general']['layer'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Layer'),
      '#description'   => $this->t('Display as overlay layer or slide down the page'),
      '#default_value' => $config->get('layer', TRUE),
    ];

    $form['android'] = [
      '#type'  => 'fieldset',
      '#title' => $this->t('Android'),
    ];

    $form['android']['android_app_id'] = [
      '#type'          => 'textfield',
      '#required'      => TRUE,
      '#title'         => $this->t('App ID'),
      '#default_value' => $config->get('android_app_id'),
      '#attributes'    => [
        'placeholder' => 'com.google.samples.apps.iosched',
      ],
    ];

    $form['android']['in_google_play'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('In Google Play Text'),
      '#description'   => $this->t('Text of price for Android'),
      '#default_value' => $config->get('in_google_play'),
    ];

    $form['ios'] = [
      '#type'        => 'fieldset',
      '#title'       => $this->t('iOS'),
      '#description' => $this->t('The banner will only show for iOS version < 6 <br> <u><small>- Mozilla/5.0 (iPhone; U; CPU iPhone OS 4_3_5 like Mac OS X; en-us) AppleWebKit/533.17.9 (KHTML, like Gecko) Version/5.0.2 Mobile/8L1 Safari/6533.18.5
</small></u><br> See the <a href=":apple">Safari Web Content Guide</a> for more information on App Banners in iOS', [
        ':apple' => 'https://developer.apple.com/library/content/documentation/AppleApplications/Reference/SafariWebContent/PromotingAppswithAppBanners/PromotingAppswithAppBanners.html',
      ]),
    ];

    $form['ios']['ios_app_id'] = [
      '#type'          => 'textfield',
      '#required'      => TRUE,
      '#title'         => $this->t('App ID'),
      '#default_value' => $config->get('ios_app_id'),
      '#attributes'    => [
        'placeholder' => '544007664',
      ],
    ];

    $form['ios']['in_app_store'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('In App Store Text'),
      '#description'   => $this->t('Text of price for iOS'),
      '#default_value' => $config->get('in_app_store'),
    ];

    $form['ios']['app_store_language'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('App Store Language'),
      '#description'   => $this->t('Language code for App Store'),
      '#default_value' => $config->get('app_store_language'),
    ];

    $form['effects'] = [
      '#type'  => 'fieldset',
      '#title' => $this->t('Effets'),
    ];

    $form['effects']['scale'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Scale'),
      '#description'   => $this->t('Scale based on viewport size (set to 1 to disable)'),
      '#default_value' => $config->get('scale'),
    ];

    $form['effects']['speed_in'] = [
      '#type'          => 'number',
      '#title'         => $this->t('Speed In'),
      '#description'   => $this->t('Show animation speed of the banner'),
      '#default_value' => $config->get('speed_in'),
    ];

    $form['effects']['speed_out'] = [
      '#type'          => 'number',
      '#title'         => $this->t('Speed Out'),
      '#description'   => $this->t('Close animation speed of the banner'),
      '#default_value' => $config->get('speed_out'),
    ];

    $form['visibility'] = [
      '#type'        => 'fieldset',
      '#title'       => $this->t('Pages'),
      '#collapsible' => FALSE,
      '#collapsed'   => FALSE,
    ];

    $form['visibility']['admin'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Include app banners tags on admin pages?'),
      '#default_value' => $config->get('admin', FALSE),
    ];

    $form['visibility']['visibility'] = [
      '#type'          => 'radios',
      '#options'       => [
        'exclude' => $this->t('All pages except those listed'),
        'include' => $this->t('Only the listed pages'),
      ],
      '#default_value' => $config->get('visibility'),
    ];

    $form['visibility']['pages'] = [
      '#type'          => 'textarea',
      '#title'         => $this->t('Include script on specific pages'),
      '#default_value' => $config->get('pages'),
      '#description'   => $this->t("Specify pages by using their paths. Enter one path per line. The '*' character is a wildcard. Example paths are %blog for the blog page and %blog-wildcard for every personal blog. %front is the front page.",
        [
          '%blog'          => '/blog',
          '%blog-wildcard' => '/blog/*',
          '%front'         => '<front>',
        ]),
    ];

    return parent::buildForm($form, $form_state);

  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    //    if ($this->moduleHandler->moduleExists('file')) {
    //
    //      // Check for a new uploaded logo.
    //      if (isset($form['general']['logo_upload'])) {
    //        $file = _file_save_upload_from_form($form['general']['logo_upload'], $form_state, 0);
    //        if ($file) {
    //          // Put the temporary file in form_values so we can save it on submit.
    //          $form_state->setValue('logo_upload', $file);
    //        }
    //      }
    //    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    // If the user uploaded a new logo or favicon, save it to a permanent location
    // and use it in place of the default theme-provided file.
    if (!empty($values['logo_upload'])) {
      $values['icon'] = (int) $values['logo_upload'][0];

      // Set permanent.
      $file = File::load($values['icon']);
      $file->setPermanent();
      $file->save();
    }

    $this->config('vactory_appbanners.settings')
      ->set('title', $values['title'])
      ->set('author', $values['author'])
      ->set('icon', $values['icon'])
      ->set('price', $values['price'])
      ->set('button', $values['button'])
      ->set('url', $values['url'])
      ->set('days_hidden', $values['days_hidden'])
      ->set('days_reminder', $values['days_reminder'])
      ->set('hide_on_install', $values['hide_on_install'])
      ->set('layer', $values['layer'])
      ->set('android_app_id', $values['android_app_id'])
      ->set('in_google_play', $values['in_google_play'])
      ->set('ios_app_id', $values['ios_app_id'])
      ->set('in_app_store', $values['in_app_store'])
      ->set('app_store_language', $values['app_store_language'])
      ->set('scale', $values['scale'])
      ->set('speed_in', $values['speed_in'])
      ->set('speed_out', $values['speed_out'])
      ->set('admin', $values['admin'])
      ->set('visibility', $values['visibility'])
      ->set('pages', $values['pages'])
      ->save();

    parent::submitForm($form, $form_state);

  }

}
