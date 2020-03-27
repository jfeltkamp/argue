<?php

namespace Drupal\argue_versions;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\KeyValueStore\KeyValueFactory;

/**
 * Class SnapshotService.
 */
class SnapshotService {

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Drupal\Core\Config\ConfigFactoryInterface definition.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Drupal\Core\Config\ImmutableConfig definition.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $argueStructureConf;

  /**
   * @var \Drupal\Core\KeyValueStore\KeyValueStoreInterface
   */
  protected $versionStore;

  /**
   * Constructs a new SnapshotService object.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ConfigFactoryInterface $config_factory, KeyValueFactory $keyValueFactory) {
    $this->entityTypeManager = $entity_type_manager;
    $this->configFactory = $config_factory;
    $this->versionStore = $keyValueFactory->get('argue.argue_versions');
    $this->argueStructureConf = $this->configFactory
      ->get('argue_structure.arguestructureconf');
  }

  /**
   * Returns law title.
   *
   * @return string
   */
  public function getTitle() {
    return $this->argueStructureConf->get('title_section_term_overview_page');
  }

  /**
   * Returns law title.
   *
   * @return string
   */
  public function getDesc() {
    return $this->argueStructureConf->get('description_section_term_overview_page');
  }

  /**
   * Return number of the next version.
   *
   * @return int|mixed
   */
  public function getNextVersionNum() {
    return $this->versionStore->get('version', 0) + 1;
  }

  /**
   * Set number of the recent version.
   *
   * @param int $num
   */
  public function setVersionNum(int $num) {
    if ($num > $this->versionStore->get('version', 0)) {
      $this->versionStore->set('version', $num);
    }
  }

  /**
   * Returns array of taxonomy term clones shaped as field values for
   * field type VocabularyCloneFieldType.
   *
   * @return array
   *   Returns array of taxonomy term clones.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getSectionsSnapshot() {
    // Save terms
    $vid = $this->argueStructureConf->get('argue_vocabulary');
    /** @var \Drupal\taxonomy\Entity\Term[] $terms */
    $terms = $this->entityTypeManager->getStorage('taxonomy_term')
      ->loadTree($vid, 0, NULL, TRUE);
    $sections_values = [];
    foreach ($terms as $term) {
      $sections_values[] = [
        'term_id' => $term->id(),
        'term_parent_id' => $term->parent->target_id,
        'name' => $term->label(),
        'description' => $term->getDescription() ?: ''
      ];
    }
    return $sections_values;
  }


  /**
   * Returns array of node ids shaped as field values for field type
   * EntityReferenceRevisions.
   *
   * @param array $query
   *
   * @return array
   *   Returns field values for field_type EntityReferenceRevisions.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getEntitySnapshot($type_id, array $query) {
    $rules_value = [];
    /** @var \Drupal\node\NodeInterface[] $nodes */
    $nodes = $this->entityTypeManager->getStorage('node')
      ->loadByProperties($query);
    foreach ($nodes as $node) {
      $rules_value[] = [
        'target_id' => $node->id(),
        'target_revision_id' => $node->getRevisionId()
      ];
    }
    return $rules_value;
  }

  /**
   * Returns array of node ids shaped as field values for field type
   * EntityReferenceRevisions.
   *
   * @return array
   *   Returns field values for field_type EntityReferenceRevisions.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getRulesSnapshot() {
    $query = [
        'type' => 'rule',
        'status' => 1,
        'field_ratified' => 1,
      ];
    return $this->getEntitySnapshot('node', $query);
  }

  /**
   * Returns array of node ids shaped as field values for field type
   * EntityReferenceRevisions.
   *
   * @return array
   *   Returns field values for field_type EntityReferenceRevisions.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getProblemsSnapshot() {
    $query = [
      'type' => 'problem',
      'status' => 1,
    ];
    return $this->getEntitySnapshot('node', $query);
  }


  /**
   * Returns array of node ids shaped as field values for field type
   * EntityReferenceRevisions.
   *
   * @return array
   *   Returns field values for field_type EntityReferenceRevisions.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getArgumentsSnapshot() {
    $query = [
      'status' => 1,
    ];
    return $this->getEntitySnapshot('argument', $query);
  }

}
