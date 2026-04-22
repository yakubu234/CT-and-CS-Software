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
        Schema::table('loan_payments', function (Blueprint $table) {

            // Outstanding interest (money → decimal, not string)
            $table->string('outstanding_interest')
                ->nullable()
                ->comment('Outstanding interest yet to be paid');

            $table->string('release_date')
                ->nullable()
                ->comment('This is used to know when a new loan issued is approved or release. to be used instead of paid at');
            $table->string('former_carry_forward_id')
                ->nullable()
                ->comment('this id is needed in-case the loan repayment is to be deleted, on can revert it back. anytime loan payment has a former  carry forward id, then the former carry forward is meant to be calculated.');


            // Carry forward state
            $table->tinyInteger('carry_forward')
                ->nullable()
                ->comment('0 = turned off by admin, 1 = to be carried over, 2 = already calculated');

            // Who initiated carry forward
            $table->unsignedBigInteger('carry_forward_by')
                ->nullable()
                ->comment('User ID who initiated carry forward');

            // Optional foreign key (safe)
            $table->foreign('carry_forward_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });

        // Modify existing columns (requires doctrine/dbal)
        Schema::table('loan_payments', function (Blueprint $table) {

            $table->decimal('interest', 10, 2)
                ->nullable()
                ->comment('Total interest to be paid')
                ->change();

            $table->string('interest_rate')
                ->nullable()
                ->comment('Interest rate entered by admin')
                ->change();

            $table->string('is_interest_piad') // keep original name before rename
                ->nullable()
                ->comment('Current interest paid by borrower')
                ->change();
        });

        // Fix typo (VERY IMPORTANT)
        Schema::table('loan_payments', function (Blueprint $table) {
            $table->renameColumn('is_interest_piad', 'is_interest_paid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('loan_payments', function (Blueprint $table) {

            // Drop foreign key first (important)
            $table->dropForeign(['carry_forward_by']);

            // Drop columns
            $table->dropColumn([
                'outstanding_interest',
                'carry_forward',
                'carry_forward_by',
                'release_date',
                'former_carry_forward_id'
            ]);
        });

        // Revert column rename
        Schema::table('loan_payments', function (Blueprint $table) {
            $table->renameColumn('is_interest_paid', 'is_interest_piad');
        });

        // Remove comments (optional rollback)
        Schema::table('loan_payments', function (Blueprint $table) {

            $table->decimal('interest', 10, 2)
                ->nullable()
                ->comment(null)
                ->change();

            $table->string('interest_rate')
                ->nullable()
                ->comment(null)
                ->change();

            $table->string('is_interest_piad')
                ->nullable()
                ->comment(null)
                ->change();
        });
    }
};
