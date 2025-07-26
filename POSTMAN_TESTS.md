# 🧪 Tests Postman pour API Woyofal

## 🚀 URL de base : `http://localhost:8081`

## 📋 Collection de tests Postman

### 1. **Test de connectivité**
```
GET http://localhost:8081/api/woyofal/ping
```
**Réponse attendue :**
```json
{
  "data": {
    "message": "pong",
    "timestamp": 1706227200.123,
    "server_time": "2025-01-26 01:20:00"
  },
  "statut": "success",
  "code": 200,
  "message": "API accessible"
}
```

### 2. **Informations API**
```
GET http://localhost:8081/
```

### 3. **Statut de l'API**
```
GET http://localhost:8081/api/woyofal/status
```

### 4. **Tranches tarifaires**
```
GET http://localhost:8081/api/woyofal/tranches
```

### 5. **🎯 ACHAT WOYOFAL (Principal)**
```
POST http://localhost:8081/api/woyofal/achat
Content-Type: application/json

{
  "compteur": "123456789",
  "montant": 5000
}
```

**Réponse succès attendue :**
```json
{
  "data": {
    "compteur": "123456789",
    "reference": "WYF250126001234",
    "code": "12345678901234567890",
    "date": "26/01/2025 01:20:15",
    "tranche": "Tranche 1 - Social",
    "prix": "75 FCFA/kWh",
    "nbreKwt": "66,67 kWh",
    "client": "Amadou DIOP"
  },
  "statut": "success",
  "code": 200,
  "message": "Achat effectué avec succès"
}
```

### 6. **Simulation d'achat**
```
POST http://localhost:8081/api/woyofal/simulate
Content-Type: application/json

{
  "compteur": "123456789",
  "montant": 10000
}
```

### 7. **Tests d'erreurs**

#### Compteur inexistant
```
POST http://localhost:8081/api/woyofal/achat
Content-Type: application/json

{
  "compteur": "999999999",
  "montant": 5000
}
```

#### Montant invalide
```
POST http://localhost:8081/api/woyofal/achat
Content-Type: application/json

{
  "compteur": "123456789",
  "montant": 100
}
```

#### Données manquantes
```
POST http://localhost:8081/api/woyofal/achat
Content-Type: application/json

{
  "compteur": ""
}
```

## 🔧 Configuration Postman

### Headers nécessaires
```
Content-Type: application/json
Accept: application/json
```

### Variables d'environnement Postman
```
base_url = http://localhost:8081
compteur_test = 123456789
montant_test = 5000
```

## 📊 Compteurs de test disponibles

| Compteur | Client | Localisation |
|----------|--------|--------------|
| 123456789 | Amadou DIOP | Medina, Dakar |
| 987654321 | Fatou FALL | HLM, Dakar |
| 456789123 | Moussa NDIAYE | Keur Massar, Pikine |
| 789123456 | Aïcha SECK | Grand Yoff, Dakar |

## 💰 Montants de test

| Montant | kWh attendus (Tranche 1) | Description |
|---------|--------------------------|-------------|
| 1000 | ~13.33 kWh | Montant minimum |
| 2500 | ~33.33 kWh | Petit achat |
| 5000 | ~66.67 kWh | Achat moyen |
| 7500 | 100 kWh exactement | Fin Tranche 1 |
| 10000 | Mix Tranche 1+2 | Test tranches multiples |
| 25000 | Mix toutes tranches | Gros achat |

## 🚨 Tests de limites

### Montant trop petit
```json
{"compteur": "123456789", "montant": 400}
```
**Erreur attendue :** "Le montant minimum est de 500 FCFA"

### Montant trop grand
```json
{"compteur": "123456789", "montant": 1500000}
```
**Erreur attendue :** "Le montant maximum est de 1 000 000 FCFA"

### Compteur invalide
```json
{"compteur": "12345", "montant": 5000}
```
**Erreur attendue :** "Le numéro de compteur doit contenir entre 8 et 12 chiffres"

## ⚡ Test de charge (optionnel)

Pour tester la performance et la journalisation :
1. Importer la collection dans Postman
2. Utiliser le "Collection Runner"
3. Exécuter 10-50 requêtes rapidement
4. Vérifier les logs dans PGAdmin (logs_achats)

## 🔍 Vérification en base de données

Connectez-vous à PGAdmin (http://localhost:5052) pour vérifier :

1. **Table achats_woyofal** : Achats enregistrés
2. **Table logs_achats** : Toutes les requêtes journalisées
3. **Table consommations_mensuelles** : Consommation par client

---

**Commande rapide pour tester :**
```bash
curl -X POST http://localhost:8081/api/woyofal/achat \
  -H "Content-Type: application/json" \
  -d '{"compteur":"123456789","montant":5000}'
```
