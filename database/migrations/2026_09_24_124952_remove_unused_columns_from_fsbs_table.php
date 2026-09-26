<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('fsbs', function (Blueprint $table) {
            $table->dropColumn([
                'ni',
                'co',
                'fe',
                'sio2',
                'cao',
                'mgo',
                'cr2o3',
                'al2o3',
                'status',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('fsbs', function (Blueprint $table) {
            $table->decimal('ni', 8, 4)->nullable();
            $table->decimal('co', 8, 4)->nullable();
            $table->decimal('fe', 8, 4)->nullable();
            $table->decimal('sio2', 8, 4)->nullable();
            $table->decimal('cao', 8, 4)->nullable();
            $table->decimal('mgo', 8, 4)->nullable();
            $table->decimal('cr2o3', 8, 4)->nullable();
            $table->decimal('al2o3', 8, 4)->nullable();
            $table->enum('status', ['pending', 'selesai'])->default('pending');
        });
    }
};
