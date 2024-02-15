<?php declare(strict_types = 1);

namespace Drupal\lehigh_islandora\Plugin\Field\FieldType;

use Drupal\Component\Utility\Random;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Defines the 'textarea_attr' field type.
 *
 * @FieldType(
 *   id = "textarea_attr",
 *   label = @Translation("Textarea with attributes"),
 *   category = @Translation("General"),
 *   default_widget = "textarea_attr",
 *   default_formatter = "textarea_attr_default",
 * )
 */
final class TextareaAttrItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings(): array {
    $settings = ['bar' => 'example'];
    return $settings + parent::defaultFieldSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state): array {
    $settings = $this->getSettings();

    $element['bar'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Bar'),
      '#default_value' => $settings['bar'],
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty(): bool {
    return $this->attr0 === NULL && $this->attr1 === NULL && $this->value === NULL && $this->format === NULL;
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition): array {

    $properties['attr0'] = DataDefinition::create('string')
      ->setLabel(t('Attribute One'));
    $properties['attr1'] = DataDefinition::create('string')
      ->setLabel(t('Attribute Two'));
    $properties['value'] = DataDefinition::create('string')
      ->setLabel(t('Value'));
    $properties['format'] = DataDefinition::create('string')
      ->setLabel(t('Format'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function getConstraints(): array {
    $constraints = parent::getConstraints();

    $options['attr0']['AllowedValues'] = array_keys(TextareaWithAttributesItem::allowedAttributeOneValues());

    $options['attr0']['NotBlank'] = [];

    $options['attr1']['AllowedValues'] = array_keys(TextareaWithAttributesItem::allowedAttributeTwoValues());

    $options['value']['NotBlank'] = [];

    $options['format']['AllowedValues'] = array_keys(TextareaWithAttributesItem::allowedFormatValues());

    $options['format']['NotBlank'] = [];

    $constraint_manager = \Drupal::typedDataManager()->getValidationConstraintManager();
    $constraints[] = $constraint_manager->create('ComplexData', $options);
    // @todo Add more constraints here.
    return $constraints;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition): array {

    $columns = [
      'attr0' => [
        'type' => 'varchar',
        'length' => 255,
      ],
      'attr1' => [
        'type' => 'varchar',
        'length' => 255,
      ],
      'value' => [
        'type' => 'text',
        'size' => 'big',
      ],
      'format' => [
        'type' => 'varchar',
        'length' => 255,
      ],
    ];

    $schema = [
      'columns' => $columns,
      // @DCG Add indexes here if necessary.
    ];

    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition): array {

    $random = new Random();

    $values['attr0'] = array_rand(self::allowedAttributeOneValues());

    $values['attr1'] = array_rand(self::allowedAttributeTwoValues());

    $values['value'] = $random->paragraphs(5);

    $values['format'] = array_rand(self::allowedFormatValues());

    return $values;
  }

  /**
   * Returns allowed values for 'attr0' sub-field.
   */
  public static function allowedAttributeOneValues(): array {
    // @todo Update allowed values.
    return [
      'alpha' => t('Alpha'),
      'beta' => t('Beta'),
      'gamma' => t('Gamma'),
    ];
  }

  /**
   * Returns allowed values for 'attr1' sub-field.
   */
  public static function allowedAttributeTwoValues(): array {
    // @todo Update allowed values.
    return [
      'alpha' => t('Alpha'),
      'beta' => t('Beta'),
      'gamma' => t('Gamma'),
    ];
  }

  /**
   * Returns allowed values for 'format' sub-field.
   */
  public static function allowedFormatValues(): array {
    // @todo Update allowed values.
    return [
      'alpha' => t('Alpha'),
      'beta' => t('Beta'),
      'gamma' => t('Gamma'),
    ];
  }

}
