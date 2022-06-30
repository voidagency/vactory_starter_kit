<?php

namespace Drupal\vactory_video_ask\Element;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\FormElement;
use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;
use \Drupal\Core\File\FileUrlGenerator;

/**
 * Provide an URL form element with link attributes.
 *
 * @FormElement("dynamic_video_ask")
 */
class DynamicVideoAskElement extends FormElement {

  /**
   * Returns the element properties for this element.
   *
   * @return array
   *   An array of element properties. See
   *   \Drupal\Core\Render\ElementInfoManagerInterface::getInfo() for
   *   documentation of the standard properties of all elements, and the
   *   return value format.
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#input' => TRUE,
      '#process' => [
        [$class, 'processDynamicVideoAsk'],
      ],
      '#element_validate' => [
        [$class, 'validateDynamicVideoAsk'],
      ],
      '#theme_wrappers' => ['fieldset'],
      '#attached' => [
        'library' => ['vactory_video_ask/video-ask-form'],
      ],
    ];
  }

  /**
   * Video Ask form element process callback.
   */
  public static function processDynamicVideoAsk(&$element, FormStateInterface $form_state, &$form) {
    $default_value = isset($element['#default_value']['screen_details']) ? $element['#default_value']['screen_details'] : '';
    $parents = $element['#parents'];
    $id_prefix = implode('-', $parents);
    $wrapper_id = Html::getUniqueId($id_prefix . '-add-more-wrapper');
    // states.
    $element_state = static::getElementState($parents, $form_state);
    if ($element_state === NULL) {
      $element_state = [
        'video_ask' => [],
        'items_count' => !empty($default_value) ? count($default_value) - 1 : 0,
      ];
      static::setElementState($parents, $form_state, $element_state);
    }
    $max = $element_state['items_count'];

    $element['screen'] = [
      '#type' => 'vertical_tabs',
      '#default_tab' => 'screen_details',
    ];

    // Background options.
    $background_options = [
      '-1' => t('-- selectionner une layout --'),
      'image' => t('Image'),
      'video' => t('Vidéo'),
      'wysiwyg' => t('Champ Wysiwyg'),
    ];

    // Type response options.
    $type_response_options = [
      '-1' => t('aucun réponse'),
      'button' => t('Button'),
      'quiz' => t('Quiz'),
      'multiple_choices' => t('Multiple Choices'),
    ];

    $user_input_values = $form_state->getUserInput();
    // Sort screens:
    usort($default_value, function ($item1, $item2) {
      return $item1['_weight'] <=> $item2['_weight'];
    });
    usort($user_input_values, function ($item1, $item2) {
      return $item1['_weight'] <=> $item2['_weight'];
    });

    // Get icon drag.
    $base_url = \Drupal::request()->getSchemeAndHttpHost();
    $module_path = \Drupal::service('extension.path.resolver')->getPath('module', 'vactory_video_ask');
    $drag_icon_url = $base_url . '/' . $module_path . '/icons/icon-drag-move.svg';
    $drag_icon = '<img src="' . $drag_icon_url . '" class="va-screens-sortable-handler"/>';

    for ($i = 0, $j = 0; $i <= $max; $i++) {
      $screen_to_delete = isset($element_state['video_ask'][$i]['screen_to_delete']) ? $element_state['video_ask'][$i]['screen_to_delete'] : NULL;
      if (isset($screen_to_delete) && (int) $screen_to_delete == $i) {
        continue;
      }

      $element['screen_details'][$i] = [
        '#type' => 'details',
        '#title' => t("Screen %j", ["%j" => $j + 1]) . ' ' . $drag_icon,
        '#group' => 'screen',
        '#tree' => TRUE,
      ];

      // Screen weight element.
      $weight_value = isset($default_value[$i]['_weight']) && !empty($default_value[$i]['_weight']) ? $default_value[$i]['_weight'] : $i + 1;
      $element['screen_details'][$i]['_weight'] = [
        '#type'          => 'number',
        '#title'         => 'Weight',
        '#min'           => 1,
        '#size'          => 5,
        '#default_value' => $weight_value,
        '#attributes' => [
          'class' => ['va-screens-weight'],
        ],
      ];

      // Add screens div element wrappers.
      if ($i === 0) {
        $element['screen_details'][$i]['#prefix'] = '<div id="video-ask-screens-wrapper">';
      }
      if ($i === $max) {
        $element['screen_details'][$i]['#suffix'] = '</div>';
      }

      $element['screen_details'][$i]['remove_screen'] = [
        '#type' => 'button',
        '#value' => t('delete screen'),
        '#name' => 'delete_screen_btn_' . $i,
        '#prefix' => '<div class="remove-setting-submit">',
        '#suffix' => '</div>',
        '#limit_validation_errors' => [],
        '#ajax' => [
          'callback' => [static::class, 'deleteFormCallback'],
          'method' => 'replace',
        ],
      ];

      $element['screen_details'][$i]['id'] = [
        '#type' => 'textfield',
        '#title' => t('Id screen'),
        '#required' => TRUE,
        '#default_value' => (isset($user_input_values[$i]['id']) && !empty($user_input_values[$i]['id'])) ?
        $user_input_values[$i]['id'] : ((isset($default_value[$i]['id']) && !empty($default_value[$i]['id'])) ? $default_value[$i]['id'] : 'screen_'.($i+1)),
      ];

      $quiz_wrapper = "quiz_group_selector_".$i;

      $element['screen_details'][$i]['quiz'] = [
        '#type' => 'details',
        '#title' => t('Quiz'),
        '#collapsible' => TRUE,
        '#collapsed' => TRUE,
        '#open' => TRUE,
        '#prefix' => "<div id='$quiz_wrapper'>",
        '#suffix' => "</div>",
      ];

      $element['screen_details'][$i]['quiz']['part_of_quiz'] = [
        '#type' => 'checkbox',
        '#title' => t('Fait partie d\'un quiz'),
        '#required' => FALSE,
        '#ajax' => [
          'callback' => [static::class, 'onChangeQuiz'],
          'wrapper' => $quiz_wrapper,
        ],
        '#default_value' => (isset($user_input_values[$i]['quiz']['part_of_quiz']) && !empty($user_input_values[$i]['quiz']['part_of_quiz'])) ?
          $user_input_values[$i]['quiz']['part_of_quiz'] : ((isset($default_value[$i]['quiz']['part_of_quiz']) && !empty($default_value[$i]['quiz']['part_of_quiz'])) ? $default_value[$i]['quiz']['part_of_quiz'] : FALSE),
      ];


      $element['update_quiz_details_' . $i] = [
        '#type'                    => 'submit',
        '#value'                   => t('Update Quiz widget'),
        '#name' => 'update_quiz_details_' . $i,
        '#attributes' => [
          'style' => ['display:none;'],
        ],
        '#ajax'                    => [
          'callback' => [static::class, 'updateQuizWidget'],
          'wrapper'  => $quiz_wrapper,
          'event'    => 'click',
          'id' => $i,
        ],
        '#limit_validation_errors' => [],
        '#submit' => [[static::class, 'updateItemsQuiz']],
      ];

      $is_part_of_quiz = isset($element_state['video_ask'][$i]['quiz']['part_of_quiz']) ? $element_state['video_ask'][$i]['quiz']['part_of_quiz'] :
        (isset($default_value[$i]['quiz']['part_of_quiz']) && !empty($default_value[$i]['quiz']['part_of_quiz']) ? $default_value[$i]['quiz']['part_of_quiz'] : FALSE);

      if ($is_part_of_quiz) {
        $element['screen_details'][$i]['quiz']['quiz_id'] = [
          '#type' => 'textfield',
          '#title' => t('Id unique de quiz'),
          '#required' => TRUE,
          '#default_value' => (isset($user_input_values[$i]['quiz']['quiz_id']) && !empty($user_input_values[$i]['quiz']['quiz_id'])) ?
            $user_input_values[$i]['quiz']['quiz_id'] : ((isset($default_value[$i]['quiz']['quiz_id']) && !empty($default_value[$i]['quiz']['quiz_id'])) ? $default_value[$i]['quiz']['quiz_id'] : "quiz_".$i),
        ];


        $element['screen_details'][$i]['quiz']['quiz_index'] = [
          '#type' => 'number',
          '#title' => t('Index of this question in quiz'),
          '#required' => TRUE,
          '#default_value' => (isset($user_input_values[$i]['quiz']['quiz_index']) && !empty($user_input_values[$i]['quiz']['quiz_index'])) ?
            $user_input_values[$i]['quiz']['quiz_index'] : ((isset($default_value[$i]['quiz']['quiz_index']) && !empty($default_value[$i]['quiz']['quiz_index'])) ? $default_value[$i]['quiz']['quiz_index'] : ""),
        ];
      }

      $background_wrapper = 'background_layout_selector_' . $i;

      $element['screen_details'][$i]['layout'] = [
        '#type' => 'details',
        '#title' => t('Layout'),
        '#collapsible' => TRUE,
        '#collapsed' => TRUE,
        '#open' => TRUE,
        '#prefix' => "<div id='$background_wrapper'>",
        '#suffix' => "</div>",
      ];
      $element['screen_details'][$i]['layout']['background'] = [
        '#type' => 'select',
        '#title' => t('Layout ' . $i),
        '#options' => $background_options,
        //'#name' => 'select_layout_bg_' . $i,
        '#ajax' => [
          'callback' => [static::class, 'onChangeLayout'],
          'wrapper' => $background_wrapper,
        ],
        '#default_value' => (isset($user_input_values[$i]['layout']['background']) && !empty($user_input_values[$i]['layout']['background'])) ?
        $user_input_values[$i]['layout']['background'] : ((isset($default_value[$i]['layout']['background']) && !empty($default_value[$i]['layout']['background']))
            ? $default_value[$i]['layout']['background'] : '-1'),
      ];

      $element['update_background_' . $i] = [
        '#type'                    => 'submit',
        '#value'                   => t('Update widget'),
        '#name' => 'update_background_' . $i,
        '#attributes' => [
          'style' => ['display:none;'],
        ],
        '#ajax'                    => [
          'callback' => [static::class, 'updateWidgetLayoutBackground'],
          'wrapper'  => $background_wrapper,
          'event'    => 'click',
          'id' => $i,
        ],
        '#limit_validation_errors' => [],
        '#submit' => [[static::class, 'updateItemsLayoutBackground']],
      ];

      $bg_selected = isset($element_state['video_ask'][$i]['selected_layout']) ? $element_state['video_ask'][$i]['selected_layout'] :
        (isset($default_value[$i]['response']) && !empty($default_value[$i]['layout']) ? $default_value[$i]['layout']['background'] : []);
      if (!empty($bg_selected)) {
        switch ($bg_selected) {
          case 'image':
            $element['screen_details'][$i]['layout']['image'] = [
              '#type' => 'media_library',
              '#title' => t('Image'),
              '#allowed_bundles' => ['onboarding_image'],
              '#required' => TRUE,
              '#default_value' => (isset($user_input_values[$i]['layout']['image']) && !empty($user_input_values[$i]['layout']['image'])) ?
              $user_input_values[$i]['layout']['image']['media_library_selection'] : ((isset($default_value[$i]['layout']['image']) && !empty($default_value[$i]['layout']['image']))
              ? $default_value[$i]['layout']['image']['id'] : NULL),
            ];
            break;

          case 'video':
            $element['screen_details'][$i]['layout']['video'] = [
              '#type' => 'media_library',
              '#title' => t('Video'),
              '#allowed_bundles' => ['onboarding_video'],
              '#required' => TRUE,
              '#default_value' => (isset($user_input_values[$i]['layout']['video']) && !empty($user_input_values[$i]['layout']['video'])) ?
              $user_input_values[$i]['layout']['video']['media_library_selection'] : ((isset($default_value[$i]['layout']['video']) && !empty($default_value[$i]['layout']['video']))
              ? $default_value[$i]['layout']['video']['id'] : NULL),
            ];
            break;

        }
      }
      $element['screen_details'][$i]['layout']['text'] = [
        '#type' => 'text_format',
        '#title' => t('Text'),
        '#format' => 'full_html',
        '#default_value' => (isset($user_input_values[$i]['layout']['text']) && !empty($user_input_values[$i]['layout']['text'])) ?
        $user_input_values[$i]['layout']['text']['value'] : ((isset($default_value[$i]['layout']['text']) && !empty($default_value[$i]['layout']['text']))
          ? $default_value[$i]['layout']['text']['value'] : ''),
      ];

      $response_wrapper = 'response_layout_selector_' . $i;
      $element['screen_details'][$i]['response'] = [
        '#type' => 'details',
        '#title' => t('Résponse'),
        '#collapsible' => TRUE,
        '#collapsed' => TRUE,
        '#open' => TRUE,
        '#prefix' => "<div id='$response_wrapper'>",
        '#suffix' => "</div>",
      ];

      // Type de réponse.
      $element['screen_details'][$i]['response']['type_response'] = [
        '#type' => 'select',
        '#title' => t('Type réponses'),
        '#options' => $type_response_options,
        '#ajax' => [
          'callback' => [static::class, 'onChangeResponseType'],
          'wrapper' => $response_wrapper,
        ],
        '#default_value' => (isset($user_input_values[$i]['response']) && !empty($user_input_values[$i]['response'])) ?
        $user_input_values[$i]['response'] : ((isset($default_value[$i]['response']) && !empty($default_value[$i]['response']))
          ? $default_value[$i]['response'] : ''),
      ];

      $element['update_type_response_' . $i] = [
        '#type'                    => 'submit',
        '#value'                   => t('Update type response'),
        '#name' => 'update_type_response_' . $i,
        '#attributes' => [
          'style' => ['display:none;'],
        ],
        '#ajax'                    => [
          'callback' => [static::class, 'updateWidgetTypeResponse'],
          'wrapper'  => $response_wrapper,
          'event'    => 'click',
        ],
        '#limit_validation_errors' => [],
        '#submit' => [[static::class, 'updateItemsTypeResponse']],
      ];
      $type_response_selected = isset($element_state['video_ask'][$i]['response_type']) ? $element_state['video_ask'][$i]['response_type'] : (isset($default_value[$i]['response']) && !empty($default_value[$i]['response']) ? $default_value[$i]['response']['type_response'] : []);
      if (!empty($type_response_selected)) {
        switch ($type_response_selected) {
          case 'button':
            $element['screen_details'][$i]['response']['settings'] = [
              '#type' => 'video_ask_button',
              '#title' => t('Button Response'),
              '#button_id' => $i,
              '#default_value' => isset($default_value[$i]['response']['settings']) && !empty($default_value[$i]['response']['settings']) ? $default_value[$i]['response']['settings'] : [],
            ];
            break;

          case 'quiz':
            $element['screen_details'][$i]['response']['settings'] = [
              '#type' => 'video_ask_quiz',
              '#title' => t('Quiz'),
              '#quiz_id' => $i,
              '#cardinality' => -1,
              '#default_value' => (isset($default_value[$i]['response']['settings']) && !empty($default_value[$i]['response']['settings'])) ? $default_value[$i]['response']['settings'] : [],
            ];
            break;

          case 'multiple_choices':
            $element['screen_details'][$i]['response']['settings'] = [
              '#type' => 'video_ask_multiple_choice',
              '#title' => t('Multiple choices'),
              '#multiple_choice_id' => $i,
              '#default_value' => (isset($default_value[$i]['response']['settings']) && !empty($default_value[$i]['response']['settings']))
              ? $default_value[$i]['response']['settings'] : [],
            ];
            break;

        }
      }

      $extra_button_wrapper = "extra_button_group_selector_".$i;

      $element['screen_details'][$i]['response']['extra_button'] = [
        '#type' => 'fieldset',
        '#title' => t('Extra Button'),
        '#prefix' => "<div id='$extra_button_wrapper'>",
        '#suffix' => "</div>",
      ];

      // Bouton supplémentaire.
      $element['screen_details'][$i]['response']['extra_button']['use_extra_button'] = [
        '#type' => 'checkbox',
        '#title' => t('Ajouter un bouton supplémentaire'),
        '#required' => FALSE,
        '#ajax' => [
          'callback' => [static::class, 'onChangeExtraButton'],
          'wrapper' => $extra_button_wrapper,
        ],
        '#default_value' => (isset($user_input_values[$i]['response']['extra_button']['use_extra_button']) && !empty($user_input_values[$i]['response']['extra_button']['use_extra_button'])) ?
          $user_input_values[$i]['response']['extra_button']['use_extra_button'] : ((isset($default_value[$i]['response']['extra_button']['use_extra_button']) && !empty($default_value[$i]['response']['extra_button']['use_extra_button'])) ? $default_value[$i]['response']['extra_button']['use_extra_button'] : FALSE),
      ];


      $use_extra_button = isset($element_state['video_ask'][$i]['response']['extra_button']['use_extra_button']) ? $element_state['video_ask'][$i]['response']['extra_button']['use_extra_button'] :
        (isset($default_value[$i]['response']['extra_button']['use_extra_button']) && !empty($default_value[$i]['response']['extra_button']['use_extra_button']) ? $default_value[$i]['response']['extra_button']['use_extra_button'] : FALSE);


      if ($use_extra_button) {
        $element['screen_details'][$i]['response']['extra_button']['extra_button'] = [
          '#type' => 'video_ask_button',
          '#title' => t('Bouton supplémentaire'),
          '#default_value' => isset($default_value[$i]['response']['extra_button']['extra_button']) && !empty($default_value[$i]['response']['extra_button']['extra_button']) ? $default_value[$i]['response']['extra_button']['extra_button'] : [],
        ];
      }


      $element['update_extra_button_' . $i] = [
        '#type'                    => 'submit',
        '#value'                   => t('Update Extra Button'),
        '#name' => 'update_extra_button_' . $i,
        '#attributes' => [
          'style' => ['display:none;'],
        ],
        '#ajax'                    => [
          'callback' => [static::class, 'updateExtraButton'],
          'wrapper'  => $extra_button_wrapper,
          'event'    => 'click',
          'id' => $i,
        ],
        '#limit_validation_errors' => [],
        '#submit' => [[static::class, 'updateItemsExtraButton']],
      ];

      $element['delete_screen_' . $i] = [
        '#type'                    => 'submit',
        '#value'                   => t('Delete screen'),
        '#name' => 'delete_screen_' . $i,
        '#attributes' => [
          'style' => ['display:none;'],
        ],
        '#ajax'                    => [
          'callback' => [static::class, 'updateScreensWidget'],
          'wrapper' => $wrapper_id,
          'event'    => 'click',
        ],
        '#limit_validation_errors' => [],
        '#submit' => [[static::class, 'updateScreensAfterDelete']],
      ];
      $j++;
    }

    $element['#prefix'] = '<div id="' . $wrapper_id . '">';
    $element['#suffix'] = '</div>';
    $element['add_more'] = [
      '#type' => 'submit',
      '#name' => strtr($id_prefix, '-', '_') . '_add_more',
      '#value' => "add more",
      '#attributes' => ['class' => ['id-label-add-more-submit']],
      '#submit' => [[static::class, 'addMoreSubmit']],
      '#ajax' => [
        'callback' => [static::class, 'addMoreAjax'],
        'wrapper' => $wrapper_id,
        'effect' => 'fade',
      ],
    ];

    return $element;
  }

