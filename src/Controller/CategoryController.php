<?php

namespace App\Controller;

use App\Entity\Category;
use App\Repository\CategoryRepository;
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

class CategoryController extends AbstractController
{
    #[Route('/api/v1/categories', name: 'categories', methods: ['GET'])]
    public function getCategories(CategoryRepository $categoryRepository, Request $request): JsonResponse
    {
        $page = max(1, $request->query->getInt('page', 1));
        $limit = max(1, $request->query->getInt('limit', 3));

        $categoryList = $categoryRepository->findCategoryWithPagination($page, $limit);

        return $this->json($categoryList, Response::HTTP_OK, [], ['groups' => 'category:read']);
    }

    #[Route('/api/v1/categories/{id}', name: 'detailCategory', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function getDetailCategory(Category $category): JsonResponse
    {
        return $this->json($category, Response::HTTP_OK, [], ['groups' => 'category:read']);
    }

    #[Route('/api/v1/categories', name: 'createCategory', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN', message: "Vous n'avez pas les droits suffisants pour créer une catégorie.")]
    public function createCategory(Request $request, SerializerInterface $serializer, EntityManagerInterface $em, UrlGeneratorInterface $urlGenerator, ValidatorInterface $validator): JsonResponse
    {
        $category = $serializer->deserialize($request->getContent(), Category::class, 'json');

        $errors = $validator->validate($category);
        if ($errors->count() > 0) {
            return $this->json($errors, Response::HTTP_BAD_REQUEST);
        }

        $em->persist($category);
        $em->flush();

        $location = $urlGenerator->generate('detailCategory', ['id' => $category->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

        return $this->json($category, Response::HTTP_CREATED, ['Location' => $location], ['groups' => 'category:read']);
    }

    #[Route('/api/v1/categories/{id}', name: 'updateCategory', requirements: ['id' => '\d+'], methods: ['PUT'])]
    #[IsGranted('ROLE_ADMIN', message: "Vous n'avez pas les droits suffisants pour modifier une catégorie.")]
    public function updateCategory(Request $request, Category $currentCategory, SerializerInterface $serializer, EntityManagerInterface $em, UrlGeneratorInterface $urlGenerator, ValidatorInterface $validator): JsonResponse
    {
        $serializer->deserialize($request->getContent(), Category::class, 'json', [AbstractNormalizer::OBJECT_TO_POPULATE => $currentCategory]);

        $errors = $validator->validate($currentCategory);
        if ($errors->count() > 0) {
            return $this->json($errors, Response::HTTP_BAD_REQUEST);
        }

        $em->flush();

        $location = $urlGenerator->generate('detailCategory', ['id' => $currentCategory->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

        return $this->json(['status' => 'success'], Response::HTTP_OK, ['Location' => $location]);
    }

    #[Route('/api/v1/categories/{id}', name: 'deleteCategory', requirements: ['id' => '\d+'], methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN', message: "Vous n'avez pas les droits suffisants pour supprimer une catégorie.")]
    public function deleteCategory(Category $category, EntityManagerInterface $em): JsonResponse
    {
        // La table de liaison video_game_category est en ON DELETE CASCADE :
        // les jeux sont conservés, seul le lien avec la catégorie disparaît.
        $em->remove($category);
        $em->flush();

        return new JsonResponse(['status' => 'success'], Response::HTTP_OK);
    }
}
