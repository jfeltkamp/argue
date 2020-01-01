<?php

namespace Drupal\argue_proscons\Entity;

use Drupal\argue_proscons\Events\ArgueEvent;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\RevisionableContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\user\UserInterface;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

/**
 * Defines the Argument entity.
 *
 * @ingroup argue_proscons
 *
 * @ContentEntityType(
 *   id = "argument",
 *   label = @Translation("Argument"),
 *   handlers = {
 *     "storage" = "Drupal\argue_proscons\ArgumentStorage",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\argue_proscons\ArgumentListBuilder",
 *     "views_data" = "Drupal\argue_proscons\Entity\ArgumentViewsData",
 *     "translation" = "Drupal\argue_proscons\ArgumentTranslationHandler",
 *
 *     "form" = {
 *       "default" = "Drupal\argue_proscons\Form\ArgumentForm",
 *       "add" = "Drupal\argue_proscons\Form\ArgumentForm",
 *       "edit" = "Drupal\argue_proscons\Form\ArgumentForm",
 *       "delete" = "Drupal\argue_proscons\Form\ArgumentDeleteForm",
 *     },
 *     "access" = "Drupal\argue_proscons\ArgumentAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\argue_proscons\ArgumentHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "argument",
 *   data_table = "argument_field_data",
 *   revision_table = "argument_revision",
 *   revision_data_table = "argument_field_revision",
 *   translatable = TRUE,
 *   admin_permission = "administer argument entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "revision" = "vid",
 *     "label" = "name",
 *     "type" = "type",
 *     "reference_id" = "reference_id",
 *     "argument" = "argument",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *     "langcode" = "langcode",
 *     "status" = "status",
 *   },
 *   links = {
 *     "canonical" = "/argument/{argument}",
 *     "add-form" = "/argument/add/{reference_id}",
 *     "edit-form" = "/argument/{argument}/edit",
 *     "delete-form" = "/argument/{argument}/delete",
 *     "version-history" = "/admin/structure/argument/{argument}/revisions",
 *     "revision" = "/admin/structure/argument/{argument}/revisions/{argument_revision}/view",
 *     "revision_revert" = "/admin/structure/argument/{argument}/revisions/{argument_revision}/revert",
 *     "revision_delete" = "/admin/structure/argument/{argument}/revisions/{argument_revision}/delete",
 *     "translation_revert" = "/admin/structure/argument/{argument}/revisions/{argument_revision}/revert/{langcode}",
 *     "collection" = "/admin/structure/argument/list",
 *   },
 *   field_ui_base_route = "argument.settings"
 * )
 */
class Argument extends RevisionableContentEntityBase implements ArgumentInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += [
      'user_id' => \Drupal::currentUser()->id(),
      'reference_id' => \Drupal::request()->get('reference_id'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function uriRelationships() {
    return array_filter(array_keys($this->linkTemplates()), function ($link_relation_type) {
      // It's not guaranteed that every link relation type also has a
      // corresponding route. For some, additional modules or configuration may
      // be necessary. The interface demands that we only return supported URI
      // relationships.

      if (in_array($link_relation_type, ['add-form', 'revision_revert', 'revision_delete'])) {
        return FALSE;
      }
      try {
        $this->toUrl($link_relation_type)->toString(TRUE)->getGeneratedUrl();
      }
      catch (RouteNotFoundException $e) {
        return FALSE;
      }
      return TRUE;
    });
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);

    foreach (array_keys($this->getTranslationLanguages()) as $langcode) {
      $translation = $this->getTranslation($langcode);

      // If no owner has been set explicitly, make the anonymous user the owner.
      if (!$translation->getOwner()) {
        $translation->setOwnerId(0);
      }
    }

    // If no revision author has been set explicitly, make the argument owner the
    // revision author.
    if (!$this->getRevisionUser()) {
      $this->setRevisionUserId($this->getOwnerId());
    }
  }

  /**
   * @return int
   */
  public function save() {
    if ($this->isNew()) {
      \Drupal::cache('render')->invalidate('sections_tree');
    }
    return parent::save();
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->get('name')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setName($name) {
    $this->set('name', $name);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getType() {
    return $this->get('type')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getTypeStr() {
    $key = (string) $this->getType();
    $strings = [
      ArgueEvent::ARGUE_PRO => 'pro',
      ArgueEvent::ARGUE_CON => 'contra',
    ];
    return (key_exists($key, $strings)) ? $strings[$key] : '';
  }

  /**
   * {@inheritdoc}
   */
  public function setType($type) {
    if (in_array($type, [ArgueEvent::ARGUE_PRO, ArgueEvent::ARGUE_CON])) {
      $this->set('type', $type);
    } else {
      $this->set('type', ArgueEvent::ARGUE_DEFAULT);
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('user_id')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('user_id')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('user_id', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('user_id', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isPublished() {
    return (bool) $this->getEntityKey('status');
  }

  /**
   * {@inheritdoc}
   */
  public function setPublished($published) {
    $this->set('status', $published ? TRUE : FALSE);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getReferenceId() {
    /** @var $ref_item EntityReferenceItem */
    $ref_item = $this->get('reference_id')->first();
    return $ref_item->getString();
  }

  /**
   * {@inheritdoc}
   */
  public function setReferenceId($reference_id) {
    $this->set('reference_id', (int) $reference_id);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The user ID of author of the Argument entity.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setTranslatable(TRUE)
      ->setDefaultValueCallback('\Drupal::currentUser')
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'author',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', FALSE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['reference_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Referred to'))
      ->setDescription(t('The entity, this argument refers to.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'node')
      ->setSetting('handler', 'default')
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'entity_reference_entity_view',
        'view_mode' => 'teaser',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['type'] = BaseFieldDefinition::create('list_integer')
      ->setLabel(t('Argument type'))
      ->setDescription(t('If the argument is a PRO or a CON.'))
      ->setRevisionable(TRUE)
      ->setRequired(TRUE)
      ->setSettings([
        'allowed_values' => [
          ArgueEvent::ARGUE_PRO => 'PRO',
          ArgueEvent::ARGUE_CON => 'CON',
        ],
      ])
      ->setDefaultValue(ArgueEvent::ARGUE_PRO)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 1,
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_buttons',
        'weight' => 1,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The name of the Argument entity.'))
      ->setRevisionable(TRUE)
      ->setSettings([
        'max_length' => 80,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 2,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 2,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['argument'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Argument'))
      ->setDescription(t('The argument text.'))
      ->setRevisionable(TRUE)
      ->setSettings([
        'text_processing' => TRUE,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 3,
      ])
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'text_processing' => 1,
        'weight' => 3,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Publishing status'))
      ->setDescription(t('A boolean indicating whether the Argument is published.'))
      ->setRevisionable(TRUE)
      ->setDefaultValue(TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'))
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 4,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    $fields['revision_translation_affected'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Revision translation affected'))
      ->setDescription(t('Indicates if the last edit of a translation belongs to current revision.'))
      ->setReadOnly(TRUE)
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->setInitialValue(TRUE);

    return $fields;
  }


}
