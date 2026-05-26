<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: create_cattle_weight_logs_table
 *
 * Tabela que registra as pesagens periódicas dos lotes de gado, permitindo
 * acompanhar a evolução do peso e o Ganho Médio Diário (GMD).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cattle_weight_logs', function (Blueprint $table) {
            $table->id();

            // Identificador do tenant para portabilidade de dados
            $table->string('tenant_id')->index();

            // Lote de gado vinculado
            $table->foreignId('cattle_lot_id')
                ->constrained('cattle_lots')
                ->onDelete('cascade');

            $table->date('weight_date');
            $table->decimal('avg_weight_kg', 8, 2);
            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cattle_weight_logs');
    }
};
