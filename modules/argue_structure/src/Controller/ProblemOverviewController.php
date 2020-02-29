<?php

namespace Drupal\argue_structure\Controller;

use Drupal\argue_structure\SectionTreeService;
use Drupal\Core\Controller\ControllerBase;
use Drupal\taxonomy\Entity\Term;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class RuleOverviewController.
 */
class ProblemOverviewController extends ControllerBase {

  /**
   * Drupal\Core\Entity\EntityTypeManager definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $sectionTreeService;

  /**
   * Constructs a new RuleOverviewController object.
   */
  public function __construct(SectionTreeService $section_tree_service) {
    $this->sectionTreeService = $section_tree_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('argue_structure.section_tree')
    );
  }

  /**
   * Returns a page title.
   */
  public function getTitle() {
    return $this->t('Problems brought up');
  }

  /**
   * @param $tid
   *
   * @return mixed
   */
  public function getSubtreeTitle($tid) {
    $term = Term::load($tid);
    return $term->getName();
  }

  /**
   * Get rule tree stating with origin.
   *
   * @return array
   *   Returns render array of the rule tree.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getTree() {
    return $this->sectionTreeService->getTree(0, 'problem');
  }


  /**
   * Get rule tree starting with branch.
   *
   * @param $tid
   *   The term id to start with.
   *
   * @return array
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getSubtree($tid) {
    return $this->sectionTreeService->getTree($tid, 'problem');
  }

}
