<?php

/*
 * (c) Antal Áron <antalaron@antalaron.hu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPUnit\Framework;

if (!class_exists('PHPUnit\Framework\Assert')) {
    abstract class Assert extends \PHPUnit_Framework_Assert
    {
    }
}

namespace Antalaron\Component\VatNumberValidator\Tests;

use Symfony\Component\Validator\Tests\Constraints\AbstractConstraintValidatorTest as BaseAbstractConstraintValidatorTest;

abstract class AbstractConstraintValidatorTest extends BaseAbstractConstraintValidatorTest
{
}
