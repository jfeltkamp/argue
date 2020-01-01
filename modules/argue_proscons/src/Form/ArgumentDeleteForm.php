<?php

namespace Drupal\argue_proscons\Form;

use Drupal\Core\Entity\ContentEntityDeleteForm;
use Drupal\Core\Field\EntityReferenceFieldItemList;

/**
 * Provides a form for deleting Argument entities.
 *
 * @ingroup argue_proscons
 */
class ArgumentDeleteForm extends ContentEntityDeleteForm {


  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  public function getCancelUrl() {
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $this->getEntity();
    if ($parent = $entity->hasField('reference_id')) {
      $referrers = $entity->get('reference_id');
      if ($referrers instanceof EntityReferenceFieldItemList) {
        $referred_to = $referrers->referencedEntities();
        $parent = reset($referred_to);
      } else {
        $parent = FALSE;
      }
    }
    return ($parent) ? $parent->toUrl('canonical') : $entity->toUrl('canonical');
  }
}
