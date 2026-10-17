<?php

/**
 * Secret Share - partage de secrets éphémères et chiffrés.
 *
 * @license AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace SecretShare\View;

use chillerlan\QRCode\Common\EccLevel;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QRCodeException;
use chillerlan\QRCode\QROptions;

/**
 * Génération des QR codes en SVG (data URI, compatible avec la CSP).
 * Le SVG est construit à partir de la matrice en fusionnant les modules
 * sombres consécutifs de chaque ligne : il est ainsi bien plus léger que la
 * sortie standard (un rectangle par module).
 */
final class QrCodeRenderer
{
    /**
     * Retourne le QR code en data URI, ou null si le contenu est trop long
     *
     * @noinspection PhpRedundantCatchClauseInspection php-qrcode lève QRCodeDataException
     *               sans la déclarer en @throws
     */
    public function dataUri(string $content): ?string
    {
        $options = new QROptions(
            [
                'eccLevel' => EccLevel::M,
                'addQuietzone' => true,
                'quietzoneSize' => 4,
            ]
        );
        try {
            $matrix = new QRCode($options)->addByteSegment($content)->getQRMatrix();
        } catch (QRCodeException) {
            return null;
        }

        $size = $matrix->moduleCount;
        $path = '';
        for ($y = 0; $y < $size; $y++) {
            $x = 0;
            while ($x < $size) {
                if (!$matrix->check($x, $y)) {
                    $x++;
                    continue;
                }
                $start = $x;
                while ($x < $size && $matrix->check($x, $y)) {
                    $x++;
                }
                // rectangle d'une ligne de haut couvrant les modules sombres consécutifs
                $path .= sprintf('M%d %dh%dv1h-%dz', $start, $y, $x - $start, $x - $start);
            }
        }
        $svg = sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 %1$d %1$d" shape-rendering="crispEdges">'
            . '<rect width="%1$d" height="%1$d" fill="#fff"/><path fill="#000" d="%2$s"/></svg>',
            $size,
            $path
        );
        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }
}
