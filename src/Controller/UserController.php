<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

// Toute la gestion des utilisateurs est réservée aux administrateurs.
#[IsGranted('ROLE_ADMIN', message: "Vous n'avez pas les droits suffisants pour gérer les utilisateurs.")]
class UserController extends AbstractController
{
    #[Route('/api/v1/users', name: 'users', methods: ['GET'])]
    public function getUsers(UserRepository $userRepository, Request $request): JsonResponse
    {
        $page = max(1, $request->query->getInt('page', 1));
        $limit = max(1, $request->query->getInt('limit', 3));

        $userList = $userRepository->findUserWithPagination($page, $limit);

        return $this->json($userList, Response::HTTP_OK, [], ['groups' => 'user:read']);
    }

    #[Route('/api/v1/users/{id}', name: 'detailUser', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function getDetailUser(User $user): JsonResponse
    {
        return $this->json($user, Response::HTTP_OK, [], ['groups' => 'user:read']);
    }

    #[Route('/api/v1/users', name: 'createUser', methods: ['POST'])]
    public function createUser(Request $request, SerializerInterface $serializer, EntityManagerInterface $em, UrlGeneratorInterface $urlGenerator, ValidatorInterface $validator, UserPasswordHasherInterface $passwordHasher): JsonResponse
    {
        $user = $serializer->deserialize($request->getContent(), User::class, 'json');

        // On valide le mot de passe en clair (longueur), puis on le hashe avant l'enregistrement.
        $errors = $validator->validate($user);
        if ($errors->count() > 0) {
            return $this->json($errors, Response::HTTP_BAD_REQUEST);
        }

        $user->setPassword($passwordHasher->hashPassword($user, $user->getPassword()));

        $em->persist($user);
        $em->flush();

        $location = $urlGenerator->generate('detailUser', ['id' => $user->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

        return $this->json($user, Response::HTTP_CREATED, ['Location' => $location], ['groups' => 'user:read']);
    }

    #[Route('/api/v1/users/{id}', name: 'updateUser', requirements: ['id' => '\d+'], methods: ['PUT'])]
    public function updateUser(Request $request, User $currentUser, SerializerInterface $serializer, EntityManagerInterface $em, UrlGeneratorInterface $urlGenerator, ValidatorInterface $validator, UserPasswordHasherInterface $passwordHasher): JsonResponse
    {
        $serializer->deserialize($request->getContent(), User::class, 'json', [AbstractNormalizer::OBJECT_TO_POPULATE => $currentUser]);

        $errors = $validator->validate($currentUser);
        if ($errors->count() > 0) {
            return $this->json($errors, Response::HTTP_BAD_REQUEST);
        }

        // Si un nouveau mot de passe est envoyé, il est en clair : on le hashe.
        // Sinon, l'ancien hash reste en place.
        if (array_key_exists('password', $request->toArray())) {
            $currentUser->setPassword($passwordHasher->hashPassword($currentUser, $currentUser->getPassword()));
        }

        $em->flush();

        $location = $urlGenerator->generate('detailUser', ['id' => $currentUser->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

        return $this->json(['status' => 'success'], Response::HTTP_OK, ['Location' => $location]);
    }

    #[Route('/api/v1/users/{id}', name: 'deleteUser', requirements: ['id' => '\d+'], methods: ['DELETE'])]
    public function deleteUser(User $user, EntityManagerInterface $em): JsonResponse
    {
        if ($user === $this->getUser()) {
            return $this->json([
                'status' => Response::HTTP_BAD_REQUEST,
                'message' => 'Vous ne pouvez pas supprimer votre propre compte.',
            ], Response::HTTP_BAD_REQUEST);
        }

        $em->remove($user);
        $em->flush();

        return new JsonResponse(['status' => 'success'], Response::HTTP_OK);
    }
}
