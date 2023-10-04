<?php

namespace Drupal\argue_user\Plugin\ArgueUserPoints;

/**
 * Plugin implementation of the argue_user_points_plugin.
 *
 * @ArgueUserPointsPlugin(
 *   id = "vote_default",
 *   base = "entity_actions",
 *   label = @Translation("Vote Actions"),
 *   description = @Translation("Receive points for adding, publishing, updating votes."),
 *   actions = {
 *     "create" = "1",
 *     "update" = "0",
 *     "delete" = "-1"
 *   },
 *   interface = "Drupal\Core\Entity\EntityInterface",
 *   default_user_point_type = "advancement",
 *   supported_entity_bundle_types = "/^vote__[a-z_]+/"
 * )
 */
class VoteActionPoints extends EntityActionPoints {

}
