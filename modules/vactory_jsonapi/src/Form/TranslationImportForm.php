<?php

namespace Drupal\vactory_jsonapi\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use \Drupal\locale\SourceString;

/**
 * Provide a strings translation import based on a context.
 *
 * @package Drupal\vactory_jsonapi\Form
 */
class TranslationImportForm extends ConfigFormBase
{

  /**
   * Gets the configuration names that will be editable.
   *
   * @return array
   *   An array of configuration object names that are editable if called in
   *   conjunction with the trait's config() method.
   */
  protected function getEditableConfigNames()
  {
    return ['vactory_jsonapi.settings'];
  }

  /**
   * Returns a unique string identifying the form.
   *
   * The returned ID should be a unique string that can be a valid PHP function
   * name, since it's used in hook implementation names such as
   * hook_form_FORM_ID_alter().
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId()
  {
    return 'vactory_jsonapi_translation_import_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state)
  {

    $form['keywords'] = [
      '#type' => 'textarea',
      '#title' => t('Keywords'),
      '#description' => t("Enter one value per line."),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state)
  {
    $input_keywords = $form_state->getValue('keywords');
    $keywords = explode("\n", $input_keywords);

    foreach ($keywords as $keyword) {
      $keyword = trim($keyword);
      $this->createString($keyword);
    }

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  private function createString($source_string)
  {
    // Find existing source string.
    $storage = \Drupal::service('locale.storage');
    $string = $storage->findString(array('source' => $source_string));
    if (is_null($string)) {
      $string = new SourceString();
      $string->context = '_FRONTEND';
      $string->setString($source_string);
      $string->setStorage($storage);
      $string->save();
    }
  }

}
