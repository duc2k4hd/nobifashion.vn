<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('images')) {
            return;
        }

        Schema::table('images', function (Blueprint $table) {
            if (! Schema::hasColumn('images', 'path')) {
                $table->string('path', 191)->nullable()->comment('Đường dẫn tương đối của file')->after('url');
            }

            if (! Schema::hasColumn('images', 'extension')) {
                $table->string('extension', 10)->nullable()->after('path');
            }

            if (! Schema::hasColumn('images', 'mime_type')) {
                $table->string('mime_type', 191)->nullable()->after('extension');
            }

            if (! Schema::hasColumn('images', 'size')) {
                $table->unsignedBigInteger('size')->default(0)->after('mime_type');
            }

            if (! Schema::hasColumn('images', 'width')) {
                $table->unsignedInteger('width')->nullable()->after('size');
            }

            if (! Schema::hasColumn('images', 'height')) {
                $table->unsignedInteger('height')->nullable()->after('width');
            }

            if (! Schema::hasColumn('images', 'context')) {
                $table->string('context', 50)->default('product')->after('height');
            }

            if (! Schema::hasColumn('images', 'file_modified_at')) {
                $table->timestamp('file_modified_at')->nullable()->after('context');
            }
        });

        $this->ensureContextIndex();
        $this->makeProductIdNullableIfNeeded();
    }

    public function down(): void
    {
        if (! Schema::hasTable('images')) {
            return;
        }

        Schema::table('images', function (Blueprint $table) {
            $columns = array_values(array_filter([
                Schema::hasColumn('images', 'path') ? 'path' : null,
                Schema::hasColumn('images', 'extension') ? 'extension' : null,
                Schema::hasColumn('images', 'mime_type') ? 'mime_type' : null,
                Schema::hasColumn('images', 'size') ? 'size' : null,
                Schema::hasColumn('images', 'width') ? 'width' : null,
                Schema::hasColumn('images', 'height') ? 'height' : null,
                Schema::hasColumn('images', 'context') ? 'context' : null,
                Schema::hasColumn('images', 'file_modified_at') ? 'file_modified_at' : null,
            ]));

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }

    protected function ensureContextIndex(): void
    {
        if (! Schema::hasColumn('images', 'context')) {
            return;
        }

        $indexExists = DB::table('information_schema.statistics')
            ->whereRaw('TABLE_SCHEMA = DATABASE()')
            ->where('TABLE_NAME', 'images')
            ->where('INDEX_NAME', 'images_context_index')
            ->exists();

        if ($indexExists) {
            return;
        }

        Schema::table('images', function (Blueprint $table) {
            $table->index('context');
        });
    }

    protected function makeProductIdNullableIfNeeded(): void
    {
        if (! Schema::hasColumn('images', 'product_id')) {
            return;
        }

        $column = DB::table('information_schema.columns')
            ->select('IS_NULLABLE')
            ->whereRaw('TABLE_SCHEMA = DATABASE()')
            ->where('TABLE_NAME', 'images')
            ->where('COLUMN_NAME', 'product_id')
            ->first();

        if (($column->IS_NULLABLE ?? 'YES') === 'YES') {
            return;
        }

        Schema::table('images', function (Blueprint $table) {
            $table->unsignedBigInteger('product_id')->nullable()->change();
        });
    }
};