  /**
   * Get the element state function.
   */
  public static function getElementState(array $parents, FormStateInterface $form_state): ?array {
    return NestedArray::getValue($form_state->getStorage(), $parents);
  }

  /**
   * Set the element state function.
   */
  public static function setElementState(array $parents, FormStateInterface $form_state, array $field_state): void {
    NestedArray::setValue($form_state->getStorage(), $parents, $field_state);
  }

  /**
   * Add More ajax btn.
   */
  public static function addMoreAjax(array $form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();
    $element = NestedArray::getValue($form, array_slice($button['#array_parents'], 0, -1));
    return $element;
  }

  /**
   * Add more submit.
   */
  public static function addMoreSubmit(array $form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();
    $element = NestedArray::getValue($form, array_slice($button['#array_parents'], 0, -1));
    $parents = $element['#parents'];
    $element_state = static::getElementState($parents, $form_state);
    $element_state['items_count']++;
    static::setElementState($parents, $form_state, $element_state);
    $form_state->setRebuild();
  }

  /**
   * On change layout function.
   */
  public static function onChangeLayout(array $form, FormStateInterface $form_state) {
    $select = $form_state->getTriggeringElement();
    preg_match_all('!\d+!', $select['#name'], $matches);
    $i = $matches[0][1];
    $response = new AjaxResponse();
    $response->addCommand(new InvokeCommand("[name=update_background_$i]", 'trigger', ['click']));
    return $response;
  }
  /**
   * On change quiz function.
   */
  public static function onChangeQuiz(array $form, FormStateInterface $form_state) {
    $select = $form_state->getTriggeringElement();
    preg_match_all('!\d+!', $select['#name'], $matches);
    $i = $matches[0][1];
    $response = new AjaxResponse();
    $response->addCommand(new InvokeCommand("[name=update_quiz_details_$i]", 'trigger', ['click']));
    return $response;
  }
  /**
   * On change Extra button.
   */
  public static function onChangeExtraButton(array $form, FormStateInterface $form_state) {
    $select = $form_state->getTriggeringElement();
    preg_match_all('!\d+!', $select['#name'], $matches);
    $i = $matches[0][1];
    $response = new AjaxResponse();
    $response->addCommand(new InvokeCommand("[name=update_extra_button_$i]", 'trigger', ['click']));
    return $response;
  }

