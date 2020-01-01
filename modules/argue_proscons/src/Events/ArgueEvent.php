<?php
/**
 * Created by PhpStorm.
 * User: jfeltkamp
 * Date: 25.09.17
 * Time: 16:15
 */
namespace Drupal\argue_proscons\Events;

final class ArgueEvent {

  /**
   * @var integer
   *   Stored value for pro argument.
   */
  const ARGUE_PRO = 1;

  /**
   * @var integer
   *   Stored value for con argument.
   */
  const ARGUE_CON = 2;

  /**
   * @var integer
   *   The default value for argument type.
   */
  const ARGUE_DEFAULT = self::ARGUE_PRO;

  /**
   * Check if voting API has votes or not.
   *
   * @param $aid integer
   *   Argument id to check.
   * @return bool
   *   If evaluation has begun or not.
   */
  public static function hasEvaluationBegun($aid) {
    unset($aid);
    // @todo insert check for voting API.
    return FALSE;
  }
}