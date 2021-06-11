<?php

namespace Drupal\argue_structure;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Config\ConfigManagerInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityViewBuilderInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Template\Attribute;
use Drupal\Core\Link;
use Drupal\path_alias\AliasManager;
use Drupal\Core\Url;

/**
 * Class SectionTreeService.
 */
class SectionTreeService {
  use StringTranslationTrait;

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Taxonomy Storage.
   *
   * @var \Drupal\taxonomy\TermStorageInterface
   */
  protected $termStorage;

  /**
   * Vocabulary.
   *
   * @var \Drupal\taxonomy\Entity\Vocabulary
   */
  protected $vocabulary;

  /**
   * Node storage.
   *
   * @var EntityStorageInterface
   */
  protected $nodeStorage;

  /**
   * Drupal\Core\Config\ConfigManagerInterface definition.
   *
   * @var \Drupal\Core\Config\ConfigManagerInterface
   */
  protected $configManager;

  /**
   * Drupal\Core\Cache\CacheBackendInterface definition.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cacheRender;

  /**
   * Drupal\Core\Render\RendererInterface definition.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Argue structure config.
   *
   * @var ImmutableConfig
   */
  protected $argueStructureConfig;

  /**
   * @var EntityViewBuilderInterface
   */
  protected $nodeViewBuilder;

  /**
   * @var AliasManager
   */
  protected $pathAliasManager;

  /**
   * Constructs a new SectionTreeService object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @param \Drupal\Core\Config\ConfigManagerInterface $config_manager
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_render
   * @param \Drupal\Core\Render\RendererInterface $renderer
   * @param \Drupal\path_alias\AliasManager $path_alias_manager
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ConfigManagerInterface $config_manager, CacheBackendInterface $cache_render, RendererInterface $renderer, AliasManager $path_alias_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->configManager = $config_manager;
    $this->argueStructureConfig = $this->configManager->getConfigFactory()
      ->get('argue_structure.arguestructureconf');
    $this->cacheRender = $cache_render;
    $this->renderer = $renderer;
    $this->pathAliasManager = $path_alias_manager;
    $this->vocabulary = $this->entityTypeManager->getStorage('taxonomy_vocabulary')
      ->load($this->argueStructureConfig->get('argue_vocabulary'));
  }


  /**
   * Returns a page title for controller or blog plugin.
   */
  public function getTitle() {
    $title = $this->vocabulary ? $this->vocabulary->label() : $this->t('No rule index found.');
    return $this->argueStructureConfig->get('title_section_term_overview_page')
      ?: $title;
  }

  /**
   * Returns the node storage.
   *
   * @return \Drupal\Core\Entity\EntityStorageInterface
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getNodeStorage() {
    if (!$this->nodeStorage) {
      $this->nodeStorage = $this->entityTypeManager->getStorage('node');
    }
    return $this->nodeStorage;
  }

  /**
   * Returns the nodeViewBuilder
   *
   * @return EntityViewBuilderInterface|mixed|object
   */
  public function getNodeViewBuilder() {
    if(!$this->nodeViewBuilder) {
      $this->nodeViewBuilder = $this->entityTypeManager->getViewBuilder('node');
    }
    return $this->nodeViewBuilder;
  }

  /**
   * @param array $tree
   * @param int $term_id
   * @param string $bundle
   * @param array $options
   * @param int $level
   *
   * @return array
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getItems(array $tree, $term_id, $bundle, $options, $level = 0) {
    $return = [];
    $level = ++$level;
    foreach($tree as $index => $item) {
      if(in_array($term_id, $item->parents)) {
        unset($tree[$index]);
        $link = ($bundle == 'rule')
          ? $this->pathAliasManager->getAliasByPath('/taxonomy/term/' . $item->tid)
          : Url::fromRoute('argue_structure.problem_overview_controller',
            ['tid' => $item->tid]);
        $return['term_' . $item->tid] = [
          '#theme' => 'argue_structure_nested_list',
          '#label' => $item->name,
          '#link' => $link,
          '#node_list' => $this->getNodeRow($item->tid, $bundle, $options),
          '#items' => $this->getItems($tree, $item->tid, $bundle, $options, $level),
          '#level' => $level,
          '#attributes' => new Attribute(['class' => ['level_'.$level]]),
        ];
      }
    }
    return $return;
  }

  /**
   * Get rule tree.
   *
   * @param int $term_id
   *   The term ID.
   * @param string $bundle
   *   The required node bundle.
   * @param array $options
   *   Optional filter options.
   *
   * @return array
   *   Returns render array of the rule tree.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getTree($term_id = 0, $bundle = 'rule', array $options = []) {
    // Build cache id = sections:taxonomy_term:12:rule
    $cache_id_comp = ['sections', 'taxonomy_term', $term_id];
    if($bundle) { $cache_id_comp[] = $bundle; }
    foreach ($options as $filter_field => $filter_value) {
      $cache_id_comp[] = "$filter_field\__$filter_value";
    }
    $cache_id = implode(':', $cache_id_comp);

    if ($cache = $this->cacheRender->get($cache_id)) {
      // Load from cache if available.
      return $cache->data;
    } elseif ($this->vocabulary instanceof \Drupal\taxonomy\VocabularyInterface) {

      $vid = $this->vocabulary->id();

      $this->termStorage = $this->entityTypeManager->getStorage('taxonomy_term');

      $max_depth = $this->argueStructureConfig->get('argue_displayed_nesting_levels');
      /** @var \stdClass[] $tree */
      $tree = $this->termStorage->loadTree($vid, $term_id, $max_depth);

