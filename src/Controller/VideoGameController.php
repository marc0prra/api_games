<?php

namespace App\Controller;

use App\Entity\VideoGame;
use App\Repository\CategoryRepository;
use App\Repository\EditorRepository;
use App\Repository\VideoGameRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class VideoGameController extends AbstractController
{
    #[Route('/api/v1/games', name: 'games', methods: ['GET'])]
    public function getGames(VideoGameRepository $videoGameRepository, Request $request): JsonResponse
    {
        $page = max(1, $request->query->getInt('page', 1));
        $limit = max(1, $request->query->getInt('limit', 3));

        $gameList = $videoGameRepository->findVideoGameWithPagination($page, $limit);

        return $this->json($gameList, Response::HTTP_OK, [], ['groups' => 'games:read']);
    }

    #[Route('/api/v1/games/{id}', name: 'detailGame', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function getDetailGame(VideoGame $videoGame): JsonResponse
    {
        return $this->json($videoGame, Response::HTTP_OK, [], ['groups' => 'games:read']);
    }

    #[Route('/api/v1/games', name: 'createGame', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN', message: "Vous n'avez pas les droits suffisants pour créer un jeu.")]
    public function createGame(Request $request, SerializerInterface $serializer, EntityManagerInterface $em, UrlGeneratorInterface $urlGenerator, ValidatorInterface $validator, EditorRepository $editorRepository, CategoryRepository $categoryRepository): JsonResponse
    {
        $videoGame = $serializer->deserialize($request->getContent(), VideoGame::class, 'json');

        // Les clés étrangères arrivent sous forme d'id dans le JSON : on va chercher les entités.
        $content = $request->toArray();
        $this->attachEditor($videoGame, $content['idEditor'] ?? null, $editorRepository);
        $this->attachCategories($videoGame, $content['idCategories'] ?? [], $categoryRepository);

        $errors = $validator->validate($videoGame);
        if ($errors->count() > 0) {
            return $this->json($errors, Response::HTTP_BAD_REQUEST);
        }

        $em->persist($videoGame);
        $em->flush();

        $location = $urlGenerator->generate('detailGame', ['id' => $videoGame->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

        return $this->json($videoGame, Response::HTTP_CREATED, ['Location' => $location], ['groups' => 'games:read']);
    }

    #[Route('/api/v1/games/{id}', name: 'updateGame', requirements: ['id' => '\d+'], methods: ['PUT'])]
    #[IsGranted('ROLE_ADMIN', message: "Vous n'avez pas les droits suffisants pour modifier un jeu.")]
    public function updateGame(Request $request, VideoGame $currentGame, SerializerInterface $serializer, EntityManagerInterface $em, ValidatorInterface $validator, EditorRepository $editorRepository, CategoryRepository $categoryRepository): JsonResponse
    {
        $serializer->deserialize($request->getContent(), VideoGame::class, 'json', [AbstractNormalizer::OBJECT_TO_POPULATE => $currentGame]);

        // En modification, on ne touche aux relations que si elles sont envoyées.
        $content = $request->toArray();
        if (array_key_exists('idEditor', $content)) {
            $this->attachEditor($currentGame, $content['idEditor'], $editorRepository);
        }
        if (array_key_exists('idCategories', $content)) {
            foreach ($currentGame->getCategories() as $category) {
                $currentGame->removeCategory($category);
            }
            $this->attachCategories($currentGame, $content['idCategories'], $categoryRepository);
        }

        $errors = $validator->validate($currentGame);
        if ($errors->count() > 0) {
            return $this->json($errors, Response::HTTP_BAD_REQUEST);
        }

        $em->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/api/v1/games/{id}', name: 'deleteGame', requirements: ['id' => '\d+'], methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN', message: "Vous n'avez pas les droits suffisants pour supprimer un jeu.")]
    public function deleteGame(VideoGame $videoGame, EntityManagerInterface $em): JsonResponse
    {
        $em->remove($videoGame);
        $em->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    private function attachEditor(VideoGame $videoGame, mixed $idEditor, EditorRepository $editorRepository): void
    {
        if (null === $idEditor) {
            return; // l'Assert NotNull sur editor renverra l'erreur si le jeu n'en a pas
        }

        $editor = $editorRepository->find($idEditor);
        if (null === $editor) {
            throw new BadRequestHttpException("L'éditeur $idEditor n'existe pas.");
        }

        $videoGame->setEditor($editor);
    }

    private function attachCategories(VideoGame $videoGame, mixed $idCategories, CategoryRepository $categoryRepository): void
    {
        if (!is_array($idCategories)) {
            throw new BadRequestHttpException('idCategories doit être un tableau d\'id.');
        }

        foreach ($idCategories as $idCategory) {
            $category = $categoryRepository->find($idCategory);
            if (null === $category) {
                throw new BadRequestHttpException("La catégorie $idCategory n'existe pas.");
            }
            $videoGame->addCategory($category);
        }
    }
}