  /**
   * On change response type.
   */
  public static function onChangeResponseType(array $form, FormStateInterface $form_state) {
    $select = $form_state->getTriggeringElement();
    preg_match_all('!\d+!', $select['#name'], $matches);
    $i = $matches[0][1];
    $response = new AjaxResponse();
    $response->addCommand(new InvokeCommand("[name=update_type_response_$i]", 'trigger', ['click']));
    return $response;
  }

  /**
   * The delete screen callback.
   */
  public static function deleteFormCallback(array $form, FormStateInterface $form_state) {
    $select = $form_state->getTriggeringElement();
    preg_match_all('!\d+!', $select['#name'], $matches);
    $i = $matches[0][0];
    $response = new AjaxResponse();
    $response->addCommand(new InvokeCommand("[name=delete_screen_$i]", 'trigger', ['click']));
    return $response;
  }

  /**
   * Dynamic video ask form element validate callback.
   */
  public static function validateDynamicVideoAsk(&$element, FormStateInterface $form_state, &$form) {
    $values = $form_state->getValue($element['#parents'])['screen_details'];
    foreach ($values as $key => $value) {
      if (!is_numeric($key)) {
        unset($values[$key]);
      }
      else {
        if (array_key_exists('remove_screen', $values[$key])) {
          unset($values[$key]['remove_screen']);
        }
        if (array_key_exists('update_background', $values[$key]['layout'])) {
          unset($values[$key]['layout']['update_background']);
        }
        if (array_key_exists('update_type_response', $values[$key]['response'])) {
          unset($values[$key]['response']['update_type_response']);
        }
        if (array_key_exists('settings', $values[$key]['response'])) {
          if (array_key_exists('add_more', $values[$key]['response']['settings'])) {
            unset($values[$key]['response']['settings']['add_more']);
          }
          if (array_key_exists('delete_item', $values[$key]['response']['settings'])) {
            unset($values[$key]['response']['settings']['delete_item']);
          }
          if (isset($values[$key]['response']['settings']) && !empty($values[$key]['response']['settings'])) {
            if (!array_key_exists('answers', $values[$key]['response']['settings'])) {
              foreach ($values[$key]['response']['settings'] as $index => $item) {
                if (is_numeric($index)) {
                  unset($values[$key]['response']['settings'][$index]['answers']['add_more']);
                  unset($values[$key]['response']['settings'][$index]['answers']['delete_item']);
                }
              }
            }
            else {
              unset($values[$key]['response']['settings']['answers']['add_more']);
              unset($values[$key]['response']['settings']['answers']['delete_item']);
            }
          }
        }
      }

      // Load image and video.
      if (isset($values[$key]['layout']) && !empty($values[$key]['layout'])) {
        $bg = $values[$key]['layout']['background'];
        if ($bg == 'image') {
          $mid = $values[$key]['layout']['image'];
          if (isset($mid) && !empty($mid)) {
            $media = Media::load($mid);
            if (isset($media) && !empty($media)) {
              $fid = $media->field_image_onboarding->target_id;
              $file = File::load($fid);
              $file->setPermanent();
              $file->save();
              $url = \Drupal::service('file_url_generator')->transformRelative(\Drupal::service('stream_wrapper_manager')->getViaUri($file->getFileUri())->getExternalUrl());
              $image = [
                'id' => $mid,
                'url' => $url,
              ];
              $values[$key]['layout']['image'] = $image;
            }
          }
        }
        if ($bg == 'video') {
          $mid = $values[$key]['layout']['video'];
          if (isset($mid) && !empty($mid)) {
            $media = Media::load($mid);
            if (isset($media) && !empty($media)) {
              $fid = $media->field_video_onboarding->target_id;
              $file = File::load($fid);
              $file->setPermanent();
              $file->save();
              $url = \Drupal::service('file_url_generator')->transformRelative(\Drupal::service('stream_wrapper_manager')->getViaUri($file->getFileUri())->getExternalUrl());
              $video = [
                'id' => $mid,
                'url' => $url,
              ];
              $values[$key]['layout']['video'] = $video;
            }
          }
        }
      }
    }
    $values = array_values($values);
    $form_state->setValue($element['#parents'], ['screen_details' => $values]);
  }

