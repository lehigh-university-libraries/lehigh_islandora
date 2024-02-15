<?php declare(strict_types = 1);

namespace Drupal\lehigh_islandora\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\lehigh_islandora\Plugin\Field\FieldType\TextfieldWithAttributesItem;
use Symfony\Component\Validator\ConstraintViolationInterface;

/**
 * Defines the 'textfield_attr' field widget.
 *
 * @FieldWidget(
 *   id = "textfield_attr",
 *   label = @Translation("Textfield with attributes"),
 *   field_types = {"textfield_attr"},
 * )
 */
final class TextfieldAttrWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state): array {

    $element['attr0'] = [
      '#type' => 'select',
      '#title' => $this->t('Attribute One'),
      '#options' => ['' => $this->t('- Select a value -')] + TextfieldWithAttributesItem::allowedAttributeOneValues(),
      '#default_value' => $items[$delta]->attr0 ?? NULL,
    ];

    $element['attr1'] = [
      '#type' => 'select',
      '#title' => $this->t('Attribute Two'),
      '#options' => ['' => $this->t('- None -')] + TextfieldWithAttributesItem::allowedAttributeTwoValues(),
      '#default_value' => $items[$delta]->attr1 ?? NULL,
    ];

    $element['value'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Value'),
      '#default_value' => $items[$delta]->value ?? NULL,
      '#size' => 20,
    ];

    $element['#theme_wrappers'] = ['container', 'form_element'];
    $element['#attributes']['class'][] = 'container-inline';
    $element['#attributes']['class'][] = 'textfield-attr-elements';
    $element['#attached']['library'][] = 'lehigh_islandora/textfield_attr';

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
    }
    return $values;
  }

}
