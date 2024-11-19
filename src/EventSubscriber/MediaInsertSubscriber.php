<?php

namespace Drupal\lehigh_islandora\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\lehigh_islandora\Event\MediaInsertEvent;

/**
 * Subscribes to custom media insert events.
 */
class MediaInsertSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      MediaInsertEvent::NAME => 'onMediaInsert',
    ];
  }

  /**
   * Responds to media insert events.
   *
   * @param \Drupal\lehigh_islandora\Event\MediaInsertEvent $event
   *   The custom media insert event.
   */
  public function onMediaInsert(MediaInsertEvent $event) {
    $media = $event->getMedia();
    if (!$media->hasField('field_media_of') ||
      $media->field_media_of->isEmpty() ||
      is_null($media->field_media_of->entity)) {
      return;
    }

    if (!$media->hasField('field_media_use') ||
      $media->field_media_use->isEmpty() ||
      is_null($media->field_media_use->entity)) {
      return;
    }

    $action_name = '';
    foreach ($media->field_media_of as $field_media_of) {
      $node = $field_media_of->entity;
      // check if microsoft file needs PDF
      if ($media->bundle() === 'file') {
        switch($media->field_media_use->entity->field_external_uri->uri) {
          case 'http://pcdm.org/use#PreservationMasterFile':
            $action_name = 'microsoft_document_to_pdf';
            break;
        }
      }
      // check if original file needs coverpage
      elseif ($node->hasField('field_add_coverpage') &&
        $node->field_add_coverpage->isEmpty() &&
        $node->field_add_coverpage->value) {

        if ($media->bundle() == 'document') {
          switch($media->field_media_use->entity->field_external_uri->uri) {
            case 'http://pcdm.org/use#OriginalFile':
              $action_name = 'digital_document_add_coverpage';
              break;
          }
        }
      }
      elseif ($node->hasField('field_model') &&
        $node->field_model->isEmpty() &&
        !is_null($node->field_model->entity)) {
        switch($media->field_media_use->entity->field_external_uri->uri) {
          case 'http://id.loc.gov/ontologies/bibframe/part':
            // todo: check if paged content parent has all children
            break;
          case 'http://vocab.getty.edu/page/aat/300027363':
          // todo: if zip, parse directory tree and create manifest
          break;
        }
      }
      if ($action_name !== '') {
        $action_storage = \Drupal::entityTypeManager()->getStorage('action');
        $action = $action_storage->load($action_name);
        $action->execute([$node]);
      }
    }
  }
}
