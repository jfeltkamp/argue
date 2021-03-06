<?php

namespace Drupal\argue_proscons\Plugin\Field\FieldFormatter;

use Drupal\argue_proscons\Entity\Argument;
use Drupal\change_requests\Events\ChangeRequests;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Template\Attribute;
use Drupal\Core\Url;

/**
 * Plugin implementation of the 'attached_improvement_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "attach_change_requests",
 *   label = @Translation("Change requests"),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class AttachChangeRequestFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
        'display_modal' => TRUE,
        'display_add_link' => TRUE,
        'show_empty_field' => TRUE,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);
    $form['display_add_link'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Add link for a new patch.'),
      '#description' => $this->t("If checked an add link is displayed next to the field label."),
      '#default_value' => $this->getSetting('display_add_link'),
    ];
    $form['display_modal'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display patch entity in modal dialog.'),
      '#description' => $this->t("If checked the linked patch entity will be load by ajax and displayed in a modal overlay."),
      '#default_value' => $this->getSetting('display_modal'),
    ];
    $form['show_empty_field'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display empty field.'),
      '#description' => $this->t("Display field even when it is empty. (Recommended in combination with add link.)"),
      '#default_value' => $this->getSetting('show_empty_field'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $summary[] = t('Display add link: %add.', [
      '%add' => ($this->getSetting('display_add_link')) ? $this->t('Yes') : $this->t('No'),
    ]);
    $summary[] = t('Display in modal dialog: %modal.', [
      '%modal' => ($this->getSetting('display_modal')) ? $this->t('Yes') : $this->t('No'),
    ]);
    $summary[] = t('Display empty field: %empty.', [
      '%empty' => ($this->getSetting('show_empty_field')) ? $this->t('Yes') : $this->t('No'),
    ]);
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function view(FieldItemListInterface $items, $langcode = NULL)
  {
    $view = parent::view($items, $langcode);

    if ($this->getSetting('display_add_link')) {
      /** @var Argument $argument */
      $argument = $items->getEntity();

      $destination = [
        'entity_type' => $argument->getEntityTypeId(),
        'entity_id' => $argument->id(),
        'field' => $items->getFieldDefinition()->getName(),
      ];

      $add_link = (\Drupal::currentUser()->hasPermission('add patch entities'))
        ? [
            '#type' => 'link',
            '#title' => 'add_item',
            '#url' => Url::fromRoute('entity.node.edit_form', [
              'node' => $argument->getReferenceId()
            ],
              ['query' => ['attach_to' => implode('/', $destination)]]
            ),
            '#options' => ['attributes' => ['class' => ['argue-icon', 'add-link']]],
          ]
        : ['#markup' => ''];

      $view['#title'] = new TranslatableMarkup('<span>@title</span> @add_link', [
        '@title' => $view['#title'],
        '@add_link' => render($add_link),
      ]);
    }

    if ($this->getSetting('display_modal')) {
      $view['#attached']['library'][] = 'core/drupal.dialog.ajax';
    }

    return $view;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    if (!$items->isEmpty()) {
      $this->crStatusConstants = new ChangeRequests;
      foreach ($items as $delta => $item) {
        if ($item->entity) {
          /** @var Argument $entity */
          $elements[] = $this->getEntityLink($item->entity);
        }
      }
    } elseif ($this->getSetting('show_empty_field')) {
      $elements[] = [
        '#type' => 'container',
        'content' => [
          '#markup' => new TranslatableMarkup('<span class="empty">No change requests.</span>')
        ]
      ];
    }

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    return ($field_definition->getSetting('target_type') == 'patch');
  }

  /**
   *
   *
   * @param EntityInterface $entity
   *
   * @return array
   */
  protected function getEntityLink(EntityInterface $entity) {

    $route = 'entity.patch.canonical';
    $attr = ($this->getSetting('display_modal'))
      ? [
        'class' => ['use-ajax'],
        'data-dialog-type' => 'modal',
        'data-dialog-options' => '{"width":"80%","dialogClass":"popup-dialog-class"}',
        'role' => 'article',
      ] : [];

    $meta_props = [
      '#theme' => 'argue_chip_set',
      '#attributes' => ['class' => ['patch_props']],
    ];
    $invoke_meta = \Drupal::moduleHandler()->invokeAll('argue_meta_patch_patch', [$entity, 'list-item']);

    if($entity instanceof EntityInterface) {
      $url = Url::fromRoute($route, ['patch' => $entity->id()]);
      return [
        '#theme' => 'change_request__list_item',
        '#label' => $entity->label(),
        '#url' => $url->toString(),
        '#status' => array_merge($meta_props, $invoke_meta),
        '#attributes' => new Attribute($attr),
      ];
    } else {
      return [];
    }
  }
}
