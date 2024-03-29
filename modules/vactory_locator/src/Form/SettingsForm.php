<?php

namespace Drupal\vactory_locator\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
    $zooms = [];
    $zooms['nothing'] = '--Nothing--';
    // Get site default stream wrapper.
    $default_stream_wrapper = $this->configFactory
      ->get('system.file')
      ->get('default_scheme');
    foreach (range(1, 20) as $i) {
      $zooms[$i] = $i;
    }

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

    $default_image = $config->get('locator_default_marker');
    if (isset($default_image) && $default_image != NULL) {
      $is_it_media_library = Media::load($default_image);
    }
    else {
      $is_it_media_library = NULL;
    }

    $form['marker']['locator_default_marker'] = [
      '#type'                => 'media_library',
      '#title'               => t('Default Map marker'),
      '#allowed_bundles' => ['image'],
      '#upload_validators'   => [
        'file_validate_extensions' => ['png svg'],
        'file_validate_size'       => [25600000],
      ],
      '#upload_location'     => $default_stream_wrapper . '://locator/marker',
      '#required'            => TRUE,
      '#default_value'       => $is_it_media_library ? $config->get('locator_default_marker') : '',
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
      '#upload_location'     => $default_stream_wrapper . '://locator/marker',
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

    $form['place'] = [
      '#type'  => 'details',
      '#title' => t('Maps Position Setting'),
      '#group' => 'tabs',
    ];

    $form['place']['lat'] = [
      '#title' => $this->t('Latitude'),
      '#type' => 'textfield',
      '#size' => 18,
      '#default_value' => !empty($config->get('lat')) ? $config->get('lat') : '',
    ];

    $form['place']['lon'] = [
      '#title' => $this->t('Longitude'),
      '#type' => 'textfield',
      '#size' => 18,
      '#default_value' => !empty($config->get('lon')) ? $config->get('lon') : '',
    ];

    $form['place']['zoom'] = [
      '#type' => 'select',
      '#title' => $this->t('Zoom'),
      '#options' => $zooms,
      '#default_value' => !empty($config->get('zoom')) ? $config->get('zoom') : $zooms['nothing'],
    ];

    $form['page_path'] = [
      '#type'  => 'details',
      '#title' => t('Path Setting'),
      '#group' => 'tabs',
    ];

    $form['page_path']['path_url'] = [
      '#type' => 'textfield',
      '#title' => t('Path Locator Full Page'),
      '#description' => t('si rempli, le lien vers la page détaille va être /ur-custom-path/{agency-name}'),
      '#default_value' => !empty($config->get('path_url')) ? $config->get('path_url') : '',
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    /* Fetch the array of the file stored temporarily in database */
    $image = $form_state->getValue('locator_default_marker');

    /* Load image from the media library */
    $media = Media::load($image);

    if (isset($media) && !empty($media)) {

      /* Getting the file id */
      $fid = $media->field_media_image->target_id;

      /* Load the object of the file by it's fid */
      $file = File::load($fid);

      /* Set the status flag permanent of the file object */
      $file->setPermanent();

      /* Save the file in database */
      $file->save();

      $marker_url = \Drupal::service('stream_wrapper_manager')
        ->getViaUri($file->getFileUri())
        ->getExternalUrl();
    }

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
      ->set('lat', $form_state->getValue('lat'))
      ->set('lon', $form_state->getValue('lon'))
      ->set('zoom', $form_state->getValue('zoom'))
      ->set('path_url', $form_state->getValue('path_url'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
