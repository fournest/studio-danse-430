<?php

namespace App\Data;

/**
 * Contenus squelettes pour les pages légales (association loi 1901 — à personnaliser).
 */
final class PageLegaleSeedData
{
    /**
     * @return list<array{titre: string, slug: string, contenu: string}>
     */
    public static function pages(): array
    {
        return [
            [
                'titre' => 'Mentions légales',
                'slug' => 'mentions-legales',
                'contenu' => self::mentionsLegales(),
            ],
            [
                'titre' => 'Politique de confidentialité (RGPD)',
                'slug' => 'politique-de-confidentialite',
                'contenu' => self::politiqueConfidentialite(),
            ],
            [
                'titre' => 'Conditions générales d\'utilisation',
                'slug' => 'cgu',
                'contenu' => self::cgu(),
            ],
        ];
    }

    private static function mentionsLegales(): string
    {
        return <<<'HTML'
<h2>1. Éditeur du site</h2>
<p>Le site <strong>Studio Danse 430</strong> est édité par l'association <strong>Studio Danse 430</strong>, association régie par la loi du 1<sup>er</sup> juillet 1901.</p>
<ul>
    <li><strong>Siège social :</strong> Rue Armand Calleau, 85430 Nieul-le-Dolent</li>
    <li><strong>Objet :</strong> promotion et enseignement de la danse</li>
    <li><strong>Directeur de la publication :</strong> le Président(e) de l'association</li>
    <li><strong>Contact :</strong> via les coordonnées indiquées sur le site ou auprès du secrétariat du club</li>
</ul>

<h2>2. Hébergement</h2>
<p>Ce site est hébergé par : <em>[à compléter : raison sociale, adresse et téléphone de l'hébergeur]</em>.</p>

<h2>3. Propriété intellectuelle</h2>
<p>L'ensemble des contenus (textes, images, logos, vidéos) présents sur ce site est protégé par le droit d'auteur. Toute reproduction sans autorisation écrite préalable de l'association est interdite.</p>

<h2>4. Responsabilité</h2>
<p>L'association s'efforce d'assurer l'exactitude des informations publiées. Elle ne saurait être tenue responsable des erreurs, omissions ou indisponibilités temporaires du service.</p>

<p><em>Ce texte est un modèle de base. Faites-le valider par votre conseil juridique ou votre assureur avant publication définitive.</em></p>
HTML;
    }

    private static function politiqueConfidentialite(): string
    {
        return <<<'HTML'
<h2>1. Responsable du traitement</h2>
<p>L'association <strong>Studio Danse 430</strong> est responsable du traitement des données personnelles collectées dans le cadre de la gestion des adhésions, inscriptions aux cours, communication et fonctionnement du site.</p>

<h2>2. Données collectées</h2>
<p>Selon les services utilisés, nous pouvons collecter : identité, coordonnées, informations familiales, données de santé strictement nécessaires à la pratique encadrée de la danse (certificats médicaux, questionnaires sportifs), données de connexion et d'inscription.</p>

<h2>3. Finalités et bases légales</h2>
<ul>
    <li>Gestion des adhésions et inscriptions (exécution du contrat / intérêt légitime de l'association)</li>
    <li>Sécurité des élèves et conformité réglementaire (obligation légale / intérêt vital pour les données de santé)</li>
    <li>Communication du club (intérêt légitime, avec possibilité d'opposition)</li>
</ul>

<h2>4. Durée de conservation</h2>
<p>Les données sont conservées pendant la durée de l'adhésion puis archivées selon les obligations légales et comptables applicables aux associations.</p>

<h2>5. Vos droits (RGPD)</h2>
<p>Vous disposez d'un droit d'accès, de rectification, d'effacement, de limitation, d'opposition et de portabilité. Pour exercer vos droits : contactez le bureau de l'association. Vous pouvez introduire une réclamation auprès de la CNIL (<a href="https://www.cnil.fr" target="_blank" rel="noopener">www.cnil.fr</a>).</p>

<h2>6. Cookies</h2>
<p>Le site peut utiliser des cookies techniques nécessaires à son fonctionnement et, le cas échéant, des cookies de mesure d'audience. Vous pouvez configurer votre navigateur pour les refuser.</p>

<p><em>Modèle indicatif pour association loi 1901 — à adapter avec votre DPO ou conseil juridique.</em></p>
HTML;
    }

    private static function cgu(): string
    {
        return <<<'HTML'
<h2>1. Objet</h2>
<p>Les présentes Conditions Générales d'Utilisation (CGU) régissent l'accès et l'utilisation du site et des services en ligne proposés par l'association <strong>Studio Danse 430</strong>.</p>

<h2>2. Accès au service</h2>
<p>L'accès à certaines fonctionnalités (espace familial, inscriptions, boutique) nécessite la création d'un compte. L'utilisateur s'engage à fournir des informations exactes et à maintenir la confidentialité de ses identifiants.</p>

<h2>3. Adhésion et inscriptions</h2>
<p>L'inscription aux cours est soumise aux statuts de l'association, au règlement intérieur et aux conditions financières en vigueur pour la saison. Le paiement des cotisations et droits d'inscription conditionne la participation aux activités.</p>

<h2>4. Comportement des utilisateurs</h2>
<p>Tout usage frauduleux, diffamatoire, contraire à l'ordre public ou portant atteinte aux droits de tiers est interdit. L'association se réserve le droit de suspendre un compte en cas de manquement grave.</p>

<h2>5. Propriété intellectuelle</h2>
<p>Les contenus du site (textes, visuels, supports pédagogiques) restent la propriété de l'association ou de leurs auteurs respectifs.</p>

<h2>6. Droit applicable</h2>
<p>Les présentes CGU sont soumises au droit français. En cas de litige, les tribunaux compétents seront ceux du ressort du siège social de l'association, après tentative de résolution amiable.</p>

<p><em>Texte type à faire valider par le bureau et, si besoin, par un conseil juridique.</em></p>
HTML;
    }
}
