<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTenantsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->string('id')->primary(); // ex: "fazenda-santos-2024"

            // Dados da fazenda / empresa do produtor
            $table->string('name');
            $table->string('slug')->unique(); // identificador de URL amigável
            $table->enum('plan', ['free', 'pro', 'enterprise'])->default('free');
            $table->date('trial_ends_at')->nullable();

            $table->timestamps();
            // Stancl/Tenancy armazena configurações extras (ex: database connection) aqui
            $table->json('data')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
}