  /**
   * Update items layout background callback.
   */
  public static function updateItemsLayoutBackground(array $form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();
    $element = NestedArray::getValue($form, array_slice($button['#array_parents'], 0, -1));
    $parents = $element['#parents'];
    $element_state = static::getElementState($parents, $form_state);
    preg_match_all('!\d+!', $button['#name'], $matches);
    $i = $matches[0][0];
    $element_state['video_ask'][$i]['selected_layout'] = $element['screen_details'][$i]['layout']['background']['#value'];
    static::setElementState($parents, $form_state, $element_state);
    $form_state->setRebuild();
  }

  /**
   * Update quiz details callback.
   */
  public static function updateItemsQuiz(array $form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();
    $element = NestedArray::getValue($form, array_slice($button['#array_parents'], 0, -1));
    $parents = $element['#parents'];
    $element_state = static::getElementState($parents, $form_state);
    preg_match_all('!\d+!', $button['#name'], $matches);
    $i = $matches[0][0];
    $element_state['video_ask'][$i]['quiz']['part_of_quiz'] = $element['screen_details'][$i]['quiz']['part_of_quiz']['#value'];
    static::setElementState($parents, $form_state, $element_state);
    $form_state->setRebuild();
  }

  /**
   * Update extra button details callback.
   */
  public static function updateItemsExtraButton(array $form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();
    $element = NestedArray::getValue($form, array_slice($button['#array_parents'], 0, -1));
    $parents = $element['#parents'];
    $element_state = static::getElementState($parents, $form_state);
    preg_match_all('!\d+!', $button['#name'], $matches);
    $i = $matches[0][0];
    $element_state['video_ask'][$i]['response']['extra_button']['use_extra_button'] = $element['screen_details'][$i]['response']['extra_button']['use_extra_button']['#value'];
    static::setElementState($parents, $form_state, $element_state);
    $form_state->setRebuild();
  }

