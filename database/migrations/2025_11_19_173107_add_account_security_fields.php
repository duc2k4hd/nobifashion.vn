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
        Schema::table('accounts', function (Blueprint $table) {
            if (!Schema::hasColumn('accounts', 'last_password_changed_at')) {
                $table->timestamp('last_password_changed_at')->nullable()->after('remember_token');
            }
            if (!Schema::hasColumn('accounts', 'login_attempts')) {
                $table->unsignedInteger('login_attempts')->default(0)->after('last_password_changed_at');
            }
            if (!Schema::hasColumn('accounts', 'account_status')) {
                $table->enum('account_status', ['active', 'suspended', 'review', 'banned'])
                    ->default('active')
                    ->after('login_attempts');
            }
            if (!Schema::hasColumn('accounts', 'security_flags')) {
                $table->json('security_flags')->nullable()->after('account_status');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            if (Schema::hasColumn('accounts', 'security_flags')) {
                $table->dropColumn('security_flags');
            }
            if (Schema::hasColumn('accounts', 'account_status')) {
                $table->dropColumn('account_status');
            }
            if (Schema::hasColumn('accounts', 'login_attempts')) {
                $table->dropColumn('login_attempts');
            }
            if (Schema::hasColumn('accounts', 'last_password_changed_at')) {
                $table->dropColumn('last_password_changed_at');
            }
        });
    }
};
