<?php
declare(strict_types=1);

namespace AdamStipak\Support;

use Nette\Utils\Strings;

class Inflector
{
    /**
     * Converts the given string to `StudlyCase`
     */
    public static function studlyCase(string $string): string
    {
        $string = Strings::capitalize(Strings::replace($string, ['/-/', '/_/'], ' '));
        return Strings::replace($string, '/ /');
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
            $match = ($match == Strings::upper($match)) ? Strings::lower($match) : Strings::firstLower($match);
        }
        return implode('-', $matches);
    }
}
