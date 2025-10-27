# Fix nginx pour les routes phpBB

## Problème

L'API REST de l'extension ne fonctionnait pas car nginx redirigait toutes les requêtes vers `/forum/index.php` au lieu de `/forum/app.php`.

Les routes des extensions phpBB 3.3.x utilisent **app.php** et non index.php.

## Solution

Modifier la configuration nginx (`/etc/nginx/conf.d/default.conf` ou équivalent) :

### Avant (incorrect)
```nginx
location @rewriteforum {
    rewrite ^(.*)$ /forum/index.php/$1 last;
}
```

### Après (correct)
```nginx
location @rewriteforum {
    rewrite ^(.*)$ /forum/app.php/$1 last;
}
```

Également dans la section PHP :
```nginx
try_files $uri $uri/ /forum/app.php$is_args$args;
```

## Commandes NiMP

```bash
# Modifier le fichier de configuration
sed -i 's|/forum/index.php|/forum/app.php|g' /home/nox/Documents/NiMP/etc/nginx/conf.d/default.conf

# Recharger nginx
docker exec web nginx -t
docker exec web nginx -s reload
```

## Résultat

✅ L'API fonctionne parfaitement :
- `POST /forum/app.php/ac_node_query` → JSON
- `POST /forum/app.php/notify` → JSON

## Test

```bash
curl -X POST http://localhost:8080/forum/app.php/ac_node_query \
     -H "Content-Type: application/json" \
     -d '{"query":"status"}' | python3 -m json.tool
```

Réponse attendue :
```json
{
    "status": "ok",
    "node_type": "phpbb_forum",
    "extension_version": "1.0.0",
    ...
}
```
