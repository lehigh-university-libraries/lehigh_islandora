<?php

/**
 * @file
 * Contains lehigh_islandora.tokens.inc.
 */

use Drupal\controlled_access_terms\EDTFUtils;
use Drupal\Core\Render\BubbleableMetadata;

/**
 * Implements hook_token_info_alter().
 */
function lehigh_islandora_token_info_alter(&$data) {
  $data['tokens']['node']['parent-url'] = [
    'name' => t("Parent URL"),
    'description' => t("This node's parent's path alias."),
  ];
  $data['tokens']['node']['lehigh_islandora_issued_ymd'] = [
    'name' => t("Issued Date YYYY/m/d"),
    'description' => t("This node's issue date in Google Scholar's desired format."),
  ];
  $data['tokens']['node']['lehigh_islandora_authors'] = [
    'name' => t("Author(s)"),
    'description' => t("The linked agent author(s)."),
  ];
}

/**
 * Implements hook_tokens().
 */
function lehigh_islandora_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {

  $token_service = \Drupal::token();

  if (isset($options['langcode'])) {
    $url_options['language'] = \Drupal::languageManager()
      ->getLanguage($options['langcode']);
    $langcode = $options['langcode'];
  }
  else {
    $langcode = NULL;
  }

  $replacements = [];
  if ($type == 'node' && !empty($data['node'])) {

    /** @var \Drupal\node\NodeInterface $node */
    $node = $data['node'];
    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'lehigh_islandora_issued_ymd':
          if ($node->hasField('field_edtf_date_issued')
            && !$node->field_edtf_date_issued->isEmpty()) {
            $date = $node->field_edtf_date_issued->value;
            $date = EDTFUtils::iso8601Value($date);
            $date = strtotime($date);
            $replacements[$original] = date('Y/n/j', $date);
          }
          break;

        case 'lehigh_islandora_authors':
          if ($node->hasField('field_linked_agent')
            && !$node->field_linked_agent->isEmpty()) {

            $authors = [];
            foreach ($node->field_linked_agent as $linked_agent) {
              // If this is an author/creator.
              if (in_array($linked_agent->rel_type, [
                // Creator.
                'relators:cre',
                // Author.
                'relators:aut',
              ])) {
                $author = $linked_agent->entity->label();
                // If the linked agent is of the format "LAST, FIRST"
                // transform to "FIRST LAST"
                // to match Google Scholar's conventions.
                if (strpos($author, ',')) {
                  $components = explode(',', $author);
                  $author = array_pop($components);
                  $author .= ' ' . implode(' ', $components);
                }
                $authors[] = $author;
              }
            }
            $replacements[$original] = implode(',', $authors);
          }
          break;
        case 'parent-url':
          if ($node->hasField('field_member_of')
            && !$node->field_member_of->isEmpty()) {
            $paths = [
              '/node/' . $node->field_member_of->target_id,
              '/browse-items/' . $node->field_member_of->target_id,
            ];
            foreach ($paths as $path) {
              $alias = \Drupal::database()->query('SELECT alias FROM {path_alias}
                WHERE path = :path
                AND status = 1', [
                  ':path' => $path
              ])->fetchField();
              if ($alias) {
                $replacements[$original] = $alias;
                break;
              }
            }
          }
          break;
      }
    }

    return $replacements;
  }
}
