<?php

namespace IgniterLabs\ImportExport\Tests;

abstract class TestCase extends \Orchestra\Testbench\TestCase
{
    protected function getPackageProviders($app)
    {
        return [
            \Igniter\Flame\ServiceProvider::class,
            \IgniterLabs\ImportExport\Extension::class,
        ];
    }
}
