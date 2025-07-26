<?php

namespace Src\Service;

use Src\Service\Interface\ValidationServiceInterface;

class ValidationService implements ValidationServiceInterface
{
    // Constantes de validation
    private const MONTANT_MIN = 500;
    private const MONTANT_MAX = 1000000;
    private const COMPTEUR_REGEX = '/^[0-9]{8,12}$/';
    private const SENEGAL_PHONE_PREFIXES = ['70', '75', '76', '77', '78'];

    public function validateAchatRequest(array $requestData): array
    {
        $errors = [];
        
        // Validation du numéro de compteur
        if (empty($requestData['compteur'])) {
            $errors['compteur'] = 'Le numéro de compteur est obligatoire';
        } else {
            $compteurValidation = $this->validateNumeroCompteur($requestData['compteur']);
            if (!$compteurValidation['valid']) {
                $errors['compteur'] = $compteurValidation['message'];
            }
        }
        
        // Validation du montant
        if (!isset($requestData['montant'])) {
            $errors['montant'] = 'Le montant est obligatoire';
        } else {
            $montantValidation = $this->validateMontant((float)$requestData['montant']);
            if (!$montantValidation['valid']) {
                $errors['montant'] = $montantValidation['message'];
            }
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'message' => empty($errors) ? 'Validation réussie' : 'Erreurs de validation détectées'
        ];
    }

    public function validateNumeroCompteur(string $numero): array
    {
        // Nettoyer le numéro (supprimer espaces, tirets, etc.)
        $numero = preg_replace('/[^0-9]/', '', trim($numero));
        
        if (empty($numero)) {
            return [
                'valid' => false,
                'message' => 'Le numéro de compteur ne peut pas être vide'
            ];
        }
        
        if (!preg_match(self::COMPTEUR_REGEX, $numero)) {
            return [
                'valid' => false,
                'message' => 'Le numéro de compteur doit contenir entre 8 et 12 chiffres'
            ];
        }
        
        return [
            'valid' => true,
            'cleaned_numero' => $numero,
            'message' => 'Numéro de compteur valide'
        ];
    }

    public function validateMontant(float $montant): array
    {
        if ($montant <= 0) {
            return [
                'valid' => false,
                'message' => 'Le montant doit être supérieur à 0'
            ];
        }
        
        if ($montant < self::MONTANT_MIN) {
            return [
                'valid' => false,
                'message' => sprintf('Le montant minimum est de %d FCFA', self::MONTANT_MIN)
            ];
        }
        
        if ($montant > self::MONTANT_MAX) {
            return [
                'valid' => false,
                'message' => sprintf('Le montant maximum est de %s FCFA', number_format(self::MONTANT_MAX, 0, ',', ' '))
            ];
        }
        
        // Vérifier que c'est un multiple de 50 (pratique courante)
        if ($montant % 50 !== 0) {
            return [
                'valid' => false,
                'message' => 'Le montant doit être un multiple de 50 FCFA'
            ];
        }
        
        return [
            'valid' => true,
            'message' => 'Montant valide'
        ];
    }

    public function validateClientData(array $clientData): array
    {
        $errors = [];
        
        // Validation du nom
        if (empty($clientData['nom'])) {
            $errors['nom'] = 'Le nom est obligatoire';
        } elseif (strlen(trim($clientData['nom'])) < 2) {
            $errors['nom'] = 'Le nom doit contenir au moins 2 caractères';
        } elseif (strlen(trim($clientData['nom'])) > 100) {
            $errors['nom'] = 'Le nom ne peut pas dépasser 100 caractères';
        }
        
        // Validation du prénom
        if (empty($clientData['prenom'])) {
            $errors['prenom'] = 'Le prénom est obligatoire';
        } elseif (strlen(trim($clientData['prenom'])) < 2) {
            $errors['prenom'] = 'Le prénom doit contenir au moins 2 caractères';
        } elseif (strlen(trim($clientData['prenom'])) > 100) {
            $errors['prenom'] = 'Le prénom ne peut pas dépasser 100 caractères';
        }
        
        // Validation de l'email (optionnel)
        if (!empty($clientData['email'])) {
            if (!$this->validateEmail($clientData['email'])) {
                $errors['email'] = 'L\'adresse email n\'est pas valide';
            }
        }
        
        // Validation du téléphone (optionnel)
        if (!empty($clientData['telephone'])) {
            if (!$this->validateSenegalPhone($clientData['telephone'])) {
                $errors['telephone'] = 'Le numéro de téléphone n\'est pas valide (format sénégalais attendu)';
            }
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'message' => empty($errors) ? 'Données client valides' : 'Erreurs dans les données client'
        ];
    }

    public function validateCompteurData(array $compteurData): array
    {
        $errors = [];
        
        // Validation du numéro
        if (empty($compteurData['numero'])) {
            $errors['numero'] = 'Le numéro de compteur est obligatoire';
        } else {
            $numeroValidation = $this->validateNumeroCompteur($compteurData['numero']);
            if (!$numeroValidation['valid']) {
                $errors['numero'] = $numeroValidation['message'];
            }
        }
        
        // Validation du client_id
        if (empty($compteurData['client_id']) || !is_numeric($compteurData['client_id'])) {
            $errors['client_id'] = 'L\'identifiant client est obligatoire et doit être numérique';
        }
        
        // Validation de l'adresse (optionnel)
        if (!empty($compteurData['adresse']) && strlen($compteurData['adresse']) > 500) {
            $errors['adresse'] = 'L\'adresse ne peut pas dépasser 500 caractères';
        }
        
        // Validation du quartier (optionnel)
        if (!empty($compteurData['quartier']) && strlen($compteurData['quartier']) > 100) {
            $errors['quartier'] = 'Le quartier ne peut pas dépasser 100 caractères';
        }
        
        // Validation de la ville
        if (!empty($compteurData['ville']) && strlen($compteurData['ville']) > 100) {
            $errors['ville'] = 'La ville ne peut pas dépasser 100 caractères';
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'message' => empty($errors) ? 'Données compteur valides' : 'Erreurs dans les données compteur'
        ];
    }

