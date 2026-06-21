<?php
declare(strict_types=1);

namespace Nexendrie\RestRoute;

if (false) { // @phpstan-ignore if.alwaysFalse
    class InvalidArgumentException extends \Nette\InvalidArgumentException
    {
    }
} else {
    class_alias(\Nette\InvalidArgumentException::class, InvalidArgumentException::class);
}
