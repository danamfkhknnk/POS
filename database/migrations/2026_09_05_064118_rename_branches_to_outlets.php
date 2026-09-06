<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop the foreign key on users.branch_id first
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
            $table->renameColumn('branch_id', 'outlet_id');
        });

        Schema::rename('branches', 'outlets');
    }

    public function down(): void
    {
        Schema::rename('outlets', 'branches');

        Schema::table('users', function (Blueprint $table) {
            $table->renameColumn('outlet_id', 'branch_id');
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('set null');
        });
    }
};
