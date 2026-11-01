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

            // User yang membuat laporan
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            // Kategori yang dikonfirmasi
            $table->foreignId('category_id')
                ->nullable()
                ->constrained('waste_categories')
                ->nullOnDelete();

            // Kategori hasil prediksi AI
            $table->foreignId('ai_category_id')
                ->nullable()
                ->constrained('waste_categories')
                ->nullOnDelete();

            // Informasi laporan
            $table->text('description');
            $table->string('image_path');

            // Lokasi GPS
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            // Status laporan
            $table->string('status', 30)->default('pending');

            // Confidence AI dalam persentase (0-100)
            $table->decimal('ai_confidence', 5, 2)->nullable();

            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('waste_reports');
    }
};