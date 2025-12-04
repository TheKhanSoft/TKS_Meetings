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
        Schema::create('agenda_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meeting_id')->constrained()->cascadeOnDelete();
            $table->foreignId('agenda_item_type_id')->constrained();
            $table->integer('sequence_number');
            $table->string('title');
            $table->text('details')->nullable();
            $table->foreignId('owner_user_id')->nullable()->constrained('users'); // Who proposed this item
            $table->string('discussion_status')->default('pending'); // pending, discussed, deferred
            $table->boolean('is_left_over')->default(false);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agenda_items');
    }
};
