<?php

namespace Drupal\vactory_locator\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;

/**
 * Configure Locator Settings.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'vactory_locator_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['vactory_locator.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    // Get the form configuration object to set default value for each field.
    $config = $this->config('vactory_locator.settings');

    $form['api_keys'] = [
      '#type'  => 'details',
      '#title' => t('API keys'),
      '#group' => 'tabs',

    ];

    $form['api_keys']['map_api_key'] = [
      '#type'          => 'textfield',
      '#title'         => t('Google Maps API key'),
      '#default_value' => !empty($config->get('map_api_key')) ? $config->get('map_api_key') : '',
    ];

    $form['marker'] = [
      '#type'  => 'details',
      '#title' => t('Default marker'),
      '#group' => 'tabs',
      '#required' => TRUE,

    ];

    $form['marker']['locator_default_marker'] = [
      '#type'                => 'managed_file',
      '#title'               => t('Default Map marker'),
      '#upload_validators'   => [
        'file_validate_extensions' => ['png svg'],
        'file_validate_size'       => [25600000],
      ],
      '#theme'               => 'image_widget',
      '#preview_image_style' => 'medium',
      '#upload_location'     => 'public://locator/marker',
      '#required'            => TRUE,
      '#default_value'       => !empty($config->get('locator_default_marker')) ? $config->get('locator_default_marker') : '',
    ];

    $form['marker']['use_geolocation'] = [
      '#type' => 'checkbox',
      '#title' => t("Afficher l'itinéraire"),
      '#description' => t("Si cochée le bouton d'itinéraire va être ajouté à la map."),
      '#default_value' => !empty($config->get('use_geolocation')) ? $config->get('use_geolocation') : 0,
    ];

    $form['marker']['current_postion_container'] = [
      '#type' => 'container',
      '#states' => [
        'visible' => [
          [
            ':input[name="use_geolocation"]' => [
              'checked' => TRUE,
            ],
          ],
        ],
      ],
    ];

    $form['marker']['current_postion_container']['geolocation_marker'] = [
      '#type'                => 'managed_file',
      '#title'               => t('Current position marker'),
      '#upload_validators'   => [
        'file_validate_extensions' => ['png svg'],
        'file_validate_size'       => [25600000],
      ],
      '#theme'               => 'image_widget',
      '#preview_image_style' => 'medium',
      '#upload_location'     => 'public://locator/marker',
      '#default_value'       => !empty($config->get('geolocation_marker')) ? $config->get('geolocation_marker') : '',
    ];

    $form['filter'] = [
      '#type'  => 'details',
      '#title' => t('Filters settings'),
      '#group' => 'tabs',
    ];

    $form['filter']['enable_filter'] = [
      '#type' => 'checkbox',
      '#title' => t("Activer le filtre par catégories"),
      '#description' => t("Si cochée l'utilisateur final peut effectuer des filtres par catégories sur la map."),
      '#default_value' => !empty($config->get('enable_filter')) ? $config->get('enable_filter') : 0,
    ];

    $form['style'] = [
      '#type'  => 'details',
      '#title' => t('Map Json Style'),
      '#group' => 'tabs',
    ];

    $form['style']['map_style'] = [
      '#type'          => 'textarea',
      '#title'         => t('Custom Google Maps Style'),
      '#default_value' => !empty($config->get('map_style')) ? $config->get('map_style') : '',
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    /* Fetch the array of the file stored temporarily in database */
    $image = $form_state->getValue('locator_default_marker');

    /* Load the object of the file by it's fid */
    $file = File::load($image[0]);

    /* Set the status flag permanent of the file object */
    $file->setPermanent();

    /* Save the file in database */
    $file->save();

    $marker_url = \Drupal::service('stream_wrapper_manager')
      ->getViaUri($file->getFileUri())
      ->getExternalUrl();

    $geolocation_marker = $form_state->getValue('geolocation_marker');
    $url_geolocation_marker = NULL;

    if (isset($geolocation_marker[0]) && is_numeric($geolocation_marker[0])) {
      $file_geolocation = File::load($geolocation_marker[0]);
      if ($file_geolocation) {
        $file_geolocation->setPermanent();
        $file_geolocation->save();
        $url_geolocation_marker = \Drupal::service('stream_wrapper_manager')
          ->getViaUri($file_geolocation->getFileUri())
          ->getExternalUrl();
      }
    }

    $this->config('vactory_locator.settings')
      ->set('map_api_key', $form_state->getValue('map_api_key'))
      ->set('locator_default_marker_url', $marker_url)
      ->set('locator_default_marker', $form_state->getValue('locator_default_marker'))
      ->set('use_geolocation', $form_state->getValue('use_geolocation'))
      ->set('geolocation_marker', $form_state->getValue('geolocation_marker'))
      ->set('url_geolocation_marker', $url_geolocation_marker)
      ->set('map_style', $form_state->getValue('map_style'))
      ->set('enable_filter', $form_state->getValue('enable_filter'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
