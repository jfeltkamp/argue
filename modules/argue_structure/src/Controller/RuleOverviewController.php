<?php

namespace Drupal\argue_structure\Controller;

use Drupal\argue_proscons\Events\ArgueEvent;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigManager;
use Drupal\Core\Config\ConfigManagerInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\node\NodeInterface;
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
   * Argue structure config.
   *
   * @var ImmutableConfig
   */
  protected $argueStructureConfig;

  /**
   * Constructs a new RuleOverviewController object.
   *
   * @param EntityTypeManager $entity_type_manager
   * @param ConfigManager $config_manager
   * @param CacheBackendInterface $cache_render
   */
  public function __construct(EntityTypeManager $entity_type_manager, ConfigManager $config_manager, CacheBackendInterface $cache_render) {
    $this->entityTypeManager = $entity_type_manager;
    $this->configManager = $config_manager;
    $this->cacheRender = $cache_render;
    $this->argueStructureConfig = $this->configManager->getConfigFactory()
      ->get('argue_structure.arguestructureconf');
    $this->vocabulary = $this->entityTypeManager->getStorage('taxonomy_vocabulary')
        ->load($this->argueStructureConfig->get('argue_vocabulary'));
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('config.manager'),
      $container->get('cache.render')
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
   * Get rule tree.
   *
   * @return array
   *   Returns render array of the rule tree.
   */
  public function getTree() {

    if ($cache = $this->cacheRender->get('sections')) {
      // Load from cache if available.
      return $cache->data;
    } elseif (is_a($this->vocabulary, '\Drupal\taxonomy\VocabularyInterface')) {

      $vid = $this->vocabulary->id();

      $this->termStorage = $this->entityTypeManager->getStorage('taxonomy_term');
      $list = [
        '#type' => 'table',
        '#header' => [
          $this->t('Sections'),
          $this->t('Count of entries'),
        ],
        /* @Todo Create Link to generate rules, or add action above table with permission check. */
        '#empty' => $this->t('No rules found '),
        '#rows' => [],
        '#sticky' => TRUE,
        '#attributes' => [
          'id' => $vid,
        ],
        '#attached' => [
          'library' => [
            'argue_structure/structure.list'
          ]
        ],
      ];

      /** @var \stdClass[] $tree */
      $tree = $this->termStorage->loadTree($this->vocabulary->id());

      foreach ($tree as $term) {
        $related_nodes = $this->getNodesByTermId($term->tid);

        $link = $this->getTermRow($term);
        $list['#rows'][] = [
          $link,
          count($related_nodes) ?: '',
        ];
        foreach ($related_nodes as $rel_node) {
          $list['#rows'][] = $this->getNodeRow($rel_node->nid, $term->depth);
        }
      }

      $render_array = [
        '#type' => '#container',
        'head' => [
          '#type' => 'markup',
          '#markup' => $this->getDescription(),
        ],
        'list' => $list,
        '#cache' => [
          'tags' => ['sections', 'taxonomy_term', 'taxonomy_vocabulary:' . $vid],
          'contexts' => ['taxonomy_term']
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
   * @param $term \stdClass
   *   The taxonomy term.
   *
   * @return Link|array
   *   The table row content.
   */
  protected function getTermRow(\stdClass $term) {
    $attributes = [
      'id' => 'rule__' . $term->vid . '_' . $term->tid,
      'class' => [
        'level_' . $term->depth
      ]
    ];

    if ($this->argueStructureConfig->get('link_section_terms_to_the_term_page')) {
      $options = [
        'attributes' => $attributes,
      ];
      return Link::createFromRoute(
        $term->name,
        'entity.taxonomy_term.canonical',
        ['taxonomy_term' => $term->tid],
        $options
      );
    } else {
      return [
        'data' => [
          '#type' => 'html_tag',
          '#tag' => 'strong',
          '#attributes' => $attributes,
          '0' => ['#markup' => $term->name]
        ],
      ];
    }
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
    $nodes = $query->execute()->fetchAll();
    return $nodes;
  }

  /**
   * Content of a node row.
   *
   * @param $nid
   *   The node ID.
   * @param int $term_depth
   *   The depth level for the text indent.
   *
   * @return array
   */
  protected function getNodeRow($nid, $term_depth = 0) {
    /** @var NodeInterface $node */
    $node = $this->getNodeStorage()->load($nid);
    $options = ['attributes' => [
        'class' => [
          'level_'.($term_depth+1),
        ]
      ]
    ];
    return [
      $node->toLink(NULL, 'canonical', $options),
      $this->getNodeCounts($node->id()),
    ];
  }

  protected function getNodeCounts($id) {
    $query = \Drupal::database()->select('argument_field_data', 'tb');
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
    return $this->t('%pro <sub>PRO</sub> %con <sub>CONTRA</sub>', [
      '%pro' => $collector[ArgueEvent::ARGUE_PRO],
      '%con' => $collector[ArgueEvent::ARGUE_CON]
    ]);
  }
}
