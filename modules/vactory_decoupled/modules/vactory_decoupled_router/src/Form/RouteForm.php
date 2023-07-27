<?php

namespace Drupal\vactory_decoupled_router\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form handler for the Route add and edit forms.
 */
class RouteForm extends EntityForm
{

    /**
     * Constructs an RouteForm object.
     *
     * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
     *   The entityTypeManager.
     */
    public function __construct(EntityTypeManagerInterface $entityTypeManager)
    {
        $this->entityTypeManager = $entityTypeManager;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container)
    {
        return new static(
            $container->get('entity_type.manager')
        );
    }

    /**
     * {@inheritdoc}
     */
    public function form(array $form, FormStateInterface $form_state)
    {
        $form = parent::form($form, $form_state);

        $route = $this->entity;

        $form['label'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Label'),
            '#maxlength' => 255,
            '#default_value' => $route->label(),
            '#description' => $this->t("Label for the Route."),
            '#required' => TRUE,
        ];

        $form['id'] = [
            '#type' => 'machine_name',
            '#default_value' => $route->id(),
            '#machine_name' => [
                'exists' => [$this, 'exist'],
            ],
            '#disabled' => !$route->isNew(),
        ];

        $form['path'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Path'),
            '#maxlength' => 255,
            '#default_value' => $route->getPath(),
            '#description' => $this->t("Specify the existing path you wish to alias. For example: /node/28."),
            '#required' => TRUE,
        ];

        $form['alias'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Alias'),
            '#maxlength' => 255,
            '#default_value' => $route->getAlias(),
            '#description' => $this->t('Specify an alternative path by which the path can be accessed. For example "/fr/account/login" or use a pattern "/fr/store-locator/{category}/{city}".'),
            '#required' => TRUE,
        ];

        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function save(array $form, FormStateInterface $form_state)
    {
        $route = $this->entity;
        $status = $route->save();

        if ($status === SAVED_NEW) {
            $this->messenger()->addMessage($this->t('The %label Route created.', [
                '%label' => $route->label(),
            ]));
        } else {
            $this->messenger()->addMessage($this->t('The %label Route updated.', [
                '%label' => $route->label(),
            ]));
        }

        $form_state->setRedirect('entity.vactory_route.collection');
    }

    /**
     * Helper function to check whether an Route configuration entity exists.
     */
    public function exist($id)
    {
        $entity = $this->entityTypeManager->getStorage('vactory_route')
          ->getQuery()
          ->condition('id', $id)
          ->accessCheck(FALSE)
          ->execute();
        return (bool) $entity;
    }
}
