<?php

namespace Drupal\argue_versions\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'vocab_clone_widget_type' widget.
 *
 * @FieldWidget(
 *   id = "vocab_clone_widget_type",
 *   module = "argue_versions",
 *   label = @Translation("Default"),
 *   field_types = {
 *     "vocab_clone_field_type"
 *   }
 * )
 */
class VocabularyCloneWidgetType extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'size' => 60,
      'placeholder' => '',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = [];
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element['term_id'] = $element + [
      '#type' => 'hidden',
      '#value' => isset($items[$delta]->term_id) ? $items[$delta]->term_id : NULL,
    ];

    return $element;
  }

}
