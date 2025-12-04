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
        // 1. Drop the old table completely to avoid SQLite alteration issues
        Schema::dropIfExists('participants');

        // 2. Recreate it as "External Participants" entity
        Schema::create('participants', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->string('designation')->nullable();
            $table->string('organization')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        // 3. Create the pivot table for Meeting <-> (User/Participant)
        Schema::create('meeting_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meeting_id')->constrained()->cascadeOnDelete();
            
            // Polymorphic relation to User or Participant (External)
            $table->morphs('participable');
            
            // Type: 'member' (for Agenda) or 'attendee' (for Minutes)
            $table->string('type')->default('attendee'); 
            
            $table->timestamps();
            
            // Prevent duplicate linking of the same person to the same meeting as the same type
            $table->unique(['meeting_id', 'participable_id', 'participable_type', 'type'], 'meeting_participant_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('meeting_participants');

        Schema::table('participants', function (Blueprint $table) {
            $table->morphs('participable');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
        });
    }
};
