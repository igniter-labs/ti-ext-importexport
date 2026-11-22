<?php

declare(strict_types=1);

use Igniter\Flame\Support\Facades\File;
use Igniter\User\Models\User;
use IgniterLabs\ImportExport\Models\History;
use SamPoyigi\Testbench\TestCase;

uses(TestCase::class)
    // Tests write import files straight to the temp folder, which doesn't exist on a fresh install
    ->beforeEach(fn() => File::ensureDirectoryExists(dirname((new History)->getCsvPath())))
    ->in(__DIR__);

function actingAsSuperUser()
{
    return test()->actingAs(User::factory()->superUser()->create(), 'igniter-admin');
}
