<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ====== TABEL UTAMA ======
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->uuid('asset_uuid');
            $table->unsignedBigInteger('reporter_id');
            $table->unsignedBigInteger('ticket_category_id'); 
            $table->unsignedBigInteger('instansi_id');
            $table->enum('priority', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->string('code')->unique();
            $table->enum('type', ['incident', 'service_request'])->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('status', ['open', 'in_progress', 'resolved', 'closed'])->default('open');
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('deadline_at')->nullable();
            $table->timestamps();
            $table->foreign('reporter_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('ticket_category_id')->references('id')->on('ticket_categories')->onDelete('cascade'); // <-- foreign key
          $table->foreign('instansi_id')->references('id')->on('instansis')->onDelete('cascade');
        });

          Schema::create('ticket_feedbacks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ticket_id');
            $table->unsignedBigInteger('user_id');
            $table->text('feedback')->nullable();
            $table->float('rating')->default(0.0); // misal rating 0-5
            $table->timestamps();

            $table->foreign('ticket_id')->references('id')->on('tickets')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        Schema::create('ticket_escalates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ticket_id');
            $table->text('description')->nullable();
            $table->enum('destination', ['seksi', 'bidang', 'opd','kota']);
            $table->enum('status', ['pending', 'approve', 'rejected'])->default('pending');
            $table->timestamps();

            $table->foreign('ticket_id')->references('id')->on('tickets')->onDelete('cascade');
        });

        Schema::create('ticket_reopens', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ticket_id');
            $table->string('reason');
            $table->text('detail')->nullable();
            $table->string('attachment')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->timestamps();

            $table->foreign('ticket_id')->references('id')->on('tickets')->onDelete('cascade');
        });

        Schema::create('ticket_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ticket_id');
            $table->unsignedBigInteger('user_id');
            $table->string('name');
            $table->text('desc')->nullable();
            $table->timestamp('time_at');
            $table->timestamps();

            $table->foreign('ticket_id')->references('id')->on('tickets')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        Schema::create('ticket_assets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ticket_id');
            $table->text('asset_code');
            $table->string('serial_number')->nullable();
            $table->timestamps();
        });

        // ====== PETUGAS ======
        Schema::create('ticket_assignees', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ticket_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamps();

            $table->foreign('ticket_id')->references('id')->on('tickets')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            $table->unique(['ticket_id', 'user_id']);
        });

        // ====== LAMPIRAN TICKET ======
        Schema::create('ticket_attachments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ticket_id');
            $table->string('file_path');
            $table->string('file_url');
            $table->string('file_name')->nullable();
            $table->string('mime_type')->nullable();
            $table->timestamps();

            $table->foreign('ticket_id')->references('id')->on('tickets')->onDelete('cascade');
        });

        // ====== DISKUSI ======
        Schema::create('ticket_discussions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ticket_id');
            $table->unsignedBigInteger('user_id');
            $table->text('message')->nullable();
            $table->timestamps();

            $table->foreign('ticket_id')->references('id')->on('tickets')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        // ====== LAMPIRAN DISKUSI ======
        Schema::create('ticket_discussion_attachments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('discussion_id');
            $table->string('file_path');
            $table->string('file_name')->nullable();
            $table->string('mime_type')->nullable();
            $table->timestamps();

            $table->foreign('discussion_id')->references('id')->on('ticket_discussions')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_discussion_attachments');
        Schema::dropIfExists('ticket_discussions');
        Schema::dropIfExists('ticket_attachments');
        Schema::dropIfExists('ticket_assignees');
        Schema::dropIfExists('ticket_assets');
        Schema::dropIfExists('ticket_logs');
        Schema::dropIfExists('ticket_feedbacks');
        Schema::dropIfExists('ticket_escalates');
        Schema::dropIfExists('ticket_reopens');
        Schema::dropIfExists('tickets');
    }
};
