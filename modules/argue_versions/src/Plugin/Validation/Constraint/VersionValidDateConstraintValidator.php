<?php

namespace Drupal\argue_versions\Plugin\Validation\Constraint;

use Drupal\Core\Datetime\DrupalDateTime;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the UniqueInteger constraint.
 */
class VersionValidDateConstraintValidator extends ConstraintValidator
{

    /**
     * {@inheritdoc}
     */
    public function validate($items, Constraint $constraint) {
      /** @var \Drupal\node\NodeInterface $node */
      $node = $items->getParent()->getEntity();
      foreach ($items as $item) {
        // First check if the value is not empty.
        if (empty($item->value)) {
          $this->context->addViolation($constraint->isEmpty, ['%value' => $item->value]);
        }

        // Check if the valid date of a new node is in the future.
        if ($node->isNew() && !$this->isInFuture($item->value)) {
          $this->context->addViolation($constraint->notInFuture, ['%value' => $item->value]);
        }
      }
    }

    private function isInFuture($value) {
      $date = new DrupalDateTime('now');
      $formatted = $date->format('Y-m-d');
      return $value > $formatted;
    }

}
