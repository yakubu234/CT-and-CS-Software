<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\AccountTypeController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\BranchSwitchController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExpenseCategoryController;
use App\Http\Controllers\IncomeExpenseController;
use App\Http\Controllers\LoanController;
use App\Http\Controllers\LoanCustomFieldController;
use App\Http\Controllers\LoanPaymentController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\MemberCustomFieldController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SmsAutomationController;
use App\Http\Controllers\SmsCampaignController;
use App\Http\Controllers\SmsMessageController;
use App\Http\Controllers\SmsSettingsController;
use App\Http\Controllers\SmsTemplateController;
use App\Http\Controllers\TransactionCategoryController;
use App\Http\Controllers\TransactionController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/accounts', [AccountController::class, 'index'])->name('accounts.index');
    Route::get('/accounts/inactive', [AccountController::class, 'inactive'])->name('accounts.inactive');
    Route::patch('/accounts/{account}/reactivate', [AccountController::class, 'reactivate'])->name('accounts.reactivate');
    Route::get('/account-types', [AccountTypeController::class, 'index'])->name('account-types.index');
    Route::get('/account-types/{accountType}', [AccountTypeController::class, 'show'])->name('account-types.show');
    Route::get('/account-types/{accountType}/edit', [AccountTypeController::class, 'edit'])->name('account-types.edit');
    Route::put('/account-types/{accountType}', [AccountTypeController::class, 'update'])->name('account-types.update');
    Route::get('/branches/switch', [BranchSwitchController::class, 'index'])->name('branches.switch.index');
    Route::post('/branches/switch', [BranchSwitchController::class, 'store'])->name('branches.switch.store');
    Route::get('/branches', [BranchController::class, 'index'])->name('branches.index');
    Route::get('/branches/create', [BranchController::class, 'create'])->name('branches.create');
    Route::get('/branches/{branch}', [BranchController::class, 'show'])->name('branches.show');
    Route::get('/branches/{branch}/edit', [BranchController::class, 'edit'])->name('branches.edit');
    Route::post('/branches', [BranchController::class, 'store'])->name('branches.store');
    Route::put('/branches/{branch}', [BranchController::class, 'update'])->name('branches.update');
    Route::delete('/branches/{branch}', [BranchController::class, 'destroy'])->name('branches.destroy');

    Route::get('/loans/custom-fields', [LoanCustomFieldController::class, 'index'])->name('loans.custom-fields.index');
    Route::get('/loans/custom-fields/create', [LoanCustomFieldController::class, 'create'])->name('loans.custom-fields.create');
    Route::post('/loans/custom-fields', [LoanCustomFieldController::class, 'store'])->name('loans.custom-fields.store');
    Route::get('/loans/custom-fields/{customField}/edit', [LoanCustomFieldController::class, 'edit'])->name('loans.custom-fields.edit');
    Route::put('/loans/custom-fields/{customField}', [LoanCustomFieldController::class, 'update'])->name('loans.custom-fields.update');
    Route::delete('/loans/custom-fields/{customField}', [LoanCustomFieldController::class, 'destroy'])->name('loans.custom-fields.destroy');

    Route::get('/loans', [LoanController::class, 'index'])->name('loans.index');
    Route::get('/loans/pending', [LoanController::class, 'pending'])->name('loans.pending');
    Route::get('/loans/active', [LoanController::class, 'active'])->name('loans.active');
    Route::get('/loans/declined', [LoanController::class, 'declined'])->name('loans.declined');
    Route::get('/loans/create', [LoanController::class, 'create'])->name('loans.create');
    Route::post('/loans', [LoanController::class, 'store'])->name('loans.store');
    Route::get('/loans/requests/{loanDetail}', [LoanController::class, 'showRequest'])->name('loans.requests.show');
    Route::get('/loans/requests/{loanDetail}/edit', [LoanController::class, 'edit'])->name('loans.requests.edit');
    Route::put('/loans/requests/{loanDetail}', [LoanController::class, 'update'])->name('loans.requests.update');
    Route::post('/loans/requests/{loanDetail}/approve', [LoanController::class, 'approve'])->name('loans.requests.approve');
    Route::post('/loans/requests/{loanDetail}/decline', [LoanController::class, 'decline'])->name('loans.requests.decline');
    Route::delete('/loans/requests/{loanDetail}', [LoanController::class, 'destroy'])->name('loans.requests.destroy');
    Route::get('/loans/{loan}', [LoanController::class, 'show'])->name('loans.show');

    Route::get('/loan-payments', [LoanPaymentController::class, 'index'])->name('loan-payments.index');
    Route::get('/loan-payments/create', [LoanPaymentController::class, 'create'])->name('loan-payments.create');
    Route::post('/loan-payments', [LoanPaymentController::class, 'store'])->name('loan-payments.store');
    Route::get('/loan-payments/{loanPayment}', [LoanPaymentController::class, 'show'])->name('loan-payments.show');
    Route::get('/loan-payments/{loanPayment}/edit', [LoanPaymentController::class, 'edit'])->name('loan-payments.edit');
    Route::put('/loan-payments/{loanPayment}', [LoanPaymentController::class, 'update'])->name('loan-payments.update');
    Route::delete('/loan-payments/{loanPayment}', [LoanPaymentController::class, 'destroy'])->name('loan-payments.destroy');

    Route::get('/members/custom-fields', [MemberCustomFieldController::class, 'index'])->name('members.custom-fields.index');
    Route::get('/members/custom-fields/create', [MemberCustomFieldController::class, 'create'])->name('members.custom-fields.create');
    Route::post('/members/custom-fields', [MemberCustomFieldController::class, 'store'])->name('members.custom-fields.store');
    Route::get('/members/custom-fields/{customField}/edit', [MemberCustomFieldController::class, 'edit'])->name('members.custom-fields.edit');
    Route::put('/members/custom-fields/{customField}', [MemberCustomFieldController::class, 'update'])->name('members.custom-fields.update');
    Route::delete('/members/custom-fields/{customField}', [MemberCustomFieldController::class, 'destroy'])->name('members.custom-fields.destroy');

    Route::get('/members', [MemberController::class, 'index'])->name('members.index');
    Route::get('/members/create', [MemberController::class, 'create'])->name('members.create');
    Route::post('/members', [MemberController::class, 'store'])->name('members.store');
    Route::get('/members/{member}', [MemberController::class, 'show'])->name('members.show');
    Route::get('/members/{member}/edit', [MemberController::class, 'edit'])->name('members.edit');
    Route::put('/members/{member}', [MemberController::class, 'update'])->name('members.update');
    Route::post('/members/{member}/documents', [MemberController::class, 'storeDocument'])->name('members.documents.store');
    Route::delete('/members/{member}', [MemberController::class, 'destroy'])->name('members.destroy');

    Route::get('/bulk-sms/settings', [SmsSettingsController::class, 'edit'])->name('bulk-sms.settings.edit');
    Route::put('/bulk-sms/settings', [SmsSettingsController::class, 'update'])->name('bulk-sms.settings.update');
    Route::post('/bulk-sms/settings/balance', [SmsSettingsController::class, 'balance'])->name('bulk-sms.settings.balance');
    Route::post('/bulk-sms/settings/test-send', [SmsSettingsController::class, 'testSend'])->name('bulk-sms.settings.test-send');
    Route::get('/bulk-sms/templates', [SmsTemplateController::class, 'index'])->name('bulk-sms.templates.index');
    Route::get('/bulk-sms/templates/create', [SmsTemplateController::class, 'create'])->name('bulk-sms.templates.create');
    Route::post('/bulk-sms/templates', [SmsTemplateController::class, 'store'])->name('bulk-sms.templates.store');
    Route::get('/bulk-sms/templates/{smsTemplate}/edit', [SmsTemplateController::class, 'edit'])->name('bulk-sms.templates.edit');
    Route::put('/bulk-sms/templates/{smsTemplate}', [SmsTemplateController::class, 'update'])->name('bulk-sms.templates.update');
    Route::delete('/bulk-sms/templates/{smsTemplate}', [SmsTemplateController::class, 'destroy'])->name('bulk-sms.templates.destroy');
    Route::get('/bulk-sms/campaigns', [SmsCampaignController::class, 'index'])->name('bulk-sms.campaigns.index');
    Route::get('/bulk-sms/campaigns/create', [SmsCampaignController::class, 'create'])->name('bulk-sms.campaigns.create');
    Route::post('/bulk-sms/campaigns', [SmsCampaignController::class, 'store'])->name('bulk-sms.campaigns.store');
    Route::get('/bulk-sms/campaigns/{smsCampaign}', [SmsCampaignController::class, 'show'])->name('bulk-sms.campaigns.show');
    Route::get('/bulk-sms/automations', [SmsAutomationController::class, 'index'])->name('bulk-sms.automations.index');
    Route::get('/bulk-sms/automations/create', [SmsAutomationController::class, 'create'])->name('bulk-sms.automations.create');
    Route::post('/bulk-sms/automations', [SmsAutomationController::class, 'store'])->name('bulk-sms.automations.store');
    Route::get('/bulk-sms/automations/{smsAutomationRule}/edit', [SmsAutomationController::class, 'edit'])->name('bulk-sms.automations.edit');
    Route::put('/bulk-sms/automations/{smsAutomationRule}', [SmsAutomationController::class, 'update'])->name('bulk-sms.automations.update');
    Route::delete('/bulk-sms/automations/{smsAutomationRule}', [SmsAutomationController::class, 'destroy'])->name('bulk-sms.automations.destroy');
    Route::get('/bulk-sms/logs', [SmsMessageController::class, 'index'])->name('bulk-sms.logs.index');

    Route::get('/transactions', [TransactionController::class, 'index'])->name('transactions.index');
    Route::get('/transactions/create', [TransactionController::class, 'create'])->name('transactions.create');
    Route::post('/transactions', [TransactionController::class, 'store'])->name('transactions.store');
    Route::get('/transactions/{transaction}', [TransactionController::class, 'show'])->name('transactions.show');
    Route::get('/transactions/{transaction}/edit', [TransactionController::class, 'edit'])->name('transactions.edit');
    Route::put('/transactions/{transaction}', [TransactionController::class, 'update'])->name('transactions.update');
    Route::delete('/transactions/{transaction}', [TransactionController::class, 'destroy'])->name('transactions.destroy');
    Route::get('/transaction-categories', [TransactionCategoryController::class, 'index'])->name('transaction-categories.index');
    Route::get('/transaction-categories/create', [TransactionCategoryController::class, 'create'])->name('transaction-categories.create');
    Route::post('/transaction-categories', [TransactionCategoryController::class, 'store'])->name('transaction-categories.store');
    Route::get('/transaction-categories/{transactionCategory}/edit', [TransactionCategoryController::class, 'edit'])->name('transaction-categories.edit');
    Route::put('/transaction-categories/{transactionCategory}', [TransactionCategoryController::class, 'update'])->name('transaction-categories.update');
    Route::delete('/transaction-categories/{transactionCategory}', [TransactionCategoryController::class, 'destroy'])->name('transaction-categories.destroy');
    Route::get('/income-expenses', [IncomeExpenseController::class, 'index'])->name('income-expenses.index');
    Route::get('/income-expenses/create', [IncomeExpenseController::class, 'create'])->name('income-expenses.create');
    Route::post('/income-expenses', [IncomeExpenseController::class, 'store'])->name('income-expenses.store');
    Route::get('/income-expenses/{incomeExpense}', [IncomeExpenseController::class, 'show'])->name('income-expenses.show');
    Route::get('/income-expenses/{incomeExpense}/edit', [IncomeExpenseController::class, 'edit'])->name('income-expenses.edit');
    Route::put('/income-expenses/{incomeExpense}', [IncomeExpenseController::class, 'update'])->name('income-expenses.update');
    Route::delete('/income-expenses/{incomeExpense}', [IncomeExpenseController::class, 'destroy'])->name('income-expenses.destroy');
    Route::get('/expense-categories', [ExpenseCategoryController::class, 'index'])->name('expense-categories.index');
    Route::get('/expense-categories/create', [ExpenseCategoryController::class, 'create'])->name('expense-categories.create');
    Route::post('/expense-categories', [ExpenseCategoryController::class, 'store'])->name('expense-categories.store');
    Route::get('/expense-categories/{expenseCategory}/edit', [ExpenseCategoryController::class, 'edit'])->name('expense-categories.edit');
    Route::put('/expense-categories/{expenseCategory}', [ExpenseCategoryController::class, 'update'])->name('expense-categories.update');
    Route::delete('/expense-categories/{expenseCategory}', [ExpenseCategoryController::class, 'destroy'])->name('expense-categories.destroy');
    Route::get('/reports/member-balance', [ReportController::class, 'memberBalance'])->name('reports.member-balance');
    Route::get('/reports/member-balance/export', [ReportController::class, 'exportMemberBalance'])->name('reports.member-balance.export');
    Route::get('/reports/loan-report', [ReportController::class, 'loanReport'])->name('reports.loan-report');
    Route::get('/reports/loan-due-report', [ReportController::class, 'loanDueReport'])->name('reports.loan-due-report');
    Route::get('/reports/soc-ledger-report', [ReportController::class, 'societyLedgerReport'])->name('reports.soc-ledger-report');
    Route::get('/reports/income-expense-report', [ReportController::class, 'incomeExpenseReport'])->name('reports.income-expense-report');
    Route::get('/reports/society-report', [ReportController::class, 'societyReport'])->name('reports.society-report');

    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
});
