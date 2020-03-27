<?php

namespace Drupal\argue_versions\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Plugin implementation of the 'version_valid_date_constraint'.
 *
 * @Constraint(
 *   id = "version_valid_date_constraint",
 *   label = @Translation("Version valid date constraint", context = "Validation"),
 * )
 */
class VersionValidDateConstraint extends Constraint
{

    // The message that will be shown if the value is empty.
    public $isEmpty = '%value is empty';

    // The message that will be shown if the value is not unique.
    public $notInFuture = '%value is not in the future';

}
