<?php

namespace App\Services;

use Picqer\Barcode\BarcodeGeneratorSVG;

class BarcodeService
{
    public function svg(string $code, int $widthFactor = 2, int $height = 45): string
    {
        return (new BarcodeGeneratorSVG())->getBarcode($code, BarcodeGeneratorSVG::TYPE_CODE_128, $widthFactor, $height);
    }
}
