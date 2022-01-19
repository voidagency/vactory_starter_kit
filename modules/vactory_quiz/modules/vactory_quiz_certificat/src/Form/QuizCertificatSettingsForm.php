<?php

namespace Drupal\vactory_quiz_certificat\Form;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Quiz Setting Form Class.
 */
class QuizCertificatSettingsForm extends ConfigFormBase {

  /**
   * {@inheritDoc}
   */
  protected function getEditableConfigNames() {
    return ['vactory_quiz_certificat.settings'];
  }

  /**
   * {@inheritDoc}
   */
  public function getFormId() {
    return 'vactory_quiz_certificat_settings';
  }

  /**
   * {@inheritDoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $config = $this->config('vactory_quiz_certificat.settings');
    $form['orientation'] = [
      '#type' => 'select',
      '#title' => $this->t('Document orientation'),
      '#options' => [
        'default' => $this->t('Default'),
        'landscape' => $this->t('Landscape (Paysage)'),
      ],
      '#default_value' => $config->get('orientation'),
    ];
    $form['certificat_body'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Certificat body'),
      '#default_value' => $config->get('certificat_body')['value'],
      '#format' => $config->get('certificat_body')['format'],
    ];
    $form['token_tree'] = $this->getTokenTree();
    $form['font_directories'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Fonts Directories'),
      '#description' => $this->t('Custom fonts directories, enter a directory per line, use project root relative path. The fonts directory should not contains any subdirectory all fonts ttf/otf files should be sotred directly under the specified font directory.'),
      '#default_value' => $config->get('font_directories'),
    ];
    $fonts_data = $config->get('fonts_data');
    if (!empty($fonts_data)) {
      $output = '<h3>Existing custom fonts</h3>';
      $output .= '<table><tr><th>Font name</th><th>Font file</th></tr>';
      foreach ($fonts_data as $font_name => $font) {
        $output .= '<tr>';
        $output .= '<td>' . $font_name . '</td>';
        $output .= '<td>' . $font['R'] . '</td>';
        $output .= '<tr>';
      }
      $output .= '</table>';
      $form['fonts_chart_wrapper'] = [
        '#type' => 'details',
        '#title' => $this->t('Detected custom fonts'),
      ];
      $form['fonts_chart_wrapper']['list'] = [
        '#markup' => $output,
      ];
    }

    return $form;
  }

  /**
   * {@inheritDoc}
   */
  function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    $font_directories = $form_state->getValue('font_directories');
    if (!empty($font_directories)) {
      $font_directories = explode(PHP_EOL, $font_directories);
      $real_font_directories = array_map(function ($el) {
        $separator = str_starts_with($el, '/') ? '' : '/';
        return DRUPAL_ROOT . $separator . trim($el);
      }, array_filter($font_directories));
      if (!empty($real_font_directories)) {
        foreach ($real_font_directories as $key => $directory) {
          if (!file_exists($directory)) {
            $form_state->setErrorByName('font_directories', $this->t('The directory "@dir" does not exist', ['@dir' => $font_directories[$key]]));
          }
        }
      }
    }
  }

  /**
   * {@inheritDoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->configFactory->getEditable('vactory_quiz_certificat.settings');
    $font_directories = $form_state->getValue('font_directories');
    $real_font_directories = [];
    if (!empty($font_directories)) {
      $font_directories = explode(PHP_EOL, $font_directories);
      $real_font_directories = array_map(function ($el) {
        $separator = str_starts_with($el, '/') ? '' : '/';
        return DRUPAL_ROOT . $separator . trim($el);
      }, array_filter($font_directories));
      $fonts_data = [];
      if (!empty($real_font_directories)) {
        foreach ($real_font_directories as $key => $directory) {
          if (file_exists($directory)) {
            $fonts = scandir($directory);
            $fonts = array_filter($fonts, function ($el) {
              return !in_array($el, ['.', '..']) && (str_ends_with($el, '.ttf') || str_ends_with($el, '.otf'));
            });
            if (!empty($fonts)) {
              foreach ($fonts as $font) {
                $font_name = strtolower(str_replace([' ', '_'], '-', $font));
                $font_name = str_replace(['.ttf', '.otf'], '', $font_name);
                $fonts_data[$font_name]['R'] = $font;
              }
            }
          }
        }
      }
    }

    \Drupal::state()->set('vactory_quiz_certificat_font_dirs', $real_font_directories);
    $config->set('certificat_body', $form_state->getValue('certificat_body'))
      ->set('orientation', $form_state->getValue('orientation'))
      ->set('font_directories', $form_state->getValue('font_directories'))
      ->set('fonts_data', $fonts_data)
      ->save();
    Cache::invalidateTags(['vactory_quiz:settings']);
    parent::submitForm($form, $form_state);
  }

  /**
   * Function providing the site token tree link.
   */
  public function getTokenTree() {
    $token_tree = [
      '#theme' => 'token_tree_link',
      '#show_restricted' => TRUE,
      '#weight' => 90,
    ];
    return [
      '#type' => 'markup',
      '#markup' => \Drupal::service('renderer')->render($token_tree),
    ];
  }

}
