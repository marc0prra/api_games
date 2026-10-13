<?php

namespace App\DataFixtures;

use App\Entity\Editor;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;

class EditorFixtures extends Fixture implements FixtureGroupInterface
{
    public const EDITOR_REF = 'editor_';

    private const EDITORS = [
        1 => ['name' => 'Nintendo', 'country' => 'Japon'],
        2 => ['name' => 'Ubisoft', 'country' => 'France'],
        3 => ['name' => 'Sony Interactive Entertainment', 'country' => 'Japon'],
        4 => ['name' => 'Electronic Arts', 'country' => 'États-Unis'],
        5 => ['name' => 'CD Projekt', 'country' => 'Pologne'],
    ];

    public static function getGroups(): array
    {
        return ['editors'];
    }

    public function load(ObjectManager $manager): void
    {
        foreach (self::EDITORS as $i => $data) {
            $editor = new Editor();
            $editor->setName($data['name']);
            $editor->setCountry($data['country']);

            $manager->persist($editor);

            $this->addReference(self::EDITOR_REF . $i, $editor);
        }

        $manager->flush();
    }
}
