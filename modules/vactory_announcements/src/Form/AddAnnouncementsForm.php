<?php

namespace Drupal\vactory_announcements\Form;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\media\Entity\Media;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;
use Drupal\user\Entity\User;

/**
 * Provide announcements adding form.
 *
 * @package Drupal\vactory_announcements\Form
 */
class AddAnnouncementsForm extends FormBase {

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
  public function getFormId() {
    return 'vactory_announcements_form';
  }

  /**
   * The build form function.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $current_user = \Drupal::currentUser();
    if ($current_user->isAnonymous()) {
      return $this->redirect('user.login',
        [
          'destination' => Url::fromRoute('vactory_announcements.add_announcement')->toString(),
        ]);
    }
    global $user;
    $form = [
      '#prefix' => '<div class="form-container">',
      '#suffix' => '</div>',
    ];
    $user = User::load($current_user->id());
    $form['container'] = [
      '#type' => 'container',
      '#title' => t('General information'),
      '#attributes' => ['class' => ['col']],
      '#prefix' => '<div class=""><span class="form-text text-muted">' . t("* Informations obligatoires") . '</span>',
      '#suffix' => '</div>',
    ];
    $form['container']['first_group'] = [
      '#type' => 'container',
      '#title' => t('General information'),
      '#attributes' => ['class' => ['group-wrapper']],
    ];
    $form['container']['first_group']['sujet'] = [
      '#type' => 'textfield',
      '#title' => t("TITRE DE VOTRE ANNONCE"),
      '#attributes' => ['placeholder' => [t("Indiquez le titre qui figurera sur votre annonce")]],
      '#required' => TRUE,
    ];
    $form['container']['first_group']['row'] = [
      '#attributes' => ['class' => ['row']],
      '#type' => 'container',
    ];
    $typology = $this->getTermByVocabulary("vactory_typologie");
    $form['container']['first_group']['row']['typology'] = [
      '#prefix' => '<div class="dropdown-multiple col-6">',
      '#suffix' => '</div>',
      '#type' => 'select',
      '#title' => t('TYPOLOGIE'),
      '#options' => $typology,
      '#empty_option' => t('Select the relevant typology'),
      '#required' => TRUE,
      '#ajax' => [
        'wrapper' => 'dates-wrapper',
        'callback' => '::ajaxDateFieldsCallback',
      ],
    ];
    $discipline = $this->getTermByVocabulary("discipline_");
    $form['container']['first_group']['row']['discipline'] = [
      '#prefix' => '<div class="dropdown-multiple col-6">',
      '#suffix' => '</div>',
      '#type' => 'select',
      '#title' => t('DISCIPLINE'),
      '#options' => $discipline,
      '#empty_option' => t('Select the relevant discipline (s)'),
      '#multiple' => TRUE,
      '#required' => TRUE,
    ];
    $form['container']['first_group']['row2'] = [
      '#prefix' => '<div id="dates-wrapper">',
      '#suffix' => '</div>',
      '#type' => 'container',
      '#attributes' => ['class' => ['row']],
    ];
    $typology_type = $form_state->getValue('typology');
    if (!empty($typology_type)) {
      $term = Term::load($typology_type);
      if (!empty($term->get('field_specify_dates')->getValue()[0]) && $term->get('field_specify_dates')->getValue()[0]['value'] == 1) {
        $form['container']['first_group']['row2']['date_start'] = [
          '#type' => 'date',
          '#prefix' => '<div class="col-6">',
          '#suffix' => '</div>',
          '#title' => t('DATE DE DÉBUT DE L’ÉVÈNEMENT'),
          '#attributes' => [
            'class' => ['_hasDatepicker'],
          ],
          '#required' => TRUE,
        ];
        $form['container']['first_group']['row2']['date_end'] = [
          '#type' => 'date',
          '#prefix' => '<div class="col-6">',
          '#suffix' => '</div>',
          '#title' => t("DATE DE FIN DE L’ÉVÈNEMENT"),
          '#attributes' => [
            'class' => ['_hasDatepicker'],
          ],
          '#required' => TRUE,
        ];
      }
    }
    $image_title = ['#markup' => t('AJOUTER UNE IMAGE (16/9) <span class="text-muted">- gif jpeg png tiff</span>')];
    $form['container']['first_group']['rowImage'] = [
      '#prefix' => '<div class="form-control-file">',
      '#suffix' => '</div>',
      '#title' => \Drupal::service('renderer')->render($image_title),
      '#type' => 'item',
    ];
    $form['container']['first_group']['rowImage']['image'] = [
      '#type'                => 'managed_file',
      '#title' => t('Choisir mon fichier'),
      '#description' => t('Donnez plus de visibilité à votre annonce en ajoutant une image - 1Mo max'),
      '#upload_validators'   => [
        'file_validate_extensions' => ['gif png jpg jpeg tiff'],
        'file_validate_size' => [1 * 1024 * 1024],
      ],
      '#theme'               => 'image_widget',
      '#upload_location'     => 'public://announcement/',
    ];
    $form['container']['first_group']['content'] = [
      '#title' => t('CONTENU DE L’ANNONCE'),
      '#type' => 'textarea',
      '#attributes' => ['placeholder' => [t("Soyez explicite et concis dès les premières lignes.")]],
      '#required' => TRUE,
    ];
    $displayad = $this->getTermByVocabulary("affichage_de_l_annonce");
    $form['container']['first_group']['display'] = [
      '#type' => 'radios',
      '#title' => t('AFFICHAGE DE L’ANNONCE'),
      '#options' => $displayad,
      '#required' => TRUE,
    ];
    $form['container']['second_group'] = [
      '#type' => 'container',
      '#title' => t('Contact utile'),
      '#attributes' => ['class' => ['row']],
    ];
    $form['container']['second_group']['name'] = [
      '#prefix' => '<div class="col-6">',
      '#suffix' => '</div>',
      '#type' => 'textfield',
      '#title' => t("NOM"),
      '#attributes' => ['placeholder' => [t("Olivirer Delas")]],
      '#required' => TRUE,
      '#default_value' => $user->get('name')->getValue()[0]['value'],
    ];
    $form['container']['second_group']['email'] = [
      '#prefix' => '<div class="col-6">',
      '#suffix' => '</div>',
      '#type' => 'email',
      '#attributes' => ['placeholder' => [t("exemple@yahoo.fr")]],
      '#title' => t("Email"),
      '#default_value' => $user->getEmail(),
    ];
    $form['container']['second_group']['phone'] = [
      '#prefix' => '<div class="col-4">',
      '#suffix' => '</div>',
      '#type' => 'textfield',
      '#required' => TRUE,
      '#attributes' => ['placeholder' => [t("06 - XX - XX - XX - XX")]],
      '#title' => t("Mobile"),
    ];
    $form['container']['second_group']['site'] = [
      '#prefix' => '<div class="col-8">',
      '#suffix' => '</div>',
      '#attributes' => ['placeholder' => [t("www.exempledesite.fr")]],
      '#type' => 'textfield',
      '#title' => t("Site"),
    ];
    $form['container']['second_group']['twitter_account'] = [
      '#prefix' => '<div class="col-6">',
      '#suffix' => '</div>',
      '#type' => 'textfield',
      '#title' => t("COMPTE TWITTER"),
      '#attributes' => ['placeholder' => [t("https://www.twitter.com/exemple")]],
    ];
    $form['container']['second_group']['facebook_account'] = [
      '#prefix' => '<div class="col-6">',
      '#suffix' => '</div>',
      '#type' => 'textfield',
      '#title' => t("COMPTE FACEBOOK"),
      '#attributes' => ['placeholder' => [t("https://www.facebook.com/exemple")]],
    ];
    $form['container']['third_group'] = [
      '#type' => 'container',
      '#title' => t('Lieu(x) de manifestation'),
      '#attributes' => ['class' => ['row']],
    ];
    $form['container']['third_group']['country'] = [
      '#prefix' => '<div class="col-6">',
      '#suffix' => '</div>',
      '#type' => 'textfield',
      '#title' => t("PAYS"),
      '#attributes' => ['placeholder' => [t("Pays de l’évènement")]],
    ];
    $form['container']['third_group']['city'] = [
      '#prefix' => '<div class="col-6">',
      '#suffix' => '</div>',
      '#type' => 'textfield',
      '#title' => t("VILLE"),
      '#attributes' => ['placeholder' => [t("Ville de l’évènement")]],
    ];
    $form['submit'] = [
      '#prefix' => '<div class="submit-wrapper add-advert-submit-wrapper"><div class="submit-box">',
      '#suffix' => '</div></div>',
      '#type' => 'submit',
      '#value' => t('Soumettre mon annonce'),
    ];
    return $form;
  }

  /**
   * The validate form function.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $data = $form_state->getValues();
    // Validation date fin.
    if (isset($data) && !empty($data)) {
      if (!empty($data['typology'])) {
        $tid = $data['typology'];
        $term = Term::load($tid);
        if (!empty($term->get('field_specify_dates')->getValue()[0]) && $term->get('field_specify_dates')->getValue()[0]['value'] == 1) {
          if (isset($data['date_start']) && !empty($data['date_start']) && isset($data['date_end']) && !empty($data['date_end'])) {
            if (!is_array($data['date_end']) && !is_array($data['date_start'])) {
              if (strtotime($data['date_end']) <= strtotime($data['date_start'])) {
                $form_state->setErrorByName("date_end", t('The End date must be greater than the Start date'));
              }
            }
          }
        }
      }
    }
    // Validation Mobile.
    if (isset($data['phone']) && !empty($data['phone'])) {
      if (!preg_match('#^0[0-9]([ .-]?[0-9]{2}){4}$#', $data['phone'])) {
        $form_state->setErrorByName("phone", t('Invalid Phone Number'));
      }
    }
    if (empty($data['email']) && empty($data['phone'])) {
      $form_state->setErrorByName('email', t('You must provide at least one contact field'));
    }
    if (isset($data['site']) && !empty($data['site'])) {
      if (!preg_match("/\b(?:(?:https?|ftp):\/\/|(?:www\.|(?!www)))[-a-z0-9+&@#\/%?=~_|!:,.;]*[-a-z0-9+&@#\/%=~_|]/i", $data['site'])) {
        $form_state->setErrorByName('site', t('Invalid URL'));
      }
    }
    if (isset($data['twitter_account']) && !empty($data['twitter_account'])) {
      if (!preg_match("/\b(?:(?:https?|ftp):\/\/|(?:www\.|(?!www)))[-a-z0-9+&@#\/%?=~_|!:,.;]*[-a-z0-9+&@#\/%=~_|]/i", $data['twitter_account'])) {
        $form_state->setErrorByName('twitter_account', t('Invalid URL'));
      }
    }
    if (isset($data['facebook_account']) && !empty($data['facebook_account'])) {
      if (!preg_match("/\b(?:(?:https?|ftp):\/\/|(?:www\.|(?!www)))[-a-z0-9+&@#\/%?=~_|!:,.;]*[-a-z0-9+&@#\/%=~_|]/i", $data['facebook_account'])) {
        $form_state->setErrorByName('facebook_account', t('Invalid URL'));
      }
    }
  }

  /**
   * The submit form function.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $data = $form_state->getValues();
    $langcode = \Drupal::languageManager()->getCurrentLanguage()->getId();
    if (isset($data) && !empty($data)) {
      $title = Xss::filter($data['sujet']);
      $typology = $data['typology'];
      $content = Xss::filter($data['content']);
      $display = $data['display'];
      $name = Xss::filter($data['name']);
      $node = Node::create(['type' => 'announcement']);
      $node->set('langcode', $langcode);
      $node->set('status', 0);
      $node->set('title', $title);
      $node->set('field_ad_display', $display);
      $node->set('field_typology', $typology);
      $node->set('field_vactory_name', $name);
      $node->set('node_summary', $content);
      $node->set('field_ad_content', $content);
      if (isset($data['email']) && !empty($data['email'])) {
        $node->set('field_vactory_email', $data['email']);
      }
      if (isset($data['phone']) && !empty($data['phone'])) {
        $node->set('field_vactory_phone', $data['phone']);
      }
      if (isset($data['country']) && !empty($data['country'])) {
        $node->set('field_country', $data['country']);
      }
      if (isset($data['city']) && !empty($data['city'])) {
        $node->set('field_city', $data['city']);
      }
      if (isset($data['site']) && !empty($data['site'])) {
        $node->set('field_site', $data['site']);
      }
      if (isset($data['twitter_account']) && !empty($data['twitter_account'])) {
        $node->set('field_twitter_account', $data['twitter_account']);
      }
      if (isset($data['facebook_account']) && !empty($data['facebook_account'])) {
        $node->set('field_facebook_account', $data['facebook_account']);
      }
      if (isset($data['image']) && !empty($data['image'])) {
        $media = Media::create([
          'bundle'           => 'image',
          'uid'              => \Drupal::currentUser()->id(),
          'field_media_image' => [
            'target_id' => $data['image'][0],
          ],
        ]);
        $media->setName($title)->setPublished(TRUE)->save();
        $node->set('field_vactory_media', $media->id());
      }
      foreach ($data['discipline'] as $discipline) {
        $node->field_discipline->appendItem(['target_id' => $discipline]);
      }
      if (isset($data['date_start']) && !empty($data['date_start']) && isset($data['date_end']) && !empty($data['date_end'])) {
        $time_start = \DateTime::createFromFormat("Y-m-d", $data['date_start']);
        $formatted_date_start = $time_start->format('Y-m-d');
        $node->set('field_event_date_start', $formatted_date_start);
        $time_end = \DateTime::createFromFormat("Y-m-d", $data['date_end']);
        $formatted_date_end = $time_end->format('Y-m-d');
        $node->set('field_event_date_end', $formatted_date_end);
      }
      $node->enforceIsNew();
      $node->save();
      // Get link delete node.
      $id = $node->id();
      $path = Url::fromRoute('vactory_announcements.annonce_delete', ['id' => $id]);
      $link_delete = Link::fromTextAndUrl(t('link delete'), $path)->toString();
      \Drupal::messenger()->addMessage(t("Merci d'avoir déposé une annonce. Elle est actuellement en attente d'approbation. Vous recevrez un courriel dans un délai de 72h si elle est acceptée. Si Vous souhaitez la supprimer, %link_delete.", ['%link_delete' => $link_delete]));
      // Send notifs to admins && webmasters.
      $config = \Drupal::config('vactory_announcements.settings')->getRawData();
      $title = isset($config['notification_title']) && !empty($config['notification_title']) ? $config['notification_title'] : t('Une nouvelle annonce à approuver : %title', ['%title' => $title]);
      $message = isset($config['notification_message']) && !empty($config['notification_message']) ? $config['notification_message'] : '';
      $receivers = isset($config['notification_mail_receiver']) ? $config['notification_mail_receiver'] : [];
      $params = [
        '!link_annonce',
        '!link_moderate',
        '!site_name',
        '!name',
        '!period_validity',
        '!date_end',
        '!date_start',
        '!title',
        '!body',
        '!country',
        '!site',
        '!facebook',
        '!twitter',
        '!phone',
        '!mail',
      ];
      foreach ($params as $param) {
        if (strpos($message, $param) !== FALSE) {
          $value = \Drupal::service('vactory_announcements.announcements.manage')->getParamValue($param, $node);
          $message = str_replace($param, $value, $message);
        }
      }
      if (!empty($receivers)) {
        foreach ($receivers as $receiver) {
          $user = User::load($receiver);
          $email = $user->getEmail();
          \Drupal::service('vactory_announcements.announcements.manage')->sendMail($title, $email, $message);
        }
      }
      else {
        // Send to all (admins, webmasters).
        $users = User::loadMultiple();
        $concernedRoles = ['administrator', 'webmaster'];
        foreach ($users as $user) {
          if (count(array_intersect($concernedRoles, $user->getRoles())) > 0) {
            $email = $user->getEmail();
            \Drupal::service('vactory_announcements.announcements.manage')->sendMail($title, $email, $message);
          }
        }
      }
    }
  }

  /**
   * Implements  getTermByVocabulary() function.
   */
  public function getTermByVocabulary($taxonomy) {
    $vocabularies = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadByProperties(['vid' => $taxonomy]);
    $terms = [];
    if (isset($vocabularies) && !empty($vocabularies)) {
      foreach ($vocabularies as $key => $term) {
        $terms[$key] = $term->get('name')->value;
      }
    }
    return $terms;
  }

  /**
   * Implements ajaxDateFieldsCallback() function.
   */
  public function ajaxDateFieldsCallback($form, FormStateInterface $form_state) {
    return $form['container']['first_group']['row2'];
  }

}
