<?php
declare(strict_types=1);

namespace Nexendrie\RestRoute;

if (false) { // @phpstan-ignore if.alwaysFalse
    class InvalidStateException extends \Nette\InvalidStateException
    {
    }
} else {
    class_alias(\Nette\InvalidStateException::class, InvalidStateException::class);
}
