# DoctrineTranslationExtension

Create a [Doctrine entity][1] with translatable fields without struggling about mapping these properties to the
database. This extension focuses on a good developer experience while still being as performant as possible.

## Installation

The package can be installed using [Composer][2]:

```bash
composer require danrot/doctrine-translation-extension
```

## Usage

### Configuration

An instance of the `TranslatableListener` has to be created. This listener will also define the default locale for the
translations. The listener has to be passed to the `EntityManager` when create along with the `EventManager` of
Doctrine:

```php
$connection = [
    // ...
];

$configuration = new Doctrine\ORM\Configuration();

$eventManager = new Doctrine\Common\EventManager();
$translatableListener = new Danrot\Doctrine\TranslatableExtension\Listener\TranslatableListener();
$eventManager->addEventSubscriber($translatableListener);

$entityManager = new Doctrine\ORM\EntityManager::create($connection, $configuration, $eventManager);
```

### Schema generation

This library offers a `@Translatable` annotation, which is used to mark a property as being available in multiple
languages. The other annotation is `@Locale`, used for the property indicating the locale of the given entity.

```php
<?php

use Danrot\Doctrine\TranslatableExtension\Mapping\Annotations\Locale;
use Danrot\Doctrine\TranslatableExtension\Mapping\Annotations\Translatable;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class Page
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * @Translatable
     * @ORM\Column(type="string")
     */
    private $title;

    /**
     * @Translatable
     * @ORM\Column(type="string")
     */
    private $description;

    /**
     * @ORM\Column(type="datetime")
     */
    private $created;

    /**
     * @Locale
     */
    private $locale;
}
```

Instead of using annotations it is also possible to hook into the XML or YAML metadata of Doctrine.

Based on these annotations Doctrine will create a schema like this:

```sql
CREATE TABLE Page (
    id INTEGER NOT NULL,
    created DATETIME NOT NULL,
    PRIMARY KEY(id)
);
CREATE TABLE Page_translation (
    id INTEGER NOT NULL,
    locale VARCHAR(5) NOT NULL,
    title VARCHAR(255) NOT NULL,
    description VARCHAR(255),
    page_id INTEGER NOT NULL,
    PRIMARY KEY(id)
);
ALTER TABLE PageTranslation ADD CONSTRAINT FK_D29B35C0C4663E4 FOREIGN KEY (page_id) REFERENCES Page (id);
```

All translatable fields of the entity will go into an own table suffixed with `_translation`. This table also contains
a separate `locale` field, which is represented as a `VARCHAR(5)` (this allows to save locales like `de_AT` but also
`de` in an efficient way). At the same time these fields are removed from the original table. There is also a foreign
key relationship between these tables. This way it is possible to load an entire language with a single join statement.

It is also possible to place the `@Translatable` annotation on a foreign key. In that case the foreign key can have
different values based on the localization. Imagine there is a `Category` entity:

```php
<?php

use Danrot\Doctrine\TranslatableExtension\Mapping\Annotations\Locale;
use Danrot\Doctrine\TranslatableExtension\Mapping\Annotations\Translatable;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class Category {
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * @Translatable
     * @ORM\Column(type="string")
     */
    private $title;

    /**
     * @Locale
     */
    private $locale;
}
```

Now a `@ManyToMany` relationship can be added to the `Page` entity:

```php
<?php

use Danrot\Doctrine\TranslatableExtension\Mapping\Annotations\Locale;
use Danrot\Doctrine\TranslatableExtension\Mapping\Annotations\Translatable;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class Page {
    // ...

    /**
     * @ManyToMany(targetEntity="Category")
     * @Translatable
     */
    private $categories;

    /**
     * @Locale
     */
    private $locale;
}
```

This will create a JoinTable with references to the `Category` entity and the translation of the `Page` entity:

```sql
CREATE TABLE Page (
    id INTEGER NOT NULL,
    created DATETIME NOT NULL,
    PRIMARY KEY(id)
);
CREATE TABLE Page_translation (
    id INTEGER NOT NULL,
    locale VARCHAR(5) NOT NULL,
    title VARCHAR(255) NOT NULL,
    description VARCHAR(255),
    page_id INTEGER NOT NULL,
    PRIMARY KEY(id)
);
CREATE TABLE Category (
    id INTEGER NOT NULL,
    PRIMARY KEY(id)
);
CREATE TABLE Category_translation (
    id INTEGER NOT NULL,
    locale VARCHAR(5) NOT NULL,
    title VARCHAR(255) NOT NULL,
    category_id INTEGER NOT NULL,
    PRIMARY KEY(id)
);
CREATE TABLE page_translation_category (
    page_translation_id INTEGER NOT NULL,
    category_id INTEGER NOT NULL,
    PRIMARY KEY(pagetranslation_id, category_id),
    CONSTRAINT FK_PAGE_TRANSLATION FOREIGN KEY (page_translation_id)
        REFERENCES Page_translation (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE,
    CONSTRAINT FK_CATEGORY FOREIGN KEY (category_id)
        REFERENCES Category (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
);
ALTER TABLE PageTranslation ADD CONSTRAINT FK_PAGE FOREIGN KEY (page_id) REFERENCES Page (id);
ALTER TABLE Category_translation ADD CONSTRAINT FK_CATEGORY FOREIGN KEY (category_id) REFERENCES Category (id);
```

### Persisting

Lets assume the previous `Page` entity has setters for all its properties available. Then persisting works the same
way as in Doctrine without the extension. The only difference is that a localization has to be set as well.

```php
$page = new Page();
$page->setTitle('English page');
$page->setDescription('This is the description for the English page');
$page->setLocale('en');
$page->setCreated(new \DateTime('2017-03-31'));
$entityManager->persist($page);
$entityManager->flush();
```

This will trigger two `INSERT` statements, one for the `Page` table and another one for the translation in the
`Page_translation` table:

```sql
INSERT INTO Page(created) VALUES('2017-03-31');
INSERT INTO Page_translation(page_id, locale, title, description)
    VALUES(1, 'en', 'English Page', 'This is the description for the English page');
```

If another translation should be added simply change the desired fields and set a new locale:

```php
$page = $entityManager->find(Page::class, 1);
$page->setLocale('de');
$page->setTitle('Deutsche Seite');
$page->setDescription('Das ist die Beschreibung für die deutsche Seite');
$entityManager->persist($page);
$entityManager->flush();
```

Since only translatable fields have been touched, thish will only create one `INSERT` query for the translation of the
`Page`:

```sql
INSERT INTO Page_translation(page_id, locale, title, description)
    VALUES(1, 'de', 'Deutsche Seite', 'Das ist die Beschreibung für die deutsche Seite');
```

### Querying

A translatable entity can be loaded via the `EntityManager` of Doctrine:

```php
$page = $entityManager->find(Page::class, 1);
```

This will load the entity in the configured default locale. If another locale for the translation should be loaded a
`Query` with a hint has to be used:

```php
$page = $entityManager->createQuery('SELECT p FROM Page p WHERE p.id = :page_id')
    ->setParameter('page_id', 1)
    ->setHint(\Danrot\Doctrine\TranslatableExtension\Listener\TranslatableListener::HINT_LOCALE, 'de')
    ->getResult();
```

## Tests

The `pdo-sqlite` extension from PHP is necessary in order to run the tests. To actually run the tests follow these
instructions:

```bash
$ composer install
$ vendor/bin/phpunit
```

[1]: http://docs.doctrine-project.org/projects/doctrine-orm/en/latest/tutorials/getting-started.html#what-are-entities
[2]: https://getcomposer.org/
