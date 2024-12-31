# Guide sur les Bundles Symfony et Sulu

## Introduction

Les bundles dans Symfony sont l'unité fondamentale d'organisation du code. Un bundle est comme un "plugin" qui contient tout ce dont vous avez besoin pour une fonctionnalité spécifique : contrôleurs, modèles, fichiers de configuration, feuilles de style, JavaScript, etc.

## Structure typique d'un bundle Symfony

```
MonBundle/
    ├── Controller/
    ├── Resources/
    │   ├── config/
    │   ├── views/
    │   └── public/
    ├── Entity/
    ├── Repository/ 
    └── MonBundleBundle.php
```

## Bundles principaux de Sulu

### 1. AdminBundle

Gère l'interface d'administration :

- Fournit le framework JavaScript pour l'interface administrateur
- Gère l'authentification et les autorisations
- Définit la structure du menu d'administration

Configuration exemple :

```yaml
# config/packages/sulu_admin.yaml
sulu_admin:
    resources:
        pages:
            form:
                type: page
                template: "default"
    navigation:
        settings:
            name: 'Settings'
            icon: 'gear'
```

### 2. PageBundle

Gère les pages et leur contenu :

- Gestion des templates de page
- Navigation et structure du site
- Gestion des versions et de la publication

### 3. MediaBundle

Gère les ressources médias :

- Upload et stockage des fichiers
- Organisation en collections
- Génération des aperçus

### 4. ContactBundle

Gère les contacts et comptes :

- Stockage des informations de contact
- Relations entre contacts
- Intégration avec d'autres bundles

## Création d'un bundle personnalisé

Pour créer votre propre bundle dans un projet Sulu :

```php
<?php
namespace App\Bundle\MonBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class MonBundle extends Bundle
{
    // Configuration spécifique au bundle si nécessaire
}
```

Enregistrez-le dans le fichier `config/bundles.php` :

```php
return [
    // ... autres bundles
    App\Bundle\MonBundle\MonBundle::class => ['all' => true],
];
```

## Bonnes pratiques

- Gardez vos bundles focalisés sur une fonctionnalité spécifique.
- Suivez les conventions de nommage de Symfony.
- Utilisez la configuration des bundles pour rendre votre code flexible.
- Tirez parti des événements Symfony pour interconnecter vos bundles.
- Documentez clairement les dépendances entre bundles.

## Exemple d'utilisation avec Twig

```twig
{# templates/pages/contact.html.twig #}

{% extends "base.html.twig" %}

{% block content %}
    {% for contact in contacts %}
        <div class="contact-card">
            <h3>{{ contact.fullName }}</h3>
            <p>{{ contact.mainEmail }}</p>
        </div>
    {% endfor %}

    {% for media in medias %}
        <img src="{{ media.url }}" alt="{{ media.title }}">
    {% endfor %}
{% endblock %}
```

## Configuration CORS (Cross-Origin Resource Sharing) pour une API Symfony 7 avec Sulu

### 1. Introduction au CORS

CORS est un mécanisme de sécurité qui permet à une application web de faire des requêtes vers une autre origine (domaine, protocole ou port différent). C'est essentiel quand votre API Symfony doit communiquer avec une application frontend sur un domaine différent.

### 2. Installation

D'abord, installez le bundle NelmioCorsBundle :

```bash
composer require nelmio/cors-bundle
```

### 3. Configuration détaillée

Créez ou modifiez le fichier `config/packages/nelmio_cors.yaml` :

```yaml
nelmio_cors:
    defaults:
        origin_regex: true
        allow_origin: ['%env(CORS_ALLOW_ORIGIN)%']
        allow_methods: ['GET', 'OPTIONS', 'POST', 'PUT', 'PATCH', 'DELETE']
        allow_headers: ['Content-Type', 'Authorization', 'X-Requested-With']
        expose_headers: ['Link', 'X-Total-Count']
        max_age: 3600
    paths:
        '^/api/':
            origin_regex: true
            allow_origin: ['*']
            allow_headers: ['*']
            allow_methods: ['POST', 'PUT', 'GET', 'DELETE', 'PATCH']
            expose_headers: ['Link', 'X-Total-Count']
            max_age: 3600
        '^/admin/':
            allow_origin: ['^https?://admin\.votredomaine\.com$']
            allow_credentials: true
            allow_headers: ['*']
            allow_methods: ['POST', 'PUT', 'GET', 'DELETE']
```

### 4. Configuration des variables d'environnement

Dans `.env` :

```env
###> nelmio/cors-bundle ###
CORS_ALLOW_ORIGIN='^https?://(localhost|127\.0\.0\.1|votredomaine\.com)(:[0-9]+)?$'
###< nelmio/cors-bundle ###
```

Dans `.env.local` pour le développement :

```env
CORS_ALLOW_ORIGIN='^https?://(localhost|127\.0\.0\.1)(:[0-9]+)?$'
```

### 5. Configuration avancée par environnement

#### Pour le développement (`config/packages/dev/nelmio_cors.yaml`) :

```yaml
nelmio_cors:
    paths:
        '^/':
            origin_regex: true
            allow_origin: ['*']
            allow_headers: ['*']
            allow_methods: ['POST', 'PUT', 'GET', 'DELETE', 'OPTIONS']
            max_age: 3600
```

#### Pour la production (`config/packages/prod/nelmio_cors.yaml`) :

```yaml
nelmio_cors:
    paths:
        '^/api/':
            origin_regex: true
            allow_origin: ['^https://api\.votredomaine\.com$']
            allow_headers: ['X-Custom-Auth', 'Content-Type', 'Authorization']
            allow_methods: ['POST', 'PUT', 'GET', 'DELETE']
            max_age: 3600
```

### 6. Gestion des cas particuliers

#### Pour les WebSockets :

```yaml
nelmio_cors:
    paths:
        '^/ws/':
            allow_origin: ['*']
            allow_headers: ['*']
            allow_methods: ['GET']
            expose_headers: []
            max_age: 3600
```

#### Pour les uploads de fichiers :

```yaml
nelmio_cors:
    paths:
        '^/api/media/':
            allow_origin: ['*']
            allow_headers: ['*']
            allow_methods: ['POST', 'OPTIONS']
            max_age: 3600
            allow_credentials: true
```

### 7. Sécurité et bonnes pratiques

1. **Limitation des origines** :
   - En production, spécifiez toujours les domaines exacts plutôt que `*`.
   - Utilisez des expressions régulières pour gérer les sous-domaines.
2. **Headers sensibles** :
   - Limitez les `allow_headers` aux headers nécessaires.
   - N'exposez que les headers requis via `expose_headers`.
3. **Credentials** :
   - Activez `allow_credentials` uniquement si nécessaire.
   - Si activé, vous ne pouvez pas utiliser `*` pour `allow_origin`.
4. **Cache** :
   - Ajustez `max_age` selon vos besoins.
   - Un max\_age plus long (3600) réduit les requêtes preflight.
5. **Vérification** :

```bash
# Testez votre configuration CORS
curl -H "Origin: http://localhost:4200" \
     -H "Access-Control-Request-Method: POST" \
     -H "Access-Control-Request-Headers: X-Requested-With" \
     -X OPTIONS --verbose \
     http://votreapi.com/api/resource
```

La configuration CORS est essentielle pour la sécurité de votre API. Une mauvaise configuration peut soit exposer votre API à des risques de sécurité, soit empêcher les clients légitimes d'y accéder.

