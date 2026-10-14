<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBillingTables extends Migration
{
    public function up()
    {
        if (Schema::hasTable('subscription_plans')) {
            $this->createRemainingBillingTables();

            return;
        }

        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('billing_interval', 20)->default('month');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('subscription_plan_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_plan_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 15, 2);
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->timestamps();

            $table->index(['subscription_plan_id', 'effective_from'], 'sub_plan_prices_plan_from_idx');
        });

        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->restrictOnDelete();
            $table->foreignId('application_id')->constrained()->restrictOnDelete();
            $table->foreignId('subscription_plan_id')->constrained()->restrictOnDelete();
            $table->decimal('price_amount', 15, 2);
            $table->date('starts_at');
            $table->date('ends_at')->nullable();
            $table->date('next_billing_date')->nullable();
            $table->unsignedInteger('grace_days')->default(0);
            $table->string('status', 20)->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['client_id', 'status'], 'subscriptions_client_status_idx');
            $table->index(['application_id', 'status'], 'subscriptions_app_status_idx');
        });

        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->constrained()->restrictOnDelete();
            $table->string('invoice_number')->unique();
            $table->date('period_start');
            $table->date('period_end');
            $table->decimal('amount', 15, 2);
            $table->decimal('amount_paid', 15, 2)->default(0);
            $table->date('due_date');
            $table->string('status', 20)->default('unpaid');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'due_date'], 'invoices_status_due_idx');
        });

        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->string('description');
            $table->decimal('amount', 15, 2);
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->restrictOnDelete();
            $table->decimal('amount', 15, 2);
            $table->date('paid_at');
            $table->string('method', 50)->default('transfer');
            $table->string('reference_number')->nullable();
            $table->string('proof_path')->nullable();
            $table->string('verification_status', 20)->default('pending');
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['invoice_id', 'reference_number'], 'payments_invoice_ref_unique');
            $table->index('verification_status', 'payments_verification_idx');
        });
    }

    protected function createRemainingBillingTables()
    {
        if (! Schema::hasTable('subscription_plan_prices')) {
            Schema::create('subscription_plan_prices', function (Blueprint $table) {
                $table->id();
                $table->foreignId('subscription_plan_id')->constrained()->cascadeOnDelete();
                $table->decimal('amount', 15, 2);
                $table->date('effective_from');
                $table->date('effective_to')->nullable();
                $table->timestamps();

                $table->index(['subscription_plan_id', 'effective_from'], 'sub_plan_prices_plan_from_idx');
            });
        }

        if (! Schema::hasTable('subscriptions')) {
            Schema::create('subscriptions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('client_id')->constrained()->restrictOnDelete();
                $table->foreignId('application_id')->constrained()->restrictOnDelete();
                $table->foreignId('subscription_plan_id')->constrained()->restrictOnDelete();
                $table->decimal('price_amount', 15, 2);
                $table->date('starts_at');
                $table->date('ends_at')->nullable();
                $table->date('next_billing_date')->nullable();
                $table->unsignedInteger('grace_days')->default(0);
                $table->string('status', 20)->default('active');
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['client_id', 'status'], 'subscriptions_client_status_idx');
                $table->index(['application_id', 'status'], 'subscriptions_app_status_idx');
            });
        }

        if (! Schema::hasTable('invoices')) {
            Schema::create('invoices', function (Blueprint $table) {
                $table->id();
                $table->foreignId('subscription_id')->constrained()->restrictOnDelete();
                $table->string('invoice_number')->unique();
                $table->date('period_start');
                $table->date('period_end');
                $table->decimal('amount', 15, 2);
                $table->decimal('amount_paid', 15, 2)->default(0);
                $table->date('due_date');
                $table->string('status', 20)->default('unpaid');
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['status', 'due_date'], 'invoices_status_due_idx');
            });
        }

        if (! Schema::hasTable('invoice_items')) {
            Schema::create('invoice_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
                $table->string('description');
                $table->decimal('amount', 15, 2);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('payments')) {
            Schema::create('payments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('invoice_id')->constrained()->restrictOnDelete();
                $table->decimal('amount', 15, 2);
                $table->date('paid_at');
                $table->string('method', 50)->default('transfer');
                $table->string('reference_number')->nullable();
                $table->string('proof_path')->nullable();
                $table->string('verification_status', 20)->default('pending');
                $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('verified_at')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->unique(['invoice_id', 'reference_number'], 'payments_invoice_ref_unique');
                $table->index('verification_status', 'payments_verification_idx');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('payments');
        Schema::dropIfExists('invoice_items');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('subscription_plan_prices');
        Schema::dropIfExists('subscription_plans');
    }
}
