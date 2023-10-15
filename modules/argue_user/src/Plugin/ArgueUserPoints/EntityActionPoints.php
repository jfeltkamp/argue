<?php

namespace Drupal\argue_user\Plugin\ArgueUserPoints;

use Drupal\argue_user\EntityActionsPluginBase;

/**
 * Plugin implementation of the argue_user_points_plugin.
 *
 * @ArgueUserPointsPlugin(
 *   id = "entity_actions",
 *   label = @Translation("Entity Actions"),
 *   description = @Translation("Receive points for adding, publishing, updating entities."),
 *   actions = {
 *     "create" = "defaultAction",
 *     "update" = "defaultAction",
 *     "delete" = "defaultAction"
 *   },
 *   validation_defaults = {
 *     "create" = {
 *        "published" = TRUE
 *      },
 *     "update" = {
 *        "repeat" = 5,
 *        "published" = TRUE,
 *        "earliest_repeat" = 86400
 *      },
 *     "delete" = {
 *        "repeat" =  0,
 *        "revert_max_age" = 2419200
 *      }
 *   },
 *   interface = "Drupal\Core\Entity\EntityInterface",
 *   default_userpoint_type = "advancement",
 *   plugin_keys = {
 *     "node__problem",
 *     "node__rule",
 *     "argument__argument",
 *     "comment__comment"
 *   }
 * )
 */
class EntityActionPoints extends EntityActionsPluginBase {}
