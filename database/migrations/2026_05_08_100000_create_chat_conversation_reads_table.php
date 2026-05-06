<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('chat_conversation_reads'))
        {
            Schema::create('chat_conversation_reads', function (Blueprint $table)
            {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('peer_id');
                $table->unsignedBigInteger('last_read_message_id')->default(0);
                $table->timestamps();
                $table->unique(['user_id', 'peer_id']);
                $table->index(['user_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_conversation_reads');
    }
};
