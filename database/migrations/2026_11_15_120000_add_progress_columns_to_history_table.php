<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('importexport_history', function(Blueprint $table): void {
            $table->unsignedInteger('total_rows')->nullable()->after('status');
            $table->unsignedInteger('processed_rows')->default(0)->after('total_rows');
            // Import summaries list row errors, which can exceed 255 characters
            $table->text('error_message')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('importexport_history', function(Blueprint $table): void {
            $table->dropColumn(['total_rows', 'processed_rows']);
            $table->string('error_message')->nullable()->change();
        });
    }
};
