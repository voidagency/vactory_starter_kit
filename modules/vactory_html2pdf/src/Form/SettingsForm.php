<?php

namespace Drupal\vactory_html2pdf\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure Vactory HTML To PDF settings for this site.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'vactory_html2pdf_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['vactory_html2pdf.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('vactory_html2pdf.settings');
    $form = parent::buildForm($form, $form_state);
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
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->configFactory->getEditable('vactory_html2pdf.settings');
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

    \Drupal::state()->set('vactory_html2pdf_font_dirs', $real_font_directories);
    $config->set('font_directories', $form_state->getValue('font_directories'))
      ->set('fonts_data', $fonts_data)
      ->save();
  }

}
