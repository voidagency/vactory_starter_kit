<?php

namespace Drupal\vactory_image_sitemap\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Configure Vactory Image Sitemap settings for this site.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'vactory_image_sitemap_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['vactory_image_sitemap.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('vactory_image_sitemap.settings');
    $languages = \Drupal::languageManager()->getLanguages();
    $created = $config->get('created');
    $date = new \DateTime();
    $date->setTimestamp($created);
    $header = [
      'sitemap_url' => $this->t('Image Sitemap URL'),
      'created' => $this->t('Created date'),
      'total_links' => $this->t('Total links'),
    ];
    $links = '<ul>';
    $total_urls = '<ul>';
    foreach ($languages as $language) {
      $links .= '<li><em>[' . strtoupper($language->getId()) . '] : </em>';
      $link = Url::fromRoute('vactory_image_sitemap.xml', [], ['absolute' => TRUE, 'language' => $language])
        ->toString();
      $links .= '<a href="'. $link . '">' . $link . '</a></li>';

      // Total links.
      $num_url = !empty($config->get($language->getId() . '_number_of_urls')) ? $config->get($language->getId() . '_number_of_urls') : 0;
      $total_urls .= '<li><em>[' . strtoupper($language->getId()) . '] : </em>';
      $total_urls .= $num_url . '</li>';
    }
    $links .= '</ul>';
    $total_urls .= '</ul>';
    $form['infos'] = [
      '#type' => 'table',
      '#header' => $header,
    ];
    $form['infos'][0] = [
      'sitemap_url' => [
        '#markup' => $links,
      ],
      'created' => [
        '#markup' => $date->format('Y-m-d H:i'),
      ],
      'total_links' => [
        '#markup' => $total_urls,
      ],
    ];

    $form['generate'] = [
      '#type' => 'submit',
      '#submit' => [[$this, 'generateImageSitemap']],
      '#value' => $this->t('Regenerate'),
    ];

    $content_types = \Drupal::entityTypeManager()->getStorage('node_type')
      ->loadMultiple();
    $options = array_map(function ($content_type) {
      return $content_type->label();
    }, $content_types);

    $form['excluded_content_types'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Excluded content types'),
      '#options' => $options,
      '#default_value' => !empty($config->get('excluded_content_types')) ? $config->get('excluded_content_types') : [],
      '#description' => $this->t('Choose which content types should be excluded from image sitemap'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('vactory_image_sitemap.settings');
    $config->set('excluded_content_types', $form_state->getValue('excluded_content_types'))
      ->save();
  }

  /**
   * Generate image sitemap submit.
   */
  public function generateImageSitemap(array &$form, FormStateInterface $form_state) {
    \Drupal::service('vactory_image_sitemap.generator')->process();
  }
}
