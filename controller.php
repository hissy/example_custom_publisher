<?php
namespace Concrete\Package\ExampleCustomPublisher;

use C5J\ExampleCustomPublisher\Block\DesignerContentPublisher;
use Concrete\Core\Package\Package;
use PortlandLabs\Concrete5\MigrationTool\Publisher\Block\Manager;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class Controller extends Package
{
    protected $appVersionRequired = '8.5.2';
    protected $pkgHandle = 'example_custom_publisher';
    protected $pkgVersion = '0.0.1';
    protected $pkgAutoloaderRegistries = [
        'src' => '\C5J\ExampleCustomPublisher',
    ];
    protected $packageDependencies = [
        'migration_tool' => '0.9',
    ];

    public function getPackageName()
    {
        return t('Example Custom Publisher');
    }

    public function getPackageDescription()
    {
        return t('This example package contains an example publisher class to import from Designer Content to Block Developer.');
    }

    public function on_start()
    {
        $app = $this->app;
        /** @var EventDispatcherInterface $director */
        $director = $app->make(EventDispatcherInterface::class);
        $director->addListener('on_before_dispatch', function ($event) use ($app) {
            /** @var Manager $manager */
            $manager = $app->make('migration/manager/publisher/block');
            if (is_object($manager)) {
                // You should pass the block type handle of legacy concrete5
                $manager->extend('dc_sample_block', function () use ($app) {
                    return $app->make(DesignerContentPublisher::class, [
                        'btDCDcSampleBlock', // Table name of legacy block type
                        ['field_2_wysiwyg_content'], // Field names of wysiwyg content
                    ]);
                });
            }
        });
    }
}
