<?php

declare(strict_types=1);

namespace IgniterLabs\ImportExport\Tests;

use Igniter\Flame\ServiceProvider;
use IgniterLabs\ImportExport\Extension;

abstract class TestCase extends \Orchestra\Testbench\TestCase
{
    protected function getPackageProviders($app)
    {
        return [
            ServiceProvider::class,
            Extension::class,
        ];
    }
}
