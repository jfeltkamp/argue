<?php

namespace Drupal\argue_versions\Plugin\Field\FieldType;

use Drupal\Component\Utility\Random;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'vocab_clone_field_type' field type.
 *
 * @FieldType(
 *   id = "vocab_clone_field_type",
 *   label = @Translation("Vocabulary clone"),
 *   description = @Translation("Can save clones of terms in a vocabulary for archive reasons."),
 *   default_widget = "vocab_clone_widget_type",
 *   default_formatter = "vocab_clone_formatter_type"
 * )
 */
class VocabularyCloneFieldType extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings() {
    return [
      'max_length' => 255,
      'is_ascii' => FALSE,
      'case_sensitive' => FALSE,
    ] + parent::defaultStorageSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    // Prevent early t() calls by using the TranslatableMarkup.
    $properties['name'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Text value'))
      ->setSetting('case_sensitive', $field_definition->getSetting('case_sensitive'))
      ->setRequired(TRUE);

    $properties['term_id'] = DataDefinition::create('integer')
      ->setLabel(t('Term ID'))
      ->setRequired(TRUE);

    $properties['term_parent_id'] = DataDefinition::create('integer')
      ->setLabel(t('Parent term ID'))
      ->setRequired(TRUE);

    $properties['description'] = DataDefinition::create('string')
      ->setLabel(t('Description'))
      ->setRequired(TRUE);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'term_id' => [
          'type' => 'int',
          'unsigned' => TRUE,
          'size' => 'normal',
        ],
        'name' => [
          'type' => 'varchar',
          'length' => (int) $field_definition->getSetting('max_length'),
          'binary' => $field_definition->getSetting('case_sensitive'),
        ],
        'description' => [
          'type' => 'text',
          'size' => 'big',
        ],
        'term_parent_id' => [
          'type' => 'int',
          'unsigned' => TRUE,
          'size' => 'normal',
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getConstraints() {
    $constraints = parent::getConstraints();

    if ($max_length = $this->getSetting('max_length')) {
      $constraint_manager = \Drupal::typedDataManager()->getValidationConstraintManager();
      $constraints[] = $constraint_manager->create('ComplexData', [
        'name' => [
          'Length' => [
            'max' => $max_length,
            'maxMessage' => t('%name: may not be longer than @max characters.', [
              '%name' => $this->getFieldDefinition()->getLabel(),
              '@max' => $max_length
            ]),
          ],
        ],
      ]);
    }

    return $constraints;
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    $random = new Random();
    $values['name'] = $random->word(mt_rand(1, $field_definition->getSetting('max_length')));
    $values['description'] = $random->word(mt_rand(200, 600));
    $values['term_id'] = mt_rand(0, 999);
    $values['term_parent_id'] = mt_rand(0, 999);
    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data) {
    $elements = [];
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->get('term_id')->getValue();
    return $value === NULL || $value === '';
  }

}
