<?php

namespace Drupal\vactory_extended_seo\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\file\Entity\File;
use Drupal\path_alias\Entity\PathAlias;

/**
 * Provide settings form for static hreflang.
 */
class ImportFile extends ConfigFormBase {

  const DELETE_ALL = -1;
  const DELETE_LAST = 1;
  /**
   * Gets the configuration names that will be editable.
   *
   * @return array
   *   An array of configuration object names that are editable if called in
   *   conjunction with the trait's config() method.
   */
  protected function getEditableConfigNames() {
    return ['vactory_extended_seo_import.settings'];
  }

  /**
   * Returns a unique string identifying the form.
   *
   * The returned ID should be a unique string that can be a valid PHP function
   * name since it's used in hook implementation names such as
   * hook_form_FORM_ID_alter().
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'vactory_extended_seo_import_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['file_instructions'] = [
      '#markup' => $this->t("<b>The mapping between the entity and the target node is based on a priority
                    computation :<br/> First we check if you provided the node id to be used if not then we perform
                    a lookup based on the title if neither of the two columns are mentioned we try to perform a lookup
                    based on the url column which must have a valid slug without the language prefix !</b> <br/>"),
    ];
    $form['file_data'] = [
      '#type' => 'managed_file',
      '#name' => 'avis_data',
      '#title' => $this->t('Hreflang mapping data (CSV)'),
      '#size' => 30,
      '#description' => '<b><a href="/modules/custom/vactory_extended_seo/artifacts/model.csv" >' .
        $this->t("CSV file example")  . '</b></a><br/>' .
        '<a href="/admin/structure/file-types/manage/document/edit" target="_blank">' .
        $this->t("Check if csv extension is enabled")  . '</a>',
      '#upload_validators' => [
        'file_validate_extensions' => ['csv'],
        'file_validate_size' => [1 * 1024 * 1024],
      ],
      '#upload_location' => 'private://vactory_extended_seo/',
      '#default_value' => $this->config('vactory_extended_seo_import.settings')->get('file_data') ?: '',
    ];

    $form['purge_last'] = [
      '#type' => 'submit',
      '#value' => $this->t('Purge ONLY last imported data'),
      '#submit' => ['::purgeAction'],
    ];

    $form['purge_all_label'] = [
      '#markup' => $this->t("<br/>The following button is to be used carefully
                    because it will delete all previously imported Entities!<br/>"),
    ];
    $form['purge_all'] = [
      '#type' => 'submit',
      '#value' => $this->t('Purge ALL DATA'),
      '#submit' => ['::purgeAction'],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $created_ids = [];
    // Get the uploaded file ID.
    $fid = $form_state->getValue('file_data')[0];
    $file = File::load($fid);
    if ($file) {
      $file_path = $file->getFileUri();
      $activeLanguages = \Drupal::service('language_manager')->getLanguages();
      $seo_entity = \Drupal::service('entity_type.manager')->getStorage('vactory_extended_seo');
      $manager = \Drupal::service('entity_type.manager')->getStorage('vactory_extended_seo');

      $file_handle = fopen($file_path, 'rb');
      if ($file_handle !== FALSE) {
        // Skip the header row.
        $header = fgetcsv($file_handle, 0, ';');
//        Flip the array to use the header as keys.
        $header = array_flip($header);

        // Loop through each row in the CSV.
        while (($data = fgetcsv($file_handle, 0, ';')) !== FALSE) {
          [$node, $url, $title, $lang] = $data;
          $nid = NULL;
          if (!empty($node)) {
            $nid = $node;
            $seo_entity = $seo_entity->loadByProperties([
              'node_id' => $node,
              'langcode' => $lang
            ]);
            $seo_entity = reset($seo_entity);
          } elseif (!empty($title)) {
            $nid = \Drupal::service('entity_type.manager')->getStorage('node')
              ->loadByProperties([
                'title' => trim($title),
                'langcode' => $lang
              ]);
            $nid = reset($nid);
            $seo_entity = $seo_entity->loadByProperties(['node_id' => $nid]);
            $seo_entity = reset($seo_entity);
          } elseif (!empty($url)) {
            $path_alias_manager =  \Drupal::service('entity_type.manager')->getStorage('path_alias');
            $alias_objects = $path_alias_manager->loadByProperties([
              'alias'     => $url,
              'langcode' => $lang
            ]);
            $alias = reset($alias_objects);
            $alias = $alias instanceof PathAlias ? $alias->getPath() : '';
            $nid = explode("/", $alias);
            $nid = end($nid);
            $seo_entity = $nid ? $seo_entity->loadByProperties(['node_id' => $nid]) : NULL;
            $seo_entity = reset($seo_entity);
          }

          if (empty($seo_entity)) {
            $seo_entity = [
              'name' => "node.$node",
              'node_id' => $nid,
              'user_id' => \Drupal::currentUser()->id(),
            ];
            foreach ($activeLanguages as $lang => $val) {
              if (!array_key_exists("hreflang_$lang", $header)) {
                continue;
              }
              $sanitizeId = str_replace('-', '_', $lang);
              $seo_entity["alternate_$sanitizeId"] = $data[$header["hreflang_$lang"]];
            }
            $seo_entity = $manager->create($seo_entity);
            $seo_entity->save();
          } else {
            foreach ($activeLanguages as $lang => $val) {
              if (!array_key_exists("hreflang_$lang", $header)) {
                continue;
              }
              $sanitizeId = str_replace('-', '_', $lang);
              $seo_entity->set("alternate_$sanitizeId", $data[$header["hreflang_$lang"]]);
            }
            $seo_entity->save();
          }

          $created_ids[] = $seo_entity?->id();
        }
        fclose($file_handle);
        $this->config('vactory_extended_seo_import.settings')
          ->set('file_data', $form_state->getValue('file_data'))
          ->set('last_imported_ids', $created_ids)
          ->save();
        \Drupal::messenger()->addMessage($this->t('Fichier chargé avec succes'), MessengerInterface::TYPE_STATUS);
      }
      else {
        \Drupal::messenger()->addMessage($this->t('Impossible de lire le fichier'), MessengerInterface::TYPE_WARNING);
      }
    }
    else {
      \Drupal::messenger()->addMessage($this->t('Aucun fichier CSV trouvé'), MessengerInterface::TYPE_WARNING);
    }
  }

  public function purge(int $mode = self::DELETE_LAST) {
    try {
      $storage_handler = \Drupal::service('entity_type.manager')->getStorage("vactory_extended_seo");
      $ids = [];
      if ($mode === self::DELETE_LAST) {
        $ids = $this->config('vactory_extended_seo_import.settings')->get('last_imported_ids');
        $ids = $storage_handler->loadMultiple($ids);

        $storage_handler?->delete($ids);

      } elseif ($mode === self::DELETE_ALL) {
        $ids = $storage_handler->loadMultiple();
        $storage_handler->delete($ids);
      }
      $this->config('vactory_extended_seo_import.settings')
        ->set('file_data', NULL)
        ->set('last_imported_ids', [])
        ->save();
    } catch (\Exception $e) {
      \Drupal::messenger()->addMessage($this->t('An error occured plz check the logs'), MessengerInterface::TYPE_ERROR);
    }
  }

  public function purgeAction(array &$form, FormStateInterface $form_state) {
    $element = $form_state->getTriggeringElement();
    $element = $element['#id'];
    if (str_contains($element, 'last')) {
      $this->purge(self::DELETE_LAST);
      \Drupal::messenger()->addMessage(
        $this->t('Data from the last imported file were deleted !'),
        MessengerInterface::TYPE_STATUS);
    } else {
      $this->purge(self::DELETE_ALL);
      \Drupal::messenger()->addMessage(
        $this->t('All Extended SEO entities were deleted !'),
        MessengerInterface::TYPE_STATUS);

    }
  }

}
