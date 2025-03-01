<?php
/** @noinspection PhpIllegalPsrClassPathInspection */
declare(strict_types=1);

namespace Stub\Issue44;

use Go\ParserReflection\LocatorInterface;

class Locator implements LocatorInterface
{
    /**
     * {@inheritDoc}
     */
    public function locateClass(string $className): bool|string
    {
        if (ltrim($className, '\\') === ClassWithNamespace::class) {
            return __DIR__ . '/ClassWithNamespace.php';
        }

        return false;
    }
}
