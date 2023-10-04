<?php

namespace Drupal\argue_user;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\userpoints\Service\UserPointsServiceInterface;
use Drupal\Core\Entity\EntityInterface;

/**
 * Class EntityActionsUserPoints manages user points on base of entity actions.
 */
class EntityActionsUserPoints {

  use StringTranslationTrait;

  /**
   * Drupal\Core\Extension\ModuleHandler definition.
   *
   * @var \Drupal\Core\Extension\ModuleHandler
   */
  protected $entityTypeManager;

  /**
   * Drupal\Core\Session\AccountProxyInterface definition.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Constructs a new ArgumentStructuredListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The famous entity type manager.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current users AccountProxy.
   * @param \Drupal\userpoints\Service\UserPointsServiceInterface $userpoints_service
   *   The user points service from UserPoints contrib module.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, AccountProxyInterface $current_user, UserPointsServiceInterface $userpoints_service) {
    $this->currentUser = $current_user;
    if ($user_id = $this->currentUser->id()) {
      $this->user = $this->entityTypeManager->getStorage('user')->load($user_id);
    }
    $this->userpointsService = $userpoints_service;
  }

  /**
   * @param EntityInterface $entity
   * @param string $action
   * @return void
   */
  public function registerEntityAction(EntityInterface $entity, string $action): void
  {
    if (!$this->currentUser->isAnonymous()) {
      $type = $entity->getEntityTypeId();
      $bundle = $entity->bundle();
      $type_string = "{$type}__$bundle";
      if ($points = $this->points[$type_string][$action] ?? NULL) {
        $log_msg = $this->t('@action @type @bundle (@id): "@title"', [
          '@action' => $this->literal[$action],
          '@type' => $type,
          '@bundle' => $bundle,
          '@id' => $entity->id(),
          '@title' => $entity->label() ?? '',
        ]);
        $this->userpointsService->addPoints($points, self::USER_POINT_TYPE, $this->user, $log_msg);
      }

    }
  }

}
