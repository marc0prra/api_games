<?php

namespace App\Controller;

use App\Entity\Editor;
use App\Repository\EditorRepository;
use Doctrine\ORM\EntityManagerInterface;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
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

#[OA\Tag(name: 'Éditeurs')]
class EditorController extends AbstractController
{
    #[Route('/api/v1/editors', name: 'editors', methods: ['GET'])]
    #[OA\Get(summary: 'Liste paginée des éditeurs')]
    #[OA\Parameter(name: 'page', in: 'query', description: 'Numéro de page', schema: new OA\Schema(type: 'integer', default: 1))]
    #[OA\Parameter(name: 'limit', in: 'query', description: "Nombre d'éléments par page", schema: new OA\Schema(type: 'integer', default: 3))]
    #[OA\Response(response: 200, description: 'Liste des éditeurs', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: new Model(type: Editor::class, groups: ['editor:read']))))]
    public function getEditors(EditorRepository $editorRepository, Request $request): JsonResponse
    {
        $page = max(1, $request->query->getInt('page', 1));
        $limit = max(1, $request->query->getInt('limit', 3));

        $editorList = $editorRepository->findEditorWithPagination($page, $limit);

        return $this->json($editorList, Response::HTTP_OK, [], ['groups' => 'editor:read']);
    }

    #[Route('/api/v1/editors/{id}', name: 'detailEditor', requirements: ['id' => '\d+'], methods: ['GET'])]
    #[OA\Get(summary: "Détail d'un éditeur")]
    #[OA\Response(response: 200, description: 'Le éditeur', content: new OA\JsonContent(ref: new Model(type: Editor::class, groups: ['editor:read'])))]
    #[OA\Response(response: 404, description: 'Ressource introuvable', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
    public function getDetailEditor(Editor $editor): JsonResponse
    {
        return $this->json($editor, Response::HTTP_OK, [], ['groups' => 'editor:read']);
    }

    #[Route('/api/v1/editors', name: 'createEditor', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN', message: "Vous n'avez pas les droits suffisants pour créer un éditeur.")]
    #[OA\Post(summary: 'Créer un éditeur')]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['name', 'country'],
            properties: [
                new OA\Property(property: 'name', type: 'string', example: 'Nintendo'),
                new OA\Property(property: 'country', type: 'string', example: 'Japon'),
            ],
        ),
    )]
    #[OA\Response(response: 201, description: 'Ressource créée (URL dans le header Location)', content: new OA\JsonContent(ref: new Model(type: Editor::class, groups: ['editor:read'])))]
    #[OA\Response(response: 400, description: 'Données invalides (erreurs de validation)')]
    #[OA\Response(response: 403, description: 'Réservé aux administrateurs', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
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
    #[OA\Put(summary: 'Modifier un éditeur', description: 'Seuls les champs envoyés sont modifiés.')]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'name', type: 'string', example: 'Nintendo'),
                new OA\Property(property: 'country', type: 'string', example: 'Japon'),
            ],
        ),
    )]
    #[OA\Response(response: 200, description: 'Ressource modifiée (URL dans le header Location)', content: new OA\JsonContent(ref: '#/components/schemas/Success'))]
    #[OA\Response(response: 400, description: 'Données invalides (erreurs de validation)')]
    #[OA\Response(response: 403, description: 'Réservé aux administrateurs', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
    #[OA\Response(response: 404, description: 'Ressource introuvable', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
    public function updateEditor(Request $request, Editor $currentEditor, SerializerInterface $serializer, EntityManagerInterface $em, UrlGeneratorInterface $urlGenerator, ValidatorInterface $validator): JsonResponse
    {
        $serializer->deserialize($request->getContent(), Editor::class, 'json', [AbstractNormalizer::OBJECT_TO_POPULATE => $currentEditor]);

        $errors = $validator->validate($currentEditor);
        if ($errors->count() > 0) {
            return $this->json($errors, Response::HTTP_BAD_REQUEST);
        }

        $em->flush();

        $location = $urlGenerator->generate('detailEditor', ['id' => $currentEditor->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

        return $this->json(['status' => 'success'], Response::HTTP_OK, ['Location' => $location]);
    }

    #[Route('/api/v1/editors/{id}', name: 'deleteEditor', requirements: ['id' => '\d+'], methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN', message: "Vous n'avez pas les droits suffisants pour supprimer un éditeur.")]
    #[OA\Delete(summary: 'Supprimer un éditeur')]
    #[OA\Response(response: 200, description: 'Ressource supprimée', content: new OA\JsonContent(ref: '#/components/schemas/Success'))]
    #[OA\Response(response: 403, description: 'Réservé aux administrateurs', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
    #[OA\Response(response: 409, description: "L'éditeur possède encore des jeux", content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
    #[OA\Response(response: 404, description: 'Ressource introuvable', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
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

        return new JsonResponse(['status' => 'success'], Response::HTTP_OK);
    }
}
