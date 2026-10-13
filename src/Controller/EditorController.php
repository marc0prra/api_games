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

 class EditorController extends AbstractController
{
    #[Route('/api/v1/editors', name: 'editors', methods: ['GET'])]
    public function getEditors(EditorRepository $editorRepository): JsonResponse
    {
        $editorList = $editorRepository->findAll();

        return $this->json($editorList, Response::HTTP_OK, [], ['groups' => 'editor:read']);
    }
}
