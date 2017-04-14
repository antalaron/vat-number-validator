VAT number validator
====================

[![Build Status](https://travis-ci.org/antalaron/vat-number-validator.svg?branch=master)](https://travis-ci.org/antalaron/vat-number-validator) [![Coverage Status](https://coveralls.io/repos/github/antalaron/vat-number-validator/badge.svg)](https://coveralls.io/github/antalaron/vat-number-validator?branch=master) [![Latest Stable Version](https://poser.pugx.org/antalaron/vat-number-validator/v/stable)](https://packagist.org/packages/antalaron/vat-number-validator) [![Latest Unstable Version](https://poser.pugx.org/antalaron/vat-number-validator/v/unstable)](https://packagist.org/packages/antalaron/vat-number-validator) [![License](https://poser.pugx.org/antalaron/vat-number-validator/license)](https://packagist.org/packages/antalaron/vat-number-validator)

PHP library to validate VAT numbers.

Installation
------------

Open a command console, enter your project directory and execute the
following command to download the latest stable version of this library:

```bash
$ composer require antalaron/vat-number-validator
```

This command requires you to have Composer installed globally, as explained
in the [installation chapter](https://getcomposer.org/doc/00-intro.md)
of the Composer documentation.

Basic usage
-----------

To validate a VAT number:

```php
use Antalaron\Component\VatNumberValidator\VatNumber;
use Symfony\Component\Validator\Validation;

$validator = Validation::createValidator();
$violations = $validator->validate('ATU37675002', new VatNumber());

if (0 !== count($violations)) {
    foreach ($violations as $violation) {
        echo $violation->getMessage().'<br>';
    }
}
```

You can add your own VAT validator via `extraVat` option:

```php
$violations = $validator->validate('11', new VatNumber(['extraVat' => function ($number) {
    return 0 !== preg_match('/^(\d{2})$/', $number);
}]));
```

Origin
------

This library is the PHP rewrite of [original JavaScript library](http://www.braemoor.co.uk/software/vat.shtml) by Braemoor Software
Freebies. Original contributors are found [here](http://www.braemoor.co.uk/software/vatupdates.shtml).

License
-------

This library is under [MIT License](http://opensource.org/licenses/mit-license.php).
