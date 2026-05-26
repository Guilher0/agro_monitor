<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: add_cattle_lot_id_to_financial_transactions_table
 *
 * Adiciona o campo `cattle_lot_id` na tabela de transações financeiras do tenant,
 * permitindo alocar receitas e despesas diretamente a um lote de gado específico.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('financial_transactions', function (Blueprint $table) {
            $table->foreignId('cattle_lot_id')
                ->nullable()
                ->after('plot_id')
                ->constrained('cattle_lots')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('financial_transactions', function (Blueprint $table) {
            $table->dropForeign(['cattle_lot_id']);
            $table->dropColumn('cattle_lot_id');
        });
    }
};
