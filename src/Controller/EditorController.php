<?php

namespace App\Controller;

use App\Entity\Editor;
use App\Repository\EditorRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class EditorController extends AbstractController
{
    #[Route('/api/v1/editors', name: 'editors', methods: ['GET'])]
    public function getEditors(EditorRepository $editorRepository): JsonResponse
    {
        $editorList = $editorRepository->findAll();

        return $this->json($editorList, Response::HTTP_OK, [], ['groups' => 'editor:read']);
    }

    #[Route('/api/v1/editors/{id}', name: 'detailEditor', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function getDetailEditor(Editor $editor): JsonResponse
    {
        return $this->json($editor, Response::HTTP_OK, [], ['groups' => 'editor:read']);
    }

    #[Route('/api/v1/editors', name: 'createEditor', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN', message: "Vous n'avez pas les droits suffisants pour créer un éditeur.")]
    public function createEditor(Request $request, SerializerInterface $serializer, EntityManagerInterface $em, UrlGeneratorInterface $urlGenerator, ValidatorInterface $validator): JsonResponse
    {
        $editor = $serializer->deserialize($request->getContent(), Editor::class, 'json');

        $errors = $validator->validate($editor);
        if ($errors->count() > 0) {
            return $this->json($errors, Response::HTTP_BAD_REQUEST);
        }

        $em->persist($editor);
        $em->flush();

        $location = $urlGenerator->generate('detailEditor', ['id' => $editor->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

        return $this->json($editor, Response::HTTP_CREATED, ['Location' => $location], ['groups' => 'editor:read']);
    }

    #[Route('/api/v1/editors/{id}', name: 'updateEditor', requirements: ['id' => '\d+'], methods: ['PUT'])]
    #[IsGranted('ROLE_ADMIN', message: "Vous n'avez pas les droits suffisants pour modifier un éditeur.")]
    public function updateEditor(Request $request, Editor $currentEditor, SerializerInterface $serializer, EntityManagerInterface $em, ValidatorInterface $validator): JsonResponse
    {
        $serializer->deserialize($request->getContent(), Editor::class, 'json', [AbstractNormalizer::OBJECT_TO_POPULATE => $currentEditor]);

        $errors = $validator->validate($currentEditor);
        if ($errors->count() > 0) {
            return $this->json($errors, Response::HTTP_BAD_REQUEST);
        }

        $em->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/api/v1/editors/{id}', name: 'deleteEditor', requirements: ['id' => '\d+'], methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN', message: "Vous n'avez pas les droits suffisants pour supprimer un éditeur.")]
    public function deleteEditor(Editor $editor, EntityManagerInterface $em): JsonResponse
    {
        if (!$editor->getVideoGames()->isEmpty()) {
            return $this->json([
                'status' => Response::HTTP_CONFLICT,
                'message' => 'Impossible de supprimer un éditeur qui possède encore des jeux.',
            ], Response::HTTP_CONFLICT);
        }

        $em->remove($editor);
        $em->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
