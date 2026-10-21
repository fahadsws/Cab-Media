<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contact_requests', function (Blueprint $t) {
            $t->id();
            $t->string('nombre', 200);
            $t->string('empresa', 200)->nullable();
            $t->string('email', 200);
            $t->string('telefono', 60)->nullable();
            $t->text('mejora');
            $t->boolean('acepta_privacidad')->default(false);
            $t->timestamp('fecha_aceptacion')->nullable();
            $t->string('status', 20)->default('new')->index();
            $t->string('ip', 45)->nullable();
            $t->string('user_agent', 300)->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_requests');
    }
};
