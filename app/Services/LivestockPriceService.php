<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Service: LivestockPriceService
 *
 * Integra com a API da AgroDoc AI para recuperar cotações da Arroba (@) do boi gordo.
 * Utiliza cache de 60 minutos por Estado (UF) para evitar extrapolar limites da API gratuita.
 */
class LivestockPriceService
{
    /**
     * Recupera a cotação da arroba para uma determinada UF.
     * Caso a API falhe, retorna um valor regional aproximado (fallback).
     *
     * @param string $uf
     * @return array
     */
    public function getPriceForUf(string $uf): array
    {
        $uf = strtoupper(trim($uf));
        $cacheKey = "livestock_price_{$uf}";

        return Cache::remember($cacheKey, now()->addMinutes(60), function () use ($uf) {
            try {
                $response = Http::timeout(5)
                    ->get("https://agrodocai.com.br/api/v1/cotacao", [
                        'uf' => $uf,
                    ]);

                if ($response->successful()) {
                    $data = $response->json();
                    if (isset($data['valor'])) {
                        return [
                            'produto' => $data['produto'] ?? 'boi_gordo',
                            'uf' => $data['uf'] ?? $uf,
                            'valor' => (float) $data['valor'],
                            'moeda' => $data['moeda'] ?? 'BRL',
                            'unidade' => $data['unidade'] ?? '@',
                            'fonte' => $data['fonte'] ?? 'cepea_esalq',
                            'data_cotacao' => $data['data_cotacao'] ?? now()->format('Y-m-d'),
                        ];
                    }
                }

                Log::warning("Resposta malsucedida ou inválida da API AgroDoc AI para a UF: {$uf}. Ativando fallback.");
            } catch (\Exception $e) {
                Log::error("Erro na requisição para a API AgroDoc AI para a UF: {$uf}. Mensagem: {$e->getMessage()}. Ativando fallback.");
            }

            return $this->getFallbackPrice($uf);
        });
    }

    /**
     * Fornece um preço padrão aproximado para o Estado (UF) selecionado, caso a API esteja indisponível.
     *
     * @param string $uf
     * @return array
     */
    private function getFallbackPrice(string $uf): array
    {
        // Valores aproximados por região para um fallback mais realista
        $prices = [
            'TO' => 345.50,
            'SP' => 355.00,
            'MG' => 348.00,
            'MS' => 346.00,
            'MT' => 339.00,
            'GO' => 342.50,
            'PR' => 350.00,
            'PA' => 335.00,
            'RO' => 332.00,
            'BA' => 340.00,
        ];

        $fallbackValue = $prices[$uf] ?? 345.50; // Valor padrão se a UF não estiver mapeada

        return [
            'produto' => 'boi_gordo',
            'uf' => $uf,
            'valor' => $fallbackValue,
            'moeda' => 'BRL',
            'unidade' => '@',
            'fonte' => 'scot_consultoria (fallback)',
            'data_cotacao' => now()->format('Y-m-d'),
        ];
    }
}
