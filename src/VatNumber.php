<?php

/*
 * (c) Antal Áron <antalaron@antalaron.hu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Antalaron\Component\VatNumberValidator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;

/**
 * Validates a VAT number.
 *
 * @Annotation
 * @Target({"PROPERTY", "METHOD", "ANNOTATION"})
 *
 * @author Antal Áron <antalaron@antalaron.hu>
 */
class VatNumber extends Constraint
{
    const MESSAGE = 'Not a VAT number.';

    public $message = self::MESSAGE;
    public $extraVat;

    /**
     * {@inheritdoc}
     */
    public function __construct($options = null)
    {
        parent::__construct($options);

        if (null !== $this->extraVat && !is_callable($this->extraVat)) {
            throw new ConstraintDefinitionException('The option "extraVat" must be callable');
        }
    }
}
