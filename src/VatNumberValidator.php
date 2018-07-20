<?php

/*
 * (c) Antal Áron <antalaron@antalaron.hu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Antalaron\Component\VatNumberValidator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates a VAT number.
 *
 * @author Antal Áron <antalaron@antalaron.hu>
 */
class VatNumberValidator extends ConstraintValidator
{
    /**
     * VAT regex.
     *
     * VAT codes without the "+" in the comment do not have check digit checking.
     *
     * @var array
     */
    protected $schemes = [
        '/^(AT)U(\d{8})$/',                         // + Austria
        '/^(BE)(0?\d{9})$/',                        // + Belgium
        '/^(BG)(\d{9,10})$/',                       // + Bulgaria
        '/^(CHE)(\d{9})(MWST|TVA|IVA)?$/',          // + Switzerland (not EU)
        '/^(CY)([0-59]\d{7}[A-Z])$/',               // + Cyprus
        '/^(CZ)(\d{8,10})(\d{3})?$/',               // + Czech Republic
        '/^(DE)([1-9]\d{8})$/',                     // + Germany
        '/^(DK)(\d{8})$/',                          // + Denmark
        '/^(EE)(10\d{7})$/',                        // + Estonia
        '/^(EL)(\d{9})$/',                          // + Greece
        '/^(ES)([A-Z]\d{8})$/',                     // + Spain (National juridical entities)
        '/^(ES)([A-HN-SW]\d{7}[A-J])$/',            // + Spain (Other juridical entities)
        '/^(ES)([0-9YZ]\d{7}[A-Z])$/',              // + Spain (Personal entities type 1)
        '/^(ES)([KLMX]\d{7}[A-Z])$/',               // + Spain (Personal entities type 2)
        '/^(EU)(\d{9})$/',                          // + EU-type
        '/^(FI)(\d{8})$/',                          // + Finland
        '/^(FR)(\d{11})$/',                         // + France (1)
        '/^(FR)([A-HJ-NP-Z]\d{10})$/',              // France (2)
        '/^(FR)(\d[A-HJ-NP-Z]\d{9})$/',             // France (3)
        '/^(FR)([A-HJ-NP-Z]{2}\d{9})$/',            // France (4)
        '/^(GB)(\d{9})$/',                          // + UK (Standard)
        '/^(GB)(\d{12})$/',                         // + UK (Branches)
        '/^(GB)(GD\d{3})$/',                        // + UK (Government)
        '/^(GB)(HA\d{3})$/',                        // + UK (Health authority)
        '/^(HR)(\d{11})$/',                         // + Croatia
        '/^(HU)(\d{8})$/',                          // + Hungary
        '/^(IE)(\d{7}[A-W])$/',                     // + Ireland (1)
        '/^(IE)([7-9][A-Z\*\+)]\d{5}[A-W])$/',      // + Ireland (2)
        '/^(IE)(\d{7}[A-W][AH])$/',                 // + Ireland (3)
        '/^(IT)(\d{11})$/',                         // + Italy
        '/^(LT)(\d{9}|\d{12})$/',                   // + Lithunia
        '/^(LV)(\d{11})$/',                         // + Latvia
        '/^(LU)(\d{8})$/',                          // + Luxembourg
        '/^(MT)([1-9]\d{7})$/',                     // + Malta
        '/^(NL)(\d{9})B\d{2}$/',                    // + Netherlands
        '/^(NO)(\d{9})$/',                          // + Norway (not EU)
        '/^(PL)(\d{10})$/',                         // + Poland
        '/^(PT)(\d{9})$/',                          // + Portugal
        '/^(RO)([1-9]\d{1,9})$/',                   // + Romania
        '/^(RS)(\d{9})$/',                          // + Serbia (not EU)
        '/^(RU)(\d{10}|\d{12})$/',                  // + Russia (not EU)
        '/^(SE)(\d{10}01)$/',                       // + Sweden
        '/^(SI)([1-9]\d{7})$/',                     // + Slovenia
        '/^(SK)([1-9]\d[2346-9]\d{7})$/',           // + Slovakia
    ];

    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if (null === $value || '' === $value) {
            return;
        }

        // Uppercase, remove spaces etc. from the VAT number to help validation
        $value = preg_replace('/(\s|-|\.)+/', '', strtoupper($value));

        // Check user callable first
        if (null !== $constraint->extraVat && call_user_func($constraint->extraVat, $value)) {
            return;
        }

        // Use only the expressions for the given country code
        $schemes = preg_grep('/^\/\^\('.preg_quote(substr($value, 0, 2), '/').'/', $this->schemes);

        // Check the string against the regular expressions for all types of
        // VAT numbers
        foreach ($schemes as $scheme) {
            // Have we recognised the VAT number?
            if (0 !== preg_match($scheme, $value, $match)) {
                // Yes - we have:
                // Call the appropriate country VAT validation routine depending,
                // on the country code (if the method exists)
                $method = $match[1].'check';
                if ($this->$method($match[2])) {
                    return;
                }

                // Having processed the number, we break from the loop
                break;
            }
        }

