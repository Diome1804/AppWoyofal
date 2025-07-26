# 🚀 Déploiement AppWoyofal sur Render + Railway

## 📋 Prérequis

1. **Compte Render** créé
2. **Base de données Railway** configurée et en cours d'exécution
3. **Repository GitHub** avec votre code

## 🔑 Étape 1: Récupérer les informations Railway

Allez sur votre dashboard Railway et notez:

```
DB_HOST=xxxx.railway.app (ou xxxx.railway.internal)
DB_PORT=5432
DB_DATABASE=railway_xxxxx
DB_USERNAME=postgres
DB_PASSWORD=xxxxxxxxxxxxx
```

## 📝 Étape 2: Compléter render.yaml

Remplacez les valeurs dans `render.yaml` avec vos vraies informations Railway:

```yaml
envVars:
  - key: DB_HOST
    value: VOTRE_HOST_RAILWAY_ICI
  - key: DB_PORT  
    value: VOTRE_PORT_RAILWAY_ICI
  - key: DB_DATABASE
    value: VOTRE_NOM_DB_RAILWAY_ICI
  - key: DB_USERNAME
    value: VOTRE_USERNAME_RAILWAY_ICI
  - key: DB_PASSWORD
    value: VOTRE_PASSWORD_RAILWAY_ICI
```

## 🚀 Étape 3: Déployer sur Render

1. **Connecter le repo GitHub**:
   - Allez sur [render.com](https://render.com)
   - Cliquez "New +" → "Web Service"
   - Connectez votre repository GitHub

2. **Configuration automatique**:
   - Render détectera automatiquement le fichier `render.yaml`
   - Vérifiez que toutes les variables d'environnement sont correctes

3. **Déployer**:
   - Cliquez "Create Web Service"
   - Render va automatiquement:
     - Installer les dépendances PHP (`composer install`)
     - Exécuter les migrations (`php scripts/setup_database.php`)
     - Créer les tranches tarifaires
     - Démarrer le serveur PHP

## ✅ Étape 4: Vérifier le déploiement

Une fois déployé, testez l'API:

```bash
curl -X POST https://VOTRE-APP.onrender.com/api/woyofal/achat \
  -H "Content-Type: application/json" \
  -d '{"compteur":"123456789","montant":5000}'
```

## 🔧 Variables d'environnement importantes

```
APP_ENV=production          # Active le mode production
DB_CONNECTION=pgsql         # Type de base de données
DB_HOST=                    # Host Railway
DB_PORT=                    # Port Railway (5432)
DB_DATABASE=                # Nom de la DB Railway
DB_USERNAME=                # Username Railway
DB_PASSWORD=                # Password Railway
```

## 📊 Monitoring

- **Logs Render**: Dashboard Render → Votre service → Logs
- **Logs Railway**: Dashboard Railway → Votre database → Logs
- **Health check**: `https://VOTRE-APP.onrender.com/` (devrait retourner du JSON)

## 🐛 Résolution de problèmes

### Erreur de connexion DB
- Vérifiez que Railway DB est accessible de l'extérieur
- Vérifiez les variables d'environnement dans Render

### Erreur de migration
- Regardez les logs Render pour voir l'erreur exacte
- La DB Railway doit exister et être vide au premier déploiement

### 500 Internal Server Error  
- Vérifiez les logs PHP dans Render
- Problème probable: variables d'environnement manquantes
