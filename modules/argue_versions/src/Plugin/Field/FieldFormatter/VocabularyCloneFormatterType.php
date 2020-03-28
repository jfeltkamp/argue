<?php

namespace Drupal\argue_versions\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\NodeInterface;

/**
 * Plugin implementation of the 'vocab_clone_formatter_type' formatter.
 *
 * @FieldFormatter(
 *   id = "vocab_clone_formatter_type",
 *   label = @Translation("Default"),
 *   field_types = {
 *     "vocab_clone_field_type"
 *   }
 * )
 */
class VocabularyCloneFormatterType extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      // Implement default settings.
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    return [
      // Implement settings form.
    ] + parent::settingsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    // Implement settings summary.

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    /** @var \Drupal\node\NodeInterface $node */
    $node = $items->getParent()->getEntity();
    $elements = \Drupal::service('argue_versions.viewbuilder')->getRenderedView($node);

    return $elements;
  }

}
