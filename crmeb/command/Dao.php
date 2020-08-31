<?php

namespace crmeb\command;

use think\console\command\Make;

/**
 * Class Business
 * @package crmeb\command
 */
class Dao extends Make
{
    protected $type = "Dao";

    protected function configure()
    {
        parent::configure();
        $this->setName('make:dao')
            ->setDescription('Create a new service class');
    }

    protected function getStub(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR. 'stubs' . DIRECTORY_SEPARATOR . 'dao.stub';
    }

    protected function getNamespace(string $app): string
    {
        return parent::getNamespace($app) . '\\dao';
    }

    protected function getPathName(string $name): string
    {
        $name = str_replace('app\\', '', $name);

        return $this->app->getBasePath() . ltrim(str_replace('\\', '/', $name), '/') . 'Dao.php';
    }
}