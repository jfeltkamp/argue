<?php

namespace Drupal\argue_proscons;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Argument entity.
 *
 * @see \Drupal\argue_proscons\Entity\Argument.
 */
class ArgumentAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\argue_proscons\Entity\ArgumentInterface $entity */
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished argument entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published argument entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit argument entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete argument entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add argument entities');
  }

}
