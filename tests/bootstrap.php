<?php

/*
 * (c) Antal Áron <antalaron@antalaron.hu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

if (!@include __DIR__.'/../vendor/autoload.php') {
    die('You must set up the project dependencies, run the following commands:
wget http://getcomposer.org/composer.phar
php composer.phar install --dev
');
}

if (!class_exists('PHPUnit\Framework\Assert')) {
    class_alias('PHPUnit\Framework\Assert', 'PHPUnit\Framework\Assert');
}

if (class_exists('Symfony\Component\Validator\Test\ConstraintValidatorTestCase')) {
    class_alias('Symfony\Component\Validator\Test\ConstraintValidatorTestCase', 'Antalaron\Component\VatNumberValidator\Tests\AbstractConstraintValidatorTest');
} else {
    class_alias('Symfony\Component\Validator\Tests\Constraints\AbstractConstraintValidatorTest', 'Antalaron\Component\VatNumberValidator\Tests\AbstractConstraintValidatorTest');
}
