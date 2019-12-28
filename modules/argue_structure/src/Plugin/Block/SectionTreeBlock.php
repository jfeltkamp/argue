<?php

namespace Drupal\argue_structure\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\taxonomy\Entity\Term;

/**
 * Provides a 'SectionTreeBlock' block.
 *
 * @Block(
 *  id = "section_tree_block",
 *  admin_label = @Translation("Section tree block"),
 * )
 */
class SectionTreeBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Drupal\argue_structure\SectionTreeService definition.
   *
   * @var \Drupal\argue_structure\SectionTreeService
   */
  protected $argueStructureSectionTree;

  /**
   * Drupal\Core\Routing\RouteMatchInterface definition.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $currentRouteMatch;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = new static($configuration, $plugin_id, $plugin_definition);
    $instance->argueStructureSectionTree = $container->get('argue_structure.section_tree');
    $instance->currentRouteMatch = $container->get('current_route_match');
    return $instance;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function build() {
    $build = [];
    if ($this->currentRouteMatch->getRouteName() == 'entity.taxonomy_term.canonical') {
      /** @var \Drupal\taxonomy\Entity\Term $term */
      $term = $this->currentRouteMatch->getParameter('taxonomy_term');

      if($term->bundle() == 'sections') {
        $build['#theme'] = 'section_tree_block';
        $build['section_tree_block'] = $this->argueStructureSectionTree->getTree($term->id());
      }
    }

    return $build;
  }

}
