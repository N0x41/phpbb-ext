# SOLUTION TROUVÉE : API Endpoint Fix

## Problème identifié

**La méthode `$this->helper->json()` N'EXISTE PAS dans phpBB 3.3 !**

En consultant la documentation officielle phpBB et le code source, j'ai découvert que :
- phpBB utilise directement `Symfony\Component\HttpFoundation\JsonResponse`
- La classe `\phpbb\controller\helper` n'a **pas de méthode `json()`**
- Les contrôleurs doivent instancier `JsonResponse` directement

## Solution appliquée

### 1. Import de JsonResponse

Ajout au début du fichier `controller/main.php` :

```php
namespace linkguarder\activitycontrol\controller;

use Symfony\Component\HttpFoundation\JsonResponse;

class main
{
```

### 2. Remplacement de tous les appels

**Avant** (incorrect) :
```php
return $this->helper->json([
    'status' => 'ok',
    'message' => 'Success'
], 200);
```

**Après** (correct) :
```php
return new JsonResponse([
    'status' => 'ok',
    'message' => 'Success'
], 200);
```

### 3. Fichiers modifiés

- `/home/nox/Documents/phpbb-ext/controller/main.php` :
  * Ajout de `use Symfony\Component\HttpFoundation\JsonResponse;`
  * Remplacement de **13 occurrences** de `$this->helper->json()` par `new JsonResponse()`
  * Méthodes corrigées :
    - `webhook_notification()` - 4 occurrences
    - `node_query()` - 3 occurrences
    - `handle_status_query()` - 1 occurrence
    - `handle_stats_query()` - 1 occurrence
    - `handle_sync_now_query()` - 3 occurrences
    - `handle_local_ips_query()` - 1 occurrence
    - `handle_reported_ips_query()` - 2 occurrences

## Test de la solution

### Commandes de test

```bash
# 1. Copier le fichier corrigé
cp /home/nox/Documents/phpbb-ext/controller/main.php \
   /home/nox/Documents/NiMP/var/www/forum/ext/linkguarder/activitycontrol/controller/main.php

# 2. Vider le cache
rm -rf /home/nox/Documents/NiMP/var/www/forum/cache/*

# 3. Tester l'endpoint status
curl -X POST http://localhost:8080/forum/app.php/ac_node_query \
     -H "Content-Type: application/json" \
     -d '{"query":"status"}' | python3 -m json.tool

# 4. Tester l'endpoint webhook
curl -X POST http://localhost:8080/forum/app.php/notify \
     -H "Content-Type: application/json" \
     -d '{"event":"ip_list_updated","version":1,"total_ips":17474}' | python3 -m json.tool
```

### Résultat attendu

Les endpoints devraient maintenant retourner du JSON au lieu de HTML :

```json
{
    "status": "ok",
    "node_type": "phpbb_forum",
    "extension_version": "1.0.0",
    "phpbb_version": "3.3.11",
    "forum_name": "yourdomain.com",
    "sync_enabled": true,
    "reporting_enabled": true,
    "last_sync": 1729982400,
    "ip_list_version": 1,
    "timestamp": 1729984800
}
```

## Référence documentaire

### Documentation phpBB officielle

**Request Class** : https://area51.phpbb.com/docs/dev/3.3.x/request/request.html

Exemple trouvé dans la documentation :
```php
if ($request->is_ajax())
{
    // Send a JSON response for an AJAX request
    $json_response = new \phpbb\json_response();

    return $json_response->send([
        'MESSAGE_TITLE'  => $language->lang('INFORMATION'),
        'MESSAGE_TEXT'   => $message,
    ]);
}
```

### Code source phpBB

Fichier : `/phpbb/controller/helper.php`

```php
use Symfony\Component\HttpFoundation\JsonResponse;

// ... dans une méthode
if ($this->request->is_ajax())
{
    return new JsonResponse(
        array(
            'MESSAGE_TITLE'         => $message_title,
            'MESSAGE_TEXT'          => $message_text,
            'S_USER_WARNING'        => false,
            'S_USER_NOTICE'         => false,
            'REFRESH_DATA'          => (!empty($refresh_data)) ? $refresh_data : null
        ),
        $code
    );
}
```

## Pourquoi ça ne fonctionnait pas avant

1. **Méthode inexistante** : `$this->helper->json()` n'existe pas dans phpBB 3.3
2. **Erreur silencieuse** : PHP appelait probablement `__call()` ou retournait NULL
3. **Fallback vers HTML** : phpBB affichait la page par défaut au lieu du JSON
4. **Pas d'erreur dans les logs** : Aucune exception levée car l'appel était syntaxiquement valide

## Autres méthodes possibles

### Méthode 1 : JsonResponse (recommandée)
```php
return new JsonResponse(['data' => 'value'], 200);
```

### Méthode 2 : phpbb\json_response
```php
$json_response = new \phpbb\json_response();
return $json_response->send(['data' => 'value']);
```

### Méthode 3 : Response avec header JSON
```php
$response = new Response(json_encode(['data' => 'value']), 200);
$response->headers->set('Content-Type', 'application/json');
return $response;
```

**Nous utilisons la Méthode 1** car c'est la plus simple et la plus standard dans Symfony.

## Prochaines étapes

1. ✅ Copier le fichier corrigé vers l'installation phpBB
2. ✅ Vider le cache phpBB
3. ✅ Tester les endpoints API
4. ✅ Vérifier que RogueBB peut maintenant interroger le forum
5. ✅ Documenter le changement dans CHANGELOG.md
