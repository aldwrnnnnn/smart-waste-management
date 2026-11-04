<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('waste_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete()->cascadeOnUpdate();
            $table->string('photo_path');
            $table->enum('waste_type', ['Organic Waste', 'Plastic Waste', 'Paper Waste'])->index();
            $table->float('confidence_score')->nullable();
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->text('address')->nullable();
            $table->text('description');
            $table->enum('status', ['Pending', 'In Progress', 'Completed', 'Rejected'])->default('Pending')->index();
            $table->text('admin_notes')->nullable();
            $table->timestamps();
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('waste_reports');
    }
};
