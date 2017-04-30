<?php

namespace Danrot\Doctrine\TranslatableExtension\Tests\Functional;

use Danrot\Doctrine\TranslatableExtension\Tests\Functional\Fixtures\Page;
use PHPUnit\DbUnit\DataSet\ArrayDataSet;

class TranslatableTest extends AbstractDatabaseTestCase
{
    public function testPersistSingleLanguage()
    {
        $page = new Page();
        $page->setTitle('English page');
        $page->setDescription('This is the description for the English page');
        $page->setLocale('en');
        $page->setCreated(new \DateTime('2017-03-31'));

        self::$entityManager->persist($page);
        self::$entityManager->flush();

        $expectedDataSet = new ArrayDataSet([
            'Page' => [
                [
                    'id' => 1,
                    'created' => '2017-03-31 00:00:00'
                ],
            ],
            'Page_translation' => [
                [
                    'id' => 1,
                    'page_id' => 1,
                    'locale' => 'en',
                    'title' => 'English page',
                    'description' => 'This is the description for the English page'
                ],
            ],
        ]);

        $this->assertTablesEqual(
            $expectedDataSet->getTable('Page'),
            $this->getConnection()->createQueryTable('Page', 'SELECT * FROM Page')
        );

        $this->assertTablesEqual(
            $expectedDataSet->getTable('Page_translation'),
            $this->getConnection()->createQueryTable('Page_translation', 'SELECT * FROM Page_translation')
        );
    }
}