  /**
   * Update widget layout background callback.
   */
  public static function updateWidgetLayoutBackground(array $form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();
    $element = NestedArray::getValue($form, array_slice($button['#array_parents'], 0, -1));
    preg_match_all('!\d+!', $button['#name'], $matches);
    $i = $matches[0][0];
    return $element['screen_details'][$i]['layout'];
  }

  /**
   * Update widget quiz callback.
   */
  public static function updateQuizWidget(array $form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();
    $element = NestedArray::getValue($form, array_slice($button['#array_parents'], 0, -1));
    preg_match_all('!\d+!', $button['#name'], $matches);
    $i = $matches[0][0];
    return $element['screen_details'][$i]['quiz'];
  }
  /**
   * Update Extra button.
   */
  public static function updateExtraButton(array $form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();
    $element = NestedArray::getValue($form, array_slice($button['#array_parents'], 0, -1));
    preg_match_all('!\d+!', $button['#name'], $matches);
    $i = $matches[0][0];
    return $element['screen_details'][$i]['response']['extra_button'];
  }

  /**
   * Update items type response.
   */
  public static function updateItemsTypeResponse(array $form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();
    $element = NestedArray::getValue($form, array_slice($button['#array_parents'], 0, -1));
    $parents = $element['#parents'];
    $element_state = static::getElementState($parents, $form_state);
    preg_match_all('!\d+!', $button['#name'], $matches);
    $i = $matches[0][0];
    $element_state['video_ask'][$i]['response_type'] = $element['screen_details'][$i]['response']['type_response']['#value'];
    static::setElementState($parents, $form_state, $element_state);
    $form_state->setRebuild();
  }

  /**
   * Update widget type response.
   */
  public static function updateWidgetTypeResponse(array $form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();
    preg_match_all('!\d+!', $button['#name'], $matches);
    $i = $matches[0][0];
    $element = NestedArray::getValue($form, array_slice($button['#array_parents'], 0, -1));
    return $element['screen_details'][$i]['response'];
  }

  /**
   * Update screens after delete.
   */
  public static function updateScreensAfterDelete(array $form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();
    $element = NestedArray::getValue($form, array_slice($button['#array_parents'], 0, -1));
    $parents = $element['#parents'];
    $element_state = static::getElementState($parents, $form_state);
    preg_match_all('!\d+!', $button['#name'], $matches);
    $i = $matches[0][0];
    $element_state['video_ask'][$i]['screen_to_delete'] = $i;
    static::setElementState($parents, $form_state, $element_state);
    $form_state->setRebuild();
  }

  /**
   * Update screens widget.
   */
  public static function updateScreensWidget(array $form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();
    $element = NestedArray::getValue($form, array_slice($button['#array_parents'], 0, -1));
    return $element;
  }

}
