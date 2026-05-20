<?php

use App\Enums\SeasonStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('seasons', function (Blueprint $table) {
            $table->id();
            $table->string('status')->default(SeasonStatus::Setup->value);
            $table->unsignedTinyInteger('current_week')->nullable();
            $table->unsignedTinyInteger('total_weeks')->default(6);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seasons');
    }
};
