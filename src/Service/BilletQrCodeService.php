<?php

namespace App\Service;

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Writer\SvgWriter;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class BilletQrCodeService
{
    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function scanUrl(string $token): string
    {
        return $this->urlGenerator->generate(
            'admin_event_scan',
            ['token' => $token],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
    }

    /**
     * Data URI PNG pour affichage &lt;img&gt;.
     */
    public function pngDataUri(string $token, int $size = 280): string
    {
        $builder = new Builder(
            writer: new PngWriter(),
            data: $this->scanUrl($token),
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::Medium,
            size: $size,
            margin: 8,
        );

        return $builder->build()->getDataUri();
    }

    public function svg(string $token, int $size = 280): string
    {
        $builder = new Builder(
            writer: new SvgWriter(),
            data: $this->scanUrl($token),
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::Medium,
            size: $size,
            margin: 8,
        );

        return $builder->build()->getString();
    }
}
