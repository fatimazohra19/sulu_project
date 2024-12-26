# Guide de Configuration API Symfony 7 pour Sulu CMS

Ce guide détaille la configuration d'une API REST moderne avec Symfony 7 dans le contexte d'un CMS Sulu, destinée à servir une application frontend Angular avec NgRx.

## Table des matières
- [Prérequis](#prérequis)
- [Installation](#installation)
- [Configuration CORS](#configuration-cors)
- [Configuration des Routes](#configuration-des-routes)
- [Structure du Projet](#structure-du-projet)
- [Implémentation des Contrôleurs](#implémentation-des-contrôleurs)
- [Configuration de la Sécurité](#configuration-de-la-sécurité)
- [Documentation API](#documentation-api)

## Prérequis

- PHP 8.1 ou supérieur
- Composer
- Symfony 7
- Sulu CMS installé
- Serveur MySQL/MariaDB

## Installation

1. Installez les dépendances nécessaires :

```bash
composer require nelmio/cors-bundle
composer require symfony/serializer-pack
composer require api-platform/core
```

## Configuration CORS

1. Créez le fichier `config/packages/nelmio_cors.yaml` :

```yaml
nelmio_cors:
    defaults:
        origin_regex: true
        allow_origin: ['%env(CORS_ALLOW_ORIGIN)%']
        allow_methods: ['GET', 'OPTIONS', 'POST', 'PUT', 'PATCH', 'DELETE']
        allow_headers: ['Content-Type', 'Authorization']
        expose_headers: ['Link']
        max_age: 3600
    paths:
        '^/api/':
            allow_origin: ['*']
            allow_headers: ['*']
            allow_methods: ['POST', 'PUT', 'GET', 'DELETE']
```

2. Ajoutez dans `.env` :

```env
###> nelmio/cors-bundle ###
CORS_ALLOW_ORIGIN='^https?://(localhost|127\.0\.0\.1)(:[0-9]+)?$'
###< nelmio/cors-bundle ###
```

## Configuration des Routes

1. Créez/Modifiez `config/routes.yaml` :

```yaml
api_controllers:
    resource:
        path: ../src/Controller/Api/
        namespace: App\Controller\Api
    type: attribute
    prefix: '/api/v1'
```

## Structure du Projet

Créez la structure suivante :

```
src/
├── Controller/
│   └── Api/
│       ├── AbstractApiController.php
│       └── ArticleApiController.php
├── Entity/
├── Repository/
└── Service/
```

## Implémentation des Contrôleurs

1. Créez le contrôleur abstrait (`AbstractApiController.php`) :

```php
namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;

abstract class AbstractApiController extends AbstractController
{
    protected function apiResponse($data, int $status = 200, array $headers = []): JsonResponse
    {
        return new JsonResponse(
            [
                'data' => $data,
                'status' => $status >= 200 && $status < 300 ? 'success' : 'error'
            ],
            $status,
            $headers
        );
    }

    protected function apiError(string $message, int $status = 400): JsonResponse
    {
        return new JsonResponse(
            [
                'message' => $message,
                'status' => 'error'
            ],
            $status
        );
    }
}
```

2. Exemple de contrôleur d'API (`ArticleApiController.php`) :

```php
namespace App\Controller\Api;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/articles', name: 'api_articles_')]
class ArticleApiController extends AbstractApiController
{
    public function __construct(
        private SerializerInterface $serializer
    ) {}

    #[Route('', name: 'list', methods: ['GET'])]
    public function getArticles(): JsonResponse
    {
        try {
            // Votre logique de récupération des articles
            $articles = []; // Remplacez par votre logique
            
            return $this->apiResponse($articles);
        } catch (\Exception $e) {
            return $this->apiError($e->getMessage());
        }
    }

    #[Route('/{id}', name: 'get', methods: ['GET'])]
    public function getArticle(int $id): JsonResponse
    {
        try {
            // Votre logique de récupération d'un article
            $article = null; // Remplacez par votre logique
            
            if (!$article) {
                return $this->apiError('Article non trouvé', 404);
            }
            
            return $this->apiResponse($article);
        } catch (\Exception $e) {
            return $this->apiError($e->getMessage());
        }
    }
}
```

## Configuration de la Sécurité

1. Configurez `config/packages/security.yaml` :

```yaml
security:
    enable_authenticator_manager: true
    providers:
        users_in_memory: { memory: null }
    firewalls:
        dev:
            pattern: ^/(_(profiler|wdt)|css|images|js)/
            security: false
        api:
            pattern: ^/api
            stateless: true
            provider: users_in_memory
            # Configurez votre méthode d'authentification ici
```

## Documentation API

1. Configurez API Platform dans `config/packages/api_platform.yaml` :

```yaml
api_platform:
    title: 'Votre API'
    description: 'Description de votre API'
    version: '1.0.0'
    formats:
        json: ['application/json']
    docs_formats:
        jsonld: ['application/ld+json']
        jsonopenapi: ['application/vnd.openapi+json']
        html: ['text/html']
```

2. Exemple d'entité avec documentation (`Article.php`) :

```php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity]
class Article
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['article:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['article:read'])]
    private ?string $title = null;

    // Getters et Setters
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }
}
```

## Tests

Pour vérifier que tout fonctionne :

1. Démarrez le serveur :
```bash
symfony server:start
```

2. Testez un endpoint :
```bash
curl -X GET http://localhost:8000/api/v1/articles
```

## Notes importantes

- Adaptez les URLs et configurations selon votre environnement
- Utilisez des variables d'environnement pour les configurations sensibles
- Implémentez une gestion appropriée des erreurs
- Documentez vos APIs au fur et à mesure
- Suivez les bonnes pratiques REST
- Pensez à la pagination pour les listes
- Utilisez les groupes de sérialisation pour contrôler les données exposées
