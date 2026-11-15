<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Stockage des contenus d'un centre dans SON Google Drive.
 *
 * - center_google_drives : un compte Google par centre, son jeton de
 *   rafraichissement (chiffre par le modele), le dossier « PrePla - <centre> »
 *   et ses sous-dossiers, la derniere sauvegarde.
 * - center_media : ou vit le fichier (local ou google_drive) et son id Drive.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('center_google_drives', function (Blueprint $table) {
            $table->id();
            $table->foreignId('center_id')->unique()->constrained('language_centers')->cascadeOnDelete();
            $table->string('email')->nullable();
            $table->text('refresh_token');
            $table->string('root_folder_id')->nullable();
            $table->json('folders')->nullable();
            $table->foreignId('connected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('connecte_le')->nullable();
            $table->timestamp('derniere_sauvegarde_le')->nullable();
            $table->string('derniere_sauvegarde_id')->nullable();
            $table->timestamps();
        });

        Schema::table('center_media', function (Blueprint $table) {
            $table->string('provider', 20)->default('local')->after('type');
            $table->string('drive_file_id')->nullable()->after('path');
            $table->index(['center_id', 'drive_file_id']);
        });
    }

    public function down(): void
    {
        Schema::table('center_media', function (Blueprint $table) {
            $table->dropIndex(['center_id', 'drive_file_id']);
            $table->dropColumn(['provider', 'drive_file_id']);
        });
        Schema::dropIfExists('center_google_drives');
    }
};
