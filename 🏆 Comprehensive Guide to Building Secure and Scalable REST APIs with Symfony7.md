# Guide de Configuration API Symfony 7

Ce guide détaille la mise en place et la configuration d'une API REST moderne avec Symfony 7, incluant les meilleures pratiques et configurations recommandées.

## Table des matières

- [Prérequis](#prérequis)
- [Installation](#installation)
- [Configuration de Base](#configuration-de-base)
- [Structure du Projet](#structure-du-projet)
- [Validation & Sécurité](#validation--sécurité)
- [Documentation API](#documentation-api)
- [Tests](#tests)
- [Déploiement](#déploiement)

## Prérequis

- PHP 8.2 ou supérieur
- Composer 2.x
- Symfony CLI
- MySQL/MariaDB
- Git

## Installation

1. Créez un nouveau projet Symfony :

```bash
symfony new api-project --version="7.*" --webapp
cd api-project
```

2. Installez les dépendances nécessaires :

```bash
composer require symfony/orm-pack
composer require symfony/serializer-pack
composer require nelmio/cors-bundle
composer require lexik/jwt-authentication-bundle
composer require nelmio/api-doc-bundle
```

## Configuration de Base

### 1. Configuration CORS

Créez ou modifiez `config/packages/nelmio_cors.yaml` :

```yaml
nelmio_cors:
    defaults:
        origin_regex: true
        allow_origin: ['%env(CORS_ALLOW_ORIGIN)%']
        allow_methods: ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS']
        allow_headers: ['Content-Type', 'Authorization']
        expose_headers: ['Link']
        max_age: 3600
    paths:
        '^/api/':
            allow_origin: ['*']
            allow_headers: ['*']
            allow_methods: ['POST', 'PUT', 'GET', 'DELETE']
```

### 2. Configuration des Routes

Modifiez `config/routes.yaml` :

```yaml
api_routes:
    resource:
        path: ../src/Controller/Api/
        namespace: App\Controller\Api
    type: attribute
    prefix: '/api/v1'
```

## Structure du Projet

Organisez votre projet selon cette structure :

```
src/
├── Controller/
│   └── Api/
│       ├── AbstractApiController.php
│       └── ProductController.php
├── Entity/
│   └── Product.php
├── Repository/
│   └── ProductRepository.php
├── Service/
│   └── ProductService.php
├── DTO/
│   └── ProductDTO.php
└── Exception/
    └── ApiException.php
```

### AbstractApiController

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
                'success' => $status < 400,
                'data' => $data,
            ],
            $status,
            $headers
        );
    }

    protected function apiError(string $message, int $status = 400): JsonResponse
    {
        return new JsonResponse(
            [
                'success' => false,
                'error' => [
                    'message' => $message,
                    'code' => $status
                ]
            ],
            $status
        );
    }
}
```

## Validation & Sécurité

### 1. Configuration JWT

Générez les clés JWT :

```bash
php bin/console lexik:jwt:generate-keypair
```

Configurez `config/packages/lexik_jwt_authentication.yaml` :

```yaml
lexik_jwt_authentication:
    secret_key: '%kernel.project_dir%/config/jwt/private.pem'
    public_key: '%kernel.project_dir%/config/jwt/public.pem'
    pass_phrase: '%env(JWT_PASSPHRASE)%'
    token_ttl: 3600
```

### 2. Configuration de la Sécurité

Modifiez `config/packages/security.yaml` :

```yaml
security:
    password_hashers:
        Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface: 'auto'
    providers:
        app_user_provider:
            entity:
                class: App\Entity\User
                property: email
    firewalls:
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

### 1. Configuration Swagger/OpenAPI

Configurez `config/packages/nelmio_api_doc.yaml` :

```yaml
nelmio_api_doc:
    documentation:
        info:
            title: API Documentation
            description: Documentation de l'API
            version: 1.0.0
    areas:
        path_patterns:
            - ^/api(?!/doc$)
```

### 2. Exemple de Documentation de Contrôleur

```php
#[Route('/api/v1/products', name: 'api_products_')]
#[Tag('Products')]
class ProductController extends AbstractApiController
{
    #[Route('', name: 'index', methods: ['GET'])]
    #[OA\Response(
        response: 200,
        description: 'Retourne la liste des produits',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: Product::class))
        )
    )]
    public function index(): JsonResponse
    {
        // Votre code ici
    }
}
```

## Tests

### 1. Configuration des Tests

```bash
composer require --dev symfony/test-pack
```

### 2. Exemple de Test Fonctionnel

```php
namespace App\Tests\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ProductControllerTest extends WebTestCase
{
    public function testGetProducts(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/v1/products');

        $this->assertResponseIsSuccessful();
        $this->assertJsonStructure($client->getResponse()->getContent(), [
            'success',
            'data' => [
                '*' => [
                    'id',
                    'name',
                    'price'
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

Example pour Nginx :

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

