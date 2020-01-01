<?php

namespace Drupal\argue_proscons;

use Drupal\Core\Database\Connection;
use Drupal\argue_proscons\Events\ArgueEvent;

/**
 * Class EvaluatingService.
 */
class EvaluatingService {

  /**
   * Drupal\Core\Database\Driver\mysql\Connection definition.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Constructs a new EvaluatingService object.
   */
  public function __construct(Connection $database) {
    $this->database = $database;
  }

  public function getRuleArgumentCounts($id) {
    $query = $this->database->select('argument_field_data', 'tb');
    $query->fields('tb', ['type']);
    $query->condition('tb.reference_id', $id);
    $query->condition('tb.status', 1);
    $args = $query->execute()->fetchAll();
    $collector = [
      ArgueEvent::ARGUE_PRO => 0,
      ArgueEvent::ARGUE_CON => 0,
    ];
    foreach ($args as $arg) {
      $collector[$arg->type]++;
    }
    return [
      'pro' => $collector[ArgueEvent::ARGUE_PRO],
      'con' => $collector[ArgueEvent::ARGUE_CON]
    ];
  }
}
