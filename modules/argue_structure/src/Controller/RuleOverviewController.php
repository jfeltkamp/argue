<?php

namespace Drupal\argue_structure\Controller;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigManager;
use Drupal\Core\Config\ConfigManagerInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityViewBuilderInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Template\Attribute;
use Drupal\node\NodeInterface;
use Drupal\Core\Render\Renderer;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Link;

/**
 * Class RuleOverviewController.
 */
class RuleOverviewController extends ControllerBase {

  /**
   * Drupal\Core\Entity\EntityTypeManager definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
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
   * Config Manager.
   *
   * @var ConfigManagerInterface
   */
  protected $configManager;

  /**
   * Config Manager.
   *
   * @var CacheBackendInterface
   */
  protected $cacheRender;

  /**
   * Renderer.
   *
   * @var Renderer
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
   * Constructs a new RuleOverviewController object.
   *
   * @param EntityTypeManager $entity_type_manager
   * @param ConfigManager $config_manager
   * @param CacheBackendInterface $cache_render
   * @param Renderer $renderer
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(EntityTypeManager $entity_type_manager, ConfigManager $config_manager, CacheBackendInterface $cache_render, Renderer $renderer) {
    $this->entityTypeManager = $entity_type_manager;
    $this->configManager = $config_manager;
    $this->cacheRender = $cache_render;
    $this->argueStructureConfig = $this->configManager->getConfigFactory()
      ->get('argue_structure.arguestructureconf');
    $this->vocabulary = $this->entityTypeManager->getStorage('taxonomy_vocabulary')
      ->load($this->argueStructureConfig->get('argue_vocabulary'));
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('config.manager'),
      $container->get('cache.render'),
      $container->get('renderer')
    );
  }

  protected function getNodeStorage() {
    if (!$this->nodeStorage) {
      $this->nodeStorage = $this->entityTypeManager->getStorage('node');
    }
    return $this->nodeStorage;
  }


  /**
   * Returns a page title.
   */
  public function getTitle() {
    $title = $this->vocabulary ? $this->vocabulary->label() : $this->t('No rule index found.');
    return $this->argueStructureConfig->get('title_section_term_overview_page')
      ?: $title;
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
   * Returns a page title.
   */
  public function getDescription() {
    $description = $this->vocabulary
      ? $this->vocabulary->getDescription()
      : $this->t('No rule description found.');
    return $this->argueStructureConfig->get('description_section_term_overview_page')
      ?: $description;
  }

  /**
   * @param array $tree
   * @param $i
   *
   * @return array
   */
  protected function getItems(array $tree, $i) {
    $return = [];
    foreach($tree as $index => $item) {
      if(in_array($i, $item->parents)) {
        unset($tree[$index]);
        $return['term_' . $item->tid] = [
          '#theme' => 'argue_structure_nested_list',
          '#label' => $item->name,
          '#node_list' => $this->getNodeRow($item->tid),
          '#items' => $this->getItems($tree, $item->tid),
          '#level' => $item->depth,
          '#attributes' => new Attribute(['class' => ['level_'.$item->depth]]),
        ];
      }
    }
    return $return;
  }

  /**
   * Get rule tree.
   *
   * @return array
   *   Returns render array of the rule tree.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getTree() {

    if ($cache = $this->cacheRender->get('sections')) {
      // Load from cache if available.
      return $cache->data;
    } elseif (is_a($this->vocabulary, '\Drupal\taxonomy\VocabularyInterface')) {

      $vid = $this->vocabulary->id();

      $this->termStorage = $this->entityTypeManager->getStorage('taxonomy_term');
      /** @var \stdClass[] $tree */
      $tree = $this->termStorage->loadTree($this->vocabulary->id());
      $list = [
        '#theme' => 'argue_structure_nested_list',
        '#items' => $this->getItems($tree,0),
        '#level' => -1,
        '#attributes' => new Attribute(['id' => $vid, 'class' => ['argue-structure-nested-list']]),
        '#attached' => [
          'library' => [
            'argue_structure/structure.list'
          ]
        ],
      ];

      $render_array = [
        '#type' => '#container',
        'head' => [
          '#markup' => $this->getDescription(),
        ],
        'list' => $list,
        '#cache' => [
          'tags' => ['sections', 'taxonomy_term', 'taxonomy_vocabulary:' . $vid],
          'contexts' => ['route', 'user.permissions']
        ]
      ];

      $this->cacheRender->set(
        'sections',
        $render_array,
        CacheBackendInterface::CACHE_PERMANENT,
        ['taxonomy_term', 'taxonomy_term_list']);
    } else {
      $link_vocabs = Link::createFromRoute($this->t('vocabularies collection'), 'entity.taxonomy_vocabulary.collection');
      $link_argue_config = Link::createFromRoute($this->t('Argue config'), 'argue_structure.argue_structure_conf_form');
      $message = new TranslatableMarkup('No rule index found. Please go to the %vocabs page to check if your vocabulary (or the preinstalled "sections") exists, then go to %config and check if it is selected correct. Inform your admin, if you don\'t have sufficient permissions.  ', [
        '%vocabs' => $link_vocabs->toString(),
        '%config' => $link_argue_config->toString(),
      ]);

      drupal_set_message($message, 'error');

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
   * @param int $term_depth
   *   The depth level for the text indent.
   *
   * @return array|NULL
   */
  protected function getNodeRow($tid, $term_depth = 0) {
    $nids = $this->getNodesByTermId($tid);
    if ($nids) {
      $nodes = $this->getNodeStorage()->loadMultiple($nids);
      $nodes_view = $this->getNodeViewBuilder()->viewMultiple($nodes, 'list_item');

      $list = [
        '#theme' => 'argue_structure_list',
        '#attributes' => new Attribute(['class' => ['mdc-list--two-line', 'mdc-list--avatar-list', 'level_'.$term_depth]]),
        '#content' => $nodes_view
      ];

      $node_html = $this->renderer->renderRoot($list);
      return $node_html;
    } else {
      return NULL;
    }
  }

}
