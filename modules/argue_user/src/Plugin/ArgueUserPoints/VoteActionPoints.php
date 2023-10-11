<?php

namespace Drupal\argue_user\Plugin\ArgueUserPoints;

use Drupal\argue_user\EntityActionsPluginBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\votingapi\VoteInterface;

/**
 * Plugin implementation of the argue_user_points_plugin.
 *
 * @ArgueUserPointsPlugin(
 *   id = "vote_default",
 *   label = @Translation("Vote Actions"),
 *   description = @Translation("Receive points for adding, publishing, updating votes."),
 *   actions = {
 *     "create" = "defaultAction",
 *     "update" = "defaultAction",
 *     "delete" = "defaultAction"
 *   },
 *   validation_defaults = {
 *     "create" = {
 *        "repeat": "0"
 *      },
 *     "update" = {
 *        "repeat": "0"
 *      },
 *     "delete" = {
 *        "repeat": "-1"
 *      }
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
  public function getLogIdStack(EntityInterface $entity, string $action): ?array {
    if ($entity instanceof VoteInterface) {
      return [
        'rule' => $this->rule['id'],
        'voted_entity' => "{$entity->getVotedEntityType()}:{$entity->getVotedEntityId()}",
      ];
    }
    return NULL;
  }

}
