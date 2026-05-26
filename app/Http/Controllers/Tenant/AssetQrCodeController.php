<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use Illuminate\Http\Response;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class AssetQrCodeController extends Controller
{
    /**
     * Gera e retorna a imagem SVG do QR Code para um ativo.
     *
     * O QR Code aponta para a URL de edição do ativo, permitindo que
     * um smartphone leia e acesse diretamente o cadastro da máquina/equipamento.
     *
     * Rota: GET /assets/{asset}/qrcode
     */
    public function __invoke(Asset $asset): Response
    {
        // URL de edição do ativo — o que o QR Code vai codificar
        $url = route('assets.edit', $asset);

        $svg = QrCode::format('svg')
            ->size(300)
            ->margin(2)
            ->errorCorrection('M')
            ->generate($url);

        return response($svg, 200, [
            'Content-Type' => 'image/svg+xml',
            'Content-Disposition' => 'inline; filename="qrcode-ativo-'.$asset->id.'.svg"',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}
