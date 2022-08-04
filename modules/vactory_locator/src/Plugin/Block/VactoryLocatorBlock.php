<?php

namespace Drupal\vactory_locator\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Url;
use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;

/**
 * Provides a "Vactory Locator Block " block.
 *
 * @Block(
 *   id = "vactory_locator_block",
 *   admin_label = @Translation("Vactory Locator Block"),
 *   category = @Translation("Vactory")
 * )
 */
class VactoryLocatorBlock extends BlockBase implements BlockPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);
    // Get site default stream wrapper.
    $default_stream_wrapper = \Drupal::config('system.file')
      ->get('default_scheme');
    $config = $this->getConfiguration();
    $terms = $term = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadByProperties(['vid' => 'locator_category']);
    $options = [
      'all' => t('ALL'),
    ];
    foreach ($terms as $term) {
      $options[$term->tid->value] = $term->name->value;
    }
    $form['locator_category'] = [
      '#type' => 'select',
      '#multiple' => TRUE,
      '#title' => $this->t('Item Category'),
      '#description' => $this->t('Choose item category if you want to display a specific category of items in map'),
      '#options' => $options,
      '#default_value' => isset($config['locator_category']) ? $config['locator_category'] : '',
    ];

    $form['google_places'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable google places input search'),
      '#default_value' => isset($config['google_places']) ? $config['google_places'] : 0,
    ];

    $form['google_places_mixte'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use both google places search input and agencies search input'),
      '#default_value' => isset($config['google_places_mixte']) ? $config['google_places_mixte'] : 0,
      '#states' => [
        'visible' => [
          'input[name="settings[google_places]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['activate_overlay'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Activer overlay'),
      '#default_value' => isset($config['activate_overlay']) ? $config['activate_overlay'] : 0,
    ];

    $form['overlay'] = [
      '#type'  => 'details',
      '#title' => t('Overlay Settings'),
      '#group' => 'tabs',
    ];

    $form['overlay']['picture_overlay'] = [
      '#type' => 'media_library',
      '#title' => t('picture overlay'),
      '#allowed_bundles' => ['image'],
      '#upload_validators' => [
        'file_validate_extensions' => ['png jpg jpeg gif'],
        'file_validate_size' => [25600000],
      ],
      '#required' => FALSE,
      '#default_value' => isset($config['picture_overlay']) ? $config['picture_overlay'] : '',
    ];

    $form['overlay']['picture_overlay_mobile'] = [
      '#type' => 'media_library',
      '#title' => t('picture overlay mobile'),
      '#allowed_bundles' => ['image'],
      '#upload_validators' => [
        'file_validate_extensions' => ['png jpg jpeg gif'],
        'file_validate_size' => [25600000],
      ],
      '#required' => FALSE,
      '#default_value' => isset($config['picture_overlay_mobile']) ? $config['picture_overlay_mobile'] : '',
    ];

    $form['overlay']['btn_overlay'] = [
      '#type' => 'textfield',
      '#title' => $this->t('button overlay titre'),
      '#default_value' => isset($config['btn_overlay']) ? $config['btn_overlay'] : '',
      '#required' => FALSE,
    ];

    $form['map_markers'] = [
      '#type'  => 'details',
      '#title' => t('Map markers settings'),
      '#group' => 'tabs',
    ];

    $form['map_markers']['locator_marker'] = [
      '#type' => 'media_library',
      '#title' => t('Map marker'),
      '#allowed_bundles' => ['image'],
      '#upload_validators' => [
        'file_validate_extensions' => ['png svg'],
        'file_validate_size' => [25600000],
      ],
      '#upload_location' => $default_stream_wrapper . '://locator/marker',
      '#required' => FALSE,
      '#default_value' => isset($config['locator_marker']) ? $config['locator_marker'] : '',
    ];

    $form['map_markers']['locator_marker_height'] = [
      '#type' => 'number',
      '#title' => t('Map marker height'),
      '#description' => $this->t('Size should be written in px'),
      '#min' => 0,
      '#required' => FALSE,
      '#default_value' => isset($config['locator_marker_height']) ? $config['locator_marker_height'] : '',
    ];

    $form['map_markers']['locator_marker_width'] = [
      '#type' => 'number',
      '#title' => t('Map marker width'),
      '#description' => $this->t('Size should be written in px'),
      '#min' => 0,
      '#required' => FALSE,
      '#default_value' => isset($config['locator_marker_width']) ? $config['locator_marker_width'] : '',
    ];

    $form['map_markers']['locator_cluster'] = [
      '#type' => 'media_library',
      '#title' => t('Map marker cluster'),
      '#allowed_bundles' => ['image'],
      '#upload_validators' => [
        'file_validate_extensions' => ['png svg'],
        'file_validate_size' => [25600000],
      ],
      '#upload_location' => $default_stream_wrapper . '://locator/marker_cluster',
      '#required' => FALSE,
      '#default_value' => isset($config['locator_cluster']) ? $config['locator_cluster'] : '',
    ];

    $form['map_markers']['locator_cluster_height'] = [
      '#type' => 'number',
      '#title' => t('Map marker cluster height'),
      '#description' => $this->t('Size should be written in px'),
      '#min' => 0,
      '#required' => FALSE,
      '#default_value' => isset($config['locator_cluster_height']) ? $config['locator_cluster_height'] : '',
    ];

    $form['map_markers']['locator_cluster_width'] = [
      '#type' => 'number',
      '#title' => t('Map marker cluster width'),
      '#description' => $this->t('Size should be written in px'),
      '#min' => 0,
      '#required' => FALSE,
      '#default_value' => isset($config['locator_cluster_width']) ? $config['locator_cluster_width'] : '',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    /* to get the values for the colapse fields */
    $values = $form_state->getValues();

    if ($values['map_markers']['locator_marker']) {
      /* Fetch the array of the file stored temporarily in database */
      // $image = $form_state->getValue('locator_marker');
      $image = $values['map_markers']['locator_marker'];

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

        $marker_url = file_url_transform_relative(\Drupal::service('stream_wrapper_manager')->getViaUri($file->getFileUri())->getExternalUrl());

      }
    }

    if ($image = $values['map_markers']['locator_cluster']) {
      // $image = $form_state->getValue('locator_cluster');
      $image = $values['map_markers']['locator_cluster'];
      $media = Media::load($image);
      if (isset($media) && !empty($media)) {
        $fid = $media->field_media_image->target_id;
        $file = File::load($fid);
        $file->setPermanent();
        $file->save();
        $cluster_url = file_url_transform_relative(\Drupal::service('stream_wrapper_manager')->getViaUri($file->getFileUri())->getExternalUrl());
      }
    }

    if ($values['overlay']['picture_overlay']) {
      $image = $values['overlay']['picture_overlay'];
      $media = Media::load($image);
      if (isset($media) && !empty($media)) {
        $fid = $media->field_media_image->target_id;
        $file = File::load($fid);
        $file->setPermanent();
        $file->save();
        $overlay_url = file_url_transform_relative(\Drupal::service('stream_wrapper_manager')->getViaUri($file->getFileUri())->getExternalUrl());
      }
    }

    if ($values['overlay']['picture_overlay_mobile']) {
      $image = $values['overlay']['picture_overlay_mobile'];
      $media = Media::load($image);
      if (isset($media) && !empty($media)) {
        $fid = $media->field_media_image->target_id;
        $file = File::load($fid);
        $file->setPermanent();
        $file->save();
        $overlay_url_mobile = file_url_transform_relative(\Drupal::service('stream_wrapper_manager')->getViaUri($file->getFileUri())->getExternalUrl());
      }
    }
    $this->configuration['locator_category'] = $form_state->getValue('locator_category');
    $this->configuration['locator_marker'] = $values['map_markers']['locator_marker'];
    $this->configuration['locator_marker_url'] = (isset($marker_url) && !empty($marker_url)) ? $marker_url : '';
    $this->configuration['locator_marker_height'] = $values['map_markers']['locator_marker_height'];
    $this->configuration['locator_marker_width'] = $values['map_markers']['locator_marker_width'];
    $this->configuration['locator_cluster'] = $values['map_markers']['locator_cluster'];
    $this->configuration['locator_cluster_url'] = (isset($cluster_url) && !empty($cluster_url)) ? $cluster_url : '';
    $this->configuration['locator_cluster_height'] = $values['map_markers']['locator_cluster_height'];
    $this->configuration['locator_cluster_width'] = $values['map_markers']['locator_cluster_width'];
    $this->configuration['picture_overlay'] = $values['overlay']['picture_overlay'];
    $this->configuration['picture_overlay_url'] = (isset($overlay_url) && !empty($overlay_url)) ? $overlay_url : '';
    $this->configuration['picture_overlay_mobile'] = $values['overlay']['picture_overlay_mobile'];
    $this->configuration['picture_overlay_mobile_url'] = (isset($overlay_url_mobile) && !empty($overlay_url_mobile)) ? $overlay_url_mobile : '';
    $this->configuration['btn_overlay'] = (!empty($values['overlay']['btn_overlay'])) ? $values['overlay']['btn_overlay'] : '';
    $this->configuration['activate_overlay'] = $form_state->getValue('activate_overlay');
    $this->configuration['google_places'] = $form_state->getValue('google_places');
    $this->configuration['google_places_mixte'] = $form_state->getValue('google_places_mixte');

    // TODO: Change the autogenerated stub.
    parent::blockSubmit($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->getConfiguration();
    $locator_settings = \Drupal::config('vactory_locator.settings');

    $category = (isset($config['locator_category']) && !empty($config['locator_category'])) ? $config['locator_category'] : ['all'];
    $marker = (isset($config['locator_marker_url']) && !empty($config['locator_marker_url'])) ? $config['locator_marker_url'] : $locator_settings->get('locator_default_marker_url');
    $marker_height = (isset($config['locator_marker_height']) && !empty($config['locator_marker_height'])) ? $config['locator_marker_height'] : FALSE;
    $marker_width = (isset($config['locator_marker_width']) && !empty($config['locator_marker_width'])) ? $config['locator_marker_width'] : FALSE;
    $cluster = (isset($config['locator_cluster_url']) && !empty($config['locator_cluster_url'])) ? $config['locator_cluster_url'] : '';
    $cluster_height = (isset($config['locator_cluster_height']) && !empty($config['locator_cluster_height'])) ? $config['locator_cluster_height'] : FALSE;
    $cluster_width = (isset($config['locator_cluster_width']) && !empty($config['locator_cluster_width'])) ? $config['locator_cluster_width'] : FALSE;
    $activate_overlay = (isset($config['activate_overlay']) && !empty($config['activate_overlay'])) ? $config['activate_overlay'] : 0;
    $google_places = (isset($config['google_places']) && !empty($config['google_places'])) ? $config['google_places'] : 0;
    $google_places_mixte = (isset($config['google_places_mixte']) && !empty($config['google_places_mixte'])) ? $config['google_places_mixte'] : 0;
    $picture_overlay = (isset($config['picture_overlay_url']) && !empty($config['picture_overlay_url'])) ? $config['picture_overlay_url'] : '';
    $picture_overlay_mobile = (isset($config['picture_overlay_mobile_url']) && !empty($config['picture_overlay_mobile_url'])) ? $config['picture_overlay_mobile_url'] : '';
    $btn_overlay = (isset($config['btn_overlay']) && !empty($config['btn_overlay'])) ? $config['btn_overlay'] : '';
    $map_key = (!empty($locator_settings->get('map_api_key'))) ? $locator_settings->get('map_api_key') : 'AIzaSyDFP5cawOX1Z3qvtMz2mb5MwRWHSBV0EDc';
    $use_geolocation = !empty($locator_settings->get('use_geolocation')) ? $locator_settings->get('use_geolocation') : FALSE;
    $url_geolocation_marker = isset($use_geolocation) && !empty($locator_settings->get('url_geolocation_marker')) ? $locator_settings->get('url_geolocation_marker') : NULL;
    $map_style = (!empty($locator_settings->get('map_style'))) ? $locator_settings->get('map_style') : '';
    $enable_filter = !empty($locator_settings->get('enable_filter')) ? $locator_settings->get('enable_filter') : FALSE;
    $lon = (!empty($locator_settings->get('lon'))) ? $locator_settings->get('lon') : '';
    $lat = (!empty($locator_settings->get('lat'))) ? $locator_settings->get('lat') : '';
    $zoom = (!empty($locator_settings->get('zoom'))) ? $locator_settings->get('zoom') : '';
    // Vactory locator view url.
    $view_url = '/vactory/locator/list/%';
    $path = str_replace('%', implode(",", $category), $view_url);
    $url = Url::fromUserInput($path, ['absolute' => TRUE])->toString();
    $countries = NULL;
    if (\Drupal::moduleHandler()->moduleExists('vactory_google_places')) {
      $countries = \Drupal::config('vactory_google_places.settings')->get('countries');
      $countries = array_values(array_map('strtolower', $countries ?? []));
    }
    return [
      '#url' => $url,
      '#enable_filter' => $enable_filter,
      '#theme' => 'map_block',
      '#content' => [
        'vactory_locator' => [
          'picture_overlay' => $picture_overlay,
          'picture_overlay_mobile' => $picture_overlay_mobile,
          'btn_overlay' => $btn_overlay,
          'isOverlayActivated' => $activate_overlay,
          'is_google_places' => $google_places,
          'is_google_places_mixte' => $google_places_mixte,
        ],
      ],
      '#attached' => [
        'library' => [
          'vactory_locator/vactory_locator.locator',
        ],
        'drupalSettings' => [
          'vactory_locator' => [
            'url' => $url,
            'url_marker' => $marker,
            'marker_height' => $marker_height,
            'marker_width' => $marker_width,
            'url_cluster' => $cluster,
            'cluster_height' => $cluster_height,
            'cluster_width' => $cluster_width,
            'map_key' => $map_key,
            'use_geolocation' => $use_geolocation,
            'url_geolocation_marker' => $url_geolocation_marker,
            'map_style' => $map_style,
            'lat' => $lat,
            'lon' => $lon,
            'zoom' => $zoom,
            'isOverlayActivated' => $activate_overlay,
            'isGooglePlaces' => $google_places,
            'isGooglePlacesMixte' => $google_places_mixte,
          ],
          'google_places' => [
            'enabled_countries' => isset($countries) ? $countries : [],
          ],
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return ['config:vactory_locator.settings', 'locator_entity_type_list:vactory_locator'];
  }

}
