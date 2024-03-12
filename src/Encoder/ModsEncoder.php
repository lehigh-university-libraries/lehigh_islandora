<?php

namespace Drupal\lehigh_islandora\Encoder;

use Symfony\Component\Serializer\Encoder\XmlEncoder;

/**
 * Mods encoder.
 */
class ModsEncoder extends XmlEncoder {

  const ROOT_NODE_NAME = 'xml_root_node_name';

  /**
   * The formats that this Encoder supports.
   *
   * @var string
   */
  protected $format = 'mods';

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
    return $format == $this->format;
  }

  /**
   * {@inheritdoc}
   */
  public function encode($entity, $format, array $context = []) : string {
    $context[self::ROOT_NODE_NAME] = 'mods';
    $mods = [
      "@xmlns" => "http://www.loc.gov/mods/v3",
      "@xmlns:xsi" => "http://www.w3.org/2001/XMLSchema-instance",
      "@xmlns:mods" => "http://www.loc.gov/mods/v3",
      "@xmlns:xlink" => "http://www.w3.org/1999/xlink",
    ];

    $mods['titleInfo']['title'] = $entity->field_full_title->isEmpty() ? $entity->label() : $entity->field_full_title->value;
    $count = 0;
    foreach ($entity->field_linked_agent as $agent) {
      if ($agent->rel_type == 'relators:pbl') {
        $mods['originInfo'] = [
          'publisher' => $agent->entity->label(),
        ];
      }
      else {
        $mods['name'][] = [
          'namePart' => $agent->entity->label(),
          'role' => [
            [
              'roleTerm' => [
                [
                  "#" => str_replace('relators:', '', $agent->rel_type),
                  "@type" => "code",
                  "@authority" => "marcrelator",
                ],
                [
                  "#" => str_replace('relators:', '', $agent->rel_type),
                  "@type" => "text",
                  "@authority" => "marcrelator",
                ],
              ],
            ],
            [
              'roleTerm' => ++$count,
            ],
          ],
        ];
      }
    }

    foreach ($entity->field_department_name as $department) {
      $mods['name'][] = [
        "@type" => "corporate",
        'namePart' => $department->entity->label(),
        'role' => [
          [
            'roleTerm' => 'Department',
          ],
          [
            'roleTerm' => ++$count,
          ],
        ],
      ];
    }
    if (!$entity->field_genre->isEmpty()) {
      $mods['genre']['#'] = $entity->field_genre->entity->label();
      if (!$entity->field_genre_uri->isEmpty()) {
        if (strpos($entity->field_genre_uri->uri, '/aat/') !== FALSE) {
          $mods['genre']['@authority'] = 'aat';
        }
        $mods['genre']['@valueURI'] = $entity->field_genre_uri->uri;
      }
    }

    $fields = [
      'field_abstract'             => 'abstract',
      'field_resource_type'        => 'typeOfResource',
      'field_rights'               => 'accessCondition',
      'field_classification'       => 'classification',
      "field_identifier"           => "identifier",
      "field_note"                 => "note",
      "field_table_of_contents"    => "tableOfContents",
      "field_media_type"           => ["physicalDescription", "internetMediaType"],
      "field_language"             => ["language", "languageTerm"],
      "field_physical_location"    => ["location", "physicalLocation"],
      "field_date_captured"        => ["originInfo", "dateCaptured"],
      "field_edtf_date_created"    => ["originInfo", "dateCreated"],
      "field_edtf_date_issued"     => ["originInfo", "dateIssued"],
      "field_date_other"           => ["originInfo", "dateOther"],
      "field_date_valid"           => ["originInfo", "dateValid"],
      "field_edition"              => ["originInfo", "edition"],
      "field_extent"               => ["physicalDescription", "extent"],
      "field_physical_form"        => ["physicalDescription", "form"],
      "field_media_type"           => ["physicalDescription", "internetMediaType"],
      "field_mode_of_issuance"     => ["originInfo", "issuance"],
      "field_digital_origin"       => ["physicalDescription", "digitalOrigin"],
      "field_place_published"      => ["originInfo", "place", "placeTerm"],
      "field_record_origin"        => ["recordInfo", "recordOrigin"],
      "field_physical_description" => ["physicalDescription", "note"],
    ];
    foreach ($fields as $fieldName => $modsField) {
      if (is_string($modsField)) {
        $modsField = [$modsField];
      }
      foreach ($entity->$fieldName as $field) {
        $tempModsField = &$mods;
        foreach ($modsField as $subfield) {
          $tempModsField = &$tempModsField[$subfield];
        }
        $value = [
          "#" => is_null($field->entity) ? $field->value : $field->entity->label(),
        ];
        if (!empty($field->attr0)) {
          // TODO: lookup field and get value for attr0 instead of assumming it's type
          if ($fieldName == 'field_extent') {
            $value["@unit"] = $field->attr0;
          }
          else {
            $value["@type"] = $field->attr0;
          }
        }
        if (!empty($field->attr1)) {
          // TODO: lookup field and get value for attr1 instead of assumming it's type
          $value["@unit"] = $field->attr1;
        }
        $tempModsField[] = $value;
      }
    }

    $fields = [
      "field_subject",
      "field_geographic_subject",
      "field_subjects_name",
    ];
    foreach ($fields as $fieldName) {
      foreach ($entity->$fieldName as $field) {
        $subject = [];
        if ($fieldName == 'field_subject') {
          $subject['topic'] = [
            "#" => $field->entity->label(),
          ];
        }
        elseif ($fieldName == 'field_geographic_subject') {
          $subject['geographic'] = [
            "#" => $field->entity->label(),
          ];
          if ($field->entity->vid->value == 'geographic_naf') {
            $subject['@authority'] = 'naf';
          }
          elseif ($field->entity->vid->value == 'geographic_local') {
            $subject['@authority'] = 'local';
          }
        }
        elseif ($fieldName == 'field_subjects_name') {
          $subject['name']['namePart'] = [
            "#" => is_null($field->entity) ? '' : $field->entity->label(),
          ];
        }
        $mods['subject'][] = $subject;
      }
    }

    if (!$entity->field_lcsh_topic->isEmpty()) {
      foreach ($entity->field_lcsh_topic as $topic) {
        $mods['subject'][] = [
          'topic' => $topic->entity->label(),
          '@authority' => 'lcsh',
        ];
      }
    }
    if (!$entity->field_subject_hierarchical_geo->isEmpty()) {
      $subject = ['@authority' => 'tgn'];
      $keys = [
        'city',
        'continent',
        'country',
        'county',
        'state',
        'territory',
      ];
      foreach ($keys as $key) {
        if ($entity->field_subject_hierarchical_geo->$key != "") {
          $subject['hierarchicalGeographic'][$key] = $entity->field_subject_hierarchical_geo->$key;
        }
      }
      $mods['subject'][] = $subject;
    }

    if (!$entity->field_related_item->isEmpty()) {
      $keys = [
        'title',
        'identifier_type',
        'identifier',
        'number',
      ];
      $relatedItem = [];
      foreach ($keys as $key) {
        if ($entity->field_related_item->$key != "") {
          if ($key == 'title') {
            $relatedItem['titleInfo']['title'] = $entity->field_related_item->$key;
          }
          elseif ($key == 'number') {
            $relatedItem['part']['detail']['number'] = $entity->field_related_item->$key;
          }
          elseif ($key == 'identifier_type') {
            $relatedItem['identifier']['@type'] = $entity->field_related_item->$key;

          }
          else {
            $relatedItem[$key]['#'] = $entity->field_related_item->$key;
          }
        }
      }
      $mods['relatedItem'][] = $relatedItem;
    }
    if (!$entity->field_part_detail->isEmpty()) {
      $keys = [
        'caption',
        'number',
        'title',
      ];
      foreach($entity->field_part_detail as $field) {
        $partDetail = [];
        foreach ($keys as $key) {
          if ($field->$key != "") {
            $partDetail[$key] = $field->$key;
          }
        }
        if ($field->type != "") {
          $partDetail['@type'] = $field->type;
        }
        $mods['part']['detail'][] = $partDetail;
      }
    }
    if (!$entity->field_edtf_date_issued->isEmpty()) {
      $date_str = !$entity->field_edtf_date_created->isEmpty() && strlen($entity->field_edtf_date_created->value) >  strlen($entity->field_edtf_date_issued->value) ?
        $entity->field_edtf_date_created->value : $entity->field_edtf_date_issued->value;
      $date = \DateTime::createFromFormat('Y-m-d', $date_str);
      $mods['originInfo']['dateIssued'] = [
        "#" => $date ? $date->format('n/j/Y') : $date_str,
        "@encoding" => "iso8691",
        "@keyDate" => "yes",
      ];
      $mods['originInfo']['dateOther'] = [
        "#" => $date ? $date->format('Y') : $date_str,
        "@type" => "year",
      ];
    }

    $xml = parent::encode($mods, $format, $context);
    return $xml;
  }

}
