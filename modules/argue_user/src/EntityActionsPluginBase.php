<?php

namespace Drupal\argue_user;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\user\UserInterface;

/**
 * Base class for argue_user_points_plugin plugins.
 */
abstract class EntityActionsPluginBase extends DefaultActionsPluginBase {

  /**
   * The created/updated/deleted entity .
   *
   * @var \Drupal\Core\Entity\EntityInterface|null
   */
  protected ?EntityInterface $entity = NULL;

  /**
   * {@inheritdoc}
   */
  public function handleAction(string $action, mixed $context = NULL): void {
    if ($this->hasAction($action) && $context instanceof EntityInterface) {
      $this->entity = $context;
      if ($method = $this->getAction($action)) {
        $this->{$method}($action);
      }
    }
  }

  /**
   * Undo all.
   *
   * Just add points without any conditions.
   *
   * @param string $action
   *   The current action passed through as string.
   */
  public function deleteAction(string $action): void {
    if ($this->initRule($action) && $this->validates($action)) {

      if ($this->hasAction('create')) {
        $log_id = $this->buildLogId($this->getLogIdStack('create'));
        $log_entries = $this->queryLog($log_id);
        $log_entries = $this->queryLog($log_id, $this->user);
      }
      if ($this->hasAction('update')) {
        $log_id = $this->buildLogId($this->getLogIdStack('update'));
        $log_entries = $this->queryLog($log_id);
        $log_entries = $this->queryLog($log_id, $this->user);
      }
      // @TODO finalize reverting userpoints on delete.
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getLogIdStack(string $action): ?array {
    return [
      'rule' => $this->buildRuleId($action),
      'entity_shortcut' => "{$this->entity->getEntityTypeId()}:{$this->entity->id()}",
    ];
  }

  /**
   * Get the user that created an entity, given ba entity_type_id and entity_id.
   *
   * @param bool $return_user_entity
   *   If FALSE the method returns the user_id, if TRUE the user object.
   *
   * @return \Drupal\user\UserInterface|int|string|null
   *   Returns the user that created the entity.
   */
  public function getEntityCreator(bool $return_user_entity = FALSE): UserInterface|int|string|null {
    $user = NULL;
    try {
      // Get entity type.
      $entity_storage = $this->entityTypeManager->getStorage($this->entity->getEntityTypeId());
      $entity_id = $this->entity->id();
      $entity_type = $entity_storage->getEntityType();
      if ($entity_type->isRevisionable() && $table = $entity_type->getRevisionTable()) {
        $entity_id_key = $entity_type->getKey('id');
        $entity_revision_user = $entity_type->getRevisionMetadataKey('revision_user');
        $entity_revision_created = $entity_type->getRevisionMetadataKey('revision_created');
        $data_set = \Drupal::database()
          ->select($table, 't')
          ->fields('t', [
            $entity_id_key,
            'vid',
            $entity_revision_user,
            $entity_revision_created,
          ])
          ->condition($entity_id_key, $entity_id)
          ->orderBy($entity_revision_created)
          ->execute()
          ->fetchAssoc();
        if ($data_set) {
          $user = $data_set[$entity_revision_user];
          $user = ((int) $user) ? $user : NULL;
        }
      }
      elseif (!$entity_type->isRevisionable() && $table = $entity_type->getDataTable()) {
        $entity_id_key = $entity_type->getKey('id');
        $owner_key = $entity_type->hasKey('owner')
          ? $entity_type->getKey('owner')
          : $entity_type->getKey('uid');
        if ($owner_key) {
          $data_set = \Drupal::database()
            ->select($table, 't')
            ->fields('t', [
              $entity_id_key,
              $owner_key,
            ])
            ->condition($entity_id_key, $entity_id)
            ->execute()
            ->fetchAssoc();
          if ($data_set) {
            $user = $data_set[$owner_key];
            $user = ((int) $user) ? $user : NULL;
          }
        }
      }
      if ($user && $return_user_entity) {
        /** @var \Drupal\user\UserStorage $user_storage */
        $user_storage = $this->entityTypeManager->getStorage('user');
        return $user_storage->load($user);
      }
    }
    catch (\Exception $exception) {
      \Drupal::logger('argue_user')->error($exception->getMessage());
    }

    return $user;
  }

  /**
   * {@inheritdoc}
   */
  protected function setUser(string $rule_id = NULL): bool {
    if (isset($this->rule['addressed_user']) && $this->rule['addressed_user'] === 'entity_creator') {
      // To enforce 'early fail', addressed user is identified immediately.
      $this->user = $this->getEntityCreator(TRUE);
      if (!$this->user) {
        $plugin_id = $rule_id ?? $this->getPluginId();
        $error_msg = "Userpoints rule '$plugin_id' requires user 'entity_creator' on entity" .
          " {$this->entity->getEntityTypeId()}:{$this->entity->id()}. But the user could not be identified.";
        \Drupal::logger('argue_user')->error($error_msg);
        return FALSE;
      }
    }
    else {
      return parent::setUser($rule_id);
    }
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function buildRuleId(string $action): string {
    return implode('__', [
      $this->getPluginId(),
      "{$this->entity->getEntityTypeId()}_{$this->entity->bundle()}",
      $action,
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function buildLogMessage(string $action, string $id): string {
    $label = $this->entity->label();
    $msg = $this->t("@action @type (@entity_id)@title", [
      '@action' => ucfirst($action),
      '@type' => $this->entity->bundle(),
      '@entity_id' => $this->entity->id(),
      '@title' => ($label) ? ": '$label'." : '',
    ])->render();
    return trim("$id \n$msg");
  }

  /**
   * {@inheritdoc}
   */
  public function validates(string $action): bool {
    if (!parent::validates($action)) {
      return FALSE;
    }
    $validation_config = $this->getValidationConfig($action);
    foreach ($validation_config as $key => $value) {
      switch ($key) {
        case 'published':
          if ($this->entity instanceof EntityPublishedInterface && $this->entity->isPublished() !== $value) {
            return FALSE;
          }
          break;

        case 'revert_max_age':
          if (!$this->validateRevertMaxAge((int) $value)) {
            return FALSE;
          }
          break;

        default:
      }

    }
    return TRUE;
  }

  /**
   * Validates, if the max age for reverting the entity userPoints is exceeded.
   *
   * Validation primarily for delete functions to avoid manipulation.
   * Reason: Users can generate userpoints by create and delete entities. So if
   * an entity is deleted, the generated user points will also be deleted.
   * But if entity is old it might not be manipulation and points can remain.
   *
   * @param int $max_age
   *   The max age of an entity to allow reverting entity points.
   *
   * @return bool
   *   Return FALSE if the max age is exceeded (so points will not reverted).
   */
  public function validateRevertMaxAge(int $max_age):bool {
    if (method_exists($this->entity, 'getCreatedTime') && $created = $this->entity->getCreatedTime()) {
      $max_time = \Drupal::time()->getRequestTime() - $max_age;
      if ($created < $max_time) {
        return FALSE;
      }
    }
    return TRUE;
  }

}
