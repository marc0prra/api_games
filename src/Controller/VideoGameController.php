<?php

namespace App\Controller;

use App\Entity\VideoGame;
use App\Repository\VideoGameRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class VideoGameController extends AbstractController
{
    #[Route('/api/v1/game', name: 'video_game')]
    public function getGames(VideoGameRepository $videoGameRepository): JsonResponse
    {
        $gameList = $videoGameRepository->findAll();

        return $this->json($gameList, Response::HTTP_OK, [], ['groups' => 'game:read']);
    }
}
