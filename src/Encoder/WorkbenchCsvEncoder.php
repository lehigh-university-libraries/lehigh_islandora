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

  public function getRow(array $data, array $multipleBundles, array $header = []) : array {
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

    // if the header from the first entity was passed
    // make 100% sure the array we iterator over is in the same order
    // as the first entity
    if (count($header)) {
      $newData = [];
      foreach ($header as $column) {
        if ($column == "node_id") {
          $column = "nid";
        }
        $newData[$column] = $data[$column];
      }
      $data = $newData;
    }

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
              $value = in_array($fieldName, $multipleBundles) ? $entity->bundle() . ":" . $entity->label() : $entity->label();
              if (isset($fieldValue['rel_type'])) {
                $value = $fieldValue['rel_type'] . ":" . $value;
              }
            }
          }
          if ($value == '') {
            $value = $fieldValue['target_id'];
          }
        }
        elseif (isset($fieldValue['attr0']) || isset($fieldValue['format'])) {
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
    // find which fields have multiple taxonomy vocabs to choose from
    $multipleBundles = [];
    if (!empty($data['nid'][0]['value'])) {
      $node_storage = \Drupal::entityTypeManager()->getStorage('node');
      $node = $node_storage->load($data['nid'][0]['value']);
      foreach($node as $fieldName => $values) {
        $settings = $node->get($fieldName)->getSettings();
        if (!empty($settings['handler_settings']['target_bundles']) && count($settings['handler_settings']['target_bundles']) > 1) {
          $multipleBundles[] = $fieldName;
        }
      }
    }

    $row = $this->getRow($data, $multipleBundles);
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
      $nodes =  $node_storage->loadMultiple($entity_ids);
      foreach ($nodes as $node) {
        $data = $serializer->serialize($node, 'json', ['plugin_id' => 'entity']);
        $data = json_decode($data, true);
        $row = $this->getRow($data, $multipleBundles, $header);
        $rows[] = array_values($row);
      }
    }

    $context['no_headers'] = TRUE;

    // assume all columns are empty
    $empty_cell = [];
    foreach ($header as $k => $v) {
      $empty_cell[$k] = TRUE;
    }

    // mark columns that have a value in some row as non-empty
    $first = TRUE;
    foreach ($rows as $row) {
      if ($first) {
        $first = FALSE;
        continue;
      }
      foreach ($row as $k => $cell) {
        if (!empty($cell)) {
          $empty_cell[$k] = FALSE;
        }
      }
    }

    // remove empty columns
    $trimmed_rows = [];
    foreach ($rows as $row) {
      $trimmed_row = [];
      foreach ($row as $k => $cell) {
        if (!$empty_cell[$k]) {
          $trimmed_row[] = $cell;
        }
      }
      $trimmed_rows[] = $trimmed_row;
    }

    $csv = parent::encode($trimmed_rows, 'csv', $context);

    return $csv;
  }

}
