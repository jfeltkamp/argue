<?php

namespace Drupal\argue_proscons\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Url;
use Drupal\argue_proscons\Entity\ArgumentInterface;

/**
 * Class ArgumentController.
 *
 *  Returns responses for Argument routes.
 */
class ArgumentController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * Displays a Argument  revision.
   *
   * @param int $argument_revision
   *   The Argument  revision ID.
   *
   * @return array
   *   An array suitable for drupal_render().
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function revisionShow($argument_revision) {
    $argument = $this->entityTypeManager()->getStorage('argument')
      ->loadRevision($argument_revision);
    $view_builder = $this->entityTypeManager()->getViewBuilder('argument');

    return $view_builder->view($argument);
  }

  /**
   * Page title callback for a Argument  revision.
   *
   * @param int $argument_revision
   *   The Arguments revision ID.
   *
   * @return string
   *   The page title.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function revisionPageTitle($argument_revision) {
    $argument = $this->entityTypeManager()->getStorage('argument')->loadRevision($argument_revision);
    return $this->t('Revision of %title from %date', ['%title' => $argument->label(), '%date' => \Drupal::service('date.formatter')->format($argument->getRevisionCreationTime())]);
  }

  /**
   * Generates an overview table of older revisions of a Argument .
   *
   * @param \Drupal\argue_proscons\Entity\ArgumentInterface $argument
   *   A Argument  object.
   *
   * @return array
   *   An array as expected by drupal_render().
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function revisionOverview(ArgumentInterface $argument) {
    $account = $this->currentUser();
    $langcode = $argument->language()->getId();
    $langname = $argument->language()->getName();
    $languages = $argument->getTranslationLanguages();
    $has_translations = (count($languages) > 1);
    $argument_storage = $this->entityTypeManager()->getStorage('argument');

    $build['#title'] = $has_translations ? $this->t('@langname revisions for %title', ['@langname' => $langname, '%title' => $argument->label()]) : $this->t('Revisions for %title', ['%title' => $argument->label()]);
    $header = [$this->t('Revision'), $this->t('Operations')];

    $revert_permission = (($account->hasPermission("revert all argument revisions") || $account->hasPermission('administer argument entities')));
    $delete_permission = (($account->hasPermission("delete all argument revisions") || $account->hasPermission('administer argument entities')));

    $rows = [];

    $vids = $argument_storage->revisionIds($argument);

    $latest_revision = TRUE;

    foreach (array_reverse($vids) as $vid) {
      /** @var \Drupal\argue_proscons\ArgumentInterface $revision */
      $revision = $argument_storage->loadRevision($vid);
      // Only show revisions that are affected by the language that is being
      // displayed.
      if ($revision->hasTranslation($langcode) && $revision->getTranslation($langcode)->isRevisionTranslationAffected()) {
        $username = [
          '#theme' => 'username',
          '#account' => $revision->getRevisionUser(),
        ];

        // Use revision link to link to revisions that are not active.
        $date = \Drupal::service('date.formatter')->format($revision->getRevisionCreationTime(), 'short');
        if ($vid != $argument->getRevisionId()) {
          $link = $this->l($date, new Url('entity.argument.revision', ['argument' => $argument->id(), 'argument_revision' => $vid]));
        }
        else {
          $link = $argument->link($date);
        }

        $row = [];
        $column = [
          'data' => [
            '#type' => 'inline_template',
            '#template' => '{% trans %}{{ date }} by {{ username }}{% endtrans %}{% if message %}<p class="revision-log">{{ message }}</p>{% endif %}',
            '#context' => [
              'date' => $link,
              'username' => \Drupal::service('renderer')->renderPlain($username),
              'message' => ['#markup' => $revision->getRevisionLogMessage(), '#allowed_tags' => Xss::getHtmlTagList()],
            ],
          ],
        ];
        $row[] = $column;

        if ($latest_revision) {
          $row[] = [
            'data' => [
              '#prefix' => '<em>',
              '#markup' => $this->t('Current revision'),
              '#suffix' => '</em>',
            ],
          ];
          foreach ($row as &$current) {
            $current['class'] = ['revision-current'];
          }
          $latest_revision = FALSE;
        }
        else {
          $links = [];
          if ($revert_permission) {
            $links['revert'] = [
              'title' => $this->t('Revert'),
              'url' => $has_translations ?
              Url::fromRoute('entity.argument.translation_revert', ['argument' => $argument->id(), 'argument_revision' => $vid, 'langcode' => $langcode]) :
              Url::fromRoute('entity.argument.revision_revert', ['argument' => $argument->id(), 'argument_revision' => $vid]),
            ];
          }

          if ($delete_permission) {
            $links['delete'] = [
              'title' => $this->t('Delete'),
              'url' => Url::fromRoute('entity.argument.revision_delete', ['argument' => $argument->id(), 'argument_revision' => $vid]),
            ];
          }

          $row[] = [
            'data' => [
              '#type' => 'operations',
              '#links' => $links,
            ],
          ];
        }

        $rows[] = $row;
      }
    }

    $build['argument_revisions_table'] = [
      '#theme' => 'table',
      '#rows' => $rows,
      '#header' => $header,
    ];

    return $build;
  }

}
