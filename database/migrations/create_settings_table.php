<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create settings table.
 *
 * This table stores application settings with optional
 * context and grouping support.
 *
 * Tenant isolation is handled by the database connection.
 *
 * Example:
 *
 * Tenant Database:
 *
 * settings
 *
 * context_type : school
 * context_id   : 10
 * group        : report_cards
 * key          : show_photo
 * value        : true
 *
 */
return new class extends Migration
{

    /**
     * Run migrations.
     */
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {

            /*
             * Primary identifier.
             */
            $table->id();


            /*
             * Optional context type.
             *
             * Examples:
             *
             * school
             * branch
             * user
             * department
             */
            $table->string('context_type')
                ->nullable();


            /*
             * Optional context identifier.
             *
             * In SchoolPalm this will normally
             * represent school_id.
             */
            $table->unsignedBigInteger('context_id')
                ->nullable();


            /*
             * Optional settings group.
             *
             * Examples:
             *
             * report_cards
             * grading
             * sms
             * payroll
             */
            $table->string('group')
                ->nullable();


            /*
             * Setting key.
             *
             * Example:
             *
             * show_photo
             * pass_mark
             */
            $table->string('key');


            /*
             * Setting value.
             *
             * JSON allows:
             *
             * - strings
             * - numbers
             * - booleans
             * - arrays
             * - objects
             */
            $table->json('value')
                ->nullable();


            $table->timestamps();


            /*
             * Prevent duplicate settings
             * in the same scope.
             *
             * Example:
             *
             * school:1
             * report_cards
             * show_photo
             *
             * can only exist once.
             */
            $table->unique([
                'context_type',
                'context_id',
                'group',
                'key',
            ], 'settings_unique_key');


            /*
             * Improve lookup speed.
             *
             * Common queries:
             *
             * - Get all school settings
             * - Get group settings
             */
            $table->index([
                'context_type',
                'context_id',
            ]);


            $table->index([
                'group',
            ]);
        });
    }


    /**
     * Reverse migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