      if ($term_id != 0) {
        /** @var \Drupal\taxonomy\Entity\Term $item */
        $item = $this->termStorage->load($term_id);
        $level = 0;
        $list = [
          '#theme' => 'argue_structure_nested_list',
          '#label' => $item->get('name')->getString(),
          '#description' => $item->getDescription(),
          '#node_list' => $this->getNodeRow($item->id(), $bundle, $options),
          '#items' => $this->getItems($tree, $item->id(), $bundle, $options, $level),
          '#level' => $level,
          '#attributes' => new Attribute(['class' => ['level_0']]),
        ];
      } else {
        $list = $this->getItems($tree, $term_id, $bundle, $options,-1);
      }

      $list = [
        '#theme' => 'argue_structure_nested_list',
        '#items' => $list,
        '#level' => -1,
        '#attributes' => new Attribute(['id' => $vid, 'class' => ['argue-structure-nested-list']]),
      ];

      $render_array = [
        '#type' => '#container',
        'list' => $list,
        '#cache' => [
          'tags' => ['sections', 'node', 'taxonomy_term', 'taxonomy_vocabulary:' . $vid],
          'contexts' => ['route', 'user.permissions']
        ]
      ];

      $this->cacheRender->set(
        $cache_id,
        $render_array,
        CacheBackendInterface::CACHE_PERMANENT,
        ['taxonomy_term', "taxonomy_term:{$term_id}:{$bundle}", 'taxonomy_term_list']);
    } else {
      $link_vocabs = Link::createFromRoute($this->t('vocabularies collection'), 'entity.taxonomy_vocabulary.collection');
      $link_argue_config = Link::createFromRoute($this->t('Argue config'), 'argue_structure.argue_structure_conf_form');
      $message = new TranslatableMarkup('No rule index found. Please go to the %vocabs page to check if your vocabulary (or the preinstalled "sections") exists, then go to %config and check if it is selected correct. Inform your admin, if you don\'t have sufficient permissions.  ', [
        '%vocabs' => $link_vocabs->toString(),
        '%config' => $link_argue_config->toString(),
      ]);

       \Drupal::messenger()->addMessage($message, 'error');

      $render_array = [
        '#markup' => '<strong>No content found.</strong>'
      ];
    }
    return $render_array;
  }

  /**
   * Get nodes tagged by this term.
   *
   * @param $tid
   *   The term id parameter.
   *
   * @return array
   *   The nodes tagged by this term.
   */
  protected function getNodesByTermId($tid) {
    $query = \Drupal::database()->select('taxonomy_index', 'ti');
    $query->fields('ti', ['nid']);
    $query->condition('ti.status', 1);
    $query->condition('ti.tid', $tid);
    $results = $query->execute()->fetchAll();
    $nodes = [];
    foreach($results as $node) {
      $nodes[] = $node->nid;
    }
    return $nodes;
  }

  /**
   * Content of a node row.
   *
   * @param integer $tid
   *   The node ID.
   * @param string $bundle
   *   The depth level for the text indent.
   * @param array $options
   *   Filter options.
   * @param int $term_depth
   *   The depth level for the text indent.
   *
   * @return \Drupal\Component\Render\MarkupInterface|null
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getNodeRow($tid, $bundle, array $options, $term_depth = 0) {
    $nids = $this->getNodesByTermId($tid);
    if ($nids) {
      $nodes = $this->getNodeStorage()->loadMultiple($nids);

      if ($bundle || count($options)) {
        // Filter by bundle.
        foreach($nodes as $key => $node) {
          /** @var  $nodes */
          if($bundle) {
            if($node->bundle() != $bundle) {
              unset($nodes[$key]);
              continue;
            }
          }
          // Filter by options
          if (count($options)) {
            foreach ($options as $filter_name => $filter_condition) {
              $valid = FALSE;
              if($node->hasField($filter_name) && $value_list = $node->get($filter_name)) {
                foreach ($value_list as $field_index => $field_value) {
                  if ($field_value->getString() == $filter_condition) $valid = TRUE;
                }
              }
              if(!$valid) unset($nodes[$key]);
            }
          }
        }
      }

      $nodes_view = $this->getNodeViewBuilder()->viewMultiple($nodes, 'list_item');

      $list = [
        '#theme' => 'argue_structure_list',
        '#attributes' => new Attribute(['class' => ['level_'.$term_depth]]),
        '#content' => $nodes_view
      ];

      return $this->renderer->renderRoot($list);

    } else {
      return NULL;
    }
  }

}
