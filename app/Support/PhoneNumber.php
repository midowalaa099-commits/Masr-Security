<?php

namespace App\Support;

class PhoneNumber
{
    public static function normalize(string $input): ?string
    {
        $input = strtr($input, [
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
        ]);
        $number = preg_replace('/[\s()\-]/u', '', $input);

        if ($number === null) {
            return null;
        }

        if (str_starts_with($number, '+20')) {
            $number = '0'.substr($number, 3);
        } elseif (str_starts_with($number, '20')) {
            $number = '0'.substr($number, 2);
        }

        return preg_match('/^01[0125][0-9]{8}$/', $number) ? $number : null;
    }
}
