<?php

namespace Drupal\lehigh_islandora\Encoder;

use Symfony\Component\Serializer\Encoder\CsvEncoder;

/**
 * Workbench CSV encoder.
 */
class WorkbenchCsvEncoder extends CsvEncoder {

  /**
   * The formats that this Encoder supports.
   *
   * @var string
   */
  protected $format = 'workbench_csv';

  /**
   * {@inheritdoc}
   */
  public function supportsEncoding(string $format) : bool {
    return $format == $this->format;
  }

  /**
   * {@inheritdoc}
   */
  public function supportsDecoding(string $format) : bool {
    return in_array($format, [$this->format]);
  }

  /**
   * {@inheritdoc}
   */
  public function decode(string $data, string $format, array $context = []) : mixed {
    return parent::decode($data, $format, $context);
  }

  public function getRow($data) {
    $entity_type_manager = \Drupal::entityTypeManager();
    $entity_storage = [];
    $remove = [
      'uid',
      'uuid',
      'vid',
      'langcode',
      'type',
      'status',
      'path',
      'revision_timestamp',
      'revision_uid',
      'revision_log',
      'metatag',
      'sticky',
      'promote',
      'changed',
      'created',
      'default_langcode',
      'revision_translation_affected',
      'content_translation_source',
      'content_translation_outdated',
    ];
    foreach ($remove as $field) {
      unset($data[$field]);
    }
    $row = [];
    foreach ($data as $fieldName => $fieldValues) {
      $cell = [];
      foreach ($fieldValues as $k => $fieldValue) {
        $value = '';
        if (!empty($fieldValue['target_id'])) {
          $entity_type = $fieldValue['target_type'];
          if (!isset($entity_storage[$entity_type])) {
            $entity_storage[$entity_type] = $entity_type_manager->getStorage($entity_type);
          }
          if ($fieldName !== "field_member_of" && !empty($entity_storage[$entity_type])) {
            $entity = $entity_storage[$entity_type]->load($fieldValue['target_id']);
            if ($entity) {
              // TODO only add if field settings have more than one bundle
              $value = $entity->bundle() . ":" . $entity->label();
              if (isset($fieldValue['rel_type'])) {
                $value = $fieldValue['rel_type'] . ":" . $value;
              }
            }
          }
          if ($value == '') {
            $value = $fieldValue['target_id'];
          }
        }
        elseif (isset($fieldValue['attr0'])) {
          $cleanValue = [];
          foreach ($fieldValue as $key => $value) {
            if (!is_null($value)) {
              $cleanValue[$key] = $value;
            }
          }
          $value = json_encode($cleanValue);
        }
        elseif (isset($fieldValue['value'])) {
          $value = $fieldValue['value'];
        }

        $cell[] = $value;
      }
      if ($fieldName == 'nid') {
        $fieldName = 'node_id';
      }
      $row[$fieldName] = implode("|", $cell);
    }

    return $row;
  }

  /**
   * {@inheritdoc}
   */
  public function encode($data, $format, array $context = []) : string { 
    $row = $this->getRow($data);
    $header = array_keys($row);
    $rows = [
      $header,
      array_values($row),
    ];
    if (!empty($row['node_id'])) {
      $serializer = \Drupal::service('serializer');
      $entity_ids = \Drupal::entityQuery('node')
        ->condition('field_member_of', $row['node_id'])
        ->accessCheck(TRUE)
        ->execute();
      $nodes =  \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($entity_ids);
      foreach ($nodes as $node) {
        $data = $serializer->serialize($node, 'json', ['plugin_id' => 'entity']);
        $data = json_decode($data, true);
        $row = $this->getRow($data);
        $rows[] = array_values($row);
      }
    }

    $context['no_headers'] = TRUE;
    $csv = parent::encode($rows, 'csv', $context);

    return $csv;
  }

}
