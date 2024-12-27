<?php

namespace App\Controller\Api;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Article;

#[Route('/articles', name: 'api_articles_')]
class ArticleApiController extends AbstractApiController
{
    public function __construct(
        private SerializerInterface $serializer,
        private EntityManagerInterface $entityManager
    ) {}

    #[Route('', name: 'list', methods: ['GET'])]
    public function getArticles(): JsonResponse
    {
        try {
            $articles = $this->entityManager->getRepository(Article::class)->findAll();
            return $this->apiResponse($articles);
        } catch (\Exception $e) {
            return $this->apiError($e->getMessage());
        }
    }

    #[Route('/{id}', name: 'get', methods: ['GET'])]
    public function getArticle(int $id): JsonResponse
    {
        try {
            $article = $this->entityManager->getRepository(Article::class)->find($id);
            if (!$article) {
                return $this->apiError('Article non trouvé', 404);
            }
            return $this->apiResponse($article);
        } catch (\Exception $e) {
            return $this->apiError($e->getMessage());
        }
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function createArticle(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            $article = new Article();
            $article->setTitle($data['title']);
            $article->setContent($data['content']);
            $this->entityManager->persist($article);
            $this->entityManager->flush();
            return $this->apiResponse($article, 201);
        } catch (\Exception $e) {
            return $this->apiError($e->getMessage());
        }
    }

    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    public function updateArticle(int $id, Request $request): JsonResponse
    {
        try {
            $article = $this->entityManager->getRepository(Article::class)->find($id);
            if (!$article) {
                return $this->apiError('Article non trouvé', 404);
            }
            $data = json_decode($request->getContent(), true);
            $article->setTitle($data['title']);
            $article->setContent($data['content']);
            $this->entityManager->flush();
            return $this->apiResponse($article);
        } catch (\Exception $e) {
            return $this->apiError($e->getMessage());
        }
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function deleteArticle(int $id): JsonResponse
    {
        try {
            $article = $this->entityManager->getRepository(Article::class)->find($id);
            if (!$article) {
                return $this->apiError('Article non trouvé', 404);
            }
            $this->entityManager->remove($article);
            $this->entityManager->flush();
            return $this->apiResponse(null, 204);
        } catch (\Exception $e) {
            return $this->apiError($e->getMessage());
        }
    }
}
