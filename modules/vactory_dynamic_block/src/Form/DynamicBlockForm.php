<?php

declare(strict_types=1);

namespace Drupal\vactory_dynamic_block\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the dynamic block entity edit forms.
 */
final class DynamicBlockForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildForm($form, $form_state);
    $config = \Drupal::config('vactory_dynamic_block.settings');

    // Attach libraries.
    $form['#attached']['library'][] = 'vactory_dynamic_block/babel';
    $form['#attached']['library'][] = 'vactory_dynamic_block/dynamic-block-form';

    // Get module path for tailwindcss-iso.
    $modulePath = \Drupal::service('extension.list.module')->getPath('vactory_dynamic_block');

    // Pass settings to JS.
    $form['#attached']['drupalSettings']['vactoryDynamicBlock'] = [
      'customThemeCSS' => $config->get('custom_theme_css') ?? '',
      'blockId' => $this->entity->get('block_id')->value ?? '',
      'tailwindIsoPath' => '/' . $modulePath . '/js/tailwindcss-iso.js',
    ];

    // Two-column layout wrapper.
    $form['#prefix'] = '<div class="dynamic-block-form-wrapper" style="display:grid;grid-template-columns:1fr 1fr;gap:2rem;align-items:start;">';
    $form['#suffix'] = '</div>';

    // Left column container for form fields.
    $form['left_column'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['form-column-left']],
      '#weight' => -100,
    ];

    // Move form fields to left column.
    $form['left_column']['label'] = $form['label'];
    $form['left_column']['type'] = $form['type'];
    $form['left_column']['content'] = $form['content'];
    unset($form['label'], $form['type'], $form['content']);

    // Hidden fields for generated data (populated by JavaScript).
    $form['css'] = [
      '#type' => 'hidden',
      '#default_value' => $this->entity->get('css')->value ?? '',
      '#attributes' => ['id' => 'edit-css'],
    ];

    $form['js_structure'] = [
      '#type' => 'hidden',
      '#default_value' => $this->entity->get('js_structure')->value ?? '',
      '#attributes' => ['id' => 'edit-js-structure'],
    ];

    $form['block_id'] = [
      '#type' => 'hidden',
      '#default_value' => $this->entity->get('block_id')->value ?? '',
      '#attributes' => ['id' => 'edit-block-id'],
    ];

    // Preview button (client-side only).
    $form['left_column']['preview_button'] = [
      '#type' => 'button',
      '#value' => $this->t('Preview'),
      '#attributes' => [
        'class' => [
          'js-dynamic-block-preview',
        ],
        'type' => 'button',
      ],
      '#weight' => 90,
    ];

    // Code preview container (CSS/JSX structure output).
    $form['left_column']['preview_container'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => [
          'dynamic-block-preview',
        ],
        'id' => 'dynamic-block-preview',
      ],
      '#weight' => 91,
    ];

    // Right column - Live preview panel.
    $form['right_column'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['form-column-right', 'live-preview-column']],
      '#weight' => -99,
    ];

    $form['right_column']['preview_title'] = [
      '#markup' => '<h3 style="margin-top:0;margin-bottom:1rem;font-size:1.1rem;font-weight:600;">Live Preview</h3>',
    ];

    // Live preview mount point for React.
    $form['right_column']['live_preview'] = [
      '#type' => 'container',
      '#attributes' => [
        'id' => 'dynamic-block-live-preview',
        'class' => ['live-preview-panel'],
        'style' => 'min-height:400px;border:1px solid #ccc;border-radius:8px;padding:1rem;background:#fff;overflow:auto;',
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    // Set hidden field values to the entity before saving.
    $css = $form_state->getValue('css');
    $js_structure = $form_state->getValue('js_structure');
    $block_id = $form_state->getValue('block_id');

    if (!empty($css)) {
      $this->entity->set('css', $css);
    }
    if (!empty($js_structure)) {
      $this->entity->set('js_structure', $js_structure);
    }
    if (!empty($block_id)) {
      $this->entity->set('block_id', $block_id);
    }

    $result = parent::save($form, $form_state);

    $message_args = ['%label' => $this->entity->toLink()->toString()];
    $logger_args = [
      '%label' => $this->entity->label(),
      'link' => $this->entity->toLink($this->t('View'))->toString(),
    ];

    switch ($result) {
      case SAVED_NEW:
        $this->messenger()->addStatus($this->t('New dynamic block %label has been created.', $message_args));
        $this->logger('vactory_dynamic_block')->notice('New dynamic block %label has been created.', $logger_args);
        break;

      case SAVED_UPDATED:
        $this->messenger()->addStatus($this->t('The dynamic block %label has been updated.', $message_args));
        $this->logger('vactory_dynamic_block')->notice('The dynamic block %label has been updated.', $logger_args);
        break;

      default:
        throw new \LogicException('Could not save the entity.');
    }

    $form_state->setRedirectUrl($this->entity->toUrl('collection'));

    return $result;
  }

}
