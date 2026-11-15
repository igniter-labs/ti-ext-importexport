<?php

declare(strict_types=1);

// Same columns as the location import, so exported files can be imported again as-is
return [
    'columns' => (require __DIR__.'/locationimport.php')['columns'],
];
