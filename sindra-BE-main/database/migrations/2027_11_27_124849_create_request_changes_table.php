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
        Schema::create('request_changes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ticket_id');
            $table->unsignedBigInteger('reporter_id');
            $table->text('asset_uuid');
            $table->string('ci_code')->nullable();
            $table->text('description')->nullable();
            $table->text('config_comment')->nullable();
            $table->enum('status',['pending','submitted','approved','rejected'])->default('pending');
            $table->enum('status_implement', ['success','failed'])->nullable();
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            $table->foreign('ticket_id')->references('id')->on('tickets')->onDelete('cascade');
            $table->foreign('reporter_id')->references('id')->on('users')->onDelete('cascade');
        });

        Schema::create('request_change_attachments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('request_change_id');
            $table->string('file_path');
            $table->timestamps();
            $table->foreign('request_change_id')->references('id')->on('request_changes')->onDelete('cascade');
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('request_changes');
        Schema::dropIfExists('request_change_attachments');
    }
};