        // If we reached this point, it is invalid
        $this->context->buildViolation($constraint->message)
            ->addViolation();
    }

    /**
     * Checks the check digits of an Austrian VAT number.
     *
     * @param string $number VAT number
     *
     * @return bool
     */
    private function ATcheck($number)
    {
        $total = 0;
        $multipliers = [1, 2, 1, 2, 1, 2, 1];
        $temp = 0;

        // Extract the next digit and multiply by the appropriate multiplier
        for ($i = 0; $i < 7; ++$i) {
            $temp = (int) $number[$i] * $multipliers[$i];
            if (9 < $temp) {
                $total += floor($temp / 10) + $temp % 10;
            } else {
                $total += $temp;
            }
        }

        // Establish check digit
        $total = 10 - ($total + 4) % 10;
        if (10 === $total) {
            $total = 0;
        }

        // Compare it with the last character of the VAT number. If it's the
        // same, then it's valid
        return $total === (int) $number[7];
    }

    /**
     * Checks the check digits of a Belgian VAT number.
     *
     * @param string $number VAT number
     *
     * @return bool
     */
    private function BEcheck($number)
    {
        // Nine digit numbers have a 0 inserted at the front.
        if (9 === strlen($number)) {
            $number = '0'.$number;
        }

        if ('0' === $number[1]) {
            return false;
        }

        // Modulus 97 check on last nine digits
        return (97 - (int) substr($number, 0, 8) % 97) === ((int) substr($number, 8, 2));
    }

    /**
     * Checks the check digits of a Bulgarian VAT number.
     *
     * @param string $number VAT number
     *
     * @return bool
     */
    private function BGcheck($number)
    {
        // Check the check digit of 9 digit Bulgarian VAT numbers.
        if (9 === strlen($number)) {
            $total = 0;

            // First try to calculate the check digit using the first multipliers
            $temp = 0;
            for ($i = 0; $i < 8; ++$i) {
                $temp += (int) $number[$i] * ($i + 1);
            }

            // See if we have a check digit yet
            $total = $temp % 11;
            if (10 !== $total) {
                return $total === (int) substr($number, 8);
            }

            // We got a modulus of 10 before so we have to keep going. Calculate
            // the new check digit using the different multipliers
            $temp = 0;
            for ($i = 0; $i < 8; ++$i) {
                $temp += (int) $number[$i] * ($i + 3);
            }

            // See if we have a check digit yet. If we still have a modulus of
            // 10, set it to 0.
            $total = $temp % 11;
            if (10 === $total) {
                $total = 0;
            }

            return $total === (int) substr($number, 8);
        }

        if (0 !== preg_match('/^\d\d[0-5]\d[0-3]\d\d{4}$/', $number)) {
            // Check month
            $month = (int) substr($number, 2, 2);
            if (($month > 0 && $month < 13) ||
                ($month > 20 && $month < 33) ||
                ($month > 40 && $month < 53)
            ) {
                // Extract the next digit and multiply by the counter.
                $multipliers = [2, 4, 8, 5, 10, 9, 7, 3, 6];
                $total = 0;
                for ($i = 0; $i < 9; ++$i) {
                    $total += (int) $number[$i] * $multipliers[$i];
                }

                // Establish check digit.
                $total = $total % 11;
                if (10 === $total) {
                    $total = 0;
                }

                // Check to see if the check digit given is correct,
                // If not, try next type of person
                if ((int) $number[9] === $total) {
                    return true;
                }
            }
        }

        // It doesn't relate to a standard physical person - see if it relates
        // to a foreigner.
        // Extract the next digit and multiply by the counter.
        $multipliers = [21, 19, 17, 13, 11, 9, 7, 3, 1];
        $total = 0;
        for ($i = 0; $i < 9; ++$i) {
            $total += (int) $number[$i] * $multipliers[$i];
        }

        // Check to see if the check digit given is correct, If not, try next
        // type of person
        if ((int) $number[9] === $total % 10) {
            return true;
        }

        // Finally, if not yet identified, see if it conforms to a miscellaneous
        // VAT number
        // Extract the next digit and multiply by the counter.
        $multipliers = [4, 3, 2, 7, 6, 5, 4, 3, 2];
        $total = 0;
        for ($i = 0; $i < 9; ++$i) {
            $total += (int) $number[$i] * $multipliers[$i];
        }

        // Establish check digit.
        $total = 11 - $total % 11;
        if (10 === $total) {
            return false;
        } elseif (11 === $total) {
            $total = 0;
        }

        // Check to see if the check digit given is correct, If not, we have
        // an error with the VAT number
        return $total === (int) $number[9];
    }

    /**
     * Checks the check digits of a Swiss VAT number.
     *
     * @param string $number VAT number
     *
     * @return bool
     */
    private function CHEcheck($number)
    {
        $multipliers = [5, 4, 3, 2, 7, 6, 5, 4];
        $total = 0;
        for ($i = 0; $i < 8; ++$i) {
            $total += (int) $number[$i] * $multipliers[$i];
        }

        // Establish check digit.
        $total = 11 - $total % 11;
        if (10 === $total) {
            return false;
        } elseif (11 === $total) {
            $total = 0;
        }

        // Check to see if the check digit given is correct, If not, we have
        // an error with the VAT number
        return $total === (int) $number[8];
    }

    /**
     * Checks the check digits of an Cypriot VAT number.
     *
     * @param string $number VAT number
     *
     * @return bool
     */
    private function CYcheck($number)
    {
        // Not allowed to start with '12'
        if ('12' === substr($number, 0, 2)) {
            return false;
        }

        // Extract the next digit and multiply by the counter.
        $total = 0;
        for ($i = 0; $i < 8; ++$i) {
            $temp = (int) $number[$i];
            if (0 === $i % 2) {
                if (0 === $temp) {
                    $temp = 1;
                } elseif (1 === $temp) {
                    $temp = 0;
                } elseif (2 === $temp) {
                    $temp = 5;
                } elseif (3 === $temp) {
                    $temp = 7;
                } elseif (4 === $temp) {
                    $temp = 9;
                } else {
                    $temp = 2 * $temp + 3;
                }
            }
            $total += $temp;
        }

        // Establish check digit using modulus 26, and translate to char. equivalent.
        $total = $total % 26;
        $total = chr($total + 65);

        // Check to see if the check digit given is correct
        return $number[8] === $total;
    }

    /**
     * Checks the check digits of an Czech VAT number.
     *
     * @param string $number VAT number
     *
     * @return bool
     */
    private function CZcheck($number)
    {
        $total = 0;
        $multipliers = [8, 7, 6, 5, 4, 3, 2];

        $czExpr = [
            '/^\d{8}$/',
            '/^[0-5][0-9][0|1|5|6][0-9][0-3][0-9]\d{3}$/',
            '/^6\d{8}$/',
            '/^\d{2}[0-3|5-8][0-9][0-3][0-9]\d{4}$/',
        ];
        $i = 0;

        // Legal entities
        if (0 !== preg_match($czExpr[0], $number)) {
            // Extract the next digit and multiply by the counter.
            for ($i = 0; $i < 7; ++$i) {
                $total += (int) $number[$i] * $multipliers[$i];
            }

            // Establish check digit.
            $total = 11 - $total % 11;
            if (10 === $total) {
                $total = 0;
            } elseif (11 === $total) {
                $total = 1;
            }

            // Compare it with the last character of the VAT number. If it's the same, then it's valid.
            return (int) $number[7] === $total;
        }
        // Individuals type 1 (Standard) - 9 digits without check digit
        elseif (0 !== preg_match($czExpr[1], $number)) {
            return (int) substr($number, 0, 2) <= 62;
        }
        // Individuals type 2 (Special Cases) - 9 digits including check digit
        elseif (0 !== preg_match($czExpr[2], $number)) {
            // Extract the next digit and multiply by the counter.
            for ($i = 0; $i < 7; ++$i) {
                $total += (int) $number[$i + 1] * $multipliers[$i];
            }

            // Establish check digit pointer into lookup table
            if (0 === $total % 11) {
                $a = $total + 11;
            } else {
                $a = ceil($total / 11) * 11;
            }
            $pointer = $a - $total;

            // Convert calculated check digit according to a lookup table;
            $lookup = [8, 7, 6, 5, 4, 3, 2, 1, 0, 9, 8];

            return $lookup[$pointer - 1] === (int) $number[8];
        }
        // Individuals type 3 - 10 digits
        elseif (0 !== preg_match($czExpr[3], $number)) {
            $temp = (int) substr($number, 0, 2) +
                (int) substr($number, 2, 2) +
                (int) substr($number, 4, 2) +
                (int) substr($number, 5, 2) +
                (int) substr($number, 6);

            return (0 === $temp % 11) && (0 === (int) $number % 11);
        }

        // else error
        return false;
    }

    /**
     * Checks the check digits of a German VAT number.
     *
     * @param string $number VAT number
     *
     * @return bool
     */
    private function DEcheck($number)
    {
        $product = 10;
        $sum = 0;
        $checkDigit = 0;
        for ($i = 0; $i < 8; ++$i) {
            // Extract the next digit and implement peculiar algorithm!.
            $sum = ((int) $number[$i] + $product) % 10;

            if (0 === $sum) {
                $sum = 10;
            }

            $product = (2 * $sum) % 11;
        }

        // Establish check digit.
        if (1 === $product) {
            $checkDigit = 0;
        } else {
            $checkDigit = 11 - $product;
        }

        // Compare it with the last two characters of the VAT number. If the
        // same, then it is a valid check digit.
        return (int) substr($number, 8, 2) === $checkDigit;
    }

    /**
     * Checks the check digits of a Danish VAT number.
     *
     * @param string $number VAT number
     *
     * @return bool
     */
    private function DKcheck($number)
    {
        $total = 0;
        $multipliers = [2, 7, 6, 5, 4, 3, 2, 1];

        // Extract the next digit and multiply by the counter.
        for ($i = 0; $i < 8; ++$i) {
            $total += (int) $number[$i] * $multipliers[$i];
        }

        // Establish check digit.
        $total = $total % 11;

        // The remainder should be 0 for it to be valid.
        return 0 === $total;
    }

    /**
     * Checks the check digits of an Estonian VAT number.
     *
     * @param string $number VAT number
     *
     * @return bool
     */
    private function EEcheck($number)
    {
        $total = 0;
        $multipliers = [3, 7, 1, 3, 7, 1, 3, 7];

        // Extract the next digit and multiply by the counter.
        for ($i = 0; $i < 8; ++$i) {
            $total += (int) $number[$i] * $multipliers[$i];
        }

        // Establish check digits using modulus 10.
        $total = 10 - $total % 10;
        if (10 === $total) {
            $total = 0;
        }

        // Compare it with the last character of the VAT number. If it's the same, then it's valid.
        return (int) $number[8] === $total;
    }

    /**
     * Checks the check digits of a Greek VAT number.
     *
     * @param string $number VAT number
     *
     * @return bool
     */
    private function ELcheck($number)
    {
        $total = 0;
        $multipliers = [256, 128, 64, 32, 16, 8, 4, 2];

        //eight character numbers should be prefixed with an 0.
        if (8 === strlen($number)) {
            $number = '0'.$number;
        }

        // Extract the next digit and multiply by the counter.
        for ($i = 0; $i < 8; ++$i) {
            $total += (int) $number[$i] * $multipliers[$i];
        }

        // Establish check digit.
        $total = $total % 11;
        if (9 < $total) {
            $total = 0;
        }

        // Compare it with the last character of the VAT number. If it's the same, then it's valid.
        return (int) $number[8] === $total;
    }

    /**
     * Checks the check digits of a Spanish VAT number.
     *
     * @param string $number VAT number
     *
     * @return bool
     */
    private function EScheck($number)
    {
        $total = 0;
        $temp = 0;
        $multipliers = [2, 1, 2, 1, 2, 1, 2];
        $esExpr = [
            '/^[A-H|J|U|V]\d{8}$/',
            '/^[A-H|N-S|W]\d{7}[A-J]$/',
            '/^[0-9|Y|Z]\d{7}[A-Z]$/',
            '/^[K|L|M|X]\d{7}[A-Z]$/',
        ];
        $i = 0;

        // National juridical entities
        if (0 !== preg_match($esExpr[0], $number)) {
            // Extract the next digit and multiply by the counter.
            for ($i = 0; $i < 7; ++$i) {
                $temp = (int) $number[$i + 1] * $multipliers[$i];
                if (9 < $temp) {
                    $total += floor($temp / 10) + $temp % 10;
                } else {
                    $total += $temp;
                }
            }

            // Now calculate the check digit itself.
            $total = 10 - $total % 10;
            if (10 === $total) {
                $total = 0;
            }

            // Compare it with the last character of the VAT number. If it's the same, then it's valid.
            return (int) $number[8] === $total;
        }
        // Juridical entities other than national ones
        elseif (0 !== preg_match($esExpr[1], $number)) {
            // Extract the next digit and multiply by the counter.
            for ($i = 0; $i < 7; ++$i) {
                $temp = (int) $number[$i + 1] * $multipliers[$i];
                if (9 < $temp) {
                    $total += floor($temp / 10) + $temp % 10;
                } else {
                    $total += $temp;
                }
            }

            // Now calculate the check digit itself.
            $total = 10 - $total % 10;
            $total = chr($total + 64);

            // Compare it with the last character of the VAT number. If it's the same, then it's valid.
            return $total === $number[8];
        }
        // Personal number (NIF) (starting with numeric of Y or Z)
        elseif (0 !== preg_match($esExpr[2], $number)) {
            $tempNumber = $number;
            if ('Y' === $tempNumber[0]) {
                $tempNumber = str_replace('Y', '1', $tempNumber);
            } elseif ('Z' === $tempNumber[0]) {
                $tempNumber = str_replace('Z', '2', $tempNumber);
            }

            $charString = 'TRWAGMYFPDXBNJZSQVHLCKE';

            return $tempNumber[8] === $charString[(int) substr($tempNumber, 0, 8) % 23];
        }
        // Personal number (NIF) (starting with K, L, M, or X)
        elseif (0 !== preg_match($esExpr[3], $number)) {
            $charString = 'TRWAGMYFPDXBNJZSQVHLCKE';

            return $tempNumber[8] === $charString[(int) substr($tempNumber, 1, 7) % 23];
        }

        return false;
    }

    /**
     * Checks the check digits of an EU VAT number.
     *
     * @param string $number VAT number
     *
     * @return bool
     */
    private function EUcheck($number)
    {
        // We know little about EU numbers apart from the fact that the first 3
        // digits represent the country, and that there are nine digits in total.
        return true;
    }

    /**
     * Checks the check digits of a Finnish VAT number.
     *
     * @param string $number VAT number
     *
     * @return bool
     */
    private function FIcheck($number)
    {
        // Checks the check digits of a Finnish VAT number.

        $total = 0;
        $multipliers = [7, 9, 10, 5, 8, 4, 2];

        // Extract the next digit and multiply by the counter.
        for ($i = 0; $i < 7; ++$i) {
            $total += (int) $number[$i] * $multipliers[$i];
        }

        // Establish check digit.
        $total = 11 - $total % 11;
        if (9 < $total) {
            $total = 0;
        }

        // Compare it with the last character of the VAT number. If it's the same, then it's valid.
        return (int) $number[7] === $total;
    }

    /**
     * Checks the check digits of a French VAT number.
     *
     * @param string $number VAT number
     *
     * @return bool
     */
    private function FRcheck($number)
    {
        // Valid for 32-bit systems
        if (4 === PHP_INT_SIZE) {
            return true;
        }

        if (0 === preg_match('/^\d{11}$/', $number)) {
            return true;
        }

        // Extract the last nine digits as an integer.
        $total = (int) substr($number, -9);

        // Establish check digit.
        $total = (100 * $total + 12) % 97;

        // Compare it with the last character of the VAT number. If it's the same, then it's valid.
        return (int) substr($number, 0, 2) === $total;
    }

    /**
     * Checks the check digits of a British VAT number.
     *
     * @param string $number VAT number
     *
     * @return bool
     */
    private function GBcheck($number)
    {
        $multipliers = [8, 7, 6, 5, 4, 3, 2];

        // Government departments
        if ('GD' === substr($number, 0, 2)) {
            return 500 > (int) substr($number, 2, 5);
        }

        // Health authorities
        if ('HA' === substr($number, 0, 2)) {
            return 499 < (int) substr($number, 2, 5);
        }

        // Standard and commercial numbers
        $total = 0;

        // 0 VAT numbers disallowed!
        if (0 === (int) $number) {
            return false;
        }

        // Check range is OK for modulus 97 calculation
        $no = (int) substr($number, 0, 7);

        // Extract the next digit and multiply by the counter.
        for ($i = 0; $i < 7; ++$i) {
            $total += (int) $number[$i] * $multipliers[$i];
        }

        // Old numbers use a simple 97 modulus, but new numbers use an adaptation of that (less 55). Our
        // VAT number could use either system, so we check it against both.

        // Establish check digits by subtracting 97 from total until negative.
        $cd = $total;
        while ($cd > 0) {
            $cd = $cd - 97;
        }

        // Get the absolute value and compare it with the last two characters of the VAT number. If the
        // same, then it is a valid traditional check digit. However, even then the number must fit within
        // certain specified ranges.
        $cd = abs($cd);
        if ((int) substr($number, 7, 2) === $cd &&
            $no < 9990001 &&
            ($no < 100000 || $no > 999999) &&
            ($no < 9490001 || $no > 9700000)
        ) {
            return true;
        }

        // Now try the new method by subtracting 55 from the check digit if we can - else add 42
        if ($cd >= 55) {
            $cd = $cd - 55;
        } else {
            $cd = $cd + 42;
        }

        return (int) substr($number, 7, 2) === $cd && $no > 1000000;
    }

    /**
     * Checks the check digits of a Croatian VAT number using ISO 7064,
     * MOD 11-10 for check digit.
     *
     * @param string $number VAT number
     *
     * @return bool
     */
    private function HRcheck($number)
    {
        $product = 10;
        $sum = 0;
        $checkDigit = 0;

        for ($i = 0; $i < 10; ++$i) {
            // Extract the next digit and implement the algorithm
            $sum = ((int) $number[$i] + $product) % 10;
            if (0 === $sum) {
                $sum = 10;
            }

            $product = (2 * $sum) % 11;
        }

        // Now check that we have the right check digit
        return 1 === ($product + (int) $number[10]) % 10;
    }

    /**
     * Checks the check digits of a Hungarian VAT number.
     *
     * @param string $number VAT number
     *
     * @return bool
     */
    private function HUcheck($number)
    {
        $total = 0;
        $multipliers = [9, 7, 3, 1, 9, 7, 3];

        // Extract the next digit and multiply by the counter
        for ($i = 0; $i < 7; ++$i) {
            $total += (int) $number[$i] * $multipliers[$i];
        }

        // Establish check digit
        $total = 10 - ($total % 10);
        if (10 === $total) {
            $total = 0;
        }

        // Compare it with the last character of the VAT number. If it's the
        // same, then it's valid
        return $total === (int) $number[7];
    }

    /**
     * Checks the check digits of an Irish VAT number.
     *
     * @param string $number VAT number
     *
     * @return bool
     */
    private function IEcheck($number)
    {
        $total = 0;
        $multipliers = [8, 7, 6, 5, 4, 3, 2];

        // If the code is type 1 format, we need to convert it to the new before performing the validation.
        if (0 !== preg_match('/^\d[A-Z\*\+]/', $number)) {
            $number = '0'.substr($number, 2, 5).substr($number, 0, 1).substr($number, 7, 1);
        }

        // Extract the next digit and multiply by the counter.
        for ($i = 0; $i < 7; ++$i) {
            $total += (int) $number[$i] * $multipliers[$i];
        }

        // If the number is type 3 then we need to include the trailing A or H in the calculation
        if (0 !== preg_match('/^\d{7}[A-Z][AH]$/', $number)) {
            // Add in a multiplier for the character A (1*9=9) or H (8*9=72)
            if ('H' === $number[8]) {
                $total += 72;
            } else {
                $total += 9;
            }
        }

        // Establish check digit using modulus 23, and translate to char. equivalent.
        $total = $total % 23;
        if (0 === $total) {
            $total = 'W';
        } else {
            $total = chr($total + 64);
        }

        // Compare it with the eighth character of the VAT number. If it's the same, then it's valid.
        return $number[7] === $total;
    }

    /**
     * Checks the check digits of an Italian VAT number.
     *
     * @param string $number VAT number
     *
     * @return bool
     */
    private function ITcheck($number)
    {
        $total = 0;
        $multipliers = [1, 2, 1, 2, 1, 2, 1, 2, 1, 2];

        // The last three digits are the issuing office, and cannot exceed more 201, unless 999 or 888
        if ('0000000' === substr($number, 0, 7)) {
            return false;
        }

        $temp = (int) substr($number, 7, 3);
        if (($temp < 1) || ($temp > 201) && $temp !== 999 && $temp !== 888) {
            return false;
        }

        // Extract the next digit and multiply by the appropriate
        for ($i = 0; $i < 10; ++$i) {
            $temp = (int) $number[$i] * $multipliers[$i];
            if ($temp > 9) {
                $total += floor($temp / 10) + $temp % 10;
            } else {
                $total += $temp;
            }
        }

        // Establish check digit.
        $total = 10 - $total % 10;
        if ($total > 9) {
            $total = 0;
        }

        // Compare it with the last character of the VAT number. If it's the same, then it's valid.
        return (int) $number[10] === $total;
    }

    /**
     * Checks the check digits of a Lithuanian VAT number.
     *
     * @param string $number VAT number
     *
     * @return bool
     */
    private function LTcheck($number)
    {
        // 9 character VAT numbers are for legal persons
        if (9 === strlen($number)) {
            // 8th character must be one
            if ('1' !== $number[7]) {
                return false;
            }

            // Extract the next digit and multiply by the counter+1.
            $total = 0;
            for ($i = 0; $i < 8; ++$i) {
                $total += (int) $number[$i] * ($i + 1);
            }

            // Can have a double check digit calculation!
            if (10 === $total % 11) {
                $multipliers = [3, 4, 5, 6, 7, 8, 9, 1];
                $total = 0;
                for ($i = 0; $i < 8; ++$i) {
                    $total += (int) $number[$i] * $multipliers[$i];
                }
            }

            // Establish check digit.
            $total = $total % 11;
            if (10 === $total) {
                $total = 0;
            }

            // Compare it with the last character of the VAT number. If it's the same, then it's valid.
            return (int) $number[8] === $total;
        }

        // 12 character VAT numbers are for temporarily registered taxpayers
        // 11th character must be one
        if ('1' !== $number[10]) {
            return false;
        }

        // Extract the next digit and multiply by the counter+1.
        $total = 0;
        $multipliers = [1, 2, 3, 4, 5, 6, 7, 8, 9, 1, 2];
        for ($i = 0; $i < 11; ++$i) {
            $total += (int) $number[$i] * $multipliers[$i];
        }

        // Can have a double check digit calculation!
        if (10 === $total % 11) {
            $multipliers = [3, 4, 5, 6, 7, 8, 9, 1, 2, 3, 4];
            $total = 0;
            for ($i = 0; $i < 11; ++$i) {
                $total += (int) $number[$i] * $multipliers[$i];
            }
        }

        // Establish check digit.
        $total = $total % 11;
        if (10 === $total) {
            $total = 0;
        }

        // Compare it with the last character of the VAT number. If it's the same, then it's valid.
        return (int) $number[11] === $total;
    }

    /**
     * Checks the check digits of a Luxembourgish VAT number.
     *
     * @param string $number VAT number
     *
     * @return bool
     */
    private function LUcheck($number)
    {
        return (int) substr($number, 0, 6) % 89 === (int) substr($number, 6, 2);
    }

    /**
     * Checks the check digits of a Latvian VAT number.
     *
     * @param string $number VAT number
     *
     * @return bool
     */
    private function LVcheck($number)
    {
        // Differentiate between legal entities and natural bodies. For the
        // latter we simply check that the first six digits correspond to
        // valid DDMMYY dates.
        if (0 !== preg_match('/^[0-3]/', $number)) {
            return 0 !== preg_match('/^[0-3][0-9][0-1][0-9]/', $number);
        }

        $total = 0;
        $multipliers = [9, 1, 4, 8, 3, 10, 2, 5, 7, 6];

        // Extract the next digit and multiply by the counter.
        for ($i = 0; $i < 10; ++$i) {
            $total += (int) $number[$i] * $multipliers[$i];
        }

        // Establish check digits by getting modulus 11.
        if (4 === $total % 11 && '9' === $number[0]) {
            $total = total - 45;
        }
        if (4 === $total % 11) {
            $total = 4 - $total % 11;
        } elseif (4 < $total % 11) {
            $total = 14 - $total % 11;
        } elseif (4 > $total % 11) {
            $total = 3 - $total % 11;
        }

        // Compare it with the last character of the VAT number. If it's the same, then it's valid.
        return (int) $number[10] === $total;
    }

    /**
     * Checks the check digits of a Maltese VAT number.
     *
     * @param string $number VAT number
     *
     * @return bool
     */
    private function MTcheck($number)
    {
        $total = 0;
        $multipliers = [3, 4, 6, 7, 8, 9];

        // Extract the next digit and multiply by the counter.
        for ($i = 0; $i < 6; ++$i) {
            $total += (int) $number[$i] * $multipliers[$i];
        }

        // Establish check digits by getting modulus 37.
        $total = 37 - $total % 37;

        // Compare it with the last character of the VAT number. If it's the same, then it's valid.
        return (int) substr($number, 6, 2) === $total;
    }

    /**
     * Checks the check digits of a Dutch VAT number.
     *
     * @param string $number VAT number
     *
     * @return bool
     */
    private function NLcheck($number)
    {
        $total = 0;
        $multipliers = [9, 8, 7, 6, 5, 4, 3, 2];

        // Extract the next digit and multiply by the counter.
        for ($i = 0; $i < 8; ++$i) {
            $total += (int) $number[$i] * $multipliers[$i];
        }

        // Establish check digits by getting modulus 11.
        $total = $total % 11;
        if (9 < $total) {
            $total = 0;
        }

        // Compare it with the last character of the VAT number. If it's the same, then it's valid.
        return (int) $number[8] === $total;
    }

    /**
     * Checks the check digits of a Norwegian VAT number.
     *
     * @see http://www.brreg.no/english/coordination/number.html
     *
     * @param string $number VAT number
     *
     * @return bool
     */
    private function NOcheck($number)
    {
        $total = 0;
        $multipliers = [3, 2, 7, 6, 5, 4, 3, 2];

        // Extract the next digit and multiply by the counter.
        for ($i = 0; $i < 8; ++$i) {
            $total += (int) $number[$i] * $multipliers[$i];
        }

        // Establish check digits by getting modulus 11. Check digits > 9 are invalid
        $total = 11 - $total % 11;
        if (11 === $total) {
            $total = 0;
        }

        // Compare it with the last character of the VAT number. If it's the same, then it's valid.
        return (int) $number[8] === $total;
    }

    /**
     * Checks the check digits of a Polish VAT number.
     *
     * @param string $number VAT number
     *
     * @return bool
     */
    private function PLcheck($number)
    {
        $total = 0;
        $multipliers = [6, 5, 7, 2, 3, 4, 5, 6, 7];

        // Extract the next digit and multiply by the counter.
        for ($i = 0; $i < 9; ++$i) {
            $total += (int) $number[$i] * $multipliers[$i];
        }

        // Establish check digits subtracting modulus 11 from 11.
        $total = $total % 11;
        if (9 < $total) {
            $total = 0;
        }

        // Compare it with the last character of the VAT number. If it's the same, then it's valid.
        return (int) $number[9] === $total;
    }

    /**
     * Checks the check digits of a Portugese VAT number.
     *
     * @param string $number VAT number
     *
     * @return bool
     */
    private function PTcheck($number)
    {
        $total = 0;
        $multipliers = [9, 8, 7, 6, 5, 4, 3, 2];

        // Extract the next digit and multiply by the counter.
        for ($i = 0; $i < 8; ++$i) {
            $total += (int) $number[$i] * $multipliers[$i];
        }

        // Establish check digits subtracting modulus 11 from 11.
        $total = 11 - $total % 11;
        if (9 < $total) {
            $total = 0;
        }

        // Compare it with the last character of the VAT number. If it's the same, then it's valid.
        return (int) $number[8] === $total;
    }

    /**
     * Checks the check digits of a Romanian VAT number.
     *
     * @param string $number VAT number
     *
     * @return bool
     */
    private function ROcheck($number)
    {
        $multipliers = [7, 5, 3, 2, 1, 7, 5, 3, 2];

        // Extract the next digit and multiply by the counter.
        $VATlen = strlen($number);
        $total = 0;
        $multipliers = array_slice($multipliers, 1 - $VATlen);
        for ($i = 0; $i < $VATlen - 1; ++$i) {
            $total += (int) $number[$i] * $multipliers[$i];
        }

        // Establish check digits by getting modulus 11.
        $total = (10 * $total) % 11;
        if (10 === $total) {
            $total = 0;
        }

        // Compare it with the last character of the VAT number. If it's the same, then it's valid.
        return (int) substr($number, -1) === $total;
    }

    /**
     * Checks the check digits of a Serbian VAT number using ISO 7064, MOD 11-10 for check digit.
     *
     * @param string $number VAT number
     *
     * @return bool
     */
    private function RScheck($number)
    {
        $product = 10;
        $sum = 0;

        for ($i = 0; $i < 8; ++$i) {
            // Extract the next digit and implement the algorithm
            $sum = ((int) $number[$i] + $product) % 10;
            if (0 === $sum) {
                $sum = 10;
            }
            $product = (2 * $sum) % 11;
        }

        // Now check that we have the right check digit
        return 1 === ($product + (int) $number[8]) % 10;
    }

    /**
     * Checks the check digits of a Russian INN number.
     *
     * @see http://russianpartner.biz/test_inn.html
     *
     * @param string $number VAT number
     *
     * @return bool
     */
    private function RUcheck($number)
    {
        // 10 digit INN numbers
        if (10 === strlen($number)) {
            $total = 0;
            $multipliers = [2, 4, 10, 3, 5, 9, 4, 6, 8, 0];

            for ($i = 0; $i < 10; ++$i) {
                $total += (int) $number[$i] * $multipliers[$i];
            }

            $total = $total % 11;
            if (9 < $total) {
                $total = $total % 10;
            }

            // Compare it with the last character of the VAT number. If it is the same, then it's valid
            return (int) $number[9] === $total;
        }
        // 12 digit INN numbers
        elseif (12 === strlen($number)) {
            $total1 = 0;
            $multipliers1 = [7, 2, 4, 10, 3, 5, 9, 4, 6, 8, 0];
            $total2 = 0;
            $multipliers2 = [3, 7, 2, 4, 10, 3, 5, 9, 4, 6, 8, 0];

            for ($i = 0; $i < 11; ++$i) {
                $total1 += (int) $number[$i] * $multipliers1[$i];
            }

            $total1 = $total1 % 11;
            if (9 < $total1) {
                $total1 = $total1 % 10;
            }

            for ($i = 0; $i < 11; ++$i) {
                $total2 += (int) $number[$i] * $multipliers2[$i];
            }

            $total2 = $total2 % 11;
            if (9 < $total2) {
                $total2 = $total2 % 10;
            }

            // Compare the first check with the 11th character and the second
            // check with the 12th and last character of the VAT number.
            // If they're both the same, then it's valid
            return (int) $number[10] === $total1 && (int) $number[11] === $total2;
        }

        return false;
    }

    /**
     * Checks the check digits of a Swedish VAT number.
     *
     * @param string $number VAT number
     *
     * @return bool
     */
    private function SEcheck($number)
    {
        // Calculate R where R = R1 + R3 + R5 + R7 + R9, and Ri = INT(Ci/5) + (Ci*2) modulo 10
        $R = 0;
        for ($i = 0; $i < 9; $i = $i + 2) {
            $digit = (int) $number[$i];
            $R += floor($digit / 5) + ((2 * $digit) % 10);
        }

        // Calculate S where S = C2 + C4 + C6 + C8
        $S = 0;
        for ($i = 1; $i < 9; $i = $i + 2) {
            $S += (int) $number[$i];
        }

        // Calculate the Check Digit
        $cd = (10 - ($R + $S) % 10) % 10;

        // Compare it with the last character of the VAT number. If it's the same, then it's valid.
        return (int) $number[9] === $cd;
    }

    /**
     * Checks the check digits of a Slovenian VAT number.
     *
     * @param string $number VAT number
     *
     * @return bool
     */
    private function SIcheck($number)
    {
        $total = 0;
        $multipliers = [8, 7, 6, 5, 4, 3, 2];

        // Extract the next digit and multiply by the counter.
        for ($i = 0; $i < 7; ++$i) {
            $total += (int) $number[$i] * $multipliers[$i];
        }

        // Establish check digits using modulus 11
        $total = 11 - $total % 11;
        if (10 === $total) {
            $total = 0;
        }

        // Compare the number with the last character of the VAT number. If it is the
        // same, then it's a valid check digit.
        return 11 !== $total && (int) $number[7] === $total;
    }

    /**
     * Checks the check digits of a Slovak VAT number.
     *
     * @param string $number VAT number
     *
     * @return bool
     */
    private function SKcheck($number)
    {
        // Valid for 32-bit systems
        if (4 === PHP_INT_SIZE) {
            return true;
        }

        // Check that the modulus of the whole VAT number is 0 - else error
        return 0 === (int) $number % 11;
    }
}
