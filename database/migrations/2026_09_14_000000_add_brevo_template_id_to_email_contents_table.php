<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('email_contents', function (Blueprint $table) {
            $table->unsignedInteger('brevo_template_id')->nullable()->after('content');
            $table->string('subject')->nullable()->change();
            $table->text('content')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('email_contents', function (Blueprint $table) {
            $table->dropColumn('brevo_template_id');
            $table->string('subject')->nullable(false)->change();
            $table->text('content')->nullable(false)->change();
        });
    }
};
