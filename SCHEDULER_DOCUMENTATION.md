# 📅 Documentation du Système de Tâches Automatiques (Scheduler)

## 🎯 Objectif

Le système de scheduler gère automatiquement les tâches récurrentes de l'application AppWoyofal, notamment :
- **Reset mensuel des tranches tarifaires** (1er de chaque mois à 00:00)
- **Nettoyage quotidien des logs** (chaque jour à 02:00)

## 📦 Dépendance utilisée : `dragonmantank/cron-expression`

### Pourquoi cette dépendance ?
- **Légère et performante** : Pas de base de données supplémentaire
- **Compatible PHP 8+** : Utilise les dernières fonctionnalités PHP
- **Expressions cron standard** : `0 0 1 * *` (minuit le 1er de chaque mois)
- **Facile à tester** : Méthodes pour simuler les dates

### Installation
```bash
composer require dragonmantank/cron-expression
```

## 🏗️ Architecture

### Interfaces
- `SchedulerServiceInterface` : Contrat pour les tâches planifiées

### Services
- `SchedulerService` : Implémentation principale avec injection de dépendances

### Scripts
- `scripts/scheduler.php` : Script à exécuter via cron système

## 🔄 Fonctionnement du Reset Mensuel

### 1. Principe IMPORTANT
```php
// ❌ FAUX : Réinitialiser toutes les consommations en base
// ✅ CORRECT : Utiliser la période actuelle pour calculer les tranches

// Dans TrancheCalculatorService.php
public function getCurrentConsommation(int $clientId): ConsommationMensuelle
{
    // Cherche LA CONSOMMATION DU MOIS ACTUEL
    $consommation = $this->consommationRepository->findCurrentByClient($clientId);
    
    if (!$consommation) {
        // Crée automatiquement pour le mois actuel = RESET AUTO !
        $periode = ConsommationMensuelle::getCurrentPeriod();
        $consommation = new ConsommationMensuelle($clientId, $periode['mois'], $periode['annee']);
    }
    
    return $consommation;
}
```

### 2. Reset automatique
- **Pas besoin de vider les données** : On utilise la consommation du mois actuel
- **Nouveau mois = Nouvelle consommation** : Démarre à 0 kWh automatiquement
- **Historique préservé** : Les anciens mois restent intacts

### 3. Vérification du nouveau mois
```php
public function isNewMonth(ConsommationMensuelle $consommation): bool
{
    $periode = ConsommationMensuelle::getCurrentPeriod();
    
    return $consommation->getMois() !== $periode['mois'] || 
           $consommation->getAnnee() !== $periode['annee'];
}
```

## 📋 Expressions Cron configurées

### Reset mensuel
```php
private const MONTHLY_RESET_CRON = '0 0 1 * *';
```
- **Format** : `minute heure jour mois jour_semaine`
- **Signification** : 00:00 le 1er de chaque mois
- **Exemple** : 1er janvier 2025 à 00:00, 1er février 2025 à 00:00, etc.

### Nettoyage quotidien
```php
private const DAILY_CLEANUP_CRON = '0 2 * * *';
```
- **Signification** : 02:00 chaque jour
- **Action** : Supprime les logs > 90 jours

## 🚀 Utilisation

### 1. Exécution manuelle
```bash
cd /path/to/AppWoyofal
php scripts/scheduler.php
```

### 2. Configuration Cron système (Linux/macOS)
```bash
# Éditer le crontab
crontab -e

# Ajouter cette ligne pour exécuter chaque minute (en production: chaque heure)
* * * * * cd /path/to/AppWoyofal && php scripts/scheduler.php >> /var/log/appwoyofal-scheduler.log 2>&1

# Ou plus raisonnablement, chaque heure
0 * * * * cd /path/to/AppWoyofal && php scripts/scheduler.php >> /var/log/appwoyofal-scheduler.log 2>&1
```

### 3. Test du reset mensuel
```php
// Pour forcer un reset (tests uniquement)
$scheduler = new SchedulerService($consommationRepo, $logRepo, $loggerService);
$result = $scheduler->forceMonthlyReset();
```

## 🔍 Monitoring

### Statut des tâches
```php
$status = $scheduler->getLastExecutionStatus();
// Retourne:
// - last_monthly_reset: Date du dernier reset
// - next_monthly_reset: Prochaine exécution
// - system_status: État du système
```

### Informations détaillées
```php
$info = $scheduler->getScheduledTasksInfo();
// Retourne les détails de chaque tâche planifiée
```

## 🧪 Tests

### Tester manuellement
```bash
# 1. Exécuter le scheduler
php scripts/scheduler.php

# 2. Vérifier les logs
tail -f /var/log/appwoyofal-scheduler.log

# 3. Tester un achat pour vérifier les tranches
curl -X POST http://localhost:8081/api/woyofal/achat \
  -H 'Content-Type: application/json' \
  -d '{"compteur":"123456789","montant":5000}'
```

### Test de simulation
```php
// Créer un client avec consommation du mois précédent
// Faire un achat -> Doit démarrer en Tranche 1 (reset auto)
```

## ⚠️ Points importants

1. **Le reset est AUTOMATIQUE** par design de code, pas par suppression de données
2. **Le scheduler sert surtout au nettoyage** et à la maintenance
3. **Chaque mois = Nouvelle table consommation_mensuelle** = Reset naturel
4. **Les logs permettent de tracer** toutes les opérations

## 🔧 Configuration Production

### Variables d'environnement
```env
# Dans .env.production
SCHEDULER_ENABLED=true
SCHEDULER_LOG_RETENTION_DAYS=90
SCHEDULER_MONTHLY_RESET_TIME="0 0 1 * *"
```

### Surveillance
- Vérifier les logs quotidiennement
- Monitorer l'espace disque (logs)
- Alertes si le scheduler échoue > 2 fois

---

Cette architecture garantit que **les tranches se remettent bien à zéro chaque mois** tout en préservant l'historique et en permettant un monitoring complet du système.
