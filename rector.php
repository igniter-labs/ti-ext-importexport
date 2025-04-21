<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\TypeDeclaration\Rector\ClassMethod\ReturnNeverTypeRector;
use Rector\TypeDeclaration\Rector\StmtsAwareInterface\DeclareStrictTypesRector;

return RectorConfig::configure()
    ->withPaths([
        __DIR__.'/resources',
        __DIR__.'/src',
        __DIR__.'/tests',
    ])
    ->withImportNames(removeUnusedImports: true)
    ->withRules([
        DeclareStrictTypesRector::class,
    ])
    ->withSkip([
        ReturnNeverTypeRector::class,
    ])
    ->withPhpSets(php83: true)
    ->withTypeCoverageLevel(0)
    ->withDeadCodeLevel(0)
    ->withCodeQualityLevel(0);
