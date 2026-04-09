<?php

declare(strict_types=1);

use Shopware\Core\TestBootstrapper;

$_SERVER['test.service_container'] = 'true';

$loader = (new TestBootstrapper())
    ->addCallingPlugin()
    ->addActivePlugins('WbmProductType')
    ->setForceInstallPlugins(true)
    ->bootstrap()
    ->getClassLoader();

$loader->addPsr4('WbmProductType\\Tests\\', __DIR__);
$loader->addPsr4('WbmProductType\\', __DIR__ . '/../src');
