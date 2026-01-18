<?php

use Bitrix\Main\Loader;

Loader::registerAutoLoadClasses(null, [
    'Otus\\Homework3\\AbstractPage' => '/local/php_interface/src/Otus/Homework3/AbstractPage.php',
    'Otus\\Homework3\\DoctorsPage'  => '/local/php_interface/src/Otus/Homework3/DoctorsPage.php',
    'Otus\\Homework3\\ProceduresPage' => '/local/php_interface/src/Otus/Homework3/ProceduresPage.php',
    'Local\Model\ORM\CarOfferTable' => '/local/php_interface/lib/Model/ORM/CarOfferTable.php',
    'Local\Model\Iblock\Cars'       => '/local/php_interface/lib/Model/Iblock/Cars.php',
    'Local\Model\Iblock\Dealers'    => '/local/php_interface/lib/Model/Iblock/Dealers.php',
]);