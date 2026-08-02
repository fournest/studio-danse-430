<?php

namespace App\Service;

/**
 * Questions du questionnaire relatif à l'état de santé du sportif mineur
 * (Annexe II-23 du Code du sport / QS-Sport mineurs).
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
            ['id' => 'q1', 'section' => 'Depuis l’année dernière', 'label' => 'Es-tu allé(e) à l’hôpital pendant toute une journée ou plusieurs jours ?'],
            ['id' => 'q2', 'section' => 'Depuis l’année dernière', 'label' => 'As-tu été opéré(e) ?'],
            ['id' => 'q3', 'section' => 'Depuis l’année dernière', 'label' => 'As-tu beaucoup plus maigri ou grossi que les autres jeunes de ton âge ?'],
            ['id' => 'q4', 'section' => 'Depuis l’année dernière', 'label' => 'As-tu eu une maladie chronique nécessitant un suivi médical régulier (asthme, diabète, etc.) ?'],
            ['id' => 'q5', 'section' => 'Depuis l’année dernière', 'label' => 'As-tu pris un nouveau médicament presque tous les jours et pour longtemps ?'],
            ['id' => 'q6', 'section' => 'Depuis l’année dernière', 'label' => 'As-tu arrêté le sport pour des raisons de santé pendant un mois ou plus ?'],
            // Aujourd'hui
            ['id' => 'q7', 'section' => 'Aujourd’hui', 'label' => 'As-tu mal au dos ?'],
            ['id' => 'q8', 'section' => 'Aujourd’hui', 'label' => 'As-tu des problèmes pour voir de loin ou de près ?'],
            ['id' => 'q9', 'section' => 'Aujourd’hui', 'label' => 'As-tu souvent des maux de tête ?'],
            ['id' => 'q10', 'section' => 'Aujourd’hui', 'label' => 'As-tu des problèmes pour faire du sport ou pour suivre le rythme des autres ?'],
            ['id' => 'q11', 'section' => 'Aujourd’hui', 'label' => 'As-tu déjà eu un malaise pendant un effort ?'],
            ['id' => 'q12', 'section' => 'Aujourd’hui', 'label' => 'As-tu déjà ressenti une douleur dans la poitrine ou un essoufflement inhabituel pendant un effort ?'],
            // Parents
            ['id' => 'q13', 'section' => 'Questions à faire remplir par tes parents', 'label' => 'Quelqu’un dans votre famille proche a-t-il eu une maladie grave du cœur ou du cerveau, ou est-il décédé subitement avant l’âge de 50 ans ?'],
            ['id' => 'q14', 'section' => 'Questions à faire remplir par tes parents', 'label' => 'Êtes-vous inquiet pour son poids ? Trouvez-vous qu’il se nourrit trop ou pas assez ?'],
            ['id' => 'q15', 'section' => 'Questions à faire remplir par tes parents', 'label' => 'Avez-vous manqué l’examen de santé prévu à l’âge de votre enfant chez le médecin ? (2, 3, 4, 5 ans, entre 8 et 9 ans, entre 11 et 13 ans, entre 15 et 16 ans)'],
        ];
    }
}
