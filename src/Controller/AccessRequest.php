<?php

namespace Drupal\lehigh_islandora\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

class AccessRequest extends ControllerBase {

    protected $entityTypeManager;
    protected $renderer;
    protected $formBuilder;

    public function __construct(EntityTypeManagerInterface $entity_type_manager, RendererInterface $renderer, FormBuilderInterface $form_builder) {
        $this->entityTypeManager = $entity_type_manager;
        $this->renderer = $renderer;
        $this->formBuilder = $form_builder;
    }

    public static function create(ContainerInterface $container) {
        return new static(
            $container->get('entity_type.manager'),
            $container->get('renderer'),
            $container->get('form_builder')
        );
    }

    public function accessRequestForm() {
        $entity = $this->entityTypeManager->getStorage('contact_message')->create([
            'contact_form' => 'access_request',
        ]);
        $form = $this->entityTypeManager->getFormObject('contact_message', 'default')->setEntity($entity);
        $form_render_array = $this->formBuilder->getForm($form);
        $form_render_array['#action'] = '/contact/access_request';

        return new JsonResponse(['form' => $this->renderer->renderRoot($form_render_array)]);
    }
}
