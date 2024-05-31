<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Mapping;

use Doctrine\Common\EventManager;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\Persistence\Mapping\Driver\MappingDriverChain;
use Gedmo\Mapping\ExtensionMetadataFactory;
use Gedmo\Tests\Mapping\Fixture\Yaml\Category;
use Gedmo\Tests\Mapping\Fixture\Yaml\ClosureCategory;
use Gedmo\Tests\Mapping\Fixture\Yaml\MaterializedPathCategory;
use Gedmo\Tests\Tree\Fixture\Closure\CategoryClosureWithoutMapping;
use Gedmo\Tree\TreeListener;

/**
 * These are mapping tests for tree extension
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
final class TreeMappingTest extends MappingORMTestCase
{
    private const TEST_YAML_ENTITY_CLASS = Category::class;
    private const YAML_CLOSURE_CATEGORY = ClosureCategory::class;
    private const YAML_MATERIALIZED_PATH_CATEGORY = MaterializedPathCategory::class;

    /**
     * @group legacy
     *
     * @see https://github.com/doctrine/persistence/pull/144
     * @see \Doctrine\Persistence\Mapping\AbstractClassMetadataFactory::getCacheKey()
     */
    public function testApcCached(): void
    {
        $this->em->getClassMetadata(self::YAML_CLOSURE_CATEGORY);
        $this->em->getClassMetadata(CategoryClosureWithoutMapping::class);

        $meta = $this->em->getConfiguration()->getMetadataCache()->getItem(
            'Gedmo__Tests__Tree__Fixture__Closure__CategoryClosureWithoutMapping__CLASSMETADATA__'
        )->get();
        static::assertNotFalse($meta);
        static::assertTrue($meta->hasAssociation('ancestor'));
        static::assertTrue($meta->hasAssociation('descendant'));
    }

    public function testYamlNestedMapping(): void
    {
        $this->em->getClassMetadata(self::TEST_YAML_ENTITY_CLASS);
        $cacheId = ExtensionMetadataFactory::getCacheId(
            self::TEST_YAML_ENTITY_CLASS,
            'Gedmo\Tree'
        );
        $config = $this->metadataCache->getItem($cacheId)->get();
        static::assertArrayHasKey('left', $config);
        static::assertSame('left', $config['left']);
        static::assertArrayHasKey('right', $config);
        static::assertSame('right', $config['right']);
        static::assertArrayHasKey('parent', $config);
        static::assertSame('parent', $config['parent']);
        static::assertArrayHasKey('level', $config);
        static::assertSame('level', $config['level']);
        static::assertArrayHasKey('root', $config);
        static::assertSame('rooted', $config['root']);
        static::assertArrayHasKey('strategy', $config);
        static::assertSame('nested', $config['strategy']);
    }

    /**
     * @group legacy
     */
    public function testYamlClosureMapping(): void
    {
        // Force metadata class loading.
        $this->em->getClassMetadata(self::YAML_CLOSURE_CATEGORY);
        $cacheId = ExtensionMetadataFactory::getCacheId(self::YAML_CLOSURE_CATEGORY, 'Gedmo\Tree');
        $config = $this->metadataCache->getItem($cacheId)->get();

        static::assertArrayHasKey('parent', $config);
        static::assertSame('parent', $config['parent']);
        static::assertArrayHasKey('strategy', $config);
        static::assertSame('closure', $config['strategy']);
        static::assertArrayHasKey('closure', $config);
        static::assertSame(CategoryClosureWithoutMapping::class, $config['closure']);
    }

    public function testYamlMaterializedPathMapping(): void
    {
        $this->em->getClassMetadata(self::YAML_MATERIALIZED_PATH_CATEGORY);
        $cacheId = ExtensionMetadataFactory::getCacheId(self::YAML_MATERIALIZED_PATH_CATEGORY, 'Gedmo\Tree');
        $config = $this->metadataCache->getItem($cacheId)->get();

        static::assertArrayHasKey('strategy', $config);
        static::assertSame('materializedPath', $config['strategy']);
        static::assertArrayHasKey('parent', $config);
        static::assertSame('parent', $config['parent']);
        static::assertArrayHasKey('activate_locking', $config);
        static::assertTrue($config['activate_locking']);
        static::assertArrayHasKey('locking_timeout', $config);
        static::assertSame(3, $config['locking_timeout']);
        static::assertArrayHasKey('level', $config);
        static::assertSame('level', $config['level']);
        static::assertArrayHasKey('path', $config);
        static::assertSame('path', $config['path']);
        static::assertArrayHasKey('path_separator', $config);
        static::assertSame(',', $config['path_separator']);
    }

    protected function addMetadataDriversToChain(MappingDriverChain $driver): void
    {
        parent::addMetadataDriversToChain($driver);

        if (PHP_VERSION_ID >= 80000) {
            $annotationOrAttributeDriver = $this->createAttributeDriver();
        } elseif (class_exists(AnnotationDriver::class)) {
            $annotationOrAttributeDriver = $this->createAnnotationDriver();
        } else {
            static::markTestSkipped('Test requires PHP 8 or doctrine/orm with annotations support.');
        }

        $driver->addDriver($annotationOrAttributeDriver, 'Gedmo\Tests\Tree\Fixture');
        $driver->addDriver($annotationOrAttributeDriver, 'Gedmo\Tree');
    }

    protected function modifyEventManager(EventManager $evm): void
    {
        $listener = new TreeListener();
        $listener->setCacheItemPool($this->metadataCache);

        $evm->addEventSubscriber($listener);
    }
}
