<?php

namespace Drupal\argue_versions;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Template\Attribute;
use Drupal\node\NodeInterface;

/**
 * Class VersionsViewBuilderService.
 */
class VersionsViewBuilderService {

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new VersionsViewBuilderService object.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  public function getRenderedView(NodeInterface $node) {
    $rules = $this->getRenderedRules($node);
    $items = [];
    foreach($node->get('field_sections') as $item) {
      $value = ['#data' => $item->getValue()];
      $value['#theme'] = 'argue_versions_tree';
      $value['#title'] = $value['#data']['name'];
      if (isset($rules[$value['#data']['term_id']])) {
        $child_nodes = [
          '#type' => 'container',
          '#attributes' => new Attribute([
            'class' => ['argue-versions-tree--nodes'],
          ]),
          ];
        $value['#child_nodes'] = array_merge($child_nodes, $rules[$value['#data']['term_id']]);
      }
      $value['#children'] = [
        '#type' => 'container',
        '#attributes' => new Attribute([
          'class' => ['argue-versions-tree--children'],
        ]),
      ];
      $items[] = $value;
    }
    return $this->buildTree($items);
  }

  /**
   * @param array $elements
   * @param int $parentId
   * @param int $level
   *
   * @return array
   */
  protected function buildTree(array &$elements, $parentId = 0, $level = 2) {
    $branch = [];

    foreach ($elements as &$element) {
      if ($element['#data']['term_parent_id'] == $parentId) {
        $children = $this->buildTree($elements, $element['#data']['term_id'], $level+1);
        if ($children) {
          if(isset($element['#children'])) {
            $element['#children'] = array_merge($element['#children'], $children);
          } else {
            $element['#data']['children'] = $children;
          }
        } else {
          if(isset($element['#children'])) {
            unset($element['#children']);
          }
        }

        // Do not add sections with no content.
        if(isset($element['#children']) || isset($element['#child_nodes'])) {
          $element['#level'] = $level;
          $branch[$element['#data']['term_id']] = $element;
        }
        unset($element);
      }
    }
    return $branch;
  }

  /**
   * @param \Drupal\node\NodeInterface $version
   *
   * @return array
   */
  protected function getRules(NodeInterface $version) {
    $rules = [];
    if ($version->hasField('field_rules')) {
      foreach ($version->get('field_rules')->referencedEntities() as $rule) {
        /** @var \Drupal\node\NodeInterface $rule */
        if ($rule->hasField('field_sector')) {
          $target_id = $rule->get('field_sector')->getString();
          $rules[$target_id][] = $rule;
        }
      };
    }
    return $rules;
  }

  /**
   * @param \Drupal\node\NodeInterface $version
   * @param string $view_mode
   *
   * @return array
   */
  protected function getRenderedRules(NodeInterface $version, $view_mode = 'result') {
    $rendered_rules = [];
    $view_builder = $this->entityTypeManager->getViewBuilder('node');
    foreach($this->getRules($version) as $key => $val) {
      foreach ($val as $rule) {
        $rendered_rules[$key][] = $view_builder->view($rule, $view_mode);
      }
    }
    return $rendered_rules;
  }


}
