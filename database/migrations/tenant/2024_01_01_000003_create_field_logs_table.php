<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: create_field_logs_table
 *
 * O Caderno de Campo Digital: registra cada atividade agrícola realizada em um talhão.
 * É o coração operacional do AgroMonitor.
 *
 * Regra de negócio:
 * - total_cost = (machine_hours × asset.hourly_rate) + (input_quantity × input_unit_price)
 *   Esse valor é calculado no Model/Service antes de persistir (armazenado para performance).
 * - Quando generates_transaction = true, um Observer cria automaticamente um registro
 *   em financial_transactions com type = 'expense'.
 * - asset_id é nullable porque atividades manuais (plantio manual, capina) não usam máquina.
 *
 * Relacionamentos (todos dentro do banco do tenant):
 * - N:1 com plots       — obrigatório, todo registro pertence a um talhão.
 * - N:1 com assets      — opcional, atividade pode ser manual (sem máquina).
 * - 1:0..1 com financial_transactions — pode gerar uma movimentação financeira.
 *
 * Multi-tenancy: executada por banco de tenant via `php artisan tenancy:migrate`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('field_logs', function (Blueprint $table) {
            $table->id();

            // Identificador do tenant para portabilidade de dados entre bancos
            $table->string('tenant_id')->index();

            // Talhão onde a atividade foi realizada (obrigatório)
            $table->foreignId('plot_id')->constrained('plots')->cascadeOnDelete();

            // Máquina/equipamento utilizado (nullable = atividade manual)
            $table->foreignId('asset_id')->nullable()->constrained('assets')->nullOnDelete();

            // ID do usuário do banco central (sem FK real por ser banco separado)
            $table->unsignedBigInteger('user_id')->index();

            // Tipo de atividade agrícola realizada
            $table->enum('activity_type', [
                'planting',
                'spraying',
                'harvesting',
                'fertilizing',
                'maintenance',
                'irrigation',
                'other',
            ]);

            $table->text('description');
            $table->date('log_date');

            // Horas de máquina consumidas nesta atividade (nullable se atividade manual)
            $table->decimal('machine_hours', 6, 1)->nullable();

            // Insumo aplicado (herbicida, fertilizante, semente, etc.)
            $table->string('input_name')->nullable();
            $table->decimal('input_quantity', 10, 3)->nullable();
            $table->decimal('input_unit_price', 10, 2)->nullable();

            // Custo total pré-calculado: (machine_hours × hourly_rate) + (input_qty × unit_price)
            // Armazenado para performance — evita recálculo em relatórios
            $table->decimal('total_cost', 12, 2)->default(0);

            // Se true, um Observer criará uma entrada em financial_transactions automaticamente
            $table->boolean('generates_transaction')->default(false);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('field_logs');
    }
};
