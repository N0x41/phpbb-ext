#!/bin/bash

echo "=== Activity Control API Test Script ==="
echo ""

# Étape 1 : Copier les fichiers mis à jour
echo "Step 1: Copying updated files..."
cp /home/nox/Documents/phpbb-ext/controller/main.php /home/nox/Documents/NiMP/var/www/forum/ext/linkguarder/activitycontrol/controller/main.php
cp /home/nox/Documents/phpbb-ext/config/routing.yml /home/nox/Documents/NiMP/var/www/forum/ext/linkguarder/activitycontrol/config/routing.yml
cp /home/nox/Documents/phpbb-ext/config/services.yml /home/nox/Documents/NiMP/var/www/forum/ext/linkguarder/activitycontrol/config/services.yml
cp /home/nox/Documents/phpbb-ext/event/listener.php /home/nox/Documents/NiMP/var/www/forum/ext/linkguarder/activitycontrol/event/listener.php
echo "✓ Files copied"

# Étape 2 : Vider le cache
echo ""
echo "Step 2: Clearing cache..."
rm -rf /home/nox/Documents/NiMP/var/www/forum/cache/*
echo "✓ Cache cleared"

echo ""
echo "Step 3: Testing API endpoints..."
echo ""
echo "--- Test 1: /ac_node_query with status query ---"
curl -s -X POST http://localhost:8080/forum/app.php/ac_node_query \
     -H "Content-Type: application/json" \
     -d '{"query":"status"}' | python3 -m json.tool 2>/dev/null || echo "ERROR: Not JSON response"

echo ""
echo ""
echo "--- Test 2: /activitycontrol/webhook/notify ---"
curl -s -X POST http://localhost:8080/forum/app.php/activitycontrol/webhook/notify \
     -H "Content-Type: application/json" \
     -d '{"event":"ip_list_updated","version":1,"total_ips":17474,"timestamp":"2025-10-26 23:00:00"}' | python3 -m json.tool 2>/dev/null || echo "ERROR: Not JSON response"

echo ""
echo ""
echo "=== Test completed ==="
