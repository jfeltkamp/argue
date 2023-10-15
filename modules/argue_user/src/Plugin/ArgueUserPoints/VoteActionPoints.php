<?php

namespace Drupal\argue_user\Plugin\ArgueUserPoints;

use Drupal\argue_user\EntityActionsPluginBase;
use Drupal\votingapi\VoteInterface;

/**
 * Plugin implementation of the argue_user_points_plugin.
 *
 * @ArgueUserPointsPlugin(
 *   id = "vote_actions",
 *   label = @Translation("Vote Actions"),
 *   description = @Translation("Receive points for adding, publishing, updating votes."),
 *   actions = {
 *     "create" = "defaultAction",
 *     "update" = "defaultAction",
 *     "delete" = "deleteAction"
 *   },
 *   validation_defaults = {
 *     "create" = { },
 *     "update" = { },
 *     "delete" = { }
 *   },
 *   interface = "Drupal\Core\Entity\EntityInterface",
 *   default_userpoint_type = "advancement",
 *   plugin_keys = "/^vote__[a-z_]+/"
 * )
 */
class VoteActionPoints extends EntityActionsPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getLogIdStack(string $action): ?array {
    if ($this->entity instanceof VoteInterface) {
      return [
        'rule' => $this->buildRuleId($action),
        'voted_entity' => "{$this->entity->getVotedEntityType()}:{$this->entity->getVotedEntityId()}",
      ];
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function buildRuleId(string $action): string {
    return implode('__', [
      $this->getPluginId(),
      "{$this->entity->getEntityTypeId()}",
      $action,
    ]);
  }

}
