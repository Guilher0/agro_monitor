<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: create_financial_transactions_table
 *
 * Controle financeiro do produtor. Registra entradas (vendas de safra) e
 * saídas (custo de insumos, manutenção, mão-de-obra) vinculadas a talhões.
 *
 * Regra de negócio:
 * - Quando field_log.generates_transaction = true, o FieldLogObserver cria automaticamente
 *   um registro aqui com type = 'expense' e amount = field_log.total_cost.
 * - Lançamentos manuais (ex: venda de soja) têm field_log_id = null.
 * - O Dashboard de Lucro por Talhão é calculado com:
 *   SUM(amount WHERE type='income') - SUM(amount WHERE type='expense') GROUP BY plot_id
 *
 * Relacionamentos (todos dentro do banco do tenant):
 * - N:1 com field_logs — optional origin, pode ser lançamento manual.
 * - N:1 com plots      — optional, lançamentos sem talhão são despesas gerais da fazenda.
 *
 * Multi-tenancy: executada por banco de tenant via `php artisan tenancy:migrate`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('financial_transactions', function (Blueprint $table) {
            $table->id();

            // Identificador do tenant para portabilidade de dados entre bancos
            $table->string('tenant_id')->index();

            // Origem da transação — null = lançamento manual
            $table->foreignId('field_log_id')->nullable()->constrained('field_logs')->nullOnDelete();

            // Talhão vinculado — null = despesa/receita geral da fazenda
            $table->foreignId('plot_id')->nullable()->constrained('plots')->nullOnDelete();

            $table->enum('type', ['income', 'expense']);

            // Categorias: insumo, mão-de-obra, venda, manutenção, arrendamento, outros
            $table->string('category');

            $table->decimal('amount', 12, 2);
            $table->text('description');
            $table->date('transaction_date');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_transactions');
    }
};
