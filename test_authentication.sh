#!/bin/bash
# Script de test du système d'authentification

echo "🧪 Test du système d'authentification RSA"
echo "=========================================="
echo ""

# Test 1: Vérifier que le fichier pkem existe
echo "1️⃣  Vérification de la clé publique..."
if [ -f "/home/nox/Documents/NiMP/var/www/forum/ext/linkguarder/activitycontrol/data/pkem" ]; then
    echo "   ✓ Fichier pkem trouvé"
    SIZE=$(stat -f%z "/home/nox/Documents/NiMP/var/www/forum/ext/linkguarder/activitycontrol/data/pkem" 2>/dev/null || stat -c%s "/home/nox/Documents/NiMP/var/www/forum/ext/linkguarder/activitycontrol/data/pkem")
    echo "   ✓ Taille: $SIZE octets"
else
    echo "   ✗ Fichier pkem introuvable !"
fi

echo ""

# Test 2: Vérifier que le service existe
echo "2️⃣  Vérification du service server_authenticator..."
if [ -f "/home/nox/Documents/NiMP/var/www/forum/ext/linkguarder/activitycontrol/service/server_authenticator.php" ]; then
    echo "   ✓ Service server_authenticator.php trouvé"
else
    echo "   ✗ Service server_authenticator.php introuvable !"
fi

echo ""

# Test 3: Vérifier la route
echo "3️⃣  Vérification de la route..."
if grep -q "ac_authenticated_write" "/home/nox/Documents/NiMP/var/www/forum/ext/linkguarder/activitycontrol/config/routing.yml"; then
    echo "   ✓ Route ac_authenticated_write trouvée dans routing.yml"
else
    echo "   ✗ Route ac_authenticated_write introuvable !"
fi

echo ""

# Test 4: Vérifier le service dans services.yml
echo "4️⃣  Vérification de la déclaration du service..."
if grep -q "linkguarder.activitycontrol.server_authenticator" "/home/nox/Documents/NiMP/var/www/forum/ext/linkguarder/activitycontrol/config/services.yml"; then
    echo "   ✓ Service déclaré dans services.yml"
else
    echo "   ✗ Service non déclaré dans services.yml !"
fi

echo ""

# Test 5: Tester l'endpoint (sans auth, juste pour voir la réponse)
echo "5️⃣  Test de l'endpoint ac_authenticated_write..."
RESPONSE=$(curl -s -X POST http://localhost:8080/forum/app.php/ac_authenticated_write \
  -H "Content-Type: application/json" \
  -d '{}' 2>&1)

if echo "$RESPONSE" | grep -q "DOCTYPE"; then
    echo "   ✗ Erreur HTML retournée (503 ou erreur PHP)"
    echo "   Réponse: ${RESPONSE:0:100}..."
elif echo "$RESPONSE" | grep -q "error"; then
    echo "   ✓ Endpoint accessible (erreur attendue sans auth)"
    echo "   Réponse: $RESPONSE"
else
    echo "   ? Réponse inattendue"
    echo "   Réponse: $RESPONSE"
fi

echo ""
echo "=========================================="
echo "Recommandation:"
echo "- Vider le cache: rm -rf /home/nox/Documents/NiMP/var/www/forum/cache/production/*"
echo "- Ou désactiver/réactiver l'extension dans l'ACP phpBB"
