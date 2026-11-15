<?php

declare(strict_types=1);

// Same columns as the customer import, so exported files can be imported again as-is
return [
    'columns' => (require __DIR__.'/customerimport.php')['columns'],
];
