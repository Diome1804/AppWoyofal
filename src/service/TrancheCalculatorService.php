<?php

namespace Src\Service;

use Src\Entity\TrancheToarifaire;
use Src\Entity\ConsommationMensuelle;
use Src\Repository\Interface\TrancheToarifaireRepositoryInterface;
use Src\Repository\Interface\ConsommationMensuelleRepositoryInterface;
use Src\Service\Interface\TrancheCalculatorServiceInterface;

class TrancheCalculatorService implements TrancheCalculatorServiceInterface
{
    public function __construct(
        private readonly TrancheToarifaireRepositoryInterface $trancheRepository,
        private readonly ConsommationMensuelleRepositoryInterface $consommationRepository
    ) {}
    
    public function calculateTrancheForClient(int $clientId, float $montant): array
    {
        try {
            // 1. Obtenir la consommation mensuelle actuelle
            $consommationActuelle = $this->getCurrentConsommation($clientId);
            
            // 2. Vérifier si c'est un nouveau mois (reset des tranches)
            if ($this->isNewMonth($consommationActuelle)) {
                $consommationActuelle = $this->resetMonthlyConsommation($clientId);
            }
            
            // 3. Calculer les kWh obtenus pour le montant
            $calculResult = $this->calculateKwhForAmount($montant, $clientId);
            
            return [
                'success' => true,
                'consommation_actuelle' => $consommationActuelle,
                'kwh_achetes' => $calculResult['kwh_total'],
                'prix_unitaire' => $calculResult['prix_unitaire'],
                'tranche' => $calculResult['tranche_finale'],
                'details_calcul' => $calculResult['details']
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Erreur lors du calcul de tranche : ' . $e->getMessage()
            ];
        }
    }
    
    public function determineTrancheForConsommation(float $consommationKwh): ?TrancheToarifaire
    {
        return $this->trancheRepository->findForConsommation($consommationKwh);
    }
    
    public function calculateKwhForAmount(float $montant, int $clientId): array
    {
        // Obtenir la consommation actuelle pour déterminer où on en est dans les tranches
        $consommationActuelle = $this->getCurrentConsommation($clientId);
        $kwhDejaConsommes = $consommationActuelle->getKwhTotal();
        
        // Obtenir toutes les tranches ordonnées
        $tranches = $this->trancheRepository->findAllActives();
        
        $result = [
            'kwh_total' => 0.0,
            'prix_unitaire' => 0.0,
            'tranche_finale' => null,
            'details' => []
        ];
        
        $montantRestant = $montant;
        $kwhCumule = $kwhDejaConsommes;
        $montantTotal = 0.0;
        $kwhTotal = 0.0;
        
        foreach ($tranches as $tranche) {
            if ($montantRestant <= 0) break;
            
            // Vérifier si on peut utiliser cette tranche
            $seuilMin = $tranche->getSeuilMin();
            $seuilMax = $tranche->getSeuilMax();
            
            // Vérifier si cette tranche s'applique à notre consommation actuelle
            if ($kwhCumule >= $seuilMin && ($seuilMax === null || $kwhCumule < $seuilMax)) {
                // Cette tranche s'applique, on peut l'utiliser
            } else {
                // Cette tranche ne s'applique pas, passer à la suivante
                continue;
            }
            
            // Calculer l'espace disponible dans cette tranche
            $kwhDisponiblesTranche = $seuilMax ? ($seuilMax - $kwhCumule) : INF;
            
            // Calculer les kWh qu'on peut acheter avec le montant restant
            $kwhPossiblesAvecMontant = $montantRestant / $tranche->getPrixKwh();
            
            // Prendre le minimum
            $kwhAUtiliser = min($kwhDisponiblesTranche, $kwhPossiblesAvecMontant);
            
            if ($kwhAUtiliser > 0) {
                $montantUtilise = $kwhAUtiliser * $tranche->getPrixKwh();
                
                $result['details'][] = [
                    'tranche_nom' => $tranche->getNom(),
                    'prix_kwh' => $tranche->getPrixKwh(),
                    'kwh_utilises' => round($kwhAUtiliser, 3),
                    'montant_utilise' => round($montantUtilise, 2)
                ];
                
                $kwhTotal += $kwhAUtiliser;
                $montantTotal += $montantUtilise;
                $montantRestant -= $montantUtilise;
                $kwhCumule += $kwhAUtiliser;
                
                $result['tranche_finale'] = $tranche;
            }
        }
        
        $result['kwh_total'] = round($kwhTotal, 3);
        $result['kwh_achetes'] = round($kwhTotal, 3); // Alias pour compatibilité
        $result['prix_unitaire'] = $kwhTotal > 0 ? round($montantTotal / $kwhTotal, 2) : 0;
        

        
        return $result;
    }
    
    public function getCurrentConsommation(int $clientId): ConsommationMensuelle
    {
        $consommation = $this->consommationRepository->findCurrentByClient($clientId);
        
        if (!$consommation) {
            // Créer une nouvelle consommation pour le mois actuel
            $periode = ConsommationMensuelle::getCurrentPeriod();
            $consommation = new ConsommationMensuelle(
                clientId: $clientId,
                mois: $periode['mois'],
                annee: $periode['annee']
            );
            
            $consommation = $this->consommationRepository->save($consommation);
        }
        
        return $consommation;
    }
    
    public function isNewMonth(ConsommationMensuelle $consommation): bool
    {
        $periode = ConsommationMensuelle::getCurrentPeriod();
        
        return $consommation->getMois() !== $periode['mois'] || 
               $consommation->getAnnee() !== $periode['annee'];
    }
    
    public function resetMonthlyConsommation(int $clientId): ConsommationMensuelle
    {
        $periode = ConsommationMensuelle::getCurrentPeriod();
        
        $nouvelleConsommation = new ConsommationMensuelle(
            clientId: $clientId,
            mois: $periode['mois'],
            annee: $periode['annee']
        );
        
        return $this->consommationRepository->save($nouvelleConsommation);
    }
    
    /**
     * Méthode utilitaire pour obtenir un résumé des tranches
     */
    public function getTranchesResume(): array
    {
        $tranches = $this->trancheRepository->findAllActives();
        
        return array_map(function(TrancheToarifaire $tranche) {
            return [
                'id' => $tranche->getId(),
                'nom' => $tranche->getNom(),
                'description' => $tranche->getSeuilDescription(),
                'prix' => $tranche->getPrixFormatted(),
                'ordre' => $tranche->getOrdre()
            ];
        }, $tranches);
    }
    
    /**
     * Simule un calcul d'achat sans le persister
     */
    public function simulateAchat(int $clientId, float $montant): array
    {
        return $this->calculateTrancheForClient($clientId, $montant);
    }
}
