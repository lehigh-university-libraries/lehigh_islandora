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
 *   id = "textarea_attr_default",
 *   label = @Translation("Default"),
 *   field_types = {"textarea_attr"},
 * )
 */
final class TextareaAttrDefaultFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings(): array {
    return ['foo' => 'bar'] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state): array {
    $element['foo'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Foo'),
      '#default_value' => $this->getSetting('foo'),
    ];
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary(): array {
    return [
      $this->t('Foo: @foo', ['@foo' => $this->getSetting('foo')]),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode): array {
    $element = [];

    foreach ($items as $delta => $item) {

      if ($item->attr0) {
        $allowed_values = TextareaWithAttributesItem::allowedAttributeOneValues();
        $element[$delta]['attr0'] = [
          '#type' => 'item',
          '#title' => $this->t('Attribute One'),
          '#markup' => $allowed_values[$item->attr0],
        ];
      }

      if ($item->attr1) {
        $allowed_values = TextareaWithAttributesItem::allowedAttributeTwoValues();
        $element[$delta]['attr1'] = [
          '#type' => 'item',
          '#title' => $this->t('Attribute Two'),
          '#markup' => $allowed_values[$item->attr1],
        ];
      }

      if ($item->value) {
        $element[$delta]['value'] = [
          '#type' => 'item',
          '#title' => $this->t('Value'),
          '#markup' => $item->value,
        ];
      }

      if ($item->format) {
        $allowed_values = TextareaWithAttributesItem::allowedFormatValues();
        $element[$delta]['format'] = [
          '#type' => 'item',
          '#title' => $this->t('Format'),
          '#markup' => $allowed_values[$item->format],
        ];
      }

    }

    return $element;
  }

}
