<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: create_assets_table
 *
 * Representa máquinas e equipamentos agrícolas (tratores, colheitadeiras, pulverizadores, etc.).
 *
 * Regra de negócio:
 * - O custo de uso é calculado por: horas_máquina × hourly_rate.
 * - Alerta de manutenção: quando (total_hours - hours_at_last_maintenance) >= maintenance_alert_hours,
 *   o ativo deve aparecer com destaque amber (amber-500) na interface.
 *
 * Relacionamentos:
 * - 1:N com field_logs — um ativo pode aparecer em múltiplos registros de caderno de campo.
 *
 * Multi-tenancy: executada por banco de tenant via `php artisan tenancy:migrate`.
 * O campo tenant_id é mantido para portabilidade e auditoria de dados.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assets', function (Blueprint $table) {
            $table->id();

            // Identificador do tenant para portabilidade de dados entre bancos
            $table->string('tenant_id')->index();

            $table->string('name');
            // Tipo do ativo: trator, colheitadeira, pulverizador, implemento ou outro
            $table->enum('type', ['tractor', 'harvester', 'sprayer', 'implement', 'other']);
            $table->string('serial_number')->nullable();
            $table->date('purchase_date')->nullable();

            // Base para cálculo de custo operacional do talhão
            $table->decimal('hourly_rate', 8, 2)->default(0);

            // Acumulado total de horas de uso desde a compra
            $table->decimal('total_hours', 8, 1)->default(0);

            // Última manutenção realizada — base para cálculo do alerta
            $table->decimal('hours_at_last_maintenance', 8, 1)->default(0);
            $table->date('last_maintenance_at')->nullable();

            // Limite de horas sem manutenção antes de disparar o alerta amber
            $table->unsignedInteger('maintenance_alert_hours')->default(250);

            $table->enum('status', ['active', 'maintenance', 'inactive'])->default('active');
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};
