<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: create_cattle_lots_table
 *
 * Tabela que gerencia os Lotes de Gado no contexto de cada Tenant.
 * O controle é feito por lotes para otimizar performance.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cattle_lots', function (Blueprint $table) {
            $table->id();

            // Identificador do tenant para portabilidade de dados
            $table->string('tenant_id')->index();

            $table->string('name');
            $table->integer('animal_count');
            $table->decimal('initial_avg_weight_kg', 8, 2);
            $table->decimal('current_avg_weight_kg', 8, 2);
            $table->decimal('total_purchase_cost', 12, 2);
            $table->enum('status', ['active', 'sold'])->default('active');
            $table->string('uf', 2)->default('TO');

            // Campos preenchidos no momento da venda do lote
            $table->decimal('sold_amount', 12, 2)->nullable();
            $table->date('sold_at')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cattle_lots');
    }
};
