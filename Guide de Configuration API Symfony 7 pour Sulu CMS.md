
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
- [Tests](#tests)
- [Déploiement](#déploiement)
- [Bonnes Pratiques](#bonnes-pratiques)

## Prérequis

- PHP 8.2 ou supérieur
- Composer 2.x
- Symfony CLI
- Sulu CMS installé
- MySQL/MariaDB
- Git

## Installation

1. Créez un nouveau projet Symfony :

```bash
symfony new api-project --version="7.*" 
cd api-project
```

2. Installez les dépendances nécessaires :

```bash
composer require symfony/orm-pack
composer require symfony/serializer-pack
composer require nelmio/cors-bundle
composer require lexik/jwt-authentication-bundle
composer require nelmio/api-doc-bundle
composer require api-platform/core
```

## Configuration CORS

1. Créez ou modifiez `config/packages/nelmio_cors.yaml` :

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

1. Générez les clés JWT :

```bash
php bin/console lexik:jwt:generate-keypair
```

2. Configurez `config/packages/lexik_jwt_authentication.yaml` :

```yaml
lexik_jwt_authentication:
    secret_key: '%kernel.project_dir%/config/jwt/private.pem'
    public_key: '%kernel.project_dir%/config/jwt/public.pem'
    pass_phrase: '%env(JWT_PASSPHRASE)%'
    token_ttl: 3600
```

3. Configurez `config/packages/security.yaml` :

```yaml
security:
    enable_authenticator_manager: true
    providers:
        users_in_memory: { memory: null }
    firewalls:
        dev:
            pattern: ^/(_(profiler|wdt)|css|images|js)/
            security: false
        login:
            pattern: ^/api/login
            stateless: true
            json_login:
                check_path: /api/login_check
                success_handler: lexik_jwt_authentication.handler.authentication_success
                failure_handler: lexik_jwt_authentication.handler.authentication_failure
        api:
            pattern: ^/api
            stateless: true
            jwt: ~
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

1. Configurez les tests :

```bash
composer require --dev symfony/test-pack
```

2. Exemple de test fonctionnel :

```php
namespace App\Tests\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ArticleApiControllerTest extends WebTestCase
{
    public function testGetArticles(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/v1/articles');

        $this->assertResponseIsSuccessful();
        $this->assertJsonStructure($client->getResponse()->getContent(), [
            'data' => [
                '*' => [
                    'id',
                    'title'
                ]
            ]
        ]);
    }
}
```

## Déploiement

### 1. Préparation

```bash
# Installation des dépendances en production
composer install --no-dev --optimize-autoloader

# Nettoyage du cache
php bin/console cache:clear --env=prod
php bin/console cache:warmup --env=prod
```

### 2. Configuration du Serveur

Exemple pour Nginx :

```nginx
server {
    server_name api.example.com;
    root /var/www/api/public;

    location / {
        try_files $uri /index.php$is_args$args;
    }

    location ~ ^/index\.php(/|$) {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_split_path_info ^(.+\.php)(/.*)$;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        fastcgi_param DOCUMENT_ROOT $realpath_root;
        internal;
    }

    location ~ \.php$ {
        return 404;
    }
}
```

## Bonnes Pratiques

1. **Versionnement API**
   - Utilisez le préfixe de route `/api/v1/`
   - Préparez la migration vers les versions futures

2. **Sécurité**
   - Validez toutes les entrées utilisateur
   - Utilisez les groupes de sérialisation
   - Implémentez des limites de taux (rate limiting)

3. **Performance**
   - Utilisez le cache HTTP
   - Implémentez la pagination
   - Optimisez les requêtes N+1

4. **Maintenance**
   - Documentez votre code
   - Écrivez des tests unitaires et fonctionnels
   - Suivez les standards PSR
