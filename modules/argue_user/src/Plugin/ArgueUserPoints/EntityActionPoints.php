<?php

namespace Drupal\argue_user\Plugin\ArgueUserPoints;

use Drupal\argue_user\ArgueUserPointsPluginBase;
use Drupal\Core\Entity\EntityInterface;

/**
 * Plugin implementation of the argue_user_points_plugin.
 *
 * @ArgueUserPointsPlugin(
 *   id = "entity_default",
 *   base = "entity_actions",
 *   label = @Translation("Entity Actions"),
 *   description = @Translation("Receive points for adding, publishing, updating entities."),
 *   actions = {
 *     "create" = "10",
 *     "update" = "5",
 *     "delete" = "0"
 *   },
 *   interface = "Drupal\Core\Entity\EntityInterface",
 *   default_user_point_type = "advancement",
 *   supported_entity_bundle_types = {
 *     "node__problem",
 *     "node__rule",
 *     "argument__argument",
 *     "comment__comment"
 *   }
 * )
 */
class EntityActionPoints extends ArgueUserPointsPluginBase {

  /**
   * {@inheritdoc}
   */
  public function handleAction(string $action, mixed $context = NULL): void {

    if ($this->hasAction($action) && $context instanceof EntityInterface) {
      if ($points = $this->getActionPoints($action) ?? NULL) {
        $log_msg = $this->t('@action @type @bundle (@id)@title', [
          '@action' => $action,
          '@type' => $context->getEntityTypeId(),
          '@bundle' => $context->bundle(),
          '@id' => $context->id(),
          '@title' => (!!$context->label()) ? ": {$context->label()}" : '',
        ]);
        $this->userpointsService->addPoints($points, $this->getDefaultUserPointsType(), $this->user, $log_msg);
      }
    }
  }

}
