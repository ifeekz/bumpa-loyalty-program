<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Failed jobs table — stores jobs that exhausted all retries.
        // Inspect via: php artisan queue:failed
        // Replay via:  php artisan queue:retry all
        Schema::create('failed_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->text('connection');
            $table->text('queue');
            $table->longText('payload');
            $table->longText('exception');
            $table->timestamp('failed_at')->useCurrent();
        });

        // NOTE: personal_access_tokens table intentionally omitted.
        // Authentication is handled by JWT (stateless) — no token rows in DB.
        // Token blacklist on logout is stored in Redis (see config/jwt.php).
    }

    public function down(): void
    {
        Schema::dropIfExists('failed_jobs');
    }
};
