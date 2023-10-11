<?php

namespace Drupal\argue_user\Plugin\ArgueUserPoints;

use Drupal\argue_user\EntityActionsPluginBase;

/**
 * Plugin implementation of the argue_user_points_plugin.
 *
 * @ArgueUserPointsPlugin(
 *   id = "entity_default",
 *   label = @Translation("Entity Actions"),
 *   description = @Translation("Receive points for adding, publishing, updating entities."),
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
 *        "repeat": "3"
 *      },
 *     "delete" = {
 *        "repeat": "0"
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
