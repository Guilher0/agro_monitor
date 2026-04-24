<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: create_plots_table
 *
 * Representa os talhões (subdivisões de área da fazenda).
 * É a unidade central de custo e produtividade do AgroMonitor.
 *
 * Regra de negócio:
 * - Custo Operacional do Talhão = SUM(field_logs.total_cost) onde plot_id = this.id.
 * - Receita = SUM(financial_transactions.amount WHERE type = 'income' AND plot_id = this.id).
 * - Lucro = Receita - Custo Operacional.
 *
 * Relacionamentos:
 * - 1:N com field_logs — um talhão pode ter múltiplos registros de caderno de campo.
 * - 1:N com financial_transactions — um talhão pode ter múltiplas movimentações financeiras.
 *
 * Multi-tenancy: executada por banco de tenant via `php artisan tenancy:migrate`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plots', function (Blueprint $table) {
            $table->id();

            // Identificador do tenant para portabilidade de dados entre bancos
            $table->string('tenant_id')->index();

            $table->string('name'); // ex: "Talhão A - Gleba Norte"
            $table->decimal('area_hectares', 8, 2)->default(0);

            // Cultura plantada atualmente: soja, milho, cana, café, etc.
            $table->string('culture')->nullable();

            // Safra de referência, ex: "2024/2025"
            $table->string('season')->nullable();

            // Coordenadas GPS em JSON: pode ser um ponto ou polígono (GeoJSON-like)
            $table->json('location_coordinates')->nullable();

            $table->string('soil_type')->nullable(); // ex: Latossolo Vermelho
            $table->enum('status', ['active', 'fallow', 'harvested'])->default('active');
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plots');
    }
};
