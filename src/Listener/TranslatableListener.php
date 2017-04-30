<?php

namespace Danrot\Doctrine\TranslatableExtension\Listener;

use Danrot\Doctrine\TranslatableExtension\Mapping\Annotations\Translatable;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Events;
use Doctrine\ORM\Tools\Event\GenerateSchemaTableEventArgs;
use Doctrine\ORM\Tools\ToolEvents;

class TranslatableListener implements EventSubscriber
{
    /**
     * @var array
     */
    static private $metadata = [];

    /**
     * @var AnnotationReader
     */
    private $annotationReader;

    public function __construct(AnnotationReader $annotationReader)
    {
        $this->annotationReader = $annotationReader;
    }

    public function getSubscribedEvents()
    {
        return [
            Events::loadClassMetadata,
            Events::postPersist,
            ToolEvents::postGenerateSchemaTable,
        ];
    }

    /**
     * Removes the field mappings being translatable from the original table and remembers to add them to the
     * translation table later.
     */
    public function loadClassMetadata(LoadClassMetadataEventArgs $event)
    {
        $classMetadata = $event->getClassMetadata();
        $class = $classMetadata->getReflectionClass();

        foreach ($class->getProperties() as $property) {
            if ($this->annotationReader->getPropertyAnnotation($property, Translatable::class)) {
                unset($classMetadata->fieldMappings[$property->getName()]);

                if (!isset(self::$metadata[$class->getName()])) {
                    self::$metadata[$class->getName()] = [];
                    self::$metadata[$class->getName()]['fields'] = [];
                }

                self::$metadata[$class->getName()]['fields'][] = $property->getName();
            }
        }
    }

    /**
     * Adds the translation tables for the affected entities.
     */
    public function postGenerateSchemaTable(GenerateSchemaTableEventArgs $event)
    {
        $translationTableName = $event->getClassTable()->getName() . '_translation'; // TODO Do not use hardcoded suffix
        $translationTable = $event->getSchema()->createTable($translationTableName);

        $translationTable->addColumn('id', 'integer');
        $translationTable->setPrimaryKey(['id']);

        $translationTable->addColumn('locale', 'string');
    }
}
