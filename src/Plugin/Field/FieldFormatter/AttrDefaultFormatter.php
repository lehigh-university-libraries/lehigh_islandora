<?php declare(strict_types = 1);

namespace Drupal\lehigh_islandora\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\lehigh_islandora\Plugin\Field\FieldType\TextareaWithAttributesItem;

/**
 * Plugin implementation of the 'textarea_attr_default' formatter.
 *
 * @FieldFormatter(
 *   id = "attr_default",
 *   label = @Translation("Default"),
 *   field_types = {
 *     "textarea_attr",
 *     "textfield_attr"
 *   },
 * )
 */
final class AttrDefaultFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings(): array {
    return ['override_label' => FALSE] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state): array {
    $element['override_label'] = [
      '#type' => 'bool',
      '#title' => $this->t('Override Label'),
      '#default_value' => $this->getSetting('override_label'),
    ];
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary(): array {
    return [
      $this->t('Override label: @v', ['@v' => $this->getSetting('override_label')]),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode): array {
    $element = [];

    foreach ($items as $delta => $item) {
      $label = '';
      $allowed_values = $item->possibleValues();
      if ($item->attr0) {
        $label = isset($allowed_values[$item->attr0]) ? $allowed_values[$item->attr0] : $item->attr0;
      }
      elseif ($item->attr1) {
        $label = isset($allowed_values[$item->attr1]) ? $allowed_values[$item->attr1] : $item->attr1;
      }

      if ($item->format) {
        $element[$delta]['value'] = [
          '#type' => 'processed_text',
          '#text' => $item->value,
          '#format' => $item->format,
          '#langcode' => $item->getLangcode(),
        ];
      }
      else {
        $element[$delta]['value'] = [
          '#type' => 'item',
          '#title' => $this->t($label),
          '#markup' => $item->value,
        ];
      }

    }

    return $element;
  }

}
