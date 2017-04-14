<?php

/*
 * (c) Antal Áron <antalaron@antalaron.hu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Antalaron\Component\VatNumberValidator;

use Symfony\Component\Validator\Constraint;

/**
 * VatNumber.
 *
 * @author Antal Áron <antalaron@antalaron.hu>
 */
class VatNumber extends Constraint
{
    const MESSAGE = 'Not a tax number.';

    public $message = self::MESSAGE;
    public $schemes;

    public function getDefaultOption()
    {
    }

    public function getRequiredOptions()
    {
    }
}
