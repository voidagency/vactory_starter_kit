<?php

namespace Drupal\vactory_locator\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Url;
use Drupal\file\Entity\File;

/**
 * Provides a "Vactory Locator Block Agence " block.
 *
 * @Block(
 *   id = "vactory_locator_block_agence",
 *   admin_label = @Translation("Vactory Locator Block Agence"),
 *   category = @Translation("Vactory")
 * )
 */
class VactoryLocatorBlockAgence extends BlockBase implements BlockPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);
    $config = $this->getConfiguration();

    $terms = \Drupal::entityTypeManager()->getStorage('locator_entity')->loadMultiple();
    $options = [
      'all' => t('ALL'),
    ];
    foreach ($terms as $term) {
      $options[$term->id()] = $term->name->value;
    }

    $form['locator_agence'] = [
      '#type' => 'select',
      '#multiple' => TRUE,
      '#title' => $this->t('Agences'),
      '#description' => $this->t('choisie agence'),
      '#options' => $options,
      '#default_value' => isset($config['locator_agence']) ? $config['locator_agence'] : '',

    ];
    $form['locator_marker'] = [
      '#type' => 'media_library',
      '#title' => t('Map marker'),
      '#allowed_bundles' => ['image'],
      '#upload_validators' => [
        'file_validate_extensions' => ['png jpg jpeg gif'],
        'file_validate_size' => [25600000],
      ],
      '#required' => FALSE,
      '#default_value' => isset($config['locator_marker']) ? $config['locator_marker'] : '',
    ];

    $form['locator_cluster'] = [
      '#type' => 'media_library',
      '#title' => t('Map marker cluster'),
      '#allowed_bundles' => ['image'],
      '#upload_validators' => [
        'file_validate_extensions' => ['png jpg jpeg gif'],
        'file_validate_size' => [25600000],
      ],
      '#required' => FALSE,
      '#default_value' => isset($config['locator_cluster']) ? $config['locator_cluster'] : '',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    if ($form_state->getValue('locator_marker')) {
      /* Fetch the array of the file stored temporarily in database */
      $image = $form_state->getValue('locator_marker');

      /* Load the object of the file by it's fid */
      $file = File::load($image[0]);

      /* Set the status flag permanent of the file object */
      $file->setPermanent();

      /* Save the file in database */
      $file->save();

      $marker_url = \Drupal::service('stream_wrapper_manager')->getViaUri($file->getFileUri())->getExternalUrl();
    }

    if ($image = $form_state->getValue('locator_cluster')) {
      $file = File::load($image[0]);
      $file->setPermanent();
      $file->save();
      $cluster_url = \Drupal::service('stream_wrapper_manager')->getViaUri($file->getFileUri())->getExternalUrl();
    }

    $this->configuration['locator_agence'] = $form_state->getValue('locator_agence');
    $this->configuration['locator_marker'] = $form_state->getValue('locator_marker');
    $this->configuration['locator_marker_url'] = (isset($marker_url) && !empty($marker_url)) ? $marker_url : '';
    $this->configuration['locator_cluster_url'] = (isset($cluster_url) && !empty($cluster_url)) ? $cluster_url : '';

    parent::blockSubmit($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->getConfiguration();
    $locator_settings = \Drupal::config('vactory_locator.settings');

    $category = (isset($config['locator_agence']) && !empty($config['locator_agence'])) ? $config['locator_agence'] : ['all'];
    $marker = (isset($config['locator_marker_url']) && !empty($config['locator_marker_url'])) ? $config['locator_marker_url'] : $locator_settings->get('locator_default_marker_url');
    $cluster = (isset($config['locator_cluster_url']) && !empty($config['locator_cluster_url'])) ? $config['locator_cluster_url'] : '';
    $map_key = (!empty($locator_settings->get('map_api_key'))) ? $locator_settings->get('map_api_key') : 'AIzaSyDFP5cawOX1Z3qvtMz2mb5MwRWHSBV0EDc';
    $use_geolocation = !empty($locator_settings->get('use_geolocation')) ? $locator_settings->get('use_geolocation') : FALSE;
    $url_geolocation_marker = isset($use_geolocation) && !empty($locator_settings->get('url_geolocation_marker')) ? $locator_settings->get('url_geolocation_marker') : NULL;
    $map_style = (!empty($locator_settings->get('map_style'))) ? $locator_settings->get('map_style') : '';
    $enable_filter = !empty($locator_settings->get('enable_filter')) ? $locator_settings->get('enable_filter') : FALSE;

    // vactory_locator view url.
    $view_url = '/vactory/locator/agence/%';
    $path = str_replace('%', implode("+", $category), $view_url);
    $url = Url::fromUserInput($path, $options = ['absolute' => TRUE])->toString();

    return [
      '#url' => $url,
      '#enable_filter' => $enable_filter,
      '#theme' => 'map_block',
      '#attached' => [
        'library' => [
          'vactory_locator/vactory_locator.locator',
        ],
        'drupalSettings' => [
          'vactory_locator' => [
            'url' => $url,
            'url_marker' => $marker,
            'url_cluster' => $cluster,
            'map_key' => $map_key,
            'use_geolocation' => $use_geolocation,
            'url_geolocation_marker' => $url_geolocation_marker,
            'map_style' => $map_style,
          ],
        ],
      ],
    ];
  }

  /**
   * {@inheritDoc}
   */
  public function getCacheTags() {
    return ['config:vactory_locator.settings', 'locator_entity_type_list:vactory_locator'];
  }

}
