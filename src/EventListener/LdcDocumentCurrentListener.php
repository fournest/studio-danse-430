<?php

namespace App\EventListener;

use App\Entity\LdcDocument;
use App\Repository\LdcDocumentRepository;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;

#[AsEntityListener(event: Events::prePersist, method: 'onPrePersist', entity: LdcDocument::class)]
#[AsEntityListener(event: Events::preUpdate, method: 'onPreUpdate', entity: LdcDocument::class)]
final class LdcDocumentCurrentListener
{
    public function __construct(
        private readonly LdcDocumentRepository $ldcDocumentRepository,
    ) {
    }

    public function onPrePersist(LdcDocument $document, PrePersistEventArgs $args): void
    {
        $this->ensureSingleCurrent($document);
    }

    public function onPreUpdate(LdcDocument $document, PreUpdateEventArgs $args): void
    {
        $this->ensureSingleCurrent($document);
    }

    private function ensureSingleCurrent(LdcDocument $document): void
    {
        if (!$document->isCurrent()) {
            return;
        }

        $this->ldcDocumentRepository->clearCurrentExcept($document->getId());
    }
}
