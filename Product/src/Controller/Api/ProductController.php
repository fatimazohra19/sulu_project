<?php

namespace App\Controller\Api;

use App\Entity\Product;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ProductController extends AbstractApiController
{
    private $productRepository;
    private $entityManager;
    private $serializer;
    private $validator;

    public function __construct(
        ProductRepository $productRepository,
        EntityManagerInterface $entityManager,
        SerializerInterface $serializer,
        ValidatorInterface $validator
    ) {
        $this->productRepository = $productRepository;
        $this->entityManager = $entityManager;
        $this->serializer = $serializer;
        $this->validator = $validator;
    }

    #[Route('/products', name: 'api_products_index', methods: ['GET'])]
    public function index(): Response
    {
        $products = $this->productRepository->findAll();
        $data = $this->serializer->serialize($products, 'json');

        return $this->apiResponse(json_decode($data, true));
    }

    #[Route('', name: 'api_products_show', methods: ['GET'])]
    public function show(int $id): Response
    {
        $product = $this->productRepository->find($id);

        if (!$product) {
            return $this->apiError('Product not found', Response::HTTP_NOT_FOUND);
        }

        $data = $this->serializer->serialize($product, 'json');

        return $this->apiResponse(json_decode($data, true));
    }

    #[Route('/products', name: 'api_products_create', methods: ['POST'])]
    public function create(Request $request): Response
    {
        $data = json_decode($request->getContent(), true);
        $product = new Product();
        $product->setName($data['name']);
        $product->setPrice($data['price']);
        $product->setQuantity($data['quantity']);
        $product->setSelected($data['selected'] ?? false);
        $product->setAvailable($data['available'] ?? true);

        $errors = $this->validator->validate($product);
        if (count($errors) > 0) {
            $errorsString = (string) $errors;
            return $this->apiError($errorsString, Response::HTTP_BAD_REQUEST);
        }

        $this->entityManager->persist($product);
        $this->entityManager->flush();

        $data = $this->serializer->serialize($product, 'json');

        return $this->apiResponse(json_decode($data, true), Response::HTTP_CREATED);
    }

    #[Route('', name: 'api_products_update', methods: ['PUT'])]
    public function update(int $id, Request $request): Response
    {
        $product = $this->productRepository->find($id);

        if (!$product) {
            return $this->apiError('Product not found', Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);
        $product->setName($data['name']);
        $product->setPrice($data['price']);
        $product->setQuantity($data['quantity']);
        $product->setSelected($data['selected'] ?? false);
        $product->setAvailable($data['available'] ?? true);

        $errors = $this->validator->validate($product);
        if (count($errors) > 0) {
            $errorsString = (string) $errors;
            return $this->apiError($errorsString, Response::HTTP_BAD_REQUEST);
        }

        $this->entityManager->flush();

        $data = $this->serializer->serialize($product, 'json');

        return $this->apiResponse(json_decode($data, true));
    }

    #[Route('', name: 'api_products_delete', methods: ['DELETE'])]
    public function delete(int $id): Response
    {
        $product = $this->productRepository->find($id);

        if (!$product) {
            return $this->apiError('Product not found', Response::HTTP_NOT_FOUND);
        }

        $this->entityManager->remove($product);
        $this->entityManager->flush();

        return $this->apiResponse(null, Response::HTTP_NO_CONTENT);
    }
}
