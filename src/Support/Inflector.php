<?php
declare(strict_types=1);

namespace Nexendrie\RestRoute\Support;

class Inflector
{
    /**
     * Converts the given string to `StudlyCase`
     */
    public static function studlyCase(string $string): string
    {
        $string = mb_convert_case(preg_replace(['/-/', '/_/'], ' ', $string), MB_CASE_TITLE, 'UTF-8');
        return preg_replace('/ /', '', $string);
    }

    /**
     * Converts the given string to `spinal-case`
     */
    public static function spinalCase(string $string): string
    {
        /** RegExp source http://stackoverflow.com/a/1993772 */
        preg_match_all('/([A-Z][A-Z0-9]*(?=$|[A-Z][a-z0-9])|[A-Za-z][a-z0-9]+)/', $string, $matches);
        $matches = $matches[0];
        foreach ($matches as &$match) {
            $match = ($match == mb_strtoupper($match, 'UTF-8')) ?
                mb_strtolower($match, 'UTF-8') :
                mb_lcfirst($match, 'UTF-8');
        }
        return implode('-', $matches);
    }
}
