<?php

namespace Drupal\Tests\lehigh_islandora\ExistingSite;

use Drupal\Core\File\FileExists;
use Drupal\Core\File\FileSystemInterface;
use Drupal\media\Entity\Media;
use Drupal\user\Entity\User;
use weitzman\DrupalTestTraits\ExistingSiteBase;
use weitzman\DrupalTestTraits\Entity\MediaCreationTrait;
use weitzman\DrupalTestTraits\Entity\NodeCreationTrait;

/**
 * Test to ensure aggregated PDFs get created.
 */
class PagedContentAggregatedPdfTest extends ExistingSiteBase {

  use MediaCreationTrait;
  use NodeCreationTrait;

  /**
   *
   */
  protected function setUp(): void {
    parent::setUp();

    // copy test files into place
    $source_dir = DRUPAL_ROOT . '/modules/contrib/lehigh_islandora/tests/assets/pc';
    $destination_dir = 'public://tests';
    $fs = \Drupal::service('file_system');
    $fs->prepareDirectory($destination_dir, FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS);
    $files = scandir($source_dir);
    foreach ($files as $file) {
      if ($file === '.' || $file === '..') {
        continue;
      }
  
      $source_path = $source_dir . '/' . $file;
      $destination_path = $destination_dir . '/' . $file;
  
      $fs->copy($source_path, $destination_path, FileExists::Replace);
    }
  
    // Cause tests to fail if an error is sent to Drupal logs.
    $this->failOnLoggedErrors();
  }

  /**
   * Make sure we can create and view sub collections.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Drupal\Core\Entity\EntityMalformedException
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testAggregatedPdf() {
    $admin = User::load(1);
    $this->drupalLogin($admin);

    $nids = [];
    $parent = $this->createNode([
      'title' => 'Paged Content Item #1',
      'type' => 'islandora_object',
      'uid' => 1,
      'status' => 1,
      'field_model' => lehigh_islandora_get_tid_by_name('Paged Content', 'islandora_models'),
    ]);
    $nids[] = $parent->id();

    $file_storage = \Drupal::entityTypeManager()->getStorage('file');
    foreach (range(0, 5) as $children) {
      $node = $this->createNode([
        'title' => 'Page #' . $children,
        'type' => 'islandora_object',
        'uid' => 1,
        'field_model' => lehigh_islandora_get_tid_by_name('Page', 'islandora_models'),
        'field_member_of' => $parent->id(),
        'status' => 1,
      ]);
      $nids[] = $node->id();

      /** @var \Drupal\file\FileInterface $entity */
      $file = $file_storage->create([
        'uri' => "public://tests/$children.tiff",
        'filename' => "$1.tiff",
        'filemime' => 'image/tiff',
      ]);
      $file->save();
      $this->markEntityForCleanup($file);

      $this->createMedia([
        'name' => 'Page #' . $children,
        'bundle' => 'file',
        'uid' => 1,
        'field_media_file' => $file->id(),
        'field_media_use' => lehigh_islandora_get_tid_by_name('Original File', 'islandora_media_use'),
        'field_media_of' => $node->id(),
        'status' => 1,
      ]);
    }

    foreach (range(0, 50) as $i) {
      $mid = \Drupal::database()->query('SELECT m.entity_id
        FROM media__field_media_of m
        INNER JOIN media__field_media_use mu ON mu.entity_id = m.entity_id
        WHERE field_media_use_target_id = :tid AND field_media_of_target_id = :nid', [
          ':tid' => lehigh_islandora_get_tid_by_name('Original File', 'islandora_media_use'),
          ':nid' => $parent->id(),
      ])->fetchField();
      if ($mid) {
        $media = Media::load($mid);
        $this->assertEquals($parent->id() . ".pdf", $media->label());
        break;
      }
      sleep(5);
    }
    $mids = \Drupal::database()->query('SELECT m.entity_id
      FROM media__field_media_of m
      INNER JOIN media__field_media_use mu ON mu.entity_id = m.entity_id
      WHERE field_media_of_target_id IN (:nids[])', [
          ':nids[]' => $nids,
      ])->fetchField();

    foreach ($mids as $mid) {
      $media = Media::load($mid);

      $file = FALSE;
      switch ($media->bundle()) {
        case 'document':
          $file = $media->field_media_document->entity;
          break;
        case 'file':
          $file = $media->field_media_file->entity;
          break;
        case 'image':
          $file = $media->field_media_image->entity;
          break;
      }
      if ($file) {
        $this->markEntityForCleanup($file);
      }
      $this->markEntityForCleanup($media);
    }
  }

}
