<?php

/*
 * UrlHum (https://urlhum.com)
 *
 * @link      https://github.com/urlhum/UrlHum
 * @copyright Copyright (c) 2019 Christian la Forgia
 * @license   https://github.com/urlhum/UrlHum/blob/master/LICENSE.md (MIT License)
 */

namespace App\Http\Controllers;

use App\Url;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Spatie\Image\Image;
use Spatie\Image\Manipulations;

/**
 * Controller handling creating/fetching the QR code associated with the short URL.
 *
 * Class QRCodeController
 * @author Michael Lindahl <me@michaellindahl.com>
 */
class QRCodeController
{
    /**
     * Retrieves the QR Code in svg format for the short URL.
     *
     * @param string $shortUrl
     * @return Application|ResponseFactory|Response
     * @throws FileNotFoundException
     */
    public function svg(string $shortUrl)
    {
        $url = Url::whereRaw('BINARY `short_url` = ?', [$shortUrl])->firstOrFail();
        return $this->qrCode($url, 'svg', 'image/svg+xml');
    }

    /**
     * Retrieves the QR Code in png format for the short URL.
     *
     * @param string $shortUrl
     * @return Application|ResponseFactory|Response
     * @throws FileNotFoundException
     */
    public function png(string $shortUrl)
    {
        $url = Url::whereRaw('BINARY `short_url` = ?', [$shortUrl])->firstOrFail();
        return $this->qrCode($url, 'png', 'image/png');
    }

    /**
     * @param Url $url
     * @param $format
     * @param $contentType
     * @return Application|ResponseFactory|Response
     * @throws FileNotFoundException
     */
    private function qrCode(Url $url, $format, $contentType)
    {
        $path = 'qrcodes/'.$url->short_url.'.'.$format;
        if (Storage::exists($path)) {
            $qrCode = Storage::get($path);
        } else {
            $qrCode = QrCode::format($format)
                ->size(300)
                ->cutout(75, 75)
                ->errorCorrection("Q") //25% loss
                ->style("round", 0.5) // ['square', 'dot', 'round'] / 0 - 1
                ->eye("circle") // ['square', 'circle']
                ->color(29, 136, 155)
                //->gradient($startRed, $startGreen, $startBlue, $endRed, $endGreen, $endBlue, string $type)
                //->gradient($startRed, $startGreen, $startBlue, $endRed, $endGreen, $endBlue, string $type)
                //->eyeColor(int $eyeNumber, int $innerRed, int $innerGreen, int $innerBlue, int $outterRed = 0, int $outterGreen = 0, int $outterBlue = 0)
                //->eyeColor(int $eyeNumber, int $innerRed, int $innerGreen, int $innerBlue, int $outterRed = 0, int $outterGreen = 0, int $outterBlue = 0)
                ->generate(route('click', $url->short_url));
            Storage::put($path, $qrCode);

            Image::load($path)
                ->watermark('logo.png')
                ->watermarkPosition(Manipulations::POSITION_CENTER)
                ->save();
        }

        return response($qrCode)->header('Content-Type', $contentType);
    }

}