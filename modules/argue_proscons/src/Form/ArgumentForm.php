<?php

namespace Drupal\argue_proscons\Form;

use Drupal\argue_proscons\Events\ArgueEvent;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for Argument edit forms.
 *
 * @ingroup argue_proscons
 */
class ArgumentForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;

    /** @var \Drupal\node\NodeInterface $reference */
    $reference = $entity->getFields()['reference_id']->entity;

    $view_builder = \Drupal::entityTypeManager()->getViewBuilder($reference->getEntityTypeId());
    $reference_view = $view_builder->view($reference, 'teaser');
    $reference_view['#weight'] = -50;

    $type = $entity->get('type')->getString();
    $typeStr = [
      ArgueEvent::ARGUE_PRO => $this->t('Pro'),
      ArgueEvent::ARGUE_CON => $this->t('Contra'),
    ];

    $title = ($this->entity->isNew())
      ? $this->t('Create a new Argument')
      : $this->t('Edit %type-Argument', ['%type' => $typeStr[$type]]);
    $form = [
      'reference' => $reference_view,
      'title' => [
        '#markup' => '<h1>' . $title->render() . '</h1>',
        '#weight' => -45,
      ],
    ];

    /** @var \Drupal\argue_proscons\Entity\Argument $entity */
    $form += parent::buildForm($form, $form_state);

    if (!$this->entity->isNew()) {

      // Output PRO/CON type as text. Not allowed to change if voting has begun.
      if (ArgueEvent::hasEvaluationBegun($entity->id())) {
        // Unset form element and replace by rendered field value.
        $titles = [
          ArgueEvent::ARGUE_PRO => $this->t('confirms'),
          ArgueEvent::ARGUE_CON => $this->t('counters'),
        ];
        $form['type'] = [
          '#markup' => $this->t('<h2>Argument %procon the preceding entity.</h2>', [
            '%procon' => $titles[$type],
          ]),
          '#weight' => $form['type']['#weight'],
        ];
      }

      // Default settings for revision.
      $revisions_default = \Drupal::config('argue_proscons.settings')
        ->get('argue_proscons.revisions_default');
      $allow_omit = \Drupal::currentUser()->hasPermission('allow to omit creation of new argument revisions');
      $form["revision"]["#default_value"] = $revisions_default;
      if ($revisions_default && !$allow_omit) {
        $form["revision"]["#disabled"] = TRUE;
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = &$this->entity;

    // Save as a new revision if requested to do so.
    if (!$form_state->isValueEmpty('revision') && $form_state->getValue('revision') != FALSE) {
      $entity->setNewRevision();

      // If a new revision is created, save the current user as revision author.
      $entity->setRevisionCreationTime(\Drupal::time()->getRequestTime());
      $entity->setRevisionUserId(\Drupal::currentUser()->id());
      $entity->setRevisionTranslationAffected(TRUE);
    }
    else {
      $entity->setNewRevision(FALSE);
    }

    $status = parent::save($form, $form_state);

    switch ($status) {
      case SAVED_NEW:
        \Drupal::messenger()->addMessage($this->t('Created the %label Argument.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        \Drupal::messenger()->addMessage($this->t('Saved the %label Argument.', [
          '%label' => $entity->label(),
        ]));
    }

    /** @var \Drupal\node\NodeInterface $reference */
    $reference = $entity->getFields()['reference_id']->entity;
    $options = ($entity->id())
      ? ['fragment' => 'argument_' . $entity->id()] : [];
    $form_state->setRedirect('entity.node.canonical',
      ['node' => $reference->id()], $options);
  }


  /**
   * {@inheritdoc}
   *
   * Button-level validation handlers are highly discouraged for entity forms,
   * as they will prevent entity validation from running. If the entity is going
   * to be saved during the form submission, this method should be manually
   * invoked from the button-level validation handler, otherwise an exception
   * will be thrown.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = parent::validateForm($form, $form_state);

    $max_text_length = $this->config('argue_proscons.settings')
      ->get('argue_proscons.max_argument_text_length');
    $argument = $form_state->getValue('argument');
    $argument = count($argument) ? $argument[0]['value'] : '';
    $current_length = strlen($argument);
    if ($current_length > $max_text_length) {
      $form_state->setErrorByName('argument',
        $this->t("Argument is too long. It is limited to %max-length char., your text has %cur_length char.",
          ['%max-length' => $max_text_length, '%cur_length' => $current_length]));
    }

    return $entity;
  }

  /**
   * {@inheritdoc}
   *
   * Show vertical tabs ad the end of the form.
   */
  public function showRevisionUi() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   *
   * Show vertical tabs ad the end of the form.
   */
  public function addRevisionableFormFields(array &$form) {
    parent::addRevisionableFormFields($form);

    if (isset($form['revision_log_message']) && isset($form['revision_information'])) {
      $form['revision_log_message']['#group'] = 'revision_information';
    }
  }

}
