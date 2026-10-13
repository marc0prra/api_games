<?php

namespace App\DataFixtures;

use App\Entity\Category;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;

class CategoryFixtures extends Fixture implements FixtureGroupInterface
{
    public const CATEGORY_REF = 'category_';

    private const CATEGORIES = [
        1 => 'Action',
        2 => 'Aventure',
        3 => 'RPG',
        4 => 'Sport',
        5 => 'Plateforme',
        6 => 'Monde ouvert',
        7 => 'Course',
    ];

    public static function getGroups(): array
    {
        return ['categories'];
    }

    public function load(ObjectManager $manager): void
    {
        foreach (self::CATEGORIES as $i => $name) {
            $category = new Category();
            $category->setName($name);

            $manager->persist($category);

            $this->addReference(self::CATEGORY_REF . $i, $category);
        }

        $manager->flush();
    }
}
