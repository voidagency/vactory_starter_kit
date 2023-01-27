<?php

namespace Drupal\vactory_academy_agency\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\node\NodeInterface;
use Drupal\taxonomy\Entity\Term;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class FormationInscriptionController.
 *
 * @package Drupal\vactory_academy_agency\Controller
 */
class FormationInscriptionController extends ControllerBase {

  /**
   * Subscribe to formation callback.
   */
  public function subscribeToFormation($nid, Request $request) {
    if (!empty($nid)) {
      $nid = decrypt($nid);
      $node = \Drupal::entityTypeManager()->getStorage('node')
        ->load($nid);
      if (isset($node) && $node instanceof NodeInterface && $node->bundle() == 'vactory_academy_agency') {
        $current_user = \Drupal::currentUser();
        if (!$current_user->isAnonymous()) {
          $current_user = \Drupal::entityTypeManager()->getStorage('user')
            ->load($current_user->id());
          $prenom = $current_user->get('field_first_name')->value;
          $nom = $current_user->get('field_last_name')->value;
          $agency_id = $node->get('field_academy_agence')->target_id;
          $phone = $current_user->get('field_telephone')->value;
          $email = $current_user->get('mail')->value;
          // Generate submission title.
          $inscription_title = ucfirst($prenom) . ' ' . strtoupper($nom) . " s'est inscrit à la formation «" . $node->label() . '»';
          // Create new formation inscription..
          $term = Term::create([
            'vid' => 'academy_subscribers',
            'name' => $inscription_title,
            'field_agence_formation' => $agency_id,
            'field_subscriber_course' => $nid,
            'field_subscriber_last_name' => $nom,
            'field_subscriber_first_name' => $prenom,
            'field_subscriber_telephone' => $phone,
            'field_subscriber_mail' => $email,
          ]);
          try {
            $term->save();
            return $this->redirect('vactory_academy_agency.inscription.confirmation', ['course_id' => base64_encode($nid)]);
          }
          catch (EntityStorageException $e) {
            $code = $e->getCode();
            if ($code == 0001) {
              \Drupal::messenger()->addWarning($e->getMessage());
            }
            else {
              \Drupal::messenger()->addWarning($this->t('Une erreur est survenue lors de la sauvegarde de votre inscription, Veuillez réessayer plus tard.'));
              \Drupal::logger('vactory_academy_agency')->warning($e->getMessage());
            }
            return $this->redirect('view.formations_agence.site_academy_agence_listing');
          }
        }

      }
    }
  }

  /**
   * Subscribe to formation confirmation callback.
   */
  public function subscribeToFormationConfirmation(Request $request) {
    $content['type_confirmation'] = 'previsionel';
    $current_user = \Drupal::currentUser();
    $course_id = $request->get('course_id');
    if (!empty($course_id) && !$current_user->isAnonymous()) {
      $course_id = base64_decode($course_id);
      if (is_numeric($course_id)) {
        $node = \Drupal::service('entity_type.manager')->getStorage('node')
          ->load($course_id);
        if ($node) {
          $current_user = \Drupal::service('entity_type.manager')->getStorage('user')
            ->load($current_user->id());
          $datalayer_fields_infos = \Drupal::config('vactory_academy_agency.settings')->get('datalayer_concerned_fields');
          $datalayer_fields_infos = $datalayer_fields_infos ? $datalayer_fields_infos : [];
          $datalayer_attributes = [];
          $client_infos_fields = ['first_name', 'last_name', 'email', 'phone'];
          $first_name = $current_user->get('field_first_name')->value;
          $last_name = $current_user->get('field_last_name')->value;
          $phone = $current_user->get('field_telephone')->value;
          $email = $current_user->get('mail')->value;
          foreach ($datalayer_fields_infos as $key => $enabled) {
            if ($enabled) {
              if ($key === 'agency_academies') {
                $academy_title = $node->label();
                $datalayer_attributes['academy'] = $academy_title;
              }
              elseif ($key === 'type_client') {
                $datalayer_attributes[$key] = 'Ancien client';
              }
              elseif (in_array($key, $client_infos_fields)) {
                $datalayer_attributes[$key] = "{$key}";
              }
            }
          }
          if (!empty($datalayer_attributes)) {
            $datalayer_snippet = '<script>dataLayer = [';
            $datalayer_snippet .= json_encode($datalayer_attributes);
            $datalayer_snippet .= '];</script>';
            $content['datalayer_snippet'] = $datalayer_snippet;
          }
        }
      }
      return [
        '#theme' => 'formation_confirmation_page',
        '#content' => $content,
      ];
    }

    throw new NotFoundHttpException();

  }

}
