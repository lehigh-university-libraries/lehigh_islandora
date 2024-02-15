<?php declare(strict_types = 1);

namespace Drupal\lehigh_islandora\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\lehigh_islandora\Plugin\Field\FieldType\TextareaWithAttributesItem;
use Symfony\Component\Validator\ConstraintViolationInterface;

/**
 * Defines the 'textarea_attr' field widget.
 *
 * @FieldWidget(
 *   id = "textarea_attr",
 *   label = @Translation("Textarea with attributes"),
 *   field_types = {"textarea_attr"},
 * )
 */
final class TextareaAttrWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state): array {

    $element['attr0'] = [
      '#type' => 'select',
      '#title' => $this->t('Attribute One'),
      '#options' => ['' => $this->t('- Select a value -')] + TextareaWithAttributesItem::allowedAttributeOneValues(),
      '#default_value' => $items[$delta]->attr0 ?? NULL,
    ];

    $element['attr1'] = [
      '#type' => 'select',
      '#title' => $this->t('Attribute Two'),
      '#options' => ['' => $this->t('- None -')] + TextareaWithAttributesItem::allowedAttributeTwoValues(),
      '#default_value' => $items[$delta]->attr1 ?? NULL,
    ];

    $element['value'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Value'),
      '#default_value' => $items[$delta]->value ?? NULL,
    ];

    $element['format'] = [
      '#type' => 'select',
      '#title' => $this->t('Format'),
      '#options' => ['' => $this->t('- Select a value -')] + TextareaWithAttributesItem::allowedFormatValues(),
      '#default_value' => $items[$delta]->format ?? NULL,
    ];

    $element['#theme_wrappers'] = ['container', 'form_element'];
    $element['#attributes']['class'][] = 'textarea-attr-elements';
    $element['#attached']['library'][] = 'lehigh_islandora/textarea_attr';

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function errorElement(array $element, ConstraintViolationInterface $error, array $form, FormStateInterface $form_state): array|bool {
    $element = parent::errorElement($element, $error, $form, $form_state);
    if ($element === FALSE) {
      return FALSE;
    }
    $error_property = explode('.', $error->getPropertyPath())[1];
    return $element[$error_property];
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state): array {
    foreach ($values as $delta => $value) {
      if ($value['attr0'] === '') {
        $values[$delta]['attr0'] = NULL;
      }
      if ($value['attr1'] === '') {
        $values[$delta]['attr1'] = NULL;
      }
      if ($value['value'] === '') {
        $values[$delta]['value'] = NULL;
      }
      if ($value['format'] === '') {
        $values[$delta]['format'] = NULL;
      }
    }
    return $values;
  }

}
