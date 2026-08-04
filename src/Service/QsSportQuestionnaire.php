<?php

namespace App\Service;

/**
 * Questions du questionnaire relatif à l'état de santé du sportif
 * (Annexe II-23 du Code du sport / QS-Sport), formulées au vouvoiement
 * pour les responsables légaux et les majeurs.
 *
 * Référence Cerfa / attestation : réponses 100 % NON → attestation sur l'honneur ;
 * au moins un OUI → certificat médical obligatoire.
 */
final class QsSportQuestionnaire
{
    /**
     * @return list<array{id: string, section: string, label: string}>
     */
    public static function questions(): array
    {
        return [
            // Depuis l'année dernière
            ['id' => 'q1', 'section' => 'Depuis l’année dernière', 'label' => 'Avez-vous ou votre enfant passé au moins une journée à l’hôpital ?'],
            ['id' => 'q2', 'section' => 'Depuis l’année dernière', 'label' => 'Avez-vous ou votre enfant été opéré(e) ?'],
            ['id' => 'q3', 'section' => 'Depuis l’année dernière', 'label' => 'Avez-vous ou votre enfant beaucoup plus maigri ou grossi que les autres jeunes du même âge ?'],
            ['id' => 'q4', 'section' => 'Depuis l’année dernière', 'label' => 'Avez-vous ou votre enfant une maladie chronique nécessitant un suivi médical régulier (asthme, diabète, etc.) ?'],
            ['id' => 'q5', 'section' => 'Depuis l’année dernière', 'label' => 'Avez-vous ou votre enfant pris un nouveau médicament presque tous les jours et pour longtemps ?'],
            ['id' => 'q6', 'section' => 'Depuis l’année dernière', 'label' => 'Avez-vous ou votre enfant arrêté le sport pour des raisons de santé pendant un mois ou plus ?'],
            // Aujourd'hui
            ['id' => 'q7', 'section' => 'Aujourd’hui', 'label' => 'Avez-vous ou votre enfant mal au dos actuellement ?'],
            ['id' => 'q8', 'section' => 'Aujourd’hui', 'label' => 'Avez-vous ou votre enfant des problèmes pour voir de loin ou de près ?'],
            ['id' => 'q9', 'section' => 'Aujourd’hui', 'label' => 'Avez-vous ou votre enfant souvent des maux de tête ?'],
            ['id' => 'q10', 'section' => 'Aujourd’hui', 'label' => 'Avez-vous ou votre enfant des difficultés à faire du sport ou à suivre le rythme des autres ?'],
            ['id' => 'q11', 'section' => 'Aujourd’hui', 'label' => 'Avez-vous ou votre enfant déjà eu un malaise pendant un effort ?'],
            ['id' => 'q12', 'section' => 'Aujourd’hui', 'label' => 'Avez-vous ou votre enfant déjà ressenti une douleur dans la poitrine ou un essoufflement inhabituel pendant un effort ?'],
            // Responsables légaux
            ['id' => 'q13', 'section' => 'Questions destinées aux responsables légaux', 'label' => 'Quelqu’un dans votre famille proche a-t-il eu une maladie grave du cœur ou du cerveau, ou est-il décédé subitement avant l’âge de 50 ans ?'],
            ['id' => 'q14', 'section' => 'Questions destinées aux responsables légaux', 'label' => 'Êtes-vous inquiet pour le poids de votre enfant ? Trouvez-vous qu’il ou elle se nourrit trop ou pas assez ?'],
            ['id' => 'q15', 'section' => 'Questions destinées aux responsables légaux', 'label' => 'Avez-vous manqué l’examen de santé prévu à l’âge de votre enfant chez le médecin ? (2, 3, 4, 5 ans, entre 8 et 9 ans, entre 11 et 13 ans, entre 15 et 16 ans)'],
        ];
    }
}
