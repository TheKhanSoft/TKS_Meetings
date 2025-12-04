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
        Schema::create('meetings', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('number'); // Meeting number e.g. "101"
            $table->foreignId('meeting_type_id')->constrained();
            $table->date('date');
            $table->time('time');
            $table->boolean('is_last')->default(false); // Is this the last meeting of this type?
            
            // Stats - could be calculated, but storing for snapshot/cache
            $table->integer('total_agenda_items')->default(0);
            $table->integer('items_discussed')->default(0);
            $table->integer('items_left_over')->default(0);

            // Key Roles for this specific meeting
            $table->foreignId('director_id')->nullable()->constrained('users');
            $table->foreignId('registrar_id')->nullable()->constrained('users');
            $table->foreignId('vc_id')->nullable()->constrained('users');
            $table->foreignId('entry_by_id')->constrained('users'); // Who created this record

            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('meetings');
    }
};
