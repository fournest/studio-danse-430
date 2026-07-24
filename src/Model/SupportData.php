<?php

namespace App\Model;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints as Assert;

class SupportData
{
    #[Assert\NotBlank(message: 'Veuillez indiquer votre nom.')]
    public ?string $nom = null;

    #[Assert\NotBlank(message: 'Veuillez indiquer votre e-mail.')]
    #[Assert\Email(message: 'Adresse e-mail invalide.')]
    public ?string $email = null;

    #[Assert\NotBlank(message: 'Veuillez sélectionner un sujet.')]
    public ?string $sujet = null;

    #[Assert\NotBlank(message: 'Veuillez décrire votre demande.')]
    public ?string $message = null;

    #[Assert\File(
        maxSize: '10M',
        mimeTypes: [
            'image/jpeg',
            'image/png',
            'image/webp',
            'application/pdf',
            'application/x-pdf'
        ],
        mimeTypesMessage: 'Seules les images (PNG, JPG, WEBP) et les documents PDF sont acceptés.',
        maxSizeMessage: 'La pièce jointe ne doit pas dépasser 10 Mo.'
    )]
    public ?UploadedFile $fichier = null;
}