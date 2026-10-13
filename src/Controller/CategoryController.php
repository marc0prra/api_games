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
use Symfony\Component\Serializer\SerializerInterface;

class CategoryController extends AbstractController
{
    #[Route('/api/v1/categories', name: 'categories', methods: ['GET'])]
    public function getCategories(CategoryRepository $categoryRepository, SerializerInterface $serializer): JsonResponse
    {
        $categoryList = $categoryRepository->findAll();

        $jsonCategories = $serializer->serialize($categoryList, 'json', ['groups' => 'category:read']);

        return new JsonResponse($jsonCategories, Response::HTTP_OK, [], true);
    }
}
