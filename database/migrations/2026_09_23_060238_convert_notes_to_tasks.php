<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Rename notes → tasks and add task fields.
     * On a fresh install without notes, create the tasks table directly.
     */
    public function up(): void
    {
        if (Schema::hasTable('notes') && ! Schema::hasTable('tasks')) {
            Schema::rename('notes', 'tasks');
        }

        if (! Schema::hasTable('tasks')) {
            Schema::create('tasks', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->text('body')->nullable();
                $table->timestamps();
            });
        }

        Schema::table('tasks', function (Blueprint $table) {
            if (! Schema::hasColumn('tasks', 'status')) {
                $table->string('status')->default('pending')->after('body');
            }
            if (! Schema::hasColumn('tasks', 'priority')) {
                $table->string('priority')->default('medium')->after('status');
            }
            if (! Schema::hasColumn('tasks', 'due_date')) {
                $table->date('due_date')->nullable()->after('priority');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn(['status', 'priority', 'due_date']);
        });

        if (Schema::hasTable('tasks') && ! Schema::hasTable('notes')) {
            Schema::rename('tasks', 'notes');
        }
    }
};
