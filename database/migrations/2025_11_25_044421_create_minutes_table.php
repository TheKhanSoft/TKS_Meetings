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
        Schema::create('minutes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agenda_item_id')->constrained()->cascadeOnDelete();
            $table->text('decision');
            $table->text('action_required')->nullable();
            $table->string('approval_status')->default('draft'); // draft, approved
            $table->foreignId('responsible_user_id')->nullable()->constrained('users');
            $table->date('target_due_date')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('minutes');
    }
};
