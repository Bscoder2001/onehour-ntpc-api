<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('messages'))
        {
            return;
        }

        Schema::table('messages', function (Blueprint $table)
        {
            if (! Schema::hasColumn('messages', 'deleted_for_everyone_at'))
            {
                $table->timestamp('deleted_for_everyone_at')->nullable()->after('message');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('messages') || ! Schema::hasColumn('messages', 'deleted_for_everyone_at'))
        {
            return;
        }

        Schema::table('messages', function (Blueprint $table)
        {
            $table->dropColumn('deleted_for_everyone_at');
        });
    }
};
