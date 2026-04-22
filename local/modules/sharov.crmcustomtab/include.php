<?php

use Bitrix\Main\Loader;

Loader::registerAutoLoadClasses(
    'sharov.crmcustomtab',
    [
        'Sharov\Crmcustomtab\Crm\Handlers' => 'lib/Crm/Handlers.php',
        'Sharov\Crmcustomtab\Model\BookTable' => 'lib/Model/BookTable.php',
    ]
);