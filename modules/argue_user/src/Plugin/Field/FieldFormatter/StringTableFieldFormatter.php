<?php

namespace Drupal\argue_user\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'piped_split_field_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "string_table_field_formatter",
 *   label = @Translation("Table formatter"),
 *   field_types = {
 *     "string"
 *   },
 * )
 */
class StringTableFieldFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $options = parent::defaultSettings();

    $options['column_names'] = 'Col 1 | Col 2';
    $options['column_count'] = 2;
    $options['column_delimiter'] = '|';

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);

    $form['column_names'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Column labels'),
      '#default_value' => $this->getSetting('column_names'),
      '#description' => $this->t('Enter column labels separated by "|" delimiter. Leave empty for no column labels.'),
      '#size' => 60,
      '#maxlength' => 256,
    ];
    $form['column_count'] = [
      '#type' => 'number',
      '#title' => $this->t('Column number'),
      '#default_value' => $this->getSetting('column_count'),
      '#description' => $this->t('Enter the max number of table columns.'),
      '#min' => 1,
      '#max' => 16,
      '#step' => 1,
    ];
    $form['column_delimiter'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Column Delimiter'),
      '#default_value' => $this->getSetting('column_delimiter'),
      '#description' => $this->t('Enter the delimiter char(s).'),
      '#size' => 5,
      '#maxlength' => 5,
      '#required' => TRUE
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $summary[] = $this->t('Display @num cols delimited by "@del".', [
        '@num' => $this->getSetting('column_count'),
        '@del' => $this->getSetting('column_delimiter'),
      ]);

    $col_name = $this->getSetting('column_names');
    if(!empty($col_name)) {
      $names = explode('|', $col_name);
      $summary[] = $this->t('Labels: @names', [
        '@names' => implode(', ', $names),
      ]);
    }
    return $summary;
  }


  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $elements[] = $this->getHeader();
    foreach ($items as $delta => $item) {
      $elements[$delta + 1] = ['#markup' => $this->viewRow($item)];
    }

    return $elements;
  }


  /**
   * Return the table header.
   *
   * @return array
   *   The generated render array for the header.
   */
  protected function getHeader() {
    $names = explode('|', $this->getSetting('column_names'));
    $num = (int) $this->getSetting('column_count');
    $collector = [];
    for($i = 0; $i <= $num-1; $i++) {
      $name = (isset($names[$i])) ? $names[$i] : '';
      $class = 'mdc-data-table__header-cell';
      $collector[] = "<th class=\"{$class}\" role=\"columnheader\" scope=\"col\">{$name}</th>";
    }
    $markup = implode($collector);
    return ['#markup' => $markup];
  }

  /**
   * Generate the HTML output appropriate for one table row.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   One field item.
   *
   * @return string
   *   The generated table row.
   */
  protected function viewRow(FieldItemInterface $item) {
    $delimiter = $this->getSetting('column_delimiter') ?: '|';
    $num = (int) $this->getSetting('column_count');
    $value = nl2br(Html::escape($item->value));

    $frags = explode($delimiter, $value);
    $collect = [];

    for($i = 0; $i <= $num-1; $i++) {
      $frag = isset($frags[$i]) ? trim($frags[$i]) : '';
      $classes = [
        'mdc-data-table__cell',
        "table_col_{$i}",
      ];
      if(is_numeric($frag)) {
        $classes[] = 'mdc-data-table__cell--numeric';
      }
      $classes = implode(' ', $classes);
      $collect[] = "<td class=\"{$classes}\">{$frag}</td>";
    }
    return implode($collect);
  }

}
