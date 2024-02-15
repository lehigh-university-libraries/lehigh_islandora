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

      if ($item->attr0) {
        $allowed_values = $item->allowedAttributeOneValues();
        $element[$delta]['attr0'] = [
          '#type' => 'item',
          '#title' => $item->attributeOneName(),
          '#markup' => $allowed_values[$item->attr0],
        ];
      }

      if ($item->attr1) {
        $allowed_values = $item->allowedAttributeTwoValues();
        $element[$delta]['attr1'] = [
          '#type' => 'item',
          '#title' => $item->attributeTwoName(),
          '#markup' => $allowed_values[$item->attr1],
        ];
      }

      if ($item->format) {
        $element[$delta]['format'] = [
          '#type' => 'item',
          '#title' => $this->t('Value'),
          '#markup' => $item->value,
        ];
      }
      else {
        $element[$delta]['value'] = [
          '#type' => 'item',
          '#title' => $this->t('Value'),
          '#markup' => $item->value,
        ];
      }

    }

    return $element;
  }

}
