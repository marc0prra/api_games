<?php

namespace App\DataFixtures;

use App\Entity\Category;
use App\Entity\Editor;
use App\Entity\VideoGame;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class VideoGamesFixtures extends Fixture implements DependentFixtureInterface, FixtureGroupInterface
{
    // title, releaseDate, n° éditeur, n° catégories, jaquette
    private const GAMES = [
        ['The Legend of Zelda: Tears of the Kingdom', '2023-05-12', 1, [2, 6], 'https://placehold.co/300x400?text=Zelda'],
        ['Super Mario Odyssey', '2017-10-27', 1, [5, 2], 'https://placehold.co/300x400?text=Super+Mario'],
        ['Mario Kart 8 Deluxe', '2017-04-28', 1, [7], 'https://placehold.co/300x400?text=Mario+Kart'],
        ["Assassin's Creed Valhalla", '2020-11-10', 2, [1, 3, 6], 'https://placehold.co/300x400?text=AC+Valhalla'],
        ['Rayman Legends', '2013-08-29', 2, [5], 'https://placehold.co/300x400?text=Rayman'],
        ['God of War Ragnarök', '2022-11-09', 3, [1, 2], 'https://placehold.co/300x400?text=God+of+War'],
        ['Horizon Forbidden West', '2022-02-18', 3, [1, 6], 'https://placehold.co/300x400?text=Horizon'],
        ['EA Sports FC 24', '2023-09-29', 4, [4], 'https://placehold.co/300x400?text=FC+24'],
        ['The Witcher 3: Wild Hunt', '2015-05-19', 5, [3, 6], 'https://placehold.co/300x400?text=Witcher+3'],
        ['Cyberpunk 2077', '2020-12-10', 5, [1, 3, 6], 'https://placehold.co/300x400?text=Cyberpunk'],

        // Dates relatives (recalculées à chaque chargement) pour la newsletter des 7 prochains jours
        ['Metroid Prime 4: Beyond', '+3 days', 1, [1, 2], 'https://placehold.co/300x400?text=Metroid+Prime+4'],
        ['Splinter Cell Remake', '+6 days', 2, [1, 2], 'https://placehold.co/300x400?text=Splinter+Cell'],
        ['The Witcher 4', '+15 days', 5, [3, 6], 'https://placehold.co/300x400?text=Witcher+4'], // hors des 7 jours : absent du mail
    ];

    public static function getGroups(): array
    {
        return ['videoGames'];
    }

    public function getDependencies(): array
    {
        return [
            EditorFixtures::class,
            CategoryFixtures::class,
        ];
    }

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');

        foreach (self::GAMES as [$title, $releaseDate, $editorNumber, $categoryNumbers, $coverImage]) {
            $videoGame = new VideoGame();
            $videoGame->setTitle($title);
            $videoGame->setReleaseDate(new \DateTimeImmutable($releaseDate));
            $videoGame->setDescription($faker->paragraph(3));
            $videoGame->setCoverImage($coverImage);

            // ManyToOne → Editor
            $editorRef = EditorFixtures::EDITOR_REF . $editorNumber;
            $videoGame->setEditor($this->getReference($editorRef, Editor::class));

            // ManyToMany → Categories
            foreach ($categoryNumbers as $categoryNumber) {
                $categoryRef = CategoryFixtures::CATEGORY_REF . $categoryNumber;
                $videoGame->addCategory($this->getReference($categoryRef, Category::class));
            }

            $manager->persist($videoGame);
        }

        $manager->flush();
    }
}
