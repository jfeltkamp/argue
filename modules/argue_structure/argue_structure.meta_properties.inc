<?php

/**
 * @file
 * Contains argue_structure.module.
 * @ToDo finalize
 *   ✓ add to view.
 *   ✓ implement hooks in modules for Arguments .
 *   ✓ add caching !!! (use existing cache tags where possible).
 *   ✓ remove old templates (argue_structure_proscons + argue_structure_proscons_pill).
 *   ✓ remove old hooks implementing meta (e.g. argue_structure_preprocess_node)
 *   ✓ reduce number of node templates.
 */

use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityInterface ;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\node\NodeInterface;
use Drupal\views\Views;

/**
 * Load meta properties of current entity.
 *
 * @param \Drupal\Core\Entity\EntityInterface $entity
 * @param \Drupal\Core\Entity\Display\EntityViewDisplayInterface $display
 *
 * @return array
 */
function argue_structure_meta_properties_load(EntityInterface $entity, EntityViewDisplayInterface $display) :array
{
  $callbackName = implode('_', [
    'argue_meta',
    $entity->getEntityTypeId(),
    $entity->bundle(),
  ]);
  // build cache tag and validate before hooking.
  $meta_props = [];
  // Invoke hooks for this entity (depends on type and bundle).
  $invoke_meta = Drupal::moduleHandler()->invokeAll($callbackName, [$entity, $display->getOriginalMode()]);
  if (count($invoke_meta)) {
    $meta_props['#theme'] = 'argue_chip_set';
    foreach ($invoke_meta as $key => $meta) {
      $meta_props[$key] = $meta;
    }
  }

  return $meta_props;
}

/**
 * Implements hook_argue_meta_ENTITY_TYPE()
 *
 * Load the meta properties of current entity.
 */
function argue_structure_argue_meta_node_rule(NodeInterface $node, $view_mode) :array
{
  $cache_tag = "node:{$node->id()}:meta:ratified";
  $result = [];
  if ($node->hasField('field_ratified')) {
    $value = $node->get('field_ratified')->getString();
    $result['ratified'] = [
      '#theme' => 'argue_chip',
      '#attributes' => ['title' => t('Rule will be ratified in next version.')],
      '#icon_before' => 'ratified',
      '#text' => $value ? t('Yes') :  t('No'),
      '#icon_after' => NULL,
      '#cache' => ['tags' => [$cache_tag]],
    ];
  }
  return $result;
}


/**
 * Implements hook_argue_meta_ENTITY_TYPE()
 *
 * Load the meta properties of current entity.
 *
 * @param \Drupal\node\NodeInterface $node
 * @param $view_mode
 *
 * @return array
 * @throws \Drupal\Core\TypedData\Exception\MissingDataException
 */
function argue_structure_argue_meta_node_problem(NodeInterface $node, $view_mode) :array
{
  $nid = $node->id();
  $cache_tag = "node:{$node->id()}:meta:num_rule";
  $result = [];

  $view = Views::getView('argue_rules_ref_this_problem');
  if (is_object($view)) {
    $view->setArguments([$nid]);
    $view->setDisplay('embed_1');
    $view->preExecute();
    $view->execute();

    $result['ratified'] = [
      '#theme' => 'argue_chip',
      '#help' => t('Number of rules handling this problem'),
      '#attributes' => ['title' =>  t('Number of rules handling this problem.')],
      '#icon_before' => 'rule',
      '#text' => count($view->result),
      '#icon_after' => NULL,
      '#cache' => ['tags' => [$cache_tag]],
    ];
  }

  return array_merge($result, _argue_structure_meta_props_from_reference_fields($node));
}


/**
 * Implements hook_argue_meta_ENTITY_TYPE_BUNDLE()
 *
 * See file argue_structure.meta_properties.inc
 *
 * @param \Drupal\Core\Entity\EntityInterface $entity
 * @param string $view_mode
 *
 * @return array
 * @throws \Drupal\Core\TypedData\Exception\MissingDataException
 */
function argue_structure_argue_meta_argument_argument(EntityInterface $entity, $view_mode) :array
{
  return _argue_structure_meta_props_from_reference_fields($entity);
}

/**
 * Implements hook_ENTITY_TYPE_presave().
 *
 * Invalidate cache tag for parent meta properties.
 */
function argue_structure_argument_presave(\Drupal\argue_proscons\Entity\ArgumentInterface $argument) {
  // Invalidate cache tag for parent meta properties.
  if ($argument->isNew() && $nid = $argument->getReferenceId()) {
    Cache::invalidateTags(["node:{$nid}:meta:argument"]);
  }
}

/**
 * Implements hook_ENTITY_TYPE_predelete().
 *
 * Invalidate cache tag for parent meta properties.
 */
function argue_structure_argument_predelete(\Drupal\argue_proscons\Entity\ArgumentInterface $argument) {
  if ($nid = $argument->getReferenceId()) {
    Cache::invalidateTags(["node:{$nid}:meta:argument"]);
  }
}

/**
 * @param \Drupal\Core\Entity\EntityInterface $entity
 *
 * @return array
 * @throws \Drupal\Core\TypedData\Exception\MissingDataException
 */
function _argue_structure_meta_props_from_reference_fields(EntityInterface $entity) :array
{
  $id = $entity->id();
  $entity_type = $entity->getEntityTypeId();
  $result = [];

  // Icon map used for chips. If not set, the entity type id is used.
  $chip_icon_map = ['patch' => 'change_request'];

  /**
   * Loop over fields and append the chips to the chip set.
   *
   * @var string $field_name
   * @var Drupal\Core\Field\FieldDefinitionInterface $field_definition
   */
  foreach ($entity->getFieldDefinitions() as $field_name => $field_definition) {
    if ($field_definition->getType() == 'comment') {
      $cache_tag = "{$entity_type}:{$id}:meta:comment";
      $field_comment = $entity->get($field_name);
      if ($field_comment instanceof \Drupal\comment\CommentFieldItemList) {
        $result['comment'] = [
          '#theme' => 'argue_chip',
          '#attributes' => ['title' => t('Number of comments on this entity.')],
          '#icon_before' => 'comments',
          '#text' => $field_comment->first()->comment_count,
          '#cache' => ['tags' => [$cache_tag]],
        ];
      }
    } elseif ($field_definition->getType() == 'entity_reference') {
      $target_type = $field_definition->getItemDefinition()->getSetting('target_type');
      $cache_tag = "{$entity_type}:{$id}:meta:{$target_type}";
      if (1 !== $field_definition->getFieldStorageDefinition()->getCardinality()) {
        $result[$target_type] = [
          '#theme' => 'argue_chip',
          '#attributes' => ['title' => t('Number of %type on this entity.', [
            '%type' => $field_definition->getLabel()
          ])],
          '#icon_before' => $chip_icon_map[$target_type] ?? $target_type,
          '#text' => $entity->get($field_name)->count(),
          '#cache' => ['tags' => [$cache_tag]],
        ];
      }
    }
  }

  return $result;
}
