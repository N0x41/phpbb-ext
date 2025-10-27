# Problème API Endpoint - Diagnostic

## Situation actuelle

L'extension Activity Control est **installée et activée** mais les endpoints API retournent du HTML au lieu de JSON.

## Tests effectués

1. ✅ Routes définies dans `/config/routing.yml`
2. ✅ Contrôleur `main.php` avec méthodes `node_query()` et `webhook_notification()`
3. ✅ Service défini dans `/config/services.yml`
4. ✅ Extension rechargée (disable/enable)
5. ✅ Cache vidé complètement
6. ✅ Routes présentes dans `/cache/production/url_generator.php`
7. ❌ **Mais phpBB renvoie la page d'accueil HTML au lieu d'exécuter le contrôleur**

## Diagnostic

### Routes dans le cache
Les routes **SONT** dans le cache Symfony :
```php
'linkguarder_activitycontrol_node_query' => array (
    '_controller' => 'linkguarder.activitycontrol.controller:node_query',
    'path' => '/ac_node_query'
)
```

### Requêtes testées
```bash
curl -X POST http://localhost:8080/forum/app.php/ac_node_query \
     -H "Content-Type: application/json" \
     -d '{"query":"status"}'
```

**Résultat**: Page HTML phpBB au lieu de JSON

### Code mort supprimé
- ✅ Méthode `handle()` supprimée du contrôleur
- ✅ Route `/activitycontrol/{name}` supprimée du routing.yml
- ✅ Code de démo supprimé du listener

## Hypothèses

### 1. phpBB n'utilise pas routing.yml pour les extensions (❓)
Peut-être que phpBB 3.3 nécessite une autre méthode pour déclarer les routes dans les extensions.

### 2. URL Matcher manquant
Le fichier `/cache/production/url_matcher.php` n'existe pas. Peut-être que phpBB le génère à la volée ou utilise un autre système.

### 3. Ordre de priorité des routes
Peut-être qu'une route plus générique intercepte nos appels avant qu'ils n'atteignent nos routes.

## Actions à effectuer

### Test 1: Vérifier l'état de l'extension
Exécutez dans le Docker :
```bash
cd /var/www/html/forum
php check_extension_status.php
```

Ce script vérifiera :
- Si l'extension est activée
- Si les routes sont chargées dans le `RouteCollection`
- Si le service contrôleur est accessible

### Test 2: Vérifier la syntaxe du routing.yml
Comparer notre `routing.yml` avec d'autres extensions phpBB pour voir si la syntaxe est correcte.

### Test 3: Ajouter des logs de debug
Modifier `main.php` pour logger au début de `node_query()` :
```php
public function node_query()
{
    file_put_contents('/tmp/api_debug.log', date('Y-m-d H:i:s') . " - node_query() called\n", FILE_APPEND);
    
    // ... reste du code
}
```

Si ce log n'apparaît jamais, c'est que le routeur n'appelle jamais notre contrôleur.

## Fichiers modifiés

1. `/home/nox/Documents/phpbb-ext/controller/main.php`
   - Méthode `handle()` supprimée
   
2. `/home/nox/Documents/phpbb-ext/config/routing.yml`
   - Route `/activitycontrol/{name}` supprimée
   - `methods: [POST]` supprimé (peut causer des problèmes)
   
3. `/home/nox/Documents/phpbb-ext/event/listener.php`
   - Code de démo supprimé

## Documentation utile

- phpBB 3.3 utilise Symfony Routing Component
- Les routes sont compilées dans `/cache/production/url_generator.php`
- Le point d'entrée est `/app.php` qui utilise `HttpKernel`

## Prochaines étapes

1. Exécuter `check_extension_status.php` pour diagnostic
2. Si les routes ne sont pas dans la `RouteCollection`, chercher comment phpBB charge les routes des extensions
3. Consulter la documentation phpBB 3.3 sur le routing des extensions
4. Potentiellement regarder le code source d'autres extensions avec des routes API

## Commandes utiles

```bash
# Vider le cache
rm -rf /var/www/html/forum/cache/*

# Recharger l'extension
php reload_extension.php

# Tester l'API
curl -X POST http://localhost:8080/forum/app.php/ac_node_query \
     -H "Content-Type: application/json" \
     -d '{"query":"status"}'

# Vérifier les routes en cache
grep "ac_node_query" /var/www/html/forum/cache/production/url_generator.php
```