    public function isValidJson(string $json): bool
    {
        json_decode($json);
        return json_last_error() === JSON_ERROR_NONE;
    }

    public function sanitizeInput(array $data): array
    {
        $sanitized = [];
        
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                // Nettoyer la chaîne
                $sanitized[$key] = trim(strip_tags($value));
            } elseif (is_numeric($value)) {
                // Garder les valeurs numériques
                $sanitized[$key] = $value;
            } elseif (is_array($value)) {
                // Récursion pour les tableaux
                $sanitized[$key] = $this->sanitizeInput($value);
            } else {
                // Autres types (bool, null, etc.)
                $sanitized[$key] = $value;
            }
        }
        
        return $sanitized;
    }

    public function validateDate(string $date, string $format = 'Y-m-d'): bool
    {
        $d = \DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) === $date;
    }

    public function validateEmail(string $email): bool
    {
        // Nettoyage de base
        $email = filter_var(trim($email), FILTER_SANITIZE_EMAIL);
        
        // Validation
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }
        
        // Vérifications supplémentaires
        if (strlen($email) > 150) {
            return false;
        }
        
        // Vérifier la présence d'un domaine
        $parts = explode('@', $email);
        if (count($parts) !== 2) {
            return false;
        }
        
        [$local, $domain] = $parts;
        
        // Validation du domaine (basique)
        if (strlen($domain) < 3 || !str_contains($domain, '.')) {
            return false;
        }
        
        return true;
    }

    public function validateSenegalPhone(string $phone): bool
    {
        // Nettoyer le numéro (supprimer espaces, tirets, parenthèses, etc.)
        $cleanPhone = preg_replace('/[^0-9]/', '', trim($phone));
        
        // Supprimer l'indicatif pays si présent (+221 ou 221)
        if (str_starts_with($cleanPhone, '221')) {
            $cleanPhone = substr($cleanPhone, 3);
        }
        
        // Vérifier la longueur (9 chiffres pour le Sénégal)
        if (strlen($cleanPhone) !== 9) {
            return false;
        }
        
        // Vérifier que ça commence par un préfixe valide
        $prefix = substr($cleanPhone, 0, 2);
        
        return in_array($prefix, self::SENEGAL_PHONE_PREFIXES);
    }

    /**
     * Valide un code de recharge Woyofal
     */
    public function validateCodeRecharge(string $code): array
    {
        $code = trim($code);
        
        if (empty($code)) {
            return [
                'valid' => false,
                'message' => 'Le code de recharge ne peut pas être vide'
            ];
        }
        
        if (strlen($code) !== 20 || !ctype_digit($code)) {
            return [
                'valid' => false,
                'message' => 'Le code de recharge doit contenir exactement 20 chiffres'
            ];
        }
        
        return [
            'valid' => true,
            'message' => 'Code de recharge valide'
        ];
    }

    /**
     * Valide une référence d'achat
     */
    public function validateReference(string $reference): array
    {
        $reference = trim($reference);
        
        if (empty($reference)) {
            return [
                'valid' => false,
                'message' => 'La référence ne peut pas être vide'
            ];
        }
        
        // Format attendu: WYF + date YYMMDD + 6 chiffres
        if (!preg_match('/^WYF\d{12}$/', $reference)) {
            return [
                'valid' => false,
                'message' => 'Format de référence invalide (attendu: WYFyymmddNNNNNN)'
            ];
        }
        
        return [
            'valid' => true,
            'message' => 'Référence valide'
        ];
    }

    /**
     * Valide les paramètres de recherche/filtrage
     */
    public function validateSearchParams(array $params): array
    {
        $errors = [];
        $cleaned = [];
        
        // Validation de la limite
        if (isset($params['limit'])) {
            $limit = (int)$params['limit'];
            if ($limit < 1 || $limit > 1000) {
                $errors['limit'] = 'La limite doit être entre 1 et 1000';
            } else {
                $cleaned['limit'] = $limit;
            }
        }
        
        // Validation de l'offset
        if (isset($params['offset'])) {
            $offset = (int)$params['offset'];
            if ($offset < 0) {
                $errors['offset'] = 'L\'offset ne peut pas être négatif';
            } else {
                $cleaned['offset'] = $offset;
            }
        }
        
        // Validation des dates
        if (isset($params['date_debut'])) {
            if (!$this->validateDate($params['date_debut'], 'Y-m-d')) {
                $errors['date_debut'] = 'Format de date de début invalide (attendu: YYYY-MM-DD)';
            } else {
                $cleaned['date_debut'] = $params['date_debut'];
            }
        }
        
        if (isset($params['date_fin'])) {
            if (!$this->validateDate($params['date_fin'], 'Y-m-d')) {
                $errors['date_fin'] = 'Format de date de fin invalide (attendu: YYYY-MM-DD)';
            } else {
                $cleaned['date_fin'] = $params['date_fin'];
            }
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'cleaned_params' => $cleaned,
            'message' => empty($errors) ? 'Paramètres valides' : 'Erreurs dans les paramètres'
        ];
    }
}
