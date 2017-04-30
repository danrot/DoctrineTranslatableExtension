<?php

namespace Danrot\Doctrine\TranslatableExtension\Tests\Functional;

use Danrot\Doctrine\TranslatableExtension\Listener\TranslatableListener;
use Danrot\Doctrine\TranslatableExtension\Tests\Functional\Fixtures\Page;
use Danrot\Doctrine\TranslatableExtension\Mapping\Driver\AnnotationDriver as TranslatableAnnotationDriver;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\EventManager;
use Doctrine\Common\Persistence\Mapping\Driver\MappingDriverChain;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\Tools\SchemaTool;
use PHPUnit\DbUnit\Database\Connection;
use PHPUnit\DbUnit\DataSet\ArrayDataSet;
use PHPUnit\DbUnit\TestCaseTrait;
use PHPUnit\Framework\TestCase;

abstract class AbstractDatabaseTestCase extends TestCase
{
    use TestCaseTrait;

    /**
     * @var PDO
     */
    private static $pdo = null;

    /**
     * @var Configuration
     */
    private static $configuration = null;

    /**
     * @var Connection
     */
    private static $connection = null;

    /**
     * @var TranslatbleListener
     */
    protected static $translatableListener;

    /**
     * @var EventManager
     */
    private static $eventManager;

    /**
     * @var EntityManager
     */
    protected static $entityManager;

    public function tearDown()
    {
        parent::tearDown();
        self::$entityManager->clear();
    }

    final public function getConnection()
    {
        if (null === self::$connection) {
            if (null === self::$pdo) {
                self::$pdo = new \PDO('sqlite::memory:');
            }
            self::$connection = $this->createDefaultDBConnection(self::$pdo, ':memory:');

            if (null === self::$configuration) {
                $annotationReader = new AnnotationReader();
                $driverChain = new MappingDriverChain();
                $driverChain->addDriver(new AnnotationDriver($annotationReader), 'Danrot');
                $driverChain->addDriver(new TranslatableAnnotationDriver($annotationReader), 'Danrot');
                self::$configuration = new Configuration();
                self::$configuration->setProxyDir(sys_get_temp_dir());
                self::$configuration->setProxyNamespace('Proxy');
                self::$configuration->setMetadataDriverImpl($driverChain);

                static::$translatableListener = new TranslatableListener($annotationReader);
                self::$eventManager = new EventManager();
                self::$eventManager->addEventSubscriber(static::$translatableListener);

                self::$entityManager = EntityManager::create(
                    DriverManager::getConnection(['pdo' => self::$pdo], null, self::$eventManager),
                    self::$configuration,
                    self::$eventManager
                );

                $schemaTool = new SchemaTool(self::$entityManager);
                $schemaTool->dropSchema([]);
                $schemaTool->createSchema([
                    self::$entityManager->getClassMetadata(Page::class),
                ]);
            }
        }

        return self::$connection;
    }

    final public function getDataSet()
    {
        return new ArrayDataSet([
            'Page' => []
        ]);
    }
}
