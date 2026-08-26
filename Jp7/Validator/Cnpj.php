<?php

namespace Jp7\Validator;

class Cnpj
{
    public function validate($attribute, $value, $parameters)
    {
        $cnpj = trim(preg_replace('/[^0-9]/', '', $value));
        $sum = 0;
        $multiplier = 0;
        $product = 0;

        if (empty($cnpj) || strlen($cnpj) != 14) {
            return false;
        }

        for ($i = 0; $i <= 9; $i++) {
            $repeated = str_pad('', 14, $i);
            if ($cnpj === $repeated) {
                return false;
            }
        }

        $part1 = substr($cnpj, 0, 12);
        $part1Reversed = strrev($part1);
        for ($i = 0; $i <= 11; $i++) {
            $multiplier = ($i == 0) || ($i == 8) ? 2 : $multiplier;
            $product = ($part1Reversed[$i] * $multiplier);
            $sum += $product;
            $multiplier++;
        }
        $rest = $sum % 11;
        $dv1 = ($rest == 0 || $rest == 1) ? 0 : 11 - $rest;

        $part1 .= $dv1;
        $part1Reversed = strrev($part1);
        $sum = 0;

        for ($i = 0; $i <= 12; $i++) {
            $multiplier = ($i == 0) || ($i == 8) ? 2 : $multiplier;
            $product = ($part1Reversed[$i] * $multiplier);
            $sum += $product;
            $multiplier++;
        }
        $rest = $sum % 11;
        $dv2 = ($rest == 0 || $rest == 1) ? 0 : 11 - $rest;

        return ($dv1 == $cnpj[12] && $dv2 == $cnpj[13]) ? true : false;
    }
}
