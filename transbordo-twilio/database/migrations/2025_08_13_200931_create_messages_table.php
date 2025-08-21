<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chat_session_id')->constrained();
            $table->enum('direction', ['inbound', 'outbound']);
            $table->text('body');
            $table->string('twilio_message_sid')->nullable(); // Novo campo
            $table->string('media_url')->nullable(); // Novo campo para mídias
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
