<?php

namespace crmeb\command;

use think\console\command\Make;

/**
 * Class Business
 * @package crmeb\command
 */
class Service extends Make
{
    protected $type = "Dao";

    protected function configure()
    {
        parent::configure();
        $this->setName('make:service')
            ->setDescription('Create a new service class');
    }

    protected function getStub(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR. 'stubs' . DIRECTORY_SEPARATOR . 'service.stub';
    }

    protected function getNamespace(string $app): string
    {
        return parent::getNamespace($app) . '\\services';
    }

    protected function getPathName(string $name): string
    {
        $name = str_replace('app\\', '', $name);

        return $this->app->getBasePath() . ltrim(str_replace('\\', '/', $name), '/') . 'Services.php';
    }
}