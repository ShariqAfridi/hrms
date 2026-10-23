@extends('layouts.app')

@section('title', 'Payroll & Compensation Management')

@if(($isPayrollLocked ?? false) && (Auth::user()->isSuperAdmin() || Auth::user()->isDeveloperAdmin()))

    @section('content')
    @endsection

    @section('fullscreen_lock')
        <!-- PIN Challenge Screen Overlay (Super Admin Only) -->
        <style>
            @keyframes pinShake {
                0%, 100% { transform: translateX(0); }
                20%, 60% { transform: translateX(-8px); }
                40%, 80% { transform: translateX(8px); }
            }
            .animate-pin-shake {
                animation: pinShake 0.4s ease-in-out;
            }

            /* Fullscreen Glassmorphic Backdrop */
            .payroll-glass-backdrop {
                position: fixed !important;
                top: 0 !important;
                left: 0 !important;
                right: 0 !important;
                bottom: 0 !important;
                width: 100vw !important;
                height: 100vh !important;
                height: 100dvh !important;
                z-index: 99999 !important;
                background: radial-gradient(circle at 20% 20%, rgba(99, 102, 241, 0.20), transparent 50%),
                            radial-gradient(circle at 80% 80%, rgba(168, 85, 247, 0.16), transparent 50%),
                            rgba(8, 14, 28, 0.88) !important;
                backdrop-filter: blur(32px) saturate(180%) !important;
                -webkit-backdrop-filter: blur(32px) saturate(180%) !important;
            }

            /* Light Theme Backdrop */
            html.theme-light .payroll-glass-backdrop,
            .theme-light .payroll-glass-backdrop {
                background: radial-gradient(circle at 20% 20%, rgba(99, 102, 241, 0.14), transparent 50%),
                            radial-gradient(circle at 80% 80%, rgba(168, 85, 247, 0.10), transparent 50%),
                            rgba(241, 245, 249, 0.90) !important;
                backdrop-filter: blur(32px) saturate(180%) !important;
                -webkit-backdrop-filter: blur(32px) saturate(180%) !important;
            }

            /* Glassmorphic Modal Card */
            .payroll-glass-card {
                background: rgba(15, 23, 42, 0.82) !important;
                backdrop-filter: blur(24px) !important;
                -webkit-backdrop-filter: blur(24px) !important;
                border: 1px solid rgba(99, 102, 241, 0.32) !important;
                box-shadow: 0 30px 70px -15px rgba(0, 0, 0, 0.8),
                            0 0 50px rgba(79, 70, 229, 0.22),
                            inset 0 1px 1px 0 rgba(255, 255, 255, 0.15) !important;
            }

            /* Light Theme Card */
            html.theme-light .payroll-glass-card,
            .theme-light .payroll-glass-card {
                background: rgba(255, 255, 255, 0.95) !important;
                backdrop-filter: blur(24px) !important;
                -webkit-backdrop-filter: blur(24px) !important;
                border: 1px solid rgba(203, 213, 225, 0.95) !important;
                box-shadow: 0 25px 50px -12px rgba(99, 102, 241, 0.18),
                            0 4px 10px -2px rgba(0, 0, 0, 0.05),
                            inset 0 1px 1px 0 rgba(255, 255, 255, 0.95) !important;
            }

            /* Numpad Buttons */
            .payroll-numpad-btn {
                background: rgba(2, 6, 23, 0.8);
                border: 1px solid rgba(30, 41, 59, 0.9);
                color: #ffffff;
            }
            .payroll-numpad-btn:hover {
                background: rgba(79, 70, 229, 0.28);
                border-color: rgba(99, 102, 241, 0.6);
                color: #ffffff;
            }
            .payroll-numpad-btn:active {
                background: rgba(79, 70, 229, 0.5);
                transform: scale(0.95);
            }

            html.theme-light .payroll-numpad-btn,
            .theme-light .payroll-numpad-btn {
                background: rgba(248, 250, 252, 0.95);
                border: 1px solid rgba(226, 232, 240, 1);
                color: #0f172a;
            }
            html.theme-light .payroll-numpad-btn:hover,
            .theme-light .payroll-numpad-btn:hover {
                background: rgba(238, 242, 255, 1);
                border-color: rgba(129, 140, 248, 0.9);
                color: #4338ca;
            }

            /* Adaptive Typography */
            html.theme-light .payroll-theme-title,
            .theme-light .payroll-theme-title {
                color: #0f172a !important;
            }
            html.theme-light .payroll-theme-subtext,
            .theme-light .payroll-theme-subtext {
                color: #64748b !important;
            }
        </style>

        <script>
        function payrollVault() {
            return {
                enteredPin: '',
                isSubmitting: false,
                isUnlocked: false,
                hasError: false,
                errorMessage: '{{ $errors->first('payroll_pin') ?? '' }}',
                showPasswordFallback: false,
                passwordFallback: '',
                autoCheckTimer: null,
                errorResetTimer: null,

                appendDigit: function(digit) {
                    if (this.isUnlocked || this.isSubmitting) return;
                    if (this.enteredPin.length < 4) {
                        this.enteredPin += digit;
                        this.errorMessage = '';
                        this.hasError = false;

                        if (this.enteredPin.length === 4) {
                            this.verifyPin(true);
                        }
                    }
                },
                backspace: function() {
                    if (this.isUnlocked || this.isSubmitting) return;
                    this.enteredPin = this.enteredPin.slice(0, -1);
                    this.errorMessage = '';
                    this.hasError = false;
                },
                clearAll: function() {
                    if (this.isUnlocked || this.isSubmitting) return;
                    this.enteredPin = '';
                    this.errorMessage = '';
                    this.hasError = false;
                },
                verifyPin: async function(isFullOrManual) {
                    if (this.isSubmitting || this.isUnlocked) return;
                    if (!this.enteredPin || this.enteredPin.length !== 4) {
                        if (isFullOrManual) {
                            this.handleError('Please enter your 4-digit Security PIN.');
                        }
                        return;
                    }

                    this.isSubmitting = true;
                    const pinAttempt = this.enteredPin;

                    try {
                        const response = await fetch('{{ route('payroll.verify-pin') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({ pin: pinAttempt })
                        });
                        const data = await response.json();

                        if (response.ok && data.success) {
                            this.isUnlocked = true;
                            this.isSubmitting = true;
                            this.enteredPin = '';
                            this.errorMessage = '';
                            this.hasError = false;
                            window.location.replace("{{ route('payroll.index') }}");
                            return;
                        } else {
                            this.handleError(data.message || 'Invalid 4-digit PIN. Please try again.');
                        }
                    } catch (e) {
                        this.handleError('Verification failed. Please try again.');
                    }
                },
                handleError: function(msg) {
                    this.errorMessage = msg;
                    this.hasError = true;
                    this.isSubmitting = false;
                    this.enteredPin = '';

                    clearTimeout(this.errorResetTimer);
                    var self = this;
                    this.errorResetTimer = setTimeout(function() {
                        self.hasError = false;
                    }, 3000);
                },
                submitPasswordFallback: async function() {
                    if (!this.passwordFallback) {
                        this.handleError('Please enter your account password.');
                        return;
                    }
                    this.isSubmitting = true;
                    this.errorMessage = '';
                    this.hasError = false;

                    try {
                        const response = await fetch('{{ route('payroll.verify-pin') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({ password_fallback: this.passwordFallback })
                        });
                        const data = await response.json();
                        if (response.ok && data.success) {
                            this.isUnlocked = true;
                            this.isSubmitting = true;
                            this.showPasswordFallback = false;
                            window.location.replace("{{ route('payroll.index') }}");
                            return;
                        } else {
                            this.handleError(data.message || 'Incorrect account password.');
                        }
                    } catch (e) {
                        this.handleError('An error occurred. Please try again.');
                    }
                }
            };
        }
        </script>

        <div 
            x-data="payrollVault()"
            id="payrollVaultLockOverlay"
            x-show="!isUnlocked"
            x-transition:leave="transition ease-out duration-300"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95 pointer-events-none"
            @keydown.window="
                if (!isUnlocked && !showPasswordFallback) {
                    if ($event.key >= '0' && $event.key <= '9') {
                        appendDigit($event.key);
                    } else if ($event.key === 'Backspace') {
                        backspace();
                    } else if ($event.key === 'Enter' && enteredPin.length === 4) {
                        verifyPin(true);
                    }
                }
            "
            class="payroll-glass-backdrop flex items-center justify-center p-4 select-none"
        >
            <!-- Anti-Clickthrough Glass Modal Shield -->
            <div class="fixed inset-0" @click.stop=""></div>

            <!-- Background Ambient Glow Orbs -->
            <div class="fixed top-1/4 left-1/4 w-96 h-96 bg-indigo-600/20 rounded-full blur-3xl pointer-events-none -z-10 animate-pulse"></div>
            <div class="fixed bottom-1/4 right-1/4 w-96 h-96 bg-purple-600/15 rounded-full blur-3xl pointer-events-none -z-10"></div>

            <div class="relative w-full max-w-md payroll-glass-card rounded-3xl p-6 lg:p-8 text-center space-y-6 overflow-hidden">
                <!-- Background Ambient Glow -->
                <div class="absolute -top-12 -left-12 w-48 h-48 bg-indigo-500/15 rounded-full blur-3xl pointer-events-none"></div>
                <div class="absolute -bottom-12 -right-12 w-48 h-48 bg-purple-500/15 rounded-full blur-3xl pointer-events-none"></div>

                <!-- Vault Icon & Title -->
                <div class="relative z-10 space-y-2">
                    <div class="w-16 h-16 mx-auto rounded-3xl bg-gradient-to-tr from-indigo-600/30 via-indigo-500/20 to-purple-500/30 border border-indigo-500/30 flex items-center justify-center text-indigo-400 shadow-xl shadow-indigo-500/10 ring-4 ring-indigo-500/10">
                        <i class="fa-solid fa-shield-halved text-2xl animate-pulse"></i>
                    </div>
                    <h2 class="text-xl font-black text-white payroll-theme-title tracking-tight">Payroll Security Vault</h2>
                    <p class="text-xs text-slate-400 payroll-theme-subtext max-w-xs mx-auto">Admin verification required. Enter your 4-digit PIN to unlock the payroll ledgers automatically.</p>
                </div>

                <!-- Standard PIN Mode -->
                <div x-show="!showPasswordFallback" class="space-y-6 relative z-10">
                    <!-- PIN Digit Indicators with Shake on Error (Netflix Style 4 Dots) -->
                    <div class="flex items-center justify-center space-x-4 py-2" :class="hasError ? 'animate-pin-shake' : ''">
                        <template x-for="i in 4" :key="i">
                            <div 
                                class="w-5 h-5 rounded-full border-2 transition-all flex items-center justify-center"
                                :class="hasError 
                                    ? 'border-rose-500 bg-rose-500/30 ring-2 ring-rose-500/40 shadow-lg shadow-rose-500/30' 
                                    : (enteredPin.length >= i 
                                        ? 'bg-indigo-500 border-indigo-400 shadow-lg shadow-indigo-500/40 scale-110' 
                                        : 'border-slate-700 bg-slate-950/60')"
                            >
                                <span x-show="enteredPin.length >= i && !hasError" class="w-2 h-2 rounded-full bg-white"></span>
                                <span x-show="hasError" class="w-2 h-2 rounded-full bg-rose-400"></span>
                            </div>
                        </template>
                    </div>

                    <!-- Error Message Banner -->
                    <div x-show="errorMessage" x-cloak class="p-3 rounded-2xl bg-rose-500/15 border border-rose-500/30 text-rose-300 text-xs font-semibold flex items-center justify-center space-x-2 animate-pin-shake">
                        <i class="fa-solid fa-circle-xmark text-rose-400 shrink-0 text-sm"></i>
                        <span x-text="errorMessage"></span>
                    </div>

                    <!-- Interactive Numpad (Always fluid, never frozen) -->
                    <div class="grid grid-cols-3 gap-2.5 max-w-[260px] mx-auto">
                        <template x-for="num in ['1','2','3','4','5','6','7','8','9']" :key="num">
                            <button 
                                type="button" 
                                @click="appendDigit(num)" 
                                class="payroll-numpad-btn h-12 rounded-2xl text-lg font-bold transition-all shadow-md active:scale-95 flex items-center justify-center select-none cursor-pointer"
                                x-text="num"
                            ></button>
                        </template>
                        <button 
                            type="button" 
                            @click="clearAll()" 
                            class="payroll-numpad-btn h-12 rounded-2xl text-xs font-bold transition-all flex items-center justify-center select-none cursor-pointer"
                        >
                            C
                        </button>
                        <button 
                            type="button" 
                            @click="appendDigit('0')" 
                            class="payroll-numpad-btn h-12 rounded-2xl text-lg font-bold transition-all shadow-md active:scale-95 flex items-center justify-center select-none cursor-pointer"
                        >
                            0
                        </button>
                        <button 
                            type="button" 
                            @click="backspace()" 
                            class="payroll-numpad-btn h-12 rounded-2xl text-sm font-bold transition-all flex items-center justify-center select-none cursor-pointer"
                        >
                            <i class="fa-solid fa-delete-left"></i>
                        </button>
                    </div>

                    <!-- Unlock Button & Forgot PIN -->
                    <div class="space-y-3 pt-2">
                        <button 
                            type="button" 
                            @click="verifyPin(true)" 
                            :disabled="isSubmitting || enteredPin.length !== 4"
                            class="w-full py-3 bg-gradient-to-r from-indigo-600 to-indigo-500 hover:from-indigo-500 hover:to-indigo-600 disabled:opacity-50 disabled:cursor-not-allowed text-white font-bold text-xs rounded-xl shadow-xl shadow-indigo-600/30 transition-all flex items-center justify-center space-x-2"
                        >
                            <i class="fa-solid" :class="isSubmitting ? 'fa-spinner fa-spin' : 'fa-lock-open'"></i>
                            <span x-text="isSubmitting ? 'Verifying...' : 'Unlock Payroll Vault'"></span>
                        </button>

                        <div class="flex items-center justify-between text-xs pt-1">
                            <button 
                                type="button" 
                                @click="showPasswordFallback = true; errorMessage = ''; hasError = false;" 
                                class="text-indigo-400 hover:text-indigo-300 font-semibold transition-colors"
                            >
                                Forgot PIN? Use Password
                            </button>
                            <a href="{{ route('profile.index') }}#payroll-pin" class="text-slate-400 hover:text-slate-200 transition-colors">
                                Reset PIN in Profile
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Password Fallback Mode -->
                <div x-show="showPasswordFallback" x-cloak class="space-y-5 text-left relative z-10">
                    <div class="p-3.5 rounded-2xl bg-slate-950/70 border border-slate-800 text-xs text-slate-300 flex items-start space-x-2.5">
                        <i class="fa-solid fa-key text-amber-400 mt-0.5 shrink-0"></i>
                        <div>
                            <span>Enter your current account login password to unlock your payroll session.</span>
                        </div>
                    </div>

                    <!-- Error Message Banner -->
                    <div x-show="errorMessage" x-cloak class="p-3 rounded-2xl bg-rose-500/15 border border-rose-500/30 text-rose-300 text-xs font-semibold flex items-center justify-center space-x-2 animate-pin-shake">
                        <i class="fa-solid fa-circle-xmark text-rose-400 shrink-0 text-sm"></i>
                        <span x-text="errorMessage"></span>
                    </div>

                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold text-slate-300 payroll-theme-title">Account Password</label>
                        <input 
                            type="password" 
                            x-model="passwordFallback" 
                            @keydown.enter="submitPasswordFallback()"
                            placeholder="••••••••" 
                            class="w-full px-4 py-2.5 bg-slate-950/80 border border-slate-800 rounded-xl text-xs text-white focus:outline-none focus:border-indigo-500"
                        >
                    </div>

                    <div class="space-y-3 pt-2">
                        <button 
                            type="button" 
                            @click="submitPasswordFallback()" 
                            :disabled="isSubmitting || !passwordFallback"
                            class="w-full py-3 bg-gradient-to-r from-indigo-600 to-indigo-500 hover:from-indigo-500 hover:to-indigo-600 disabled:opacity-50 text-white font-bold text-xs rounded-xl shadow-xl shadow-indigo-600/30 transition-all flex items-center justify-center space-x-2"
                        >
                            <i class="fa-solid" :class="isSubmitting ? 'fa-spinner fa-spin' : 'fa-lock-open'"></i>
                            <span x-text="isSubmitting ? 'Verifying Password...' : 'Unlock via Password'"></span>
                        </button>

                        <div class="flex items-center justify-between text-xs pt-1">
                            <button 
                                type="button" 
                                @click="showPasswordFallback = false; errorMessage = ''; hasError = false;" 
                                class="text-indigo-400 hover:text-indigo-300 font-semibold transition-colors"
                            >
                                Back to PIN Entry
                            </button>
                            <a href="{{ route('profile.index') }}#payroll-pin" class="text-amber-400 hover:text-amber-300 font-medium transition-colors flex items-center space-x-1">
                                <i class="fa-solid fa-key text-[10px]"></i>
                                <span>Reset PIN in Profile</span>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Fallback Hidden Form for Standard Submits -->
                <form id="payrollPinHiddenForm" action="{{ route('payroll.verify-pin') }}" method="POST" class="hidden">
                    @csrf
                    <input type="hidden" name="pin" :value="enteredPin">
                </form>
            </div>
        </div>

        <!-- Active Anti-Tamper Guard for Lock Overlay -->
        <script>
        (function() {
            var overlay = document.getElementById('payrollVaultLockOverlay');
            if (overlay) {
                var guard = new MutationObserver(function() {
                    if (!document.contains(overlay)) {
                        document.body && (document.body.innerHTML = '');
                        window.location.reload();
                    }
                });
                guard.observe(document.documentElement, { childList: true, subtree: true });
            }
        })();
        </script>
    @endsection

@else

    @section('content')

<div x-data="{ 
    activeTab: '{{ $activeTab ?? (!Auth::user()->canManagePayroll() ? 'requests' : 'staff') }}',
    revealedStaffId: null,
    revealedPayslipId: null,
    revealedAdvId: null,
    toggleStaffRow(id) {
        this.revealedStaffId = (this.revealedStaffId === id) ? null : id;
    },
    togglePayslipRow(id) {
        this.revealedPayslipId = (this.revealedPayslipId === id) ? null : id;
    },
    toggleAdvRow(id) {
        this.revealedAdvId = (this.revealedAdvId === id) ? null : id;
    },
    closeRevealedRows() {
        if (!this.payModalOpen && !this.viewPayslipModalOpen && !this.grantAdvanceModalOpen && !this.rejectModalOpen && !this.createModalOpen && !this.editModalOpen && !this.runModalOpen && !this.assignModalOpen && !this.requestPayslipModalOpen) {
            this.revealedStaffId = null;
            this.revealedPayslipId = null;
            this.revealedAdvId = null;
        }
    },
    createModalOpen: false, 
    editModalOpen: false, 
    assignModalOpen: false, 
    runModalOpen: false,
    grantAdvanceModalOpen: false,
    viewPayslipModalOpen: false,
    payModalOpen: false,
    rejectModalOpen: false,
    requestPayslipModalOpen: false,
    rejectRequestId: '',
    rejectEmployeeName: '',
    currentPayslip: null,
    activePayroll: {},
    targetPayrollId: '',
    selectedEmployee: {},
    openRejectModal(id, name) {
        this.rejectRequestId = id;
        this.rejectEmployeeName = name;
        this.rejectModalOpen = true;
    },
    payForm: {
        user_id: '',
        period_start: '{{ date('Y-m-01') }}',
        period_end: '{{ date('Y-m-t') }}',
        pay_date: '{{ date('Y-m-d') }}',
        base_salary: 0,
        fuel_allowance: 0,
        special_allowance: 0,
        cash_allowance: 0,
        other_allowances: 0,
        advance_deduction: 0,
        late_deduction: 0,
        late_days_deducted: 0,
        absent_deduction: 0,
        eobi_deduction: 0,
        other_deductions: 0,
        tax: 0,
        payment_method: 'BANK_TRANSFER',
        template_style: 'standard',
        notes: ''
    },
    openPayModal(emp) {
        this.selectedEmployee = emp;
        this.payForm.user_id = emp.id;
        this.payForm.base_salary = Number(emp.base_salary || 0);
        this.payForm.fuel_allowance = Number(emp.fuel_allowance || 0);
        this.payForm.special_allowance = Number(emp.special_allowance || 0);
        this.payForm.cash_allowance = Number(emp.cash_allowance || 0);
        this.payForm.other_allowances = 0;
        this.payForm.advance_deduction = Number(emp.pending_advance_amount || 0);
        this.payForm.late_deduction = Number(emp.suggested_late_deduction || 0);
        this.payForm.late_days_deducted = Number(emp.suggested_late_days || 0);
        this.payForm.absent_deduction = 0;
        this.payForm.eobi_deduction = Number(emp.eobi_deduction || 0);
        this.payForm.other_deductions = 0;
        
        let defaultTax = {{ (float) \App\Models\SystemSetting::get('company_tax_rate', 12.0) }};
        let taxRate = Number(
            (emp.payroll && Number(emp.payroll.tax_rate) > 0) ? emp.payroll.tax_rate :
            ((emp.organization && Number(emp.organization.payroll_tax_rate) > 0) ? emp.organization.payroll_tax_rate :
            defaultTax)
        );
        this.currentTaxRate = taxRate;
        let taxable = Math.max(0, (Number(this.payForm.base_salary) || 0) + (Number(this.payForm.fuel_allowance) || 0) + (Number(this.payForm.special_allowance) || 0) - (Number(this.payForm.eobi_deduction) || 0));
        this.payForm.tax = Math.round((taxable * (taxRate / 100)) * 100) / 100;
        
        this.payForm.payment_method = emp.payment_method || 'BANK_TRANSFER';
        this.payForm.template_style = 'standard';
        this.payForm.notes = '';
        this.payModalOpen = true;
    },
    recalculateTax() {
        let defaultTax = {{ (float) \App\Models\SystemSetting::get('company_tax_rate', 12.0) }};
        let emp = this.selectedEmployee;
        let taxRate = Number(
            (emp && emp.payroll && Number(emp.payroll.tax_rate) > 0) ? emp.payroll.tax_rate :
            ((emp && emp.organization && Number(emp.organization.payroll_tax_rate) > 0) ? emp.organization.payroll_tax_rate :
            defaultTax)
        );
        this.currentTaxRate = taxRate;
        let taxable = Math.max(0, (Number(this.payForm.base_salary) || 0) + (Number(this.payForm.fuel_allowance) || 0) + (Number(this.payForm.special_allowance) || 0) - (Number(this.payForm.eobi_deduction) || 0));
        this.payForm.tax = Math.round((taxable * (taxRate / 100)) * 100) / 100;
    },
    calculateGross() {
        return Number(this.payForm.base_salary || 0) + 
               Number(this.payForm.fuel_allowance || 0) + 
               Number(this.payForm.special_allowance || 0) + 
               Number(this.payForm.other_allowances || 0);
    },
    calculateDeductions() {
        return Number(this.payForm.advance_deduction || 0) + 
               Number(this.payForm.late_deduction || 0) + 
               Number(this.payForm.absent_deduction || 0) + 
               Number(this.payForm.eobi_deduction || 0) + 
               Number(this.payForm.other_deductions || 0) + 
               Number(this.payForm.tax || 0);
    },
    calculateNet() {
        return Math.max(0, this.calculateGross() - this.calculateDeductions());
    }
}" @keydown.escape.window="closeRevealedRows()" class="space-y-6">

    <!-- Top Page Header & Quick Actions -->
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-white tracking-tight flex items-center space-x-3">
                <span class="w-9 h-9 rounded-xl bg-indigo-600/20 border border-indigo-500/30 flex items-center justify-center text-indigo-400 text-lg">
                    <i class="fa-solid fa-money-check-dollar"></i>
                </span>
                <span>{{ !Auth::user()->canManagePayroll() ? 'My Payslips & Remuneration Requests' : 'Corporate Payroll & Remuneration Hub' }}</span>
            </h1>
            <p class="text-xs font-medium text-slate-400 mt-1">
                {{ !Auth::user()->canManagePayroll() ? 'Request official monthly salary vouchers, track clearance approvals from HR & System Admin, and view released payslips.' : 'Manage staff salaries, view bank transfer vs cash in hand distributions, review employee payslip requests, and clear monthly payroll.' }}
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2.5">
@if(!Auth::user()->canManagePayroll())
                <button @click="requestPayslipModalOpen = true" class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs rounded-xl shadow-lg shadow-indigo-600/30 transition-all flex items-center space-x-2">
                    <i class="fa-solid fa-file-circle-plus"></i>
                    <span>Request Payslip</span>
                </button>
            @else
                @if(($hasPayrollPin ?? false) && (Auth::user()->isSuperAdmin() || Auth::user()->isDeveloperAdmin()))
                    <form action="{{ route('payroll.lock') }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="px-3.5 py-2.5 bg-slate-800/90 hover:bg-rose-950/40 text-slate-300 hover:text-rose-400 font-bold text-xs rounded-xl border border-slate-700/80 hover:border-rose-500/30 shadow-md transition-all flex items-center space-x-1.5" title="Lock Payroll Vault immediately">
                            <i class="fa-solid fa-lock text-rose-400"></i>
                            <span>Lock Hub</span>
                        </button>
                    </form>
                    <a href="{{ route('profile.index') }}#payroll-pin" class="px-3 py-2.5 bg-slate-800/90 hover:bg-slate-700 text-slate-300 hover:text-white font-bold text-xs rounded-xl border border-slate-700/80 shadow-md transition-all flex items-center space-x-1.5" title="Manage or Reset Security PIN">
                        <i class="fa-solid fa-key text-amber-400"></i>
                        <span>PIN Settings</span>
                    </a>
                @endif

                <button @click="grantAdvanceModalOpen = true" class="px-3.5 py-2.5 bg-emerald-600/20 hover:bg-emerald-600 text-emerald-400 hover:text-white font-bold text-xs rounded-xl border border-emerald-500/30 shadow-md transition-all flex items-center space-x-2">
                    <i class="fa-solid fa-hand-holding-dollar"></i>
                    <span>Grant Advance</span>
                </button>

                <button @click="assignModalOpen = true" class="px-3.5 py-2.5 bg-slate-800 hover:bg-slate-700 text-slate-200 font-bold text-xs rounded-xl border border-slate-700 shadow-md transition-all flex items-center space-x-2">
                    <i class="fa-solid fa-user-gear text-indigo-400"></i>
                    <span>Assign Staff</span>
                </button>

                <button @click="createModalOpen = true" class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs rounded-xl shadow-lg shadow-indigo-600/30 transition-all flex items-center space-x-2">
                    <i class="fa-solid fa-plus"></i>
                    <span>Create Payroll Tier</span>
                </button>
            @endif
        </div>
    </div>

    <!-- Navigation Tabs Bar -->
    <div class="flex flex-wrap items-center gap-1.5 bg-slate-950/80 p-1.5 rounded-2xl border border-slate-800 text-xs font-bold w-fit shadow-xl">
        @if(Auth::user()->canManagePayroll())
            <!-- TAB 1: Staff Salary & Direct Pay (Default for HR / Admin) -->
            <button type="button" @click="activeTab = 'staff'" :class="activeTab === 'staff' ? 'bg-indigo-600 text-white shadow-md' : 'text-slate-400 hover:text-slate-200'" class="px-4 py-2 rounded-xl transition-all flex items-center space-x-2">
                <i class="fa-solid fa-users text-xs"></i>
                <span>Staff Salary &amp; Pay</span>
                @if(isset($staffTotalCount) && $staffTotalCount > 0)
                    <span class="px-1.5 py-0.5 rounded-full text-[10px] bg-slate-900 border border-slate-700 text-indigo-300 font-extrabold">{{ $staffTotalCount }}</span>
                @endif
            </button>

            <!-- TAB 2: Payslip Requests (Incoming from non-admins) -->
            <button type="button" @click="activeTab = 'requests'" :class="activeTab === 'requests' ? 'bg-indigo-600 text-white shadow-md' : 'text-slate-400 hover:text-slate-200'" class="px-4 py-2 rounded-xl transition-all flex items-center space-x-2">
                <i class="fa-solid fa-file-signature text-xs"></i>
                <span>Payslip Requests</span>
                @if(isset($pendingPayslipRequestsCount) && $pendingPayslipRequestsCount > 0)
                    <span class="px-1.5 py-0.5 rounded-full text-[10px] bg-amber-500 text-slate-950 font-black">{{ $pendingPayslipRequestsCount }}</span>
                @endif
            </button>

            <!-- TAB 3: Payroll History -->
            <button type="button" @click="activeTab = 'history'" :class="activeTab === 'history' ? 'bg-indigo-600 text-white shadow-md' : 'text-slate-400 hover:text-slate-200'" class="px-4 py-2 rounded-xl transition-all flex items-center space-x-2">
                <i class="fa-solid fa-clock-rotate-left text-xs"></i>
                <span>Disbursal History</span>
                @if(isset($historyTotalCount) && $historyTotalCount > 0)
                    <span class="px-1.5 py-0.5 rounded-full text-[10px] bg-slate-900 border border-slate-700 text-emerald-400 font-extrabold">{{ $historyTotalCount }}</span>
                @endif
            </button>

            <!-- TAB 4: Reports & Analytics -->
            <button type="button" @click="activeTab = 'reports'" :class="activeTab === 'reports' ? 'bg-indigo-600 text-white shadow-md' : 'text-slate-400 hover:text-slate-200'" class="px-4 py-2 rounded-xl transition-all flex items-center space-x-2">
                <i class="fa-solid fa-chart-pie text-xs"></i>
                <span>Reports &amp; Analytics</span>
            </button>

            <!-- TAB 5: Company Profiles -->
            <button type="button" @click="activeTab = 'overview'" :class="activeTab === 'overview' ? 'bg-indigo-600 text-white shadow-md' : 'text-slate-400 hover:text-slate-200'" class="px-4 py-2 rounded-xl transition-all flex items-center space-x-2">
                <i class="fa-solid fa-building text-xs"></i>
                <span>Company Profiles</span>
            </button>
        @else
            <!-- Non-Admin Tabs: Payslip Requests Tracker -->
            <button type="button" @click="activeTab = 'requests'" :class="activeTab === 'requests' ? 'bg-indigo-600 text-white shadow-md' : 'text-slate-400 hover:text-slate-200'" class="px-4 py-2 rounded-xl transition-all flex items-center space-x-2">
                <i class="fa-solid fa-file-signature text-xs"></i>
                <span>My Payslip Requests</span>
                @if(isset($myApprovedRequestsCount) && $myApprovedRequestsCount > 0)
                    <span class="px-1.5 py-0.5 rounded-full text-[10px] bg-emerald-500/20 border border-emerald-500/30 text-emerald-400 font-extrabold">{{ $myApprovedRequestsCount }} Released</span>
                @endif
            </button>
        @endif

        <!-- Advance Salary & Loans (available to both, scoped by controller) -->
        <button type="button" @click="activeTab = 'advances'" :class="activeTab === 'advances' ? 'bg-indigo-600 text-white shadow-md' : 'text-slate-400 hover:text-slate-200'" class="px-4 py-2 rounded-xl transition-all flex items-center space-x-2">
            <i class="fa-solid fa-hand-holding-dollar text-xs"></i>
            <span>Advance Salary &amp; Loans</span>
            @if(isset($advanceSalaries) && count($advanceSalaries) > 0)
                <span class="px-1.5 py-0.5 rounded-full text-[10px] bg-slate-900 border border-slate-700 text-amber-400 font-extrabold">{{ count($advanceSalaries) }}</span>
            @endif
        </button>
    </div>

    <!-- ============================================================= -->
    <!-- TAB 1: STAFF SALARY MANAGEMENT & DIRECT SINGLE-EMPLOYEE PAY   -->
    <!-- ============================================================= -->
    @if(Auth::user()->canManagePayroll())
    <div x-show="activeTab === 'staff'" class="space-y-6">

        @if(session('just_paid_slip'))
            @php $justPaid = session('just_paid_slip'); @endphp
            <div class="p-4 md:p-5 rounded-2xl bg-gradient-to-r from-emerald-950/90 via-slate-900 to-indigo-950/90 border border-emerald-500/50 shadow-2xl flex flex-col sm:flex-row sm:items-center justify-between gap-4 animate-fade-in">
                <div class="flex items-center space-x-3.5">
                    <div class="w-12 h-12 rounded-2xl bg-emerald-500/20 border border-emerald-500/40 text-emerald-400 flex items-center justify-center text-xl shrink-0 shadow-lg">
                        <i class="fa-solid fa-circle-check"></i>
                    </div>
                    <div>
                        <div class="flex items-center space-x-2">
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">Salary Disbursed Successfully</span>
                            <span class="text-xs text-slate-400">• {{ $justPaid['month'] ?? '' }}</span>
                        </div>
                        <h4 class="text-base font-extrabold text-white mt-0.5">
                            {{ $justPaid['currency'] ?? 'PKR' }} {{ $justPaid['amount'] ?? '0.00' }} disbursed to {{ $justPaid['employee_name'] ?? 'Staff Member' }}
                        </h4>
                        <p class="text-xs text-slate-400">Official executive salary slip generated. You can view or download it directly right now.</p>
                    </div>
                </div>
                <div class="flex items-center gap-2.5 shrink-0">
                    <a href="{{ route('payroll.payslip.download', $justPaid['id']) }}" target="_blank" class="px-5 py-2.5 bg-gradient-to-r from-emerald-600 to-emerald-500 hover:from-emerald-500 hover:to-emerald-600 text-white font-bold text-xs rounded-xl shadow-lg shadow-emerald-600/30 transition-all flex items-center space-x-2">
                        <i class="fa-solid fa-file-pdf"></i>
                        <span>Salary Slip</span>
                    </a>
                </div>
            </div>
        @endif
        
        <!-- Dual Salary Structure Breakdown & KPI Cards for Super Admin & HR (4 Columns in a Row) -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- 1. Enrolled Staff -->
            <div class="bg-slate-950/60 border border-slate-800 rounded-2xl p-4 shadow-xl flex items-center justify-between">
                <div class="space-y-1 min-w-0">
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Total Staff</p>
                    <h3 class="text-xl font-black text-white truncate">{{ $staffTotalCount }} <span class="text-[11px] text-slate-500 font-medium">Staff</span></h3>
                    <p class="text-[10px] text-slate-500 truncate">Across all companies</p>
                </div>
                <div class="w-10 h-10 rounded-xl bg-indigo-500/10 border border-indigo-500/20 text-indigo-400 flex items-center justify-center text-base shrink-0 ml-3">
                    <i class="fa-solid fa-users"></i>
                </div>
            </div>

            <!-- 2. Bank Transfer Structure (Component 1) -->
            <div class="bg-slate-950/60 border border-indigo-500/30 rounded-2xl p-4 shadow-xl flex items-center justify-between">
                <div class="space-y-1 min-w-0">
                    <div class="flex items-center space-x-1.5">
                        <span class="w-2 h-2 rounded-full bg-indigo-400 shrink-0"></span>
                        <p class="text-[10px] font-extrabold text-indigo-300 uppercase tracking-wider truncate">Bank Transfer</p>
                    </div>
                    <h3 class="text-base font-black text-white font-mono truncate">PKR {{ number_format($staffBankCommitment, 0) }}</h3>
                    <p class="text-[10px] text-slate-400 truncate">Direct deposit payroll</p>
                </div>
                <div class="w-10 h-10 rounded-xl bg-indigo-500/10 border border-indigo-500/20 text-indigo-400 flex items-center justify-center text-base shrink-0 ml-3">
                    <i class="fa-solid fa-building-columns"></i>
                </div>
            </div>

            <!-- 3. Cash in Hand Structure (Component 2) -->
            <div class="bg-slate-950/60 border border-amber-500/30 rounded-2xl p-4 shadow-xl flex items-center justify-between">
                <div class="space-y-1 min-w-0">
                    <div class="flex items-center space-x-1.5">
                        <span class="w-2 h-2 rounded-full bg-amber-400 shrink-0"></span>
                        <p class="text-[10px] font-extrabold text-amber-300 uppercase tracking-wider truncate">Cash in Hand</p>
                    </div>
                    <h3 class="text-base font-black text-amber-300 font-mono truncate">PKR {{ number_format($staffCashCommitment, 0) }}</h3>
                    <p class="text-[10px] text-amber-400/80 font-medium truncate">Confidential cash</p>
                </div>
                <div class="w-10 h-10 rounded-xl bg-amber-500/10 border border-amber-500/20 text-amber-400 flex items-center justify-center text-base shrink-0 ml-3">
                    <i class="fa-solid fa-hand-holding-dollar"></i>
                </div>
            </div>

            <!-- 4. Total Combined Commitment -->
            <div class="bg-slate-950/60 border border-cyan-500/30 rounded-2xl p-4 shadow-xl flex items-center justify-between">
                <div class="space-y-1 min-w-0">
                    <p class="text-[10px] font-bold text-cyan-300 uppercase tracking-wider">Combined Total</p>
                    <h3 class="text-base font-black text-cyan-400 font-mono truncate">PKR {{ number_format($staffTotalCommitment, 0) }}</h3>
                    <p class="text-[10px] text-slate-500 truncate">Bank + Cash monthly</p>
                </div>
                <div class="w-10 h-10 rounded-xl bg-cyan-500/10 border border-cyan-500/20 text-cyan-400 flex items-center justify-center text-base shrink-0 ml-3">
                    <i class="fa-solid fa-wallet"></i>
                </div>
            </div>

            <!-- 5. Disbursed This Month -->
            <div class="bg-slate-950/60 border border-emerald-500/30 rounded-2xl p-4 shadow-xl flex items-center justify-between col-span-1 sm:col-span-1 lg:col-span-2">
                <div class="space-y-1 min-w-0">
                    <p class="text-[10px] font-bold text-emerald-300 uppercase tracking-wider">Paid This Month</p>
                    <h3 class="text-xl font-black text-emerald-400 font-mono truncate">PKR {{ number_format($staffPaidAmount, 0) }}</h3>
                    <p class="text-[11px] text-emerald-500/80 font-semibold truncate">{{ $staffPaidCount }} of {{ $staffTotalCount }} Staff Paid ({{ $staffTotalCount > 0 ? round(($staffPaidCount / $staffTotalCount) * 100) : 0 }}%)</p>
                </div>
                <div class="w-10 h-10 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 flex items-center justify-center text-base shrink-0 ml-3">
                    <i class="fa-solid fa-circle-check"></i>
                </div>
            </div>

            <!-- 6. Pending Disbursals -->
            <div class="bg-slate-950/60 border border-slate-800 rounded-2xl p-4 shadow-xl flex items-center justify-between col-span-1 sm:col-span-1 lg:col-span-2">
                <div class="space-y-1 min-w-0">
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Pending Pay</p>
                    <h3 class="text-xl font-black text-amber-400 truncate">{{ $staffPendingCount }} <span class="text-[11px] text-slate-500 font-medium">Staff Pending</span></h3>
                    <p class="text-[11px] text-amber-500/80 font-semibold truncate">PKR {{ number_format(max(0, $staffTotalCommitment - $staffPaidAmount), 0) }} awaiting payment</p>
                </div>
                <div class="w-10 h-10 rounded-xl bg-amber-500/10 border border-amber-500/20 text-amber-400 flex items-center justify-center text-base shrink-0 ml-3">
                    <i class="fa-solid fa-hourglass-half"></i>
                </div>
            </div>
        </div>

        <!-- Filter & Search Toolbar -->
        <div class="bg-slate-950/60 border border-slate-800 rounded-2xl p-4 shadow-xl">
            <form action="{{ route('payroll.index') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                <input type="hidden" name="tab" value="staff">

                <!-- Company Selector -->
                <div>
                    <label class="block text-[11px] font-bold text-slate-400 mb-1"><i class="fa-solid fa-building text-indigo-400 mr-1"></i>Company</label>
                    <select name="staff_org_id" onchange="this.form.submit()" class="w-full px-3 py-2 bg-slate-900 border border-slate-800 rounded-xl text-xs font-bold text-white focus:outline-none focus:border-indigo-500">
                        <option value="">All Companies ({{ count($organizations) }})</option>
                        @foreach($organizations as $org)
                            <option value="{{ $org->id }}" {{ ($staffOrgId ?? '') == $org->id ? 'selected' : '' }}>
                                {{ $org->name }} ({{ $org->currency }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Department Selector -->
                <div>
                    <label class="block text-[11px] font-bold text-slate-400 mb-1"><i class="fa-solid fa-sitemap text-indigo-400 mr-1"></i>Department</label>
                    <select name="staff_dept_id" onchange="this.form.submit()" class="w-full px-3 py-2 bg-slate-900 border border-slate-800 rounded-xl text-xs font-bold text-white focus:outline-none focus:border-indigo-500">
                        <option value="">All Departments</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept->id }}" {{ ($staffDeptId ?? '') == $dept->id ? 'selected' : '' }}>
                                {{ $dept->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Search Input / Submit -->
                <div class="lg:col-span-2">
                    <label class="block text-[11px] font-bold text-slate-400 mb-1"><i class="fa-solid fa-magnifying-glass text-indigo-400 mr-1"></i>Search Staff</label>
                    <div class="flex items-center space-x-2">
                        <input type="text" name="staff_search" value="{{ $staffSearch ?? '' }}" placeholder="Search by name or email..." class="w-full px-3 py-2 bg-slate-900 border border-slate-800 rounded-xl text-xs font-medium text-white focus:outline-none focus:border-indigo-500">
                        <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-bold shrink-0">Search</button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Staff List Table with Divided Salary Structure -->
        <div @click.outside="closeRevealedRows()" class="bg-slate-950/60 border border-slate-800 rounded-3xl overflow-hidden shadow-2xl space-y-4">
            <div class="p-6 border-b border-slate-800 flex items-center justify-between">
                <div>
                    <h3 class="text-base font-bold text-white tracking-tight">Staff Salary Structure &amp; Direct Disbursal</h3>
                    <p class="text-xs text-slate-400">Review individual salary structures divided into official Bank Transfer and confidential Cash in Hand.</p>
                </div>
                <span class="px-3 py-1 bg-slate-900 border border-slate-800 text-slate-300 font-bold text-xs rounded-xl">
                    {{ $staffTotalCount }} Staff Members
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-900/80 border-b border-slate-800 text-[11px] font-bold text-slate-400 uppercase tracking-wider">
                            <th class="py-3.5 px-6 whitespace-nowrap">Employee</th>
                            <th class="py-3.5 px-6 whitespace-nowrap">Company &amp; Dept</th>
                            <th class="py-3.5 px-6 whitespace-nowrap">Bank Transfer Salary</th>
                            <th class="py-3.5 px-6 whitespace-nowrap">Cash in Hand</th>
                            <th class="py-3.5 px-6 whitespace-nowrap">Total Remuneration</th>
                            <th class="py-3.5 px-6 whitespace-nowrap">Pending Advance</th>
                            <th class="py-3.5 px-6 whitespace-nowrap">Current Status</th>
                            <th class="py-3.5 px-6 text-right whitespace-nowrap">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/80 text-xs">
                        @forelse($employees as $emp)
                            @php
                                $org = $emp->organization;
                                $currency = $org->currency ?? 'PKR';
                                $allowancesTotal = (float)($emp->fuel_allowance ?? 0) + (float)($emp->special_allowance ?? 0);
                                $bankSalaryTotal = (float)($emp->base_salary ?? 0) + $allowancesTotal;
                                $cashInHand = (float)($emp->cash_allowance ?? 0);
                                $totalComp = $bankSalaryTotal + $cashInHand;
                                $isPaid = $emp->is_paid_this_month ?? false;
                                $slip = $emp->current_month_payslip ?? null;
                                
                                $staffSlipPayload = null;
                                if ($isPaid && $slip) {
                                    $slipOrg = $emp->organization;
                                    $fuel = (float)($slip->fuel_allowance ?? 0);
                                    $special = (float)($slip->special_allowance ?? 0);
                                    $otherAllow = max(0, (float)$slip->allowances - $fuel - $special);
                                    $grossSal = (float)$slip->base_salary + (float)$slip->allowances;

                                    $advanceDed = (float)($slip->advance_salary_deduction ?? 0);
                                    $lateDed = (float)($slip->late_deduction ?? 0);
                                    $lateDays = (int)($slip->late_days_deducted ?? 0);
                                    $absentDed = (float)($slip->absent_deduction ?? 0);
                                    $eobiDed = (float)($slip->eobi_deduction ?? 0);
                                    $otherDed = max(0, (float)$slip->deductions - $advanceDed - $lateDed - $absentDed - $eobiDed);
                                    $grossDed = (float)$slip->deductions + (float)$slip->tax;

                                    $staffSlipPayload = [
                                        'id' => $slip->id,
                                        'ref_no' => 'PAY-' . str_pad($slip->id, 6, '0', STR_PAD_LEFT),
                                        'salary_period' => !empty($slip->payment_month) ? date('F Y', strtotime($slip->payment_month . '-01')) : date('F Y', strtotime(($currentMonth ?? date('Y-m')) . '-01')),
                                        'pay_date' => !empty($slip->pay_date) ? date('d M Y', strtotime($slip->pay_date)) : date('d M Y', strtotime($slip->created_at)),
                                        'employee_name' => $emp->name,
                                        'employee_code' => $emp->employee_id ?? ('EMP-' . str_pad($emp->id, 4, '0', STR_PAD_LEFT)),
                                        'designation' => $emp->designation->title ?? ($emp->designation_name ?? 'Staff Member'),
                                        'department' => $emp->department->name ?? 'Operations',
                                        'bank_account' => $emp->bank_account ?? 'Direct Bank Transfer',
                                        'payment_method' => $slip->payment_method ?? 'Direct Bank Transfer',
                                        'company_name' => $slipOrg->name ?? 'AxeCorp Technologies',
                                        'company_logo' => $slipOrg->logo_url ?? asset('images/logo.png'),
                                        'company_address' => $slipOrg->address ?? '',
                                        'company_tax_no' => $slipOrg->tax_number ?? '',
                                        'company_email' => $slipOrg->email ?? '',
                                        'company_phone' => $slipOrg->phone ?? '',
                                        'currency' => $currency,
                                        'base_salary' => (float)$slip->base_salary,
                                        'fuel_allowance' => $fuel,
                                        'special_allowance' => $special,
                                        'other_allowances' => $otherAllow,
                                        'gross_salary' => $grossSal,
                                        'advance_deduction' => $advanceDed,
                                        'late_deduction' => $lateDed,
                                        'late_days_deducted' => $lateDays,
                                        'absent_deduction' => $absentDed,
                                        'eobi_deduction' => $eobiDed,
                                        'other_deductions' => $otherDed,
                                        'tax' => (float)$slip->tax,
                                        'gross_deduction' => $grossDed,
                                        'net_pay' => (float)$slip->net_pay,
                                        'notes' => $slip->notes ?? '',
                                    ];
                                }
                            @endphp
                            <tr 
                                @click="toggleStaffRow('{{ $emp->id }}')" 
                                :class="revealedStaffId === '{{ $emp->id }}' ? 'privacy-row-revealed border-l-4 border-indigo-500' : 'hover:bg-slate-900/40 border-l-4 border-transparent'"
                                class="transition-all duration-200 cursor-pointer group"
                                title="Click to toggle salary privacy"
                            >
                                <!-- Employee info -->
                                <td class="py-4 px-6 whitespace-nowrap">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-indigo-600 to-indigo-800 text-white flex items-center justify-center font-bold text-xs shadow-md shrink-0">
                                            @if($emp->avatar_url)
                                                <img src="{{ $emp->avatar_url }}" alt="{{ $emp->name }}" class="w-full h-full object-cover rounded-xl">
                                            @else
                                                {{ strtoupper(substr($emp->name, 0, 2)) }}
                                            @endif
                                        </div>
                                        <div>
                                            <div class="flex items-center space-x-2">
                                                <p class="font-bold text-white text-sm">{{ $emp->name }}</p>
                                                <span x-show="revealedStaffId === '{{ $emp->id }}'" x-cloak class="inline-flex items-center gap-1 text-[9px] font-bold text-emerald-400 bg-emerald-500/10 px-1.5 py-0.5 rounded border border-emerald-500/20">
                                                    <i class="fa-solid fa-eye text-[8px]"></i> Visible
                                                </span>
                                                <span x-show="revealedStaffId !== '{{ $emp->id }}'" class="inline-flex items-center gap-1 text-[9px] font-medium text-slate-500 bg-slate-900/80 px-1.5 py-0.5 rounded border border-slate-800 group-hover:text-slate-400 transition-colors">
                                                    <i class="fa-solid fa-eye-slash text-[8px]"></i> Click to view
                                                </span>
                                            </div>
                                            <p class="text-slate-400 text-[11px]">{{ $emp->designation->title ?? 'Staff Member' }} &bull; <span class="font-mono text-slate-500">ID: {{ substr(preg_replace('/[^0-9]/', '', $emp->id), 0, 4) ?: '00' }}</span></p>
                                        </div>
                                    </div>
                                </td>

                                <!-- Company & Dept -->
                                <td class="py-4 px-6 whitespace-nowrap">
                                    <div :class="revealedStaffId === '{{ $emp->id }}' ? 'privacy-clear' : 'privacy-blur'">
                                        <div class="space-y-1">
                                            <span class="inline-flex items-center space-x-1.5 px-2.5 py-0.5 bg-slate-900 border border-slate-800 rounded-lg text-slate-300 font-semibold text-[11px]">
                                                @if($org && $org->logo_url)
                                                    <img src="{{ $org->logo_url }}" alt="{{ $org->name }}" class="w-3.5 h-3.5 object-contain rounded shrink-0">
                                                @else
                                                    <i class="fa-solid fa-building text-[10px] text-indigo-400"></i>
                                                @endif
                                                <span>{{ $org->name ?? 'Company' }}</span>
                                            </span>
                                            <p class="text-[11px] text-slate-400 pl-1">{{ $emp->department->name ?? 'Operations' }}</p>
                                        </div>
                                    </div>
                                </td>

                                <!-- Bank Transfer Structure -->
                                <td class="py-4 px-6 whitespace-nowrap">
                                    <div :class="revealedStaffId === '{{ $emp->id }}' ? 'privacy-clear' : 'privacy-blur'">
                                        <span class="font-bold text-white font-mono block text-sm">{{ $currency }} {{ number_format($bankSalaryTotal, 2) }}</span>
                                        <div class="text-[10px] text-slate-400">Base: {{ number_format($emp->base_salary, 0) }} | Allow: +{{ number_format($allowancesTotal, 0) }}</div>
                                    </div>
                                </td>

                                <!-- Cash in Hand Structure -->
                                <td class="py-4 px-6 whitespace-nowrap">
                                    <div :class="revealedStaffId === '{{ $emp->id }}' ? 'privacy-clear' : 'privacy-blur'">
                                        @if($cashInHand > 0)
                                            <span class="inline-flex items-center space-x-1.5 px-2.5 py-1 rounded-xl bg-amber-500/10 border border-amber-500/25 text-amber-300 font-bold font-mono text-xs">
                                                <i class="fa-solid fa-hand-holding-dollar text-emerald-400"></i>
                                                <span>{{ $currency }} {{ number_format($cashInHand, 2) }}</span>
                                            </span>
                                            <span class="text-[9px] text-slate-500 block uppercase font-bold tracking-wider mt-0.5">Confidential Cash</span>
                                        @else
                                            <span class="text-slate-500 text-xs font-mono">0.00</span>
                                        @endif
                                    </div>
                                </td>

                                <!-- Total Combined Remuneration -->
                                <td class="py-4 px-6 whitespace-nowrap">
                                    <div :class="revealedStaffId === '{{ $emp->id }}' ? 'privacy-clear' : 'privacy-blur'">
                                        <strong class="text-cyan-400 font-mono font-black text-sm block">{{ $currency }} {{ number_format($totalComp, 2) }}</strong>
                                        <span class="text-[10px] text-slate-400 font-semibold">Bank + Cash</span>
                                    </div>
                                </td>

                                <!-- Pending Advance -->
                                <td class="py-4 px-6 whitespace-nowrap">
                                    <div :class="revealedStaffId === '{{ $emp->id }}' ? 'privacy-clear' : 'privacy-blur'">
                                        @if(($emp->pending_advance_amount ?? 0) > 0)
                                            <span class="inline-flex items-center space-x-1 px-2.5 py-1 rounded-full bg-rose-500/10 border border-rose-500/20 text-rose-400 font-bold text-[11px]">
                                                <i class="fa-solid fa-triangle-exclamation text-[10px]"></i>
                                                <span>-{{ $currency }} {{ number_format($emp->pending_advance_amount, 2) }}</span>
                                            </span>
                                        @else
                                            <span class="text-slate-500 text-[11px] font-medium">None</span>
                                        @endif
                                    </div>
                                </td>

                                <!-- Status -->
                                <td class="py-4 px-6 whitespace-nowrap">
                                    <div :class="revealedStaffId === '{{ $emp->id }}' ? 'privacy-clear' : 'privacy-blur'">
                                        @if($isPaid)
                                            <span class="inline-flex items-center space-x-1.5 px-3 py-1 rounded-full bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 font-bold text-[11px]">
                                                <i class="fa-solid fa-circle-check text-[10px]"></i>
                                                <span>PAID ({{ $currency }} {{ number_format($slip->net_pay ?? 0, 0) }})</span>
                                            </span>
                                        @else
                                            <span class="inline-flex items-center space-x-1.5 px-3 py-1 rounded-full bg-amber-500/10 border border-amber-500/20 text-amber-400 font-bold text-[11px]">
                                                <i class="fa-solid fa-clock text-[10px]"></i>
                                                <span>UNPAID / PENDING</span>
                                            </span>
                                        @endif
                                    </div>
                                </td>

                                <!-- Actions -->
                                <td class="py-4 px-6 text-right whitespace-nowrap">
                                    <div :class="revealedStaffId === '{{ $emp->id }}' ? 'privacy-clear' : 'privacy-blur'">
                                        <div class="flex items-center justify-end space-x-1.5">
                                            @if($isPaid && $slip)
                                                <a 
                                                    href="{{ route('payroll.payslip.download', $slip->id) }}" 
                                                    target="_blank" 
                                                    @click.stop 
                                                    class="px-3 py-2 bg-indigo-600/20 hover:bg-indigo-600 text-indigo-300 hover:text-white border border-indigo-500/30 font-bold text-xs rounded-xl transition-all flex items-center space-x-1.5 shadow-sm group"
                                                    title="Official Salary Slip (PDF)"
                                                >
                                                    <i class="fa-solid fa-file-pdf text-xs text-indigo-400 group-hover:text-white"></i>
                                                    <span>Salary Slip</span>
                                                </a>
                                            @endif
                                            <button @click.stop="openPayModal({{ json_encode($emp) }})" class="px-3.5 py-2 {{ $isPaid ? 'bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700' : 'bg-emerald-600 hover:bg-emerald-500 text-white shadow-lg shadow-emerald-600/25' }} font-bold text-xs rounded-xl transition-all flex items-center space-x-1.5">
                                                <i class="fa-solid fa-hand-holding-dollar text-xs"></i>
                                                <span>{{ $isPaid ? 'Re-issue' : 'Pay Salary' }}</span>
                                            </button>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="py-12 text-center text-slate-500 font-medium">
                                    <i class="fa-solid fa-users-slash text-2xl text-slate-600 mb-2 block"></i>
                                    No staff members found matching the selected filters.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
    @endif

    <!-- ============================================================= -->
    <!-- TAB: PAYSLIP REQUESTS & CLEARANCE TRACKER                     -->
    <!-- ============================================================= -->
    <div x-show="activeTab === 'requests'" class="space-y-6">
        @if(!Auth::user()->canManagePayroll())
            <!-- Non-Admin / Employee Remuneration Hub -->
            <!-- Summary KPI Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-slate-950/60 border border-slate-800 rounded-3xl p-5 shadow-xl flex items-center justify-between">
                    <div class="space-y-1">
                        <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Clearance Protocol</p>
                        <h3 class="text-base font-black text-emerald-400">Request-Driven</h3>
                        <p class="text-[11px] text-slate-500">Gated via HR &amp; System Admin</p>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 flex items-center justify-center text-xl shrink-0">
                        <i class="fa-solid fa-shield-halved"></i>
                    </div>
                </div>

                <div class="bg-slate-950/60 border border-slate-800 rounded-3xl p-5 shadow-xl flex items-center justify-between">
                    <div class="space-y-1">
                        <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Submitted Requests</p>
                        <h3 class="text-2xl font-black text-white">{{ count($myPayslipRequests) }} <span class="text-xs text-slate-500 font-medium">Total</span></h3>
                        <p class="text-[11px] text-slate-500">Historical clearance applications</p>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-indigo-500/10 border border-indigo-500/20 text-indigo-400 flex items-center justify-center text-xl shrink-0">
                        <i class="fa-solid fa-file-signature"></i>
                    </div>
                </div>

                <div class="bg-slate-950/60 border border-slate-800 rounded-3xl p-5 shadow-xl flex items-center justify-between">
                    <div class="space-y-1">
                        <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Approved &amp; Ready</p>
                        <h3 class="text-2xl font-black text-emerald-400">{{ $myApprovedRequestsCount }} <span class="text-xs text-slate-500 font-medium">Vouchers</span></h3>
                        <p class="text-[11px] text-emerald-500/80 font-semibold">Available for viewing &amp; print</p>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 flex items-center justify-center text-xl shrink-0">
                        <i class="fa-solid fa-circle-check"></i>
                    </div>
                </div>

                <div class="bg-slate-950/60 border border-slate-800 rounded-3xl p-5 shadow-xl flex items-center justify-between">
                    <div class="space-y-1">
                        <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Pending Review</p>
                        <h3 class="text-2xl font-black text-amber-400">{{ $myPendingRequestsCount }} <span class="text-xs text-slate-500 font-medium">Pending</span></h3>
                        <p class="text-[11px] text-amber-500/80 font-semibold">Awaiting HR or Admin approval</p>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-amber-500/10 border border-amber-500/20 text-amber-400 flex items-center justify-center text-xl shrink-0">
                        <i class="fa-solid fa-hourglass-half"></i>
                    </div>
                </div>
            </div>

            <!-- Request Payslip Action Form (Multi-Month Selection with Strict Paid Verification) -->
            <div class="bg-gradient-to-r from-slate-950 via-indigo-950/30 to-slate-950 border border-indigo-500/30 rounded-3xl p-6 shadow-2xl space-y-5"
                 x-data="{
                     selectedMonths: [],
                     eligibleMonths: {{ json_encode(($myEligibleSalaryMonths ?? collect())->where('can_request', true)->pluck('payment_month')->values()) }},
                     toggleAll() {
                         if (this.selectedMonths.length === this.eligibleMonths.length) {
                             this.selectedMonths = [];
                         } else {
                             this.selectedMonths = [...this.eligibleMonths];
                         }
                     }
                 }">
                <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4 border-b border-slate-800/80 pb-4">
                    <div class="space-y-1">
                        <h3 class="text-base font-extrabold text-white flex items-center space-x-2">
                            <span class="w-8 h-8 rounded-xl bg-indigo-600/20 border border-indigo-500/30 text-indigo-400 flex items-center justify-center text-xs">
                                <i class="fa-solid fa-file-circle-plus"></i>
                            </span>
                            <span>Request Official Salary Voucher</span>
                        </h3>
                        <p class="text-xs text-slate-400">
                            Select one or multiple disbursed salary months from your official payroll history. Once cleared by HR &amp; System Admin, your verified printable vouchers will be unlocked.
                        </p>
                    </div>

                    @if(isset($myEligibleSalaryMonths) && $myEligibleSalaryMonths->where('can_request', true)->isNotEmpty())
                        <button type="button" @click="toggleAll()" class="px-3.5 py-1.5 rounded-xl border border-indigo-500/40 text-indigo-300 hover:text-white bg-indigo-600/10 hover:bg-indigo-600/20 text-xs font-semibold transition-all flex items-center space-x-1.5 shrink-0 shadow-sm">
                            <i class="fa-solid fa-list-check text-[11px]"></i>
                            <span x-text="selectedMonths.length === eligibleMonths.length ? 'Deselect All' : 'Select All Eligible Months'"></span>
                        </button>
                    @endif
                </div>

                @if(!isset($myEligibleSalaryMonths) || $myEligibleSalaryMonths->isEmpty())
                    <!-- Zero Paid Salary Notice -->
                    <div class="p-4 rounded-2xl bg-amber-500/10 border border-amber-500/30 text-amber-400 text-xs flex items-start space-x-3">
                        <div class="w-8 h-8 rounded-xl bg-amber-500/20 border border-amber-500/30 text-amber-400 flex items-center justify-center text-sm shrink-0 mt-0.5">
                            <i class="fa-solid fa-clock-rotate-left"></i>
                        </div>
                        <div class="space-y-1">
                            <strong class="font-bold text-amber-300">No Disbursed Salary Records Found:</strong>
                            <p class="text-slate-300 leading-relaxed">
                                Salary vouchers can strictly be requested for months where your salary has already been calculated, approved, and disbursed by HR/Finance. Once your first monthly salary is processed and disbursed, your eligible months will automatically appear here for selection.
                            </p>
                        </div>
                    </div>
                @else
                    <form action="{{ route('payroll.payslip-requests.store') }}" method="POST" class="space-y-4">
                        @csrf
                        
                        <!-- Eligible Months Grid -->
                        <div>
                            <label class="block text-[11px] font-bold text-slate-300 mb-2">
                                <i class="fa-regular fa-calendar-check text-indigo-400 mr-1.5"></i>Select Salary Month(s) to Request *
                                <span class="text-[10px] font-normal text-slate-400 ml-1">(Multi-select enabled: choose one or multiple months)</span>
                            </label>
                            
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3">
                                @foreach($myEligibleSalaryMonths as $item)
                                    @php
                                        $isPending = ($item['request_status'] === 'PENDING');
                                        $isApproved = ($item['request_status'] === 'APPROVED');
                                        $isCanRequest = $item['can_request'];
                                    @endphp
                                    <label class="relative flex items-center p-3 rounded-2xl border transition-all cursor-pointer select-none"
                                           :class="[
                                               selectedMonths.includes('{{ $item['payment_month'] }}')
                                                   ? 'bg-indigo-950/50 border-indigo-500 ring-1 ring-indigo-500/50 shadow-md shadow-indigo-950'
                                                   : 'bg-slate-900/90 border-slate-800 hover:border-slate-700'
                                           ]"
                                           style="{{ !$isCanRequest ? 'opacity: 0.65; cursor: default;' : '' }}">
                                        
                                        @if($isCanRequest)
                                            <input type="checkbox" name="payment_months[]" value="{{ $item['payment_month'] }}"
                                                   x-model="selectedMonths"
                                                   class="w-4 h-4 rounded text-indigo-600 bg-slate-950 border-slate-700 focus:ring-indigo-500 focus:ring-offset-slate-900 mr-3 shrink-0">
                                        @else
                                            <div class="w-4 h-4 mr-3 shrink-0 flex items-center justify-center">
                                                @if($isApproved)
                                                    <i class="fa-solid fa-circle-check text-emerald-400 text-xs"></i>
                                                @else
                                                    <i class="fa-solid fa-clock text-amber-400 text-xs"></i>
                                                @endif
                                            </div>
                                        @endif

                                        <div class="min-w-0 flex-1">
                                            <div class="flex items-center justify-between gap-1">
                                                <span class="text-xs font-bold text-white truncate">{{ $item['month_label'] }}</span>
                                                @if($isApproved)
                                                    <span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-emerald-500/20 text-emerald-400 border border-emerald-500/30">Cleared</span>
                                                @elseif($isPending)
                                                    <span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-amber-500/20 text-amber-400 border border-amber-500/30">In Review</span>
                                                @else
                                                    <span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-indigo-500/20 text-indigo-400 border border-indigo-500/30">Eligible</span>
                                                @endif
                                            </div>
                                            <div class="flex items-center justify-between text-[10px] text-slate-400 mt-1">
                                                <span class="font-mono font-semibold text-emerald-400">{{ $item['currency'] }} {{ number_format($item['net_pay'], 2) }}</span>
                                                <span class="text-[9px] text-slate-500">{{ $item['pay_date'] }}</span>
                                            </div>
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <!-- Purpose / Reason & Submit Action -->
                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 items-end pt-2">
                            <div class="lg:col-span-2">
                                <label class="block text-[11px] font-bold text-slate-300 mb-1.5">
                                    <i class="fa-solid fa-pen text-indigo-400 mr-1.5"></i>Purpose / Reason for Request (Optional)
                                </label>
                                <input type="text" name="reason" placeholder="e.g. Visa application, personal banking records, annual tax filing..." class="w-full px-3.5 py-2.5 bg-slate-900/90 border border-slate-800 rounded-xl text-xs font-medium text-white focus:outline-none focus:border-indigo-500 shadow-inner">
                            </div>

                            <div>
                                <button type="submit"
                                        :disabled="selectedMonths.length === 0"
                                        :class="selectedMonths.length === 0 ? 'opacity-50 cursor-not-allowed bg-indigo-600/50' : 'bg-indigo-600 hover:bg-indigo-500 shadow-lg shadow-indigo-600/30'"
                                        class="w-full py-2.5 px-4 text-white font-bold text-xs rounded-xl transition-all flex items-center justify-center space-x-2">
                                    <i class="fa-solid fa-paper-plane"></i>
                                    <span x-text="selectedMonths.length > 0 ? 'Submit Request (' + selectedMonths.length + ' Month' + (selectedMonths.length > 1 ? 's' : '') + ')' : 'Select Month(s) to Request'"></span>
                                </button>
                            </div>
                        </div>
                    </form>
                @endif
            </div>

            <!-- My Payslip Requests & Released Vouchers Table -->
            <div class="bg-slate-950/60 border border-slate-800 rounded-3xl overflow-hidden shadow-2xl space-y-4">
                <div class="p-6 border-b border-slate-800 flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-bold text-white tracking-tight">My Requested Payslip Statements</h3>
                        <p class="text-xs text-slate-400">Track clearance approvals from HR &amp; System Admin and access released salary vouchers.</p>
                    </div>
                    <span class="px-3 py-1 bg-slate-900 border border-slate-800 text-slate-300 font-bold text-xs rounded-xl">
                        {{ count($myPayslipRequests) }} Requests
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-900/80 border-b border-slate-800 text-[11px] font-bold text-slate-400 uppercase tracking-wider">
                                <th class="py-3.5 px-6 whitespace-nowrap">Pay Period</th>
                                <th class="py-3.5 px-6 whitespace-nowrap">Submitted At</th>
                                <th class="py-3.5 px-6 whitespace-nowrap">Purpose / Reason</th>
                                <th class="py-3.5 px-6 whitespace-nowrap">Clearance Status</th>
                                <th class="py-3.5 px-6 whitespace-nowrap">Reviewed By</th>
                                <th class="py-3.5 px-6 text-right whitespace-nowrap">Voucher Clearance</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/80 text-xs">
                            @forelse($myPayslipRequests as $req)
                                @php
                                    $ps = $req->payslip;
                                    $slipPayload = null;
                                    if ($ps) {
                                        $u = $ps->user ?? Auth::user();
                                        $org = $u->organization ?? $ps->payrollRun->payroll->organization ?? null;
                                        $fuel = (float)($ps->fuel_allowance ?? $u->fuel_allowance ?? 0);
                                        $special = (float)($ps->special_allowance ?? $u->special_allowance ?? 0);
                                        $otherAllow = (float)($ps->other_allowances ?? 0);
                                        $advanceDed = (float)($ps->advance_deduction ?? 0);
                                        $lateDed = (float)($ps->late_deduction ?? 0);
                                        $lateDays = (int)($ps->late_days_deducted ?? 0);
                                        $absentDed = (float)($ps->absent_deduction ?? 0);
                                        $eobiDed = (float)($ps->eobi_deduction ?? $u->eobi_deduction ?? 0);
                                        $otherDed = (float)($ps->other_deductions ?? 0);
                                        $grossSal = (float)($ps->base_salary + $ps->allowances);
                                        $grossDed = (float)($ps->deductions + $ps->tax);
                                        $periodText = $ps->payment_month ? date('F Y', strtotime($ps->payment_month . '-01')) : date('F Y', strtotime($req->payment_month . '-01'));

                                        $slipPayload = [
                                            'id' => $ps->id,
                                            'ref_no' => 'PSL-' . strtoupper(substr(str_replace('-', '', $ps->id), 0, 8)),
                                            'employee_name' => $u->name ?? 'Employee',
                                            'employee_code' => 'PD:' . ($u ? substr(preg_replace('/[^0-9]/', '', $u->id), 0, 4) : '55'),
                                            'cnic_no' => $u->national_id_number ?? '35202-8923411-9',
                                            'joining_date' => $u && $u->date_of_joining ? date('d-M-Y', strtotime($u->date_of_joining)) : '01-Nov-2023',
                                            'department' => $u->department->name ?? 'Operations',
                                            'designation' => $u->designation->title ?? 'Team Member',
                                            'salary_period' => $periodText,
                                            'pay_date' => $ps->pay_date ? date('d-M-Y', strtotime($ps->pay_date)) : date('d-M-Y', strtotime($ps->created_at ?? now())),
                                            'bank_name' => $u->bank_name ?? 'Direct Bank Deposit',
                                            'bank_account' => $u->bank_account_number ?? $u->bank_iban ?? 'PK89MEZN00992384102',
                                            'payment_method' => $ps->payment_method ?: ($u->payment_method ?: 'BANK_TRANSFER'),
                                            'template_style' => 'standard',
                                            'company_name' => $org->name ?? 'Corporate',
                                            'company_legal_name' => $org->legal_name ?? $org->name ?? 'Corporate Organization',
                                            'company_logo' => ($org && $org->logo_url) ? $org->logo_url : axecorp_logo_src(),
                                            'company_tax_no' => $org->tax_number ?? '',
                                            'company_address' => trim(($org->city ?? 'Karachi') . ', ' . ($org->country ?? 'Pakistan'), ', '),
                                            'company_email' => $org->email ?? 'hr@corporate.com',
                                            'company_phone' => $org->phone ?? '',
                                            'currency' => $org->currency ?? 'PKR',
                                            // Official Remuneration Earnings (strictly excluding confidential cash in hand)
                                            'base_salary' => (float)$ps->base_salary,
                                            'fuel_allowance' => $fuel,
                                            'special_allowance' => $special,
                                            'other_allowances' => $otherAllow,
                                            'gross_salary' => $grossSal,
                                            'advance_deduction' => $advanceDed,
                                            'late_deduction' => $lateDed,
                                            'late_days_deducted' => $lateDays,
                                            'absent_deduction' => $absentDed,
                                            'eobi_deduction' => $eobiDed,
                                            'other_deductions' => $otherDed,
                                            'tax' => (float)$ps->tax,
                                            'gross_deduction' => $grossDed,
                                            'net_pay' => (float)$ps->net_pay,
                                            'notes' => $ps->notes ?? '',
                                        ];
                                    }
                                @endphp
                                <tr class="hover:bg-slate-900/40 transition-colors">
                                    <!-- Period -->
                                    <td class="py-4 px-6 whitespace-nowrap font-bold text-white">
                                        <div class="flex items-center space-x-2">
                                            <i class="fa-regular fa-calendar-check text-indigo-400"></i>
                                            <span>{{ date('F Y', strtotime($req->payment_month . '-01')) }}</span>
                                        </div>
                                    </td>

                                    <!-- Submitted At -->
                                    <td class="py-4 px-6 whitespace-nowrap text-slate-400">
                                        {{ date('d-M-Y, h:i A', strtotime($req->created_at)) }}
                                    </td>

                                    <!-- Reason -->
                                    <td class="py-4 px-6 whitespace-nowrap text-slate-300">
                                        {{ $req->reason ?: 'Personal Remuneration Record' }}
                                    </td>

                                    <!-- Status Badge -->
                                    <td class="py-4 px-6 whitespace-nowrap">
                                        @if($req->status === 'APPROVED')
                                            <span class="inline-flex items-center space-x-1.5 px-3 py-1 rounded-full bg-emerald-500/10 border border-emerald-500/25 text-emerald-400 font-bold text-xs">
                                                <i class="fa-solid fa-circle-check text-[11px]"></i>
                                                <span>Approved &amp; Released</span>
                                            </span>
                                        @elseif($req->status === 'REJECTED')
                                            <span class="inline-flex items-center space-x-1.5 px-3 py-1 rounded-full bg-rose-500/10 border border-rose-500/25 text-rose-400 font-bold text-xs">
                                                <i class="fa-solid fa-circle-xmark text-[11px]"></i>
                                                <span>Request Declined</span>
                                            </span>
                                        @else
                                            <span class="inline-flex items-center space-x-1.5 px-3 py-1 rounded-full bg-amber-500/10 border border-amber-500/25 text-amber-400 font-bold text-xs">
                                                <i class="fa-solid fa-clock text-[11px]"></i>
                                                <span>Pending Review</span>
                                            </span>
                                        @endif
                                    </td>

                                    <!-- Reviewed By -->
                                    <td class="py-4 px-6 whitespace-nowrap text-slate-400">
                                        @if($req->status === 'APPROVED')
                                            <span class="text-slate-300 font-medium">{{ $req->reviewer->name ?? 'HR / Admin' }}</span>
                                            <span class="text-[10px] text-slate-500 block">{{ $req->reviewed_at ? date('d-M-Y', strtotime($req->reviewed_at)) : '' }}</span>
                                        @elseif($req->status === 'REJECTED')
                                            <span class="text-rose-400 font-medium">{{ $req->reviewer->name ?? 'HR / Admin' }}</span>
                                            @if($req->rejection_reason)
                                                <span class="text-[10px] text-rose-400/80 block">{{ $req->rejection_reason }}</span>
                                            @endif
                                        @else
                                            <span class="text-slate-500 font-medium italic">Awaiting HR / Admin</span>
                                        @endif
                                    </td>

                                    <!-- Action -->
                                    <td class="py-4 px-6 text-right whitespace-nowrap">
                                        @if($req->status === 'APPROVED' && $slipPayload)
                                            <div class="flex items-center justify-end space-x-1.5">
                                                @if(isset($slipPayload['id']))
                                                    <a href="{{ route('payroll.payslip.download', $slipPayload['id']) }}" target="_blank" class="inline-flex items-center space-x-1.5 px-3.5 py-2 bg-indigo-600/20 hover:bg-indigo-600 text-indigo-300 hover:text-white border border-indigo-500/30 rounded-xl font-bold text-xs transition-all shadow-sm group">
                                                        <i class="fa-solid fa-file-pdf text-xs text-indigo-400 group-hover:text-white"></i>
                                                        <span>Salary Slip</span>
                                                    </a>
                                                @endif
                                            </div>
                                        @elseif($req->status === 'APPROVED' && !$slipPayload)
                                            <span class="text-xs font-semibold text-slate-400">
                                                <i class="fa-solid fa-circle-check text-emerald-400 mr-1"></i>Approved (Awaiting Monthly Disbursement)
                                            </span>
                                        @elseif($req->status === 'REJECTED')
                                            <span class="text-xs text-rose-400/80 font-medium">
                                                <i class="fa-solid fa-circle-xmark mr-1"></i>Declined
                                            </span>
                                        @else
                                            <span class="text-xs text-slate-500 font-medium">
                                                <i class="fa-solid fa-lock text-amber-500/70 mr-1"></i>Locked Pending Review
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="py-12 text-center text-slate-500 font-medium">
                                        <i class="fa-solid fa-file-signature text-2xl text-slate-600 mb-2 block"></i>
                                        You have not submitted any payslip requests yet. Use the form above to request your salary voucher.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @else
            <!-- HR / System Admin Payslip Requests Queue -->
            <div class="bg-slate-950/60 border border-slate-800 rounded-3xl p-6 shadow-xl flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
                <div class="space-y-1">
                    <div class="flex items-center space-x-2.5">
                        <span class="w-8 h-8 rounded-xl bg-amber-500/10 border border-amber-500/20 text-amber-400 flex items-center justify-center text-xs font-bold">
                            <i class="fa-solid fa-file-signature"></i>
                        </span>
                        <h3 class="text-base font-bold text-white">Employee Payslip Requests Clearance Hub</h3>
                    </div>
                    <p class="text-xs text-slate-400">Review employee requests for salary vouchers. Approved requests unlock the official remuneration voucher for the employee.</p>
                </div>

                <div class="flex items-center gap-3">
                    <span class="px-3 py-1.5 bg-amber-500/10 border border-amber-500/25 text-amber-400 font-bold text-xs rounded-xl flex items-center space-x-1.5">
                        <i class="fa-solid fa-clock"></i>
                        <span>{{ $pendingPayslipRequestsCount }} Pending Approvals</span>
                    </span>
                </div>
            </div>

            <!-- Filter Bar -->
            <div class="bg-slate-950/60 border border-slate-800 rounded-2xl p-4 shadow-xl">
                <form action="{{ route('payroll.index') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <input type="hidden" name="tab" value="requests">

                    <div>
                        <label class="block text-[11px] font-bold text-slate-400 mb-1"><i class="fa-solid fa-filter text-indigo-400 mr-1"></i>Filter by Status</label>
                        <select name="request_status" onchange="this.form.submit()" class="w-full px-3 py-2 bg-slate-900 border border-slate-800 rounded-xl text-xs font-bold text-white focus:outline-none focus:border-indigo-500">
                            <option value="">All Statuses (Pending, Approved, Rejected)</option>
                            <option value="PENDING" {{ request('request_status') == 'PENDING' ? 'selected' : '' }}>Pending Clearance Only</option>
                            <option value="APPROVED" {{ request('request_status') == 'APPROVED' ? 'selected' : '' }}>Approved Only</option>
                            <option value="REJECTED" {{ request('request_status') == 'REJECTED' ? 'selected' : '' }}>Rejected Only</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-slate-400 mb-1"><i class="fa-solid fa-building text-indigo-400 mr-1"></i>Company</label>
                        <select name="organization_id" onchange="this.form.submit()" class="w-full px-3 py-2 bg-slate-900 border border-slate-800 rounded-xl text-xs font-bold text-white focus:outline-none focus:border-indigo-500">
                            <option value="">All Companies ({{ count($organizations) }})</option>
                            @foreach($organizations as $org)
                                <option value="{{ $org->id }}" {{ ($selectedOrgId ?? '') == $org->id ? 'selected' : '' }}>{{ $org->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex items-end">
                        <a href="{{ route('payroll.index', ['tab' => 'requests']) }}" class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-slate-300 font-bold text-xs rounded-xl border border-slate-800 transition-all flex items-center space-x-1.5">
                            <i class="fa-solid fa-rotate-left"></i>
                            <span>Reset Filters</span>
                        </a>
                    </div>
                </form>
            </div>

            <!-- Requests Queue Table -->
            <div class="bg-slate-950/60 border border-slate-800 rounded-3xl overflow-hidden shadow-2xl space-y-4">
                <div class="p-6 border-b border-slate-800 flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-bold text-white tracking-tight">Staff Payslip Applications Queue</h3>
                        <p class="text-xs text-slate-400">Approve to release payslip vouchers or decline with remarks.</p>
                    </div>
                    <span class="px-3 py-1 bg-slate-900 border border-slate-800 text-slate-300 font-bold text-xs rounded-xl">
                        {{ $allPayslipRequests->total() }} Total Requests
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-900/80 border-b border-slate-800 text-[11px] font-bold text-slate-400 uppercase tracking-wider">
                                <th class="py-3.5 px-6 whitespace-nowrap">Employee</th>
                                <th class="py-3.5 px-6 whitespace-nowrap">Company &amp; Dept</th>
                                <th class="py-3.5 px-6 whitespace-nowrap">Requested Month</th>
                                <th class="py-3.5 px-6 whitespace-nowrap">Reason / Note</th>
                                <th class="py-3.5 px-6 whitespace-nowrap">Submitted At</th>
                                <th class="py-3.5 px-6 whitespace-nowrap">Clearance Status</th>
                                <th class="py-3.5 px-6 text-right whitespace-nowrap">Clearance Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/80 text-xs">
                            @forelse($allPayslipRequests as $req)
                                @php
                                    $u = $req->user;
                                    $org = $u->organization ?? $req->organization;
                                    $ps = $req->payslip;
                                    $slipPayload = null;
                                    if ($ps) {
                                        $fuel = (float)($ps->fuel_allowance ?? $u->fuel_allowance ?? 0);
                                        $special = (float)($ps->special_allowance ?? $u->special_allowance ?? 0);
                                        $otherAllow = (float)($ps->other_allowances ?? 0);
                                        $advanceDed = (float)($ps->advance_deduction ?? 0);
                                        $lateDed = (float)($ps->late_deduction ?? 0);
                                        $lateDays = (int)($ps->late_days_deducted ?? 0);
                                        $absentDed = (float)($ps->absent_deduction ?? 0);
                                        $eobiDed = (float)($ps->eobi_deduction ?? $u->eobi_deduction ?? 0);
                                        $otherDed = (float)($ps->other_deductions ?? 0);
                                        $grossSal = (float)($ps->base_salary + $ps->allowances);
                                        $grossDed = (float)($ps->deductions + $ps->tax);
                                        $periodText = $ps->payment_month ? date('F Y', strtotime($ps->payment_month . '-01')) : date('F Y', strtotime($req->payment_month . '-01'));

                                        $slipPayload = [
                                            'id' => $ps->id,
                                            'ref_no' => 'PSL-' . strtoupper(substr(str_replace('-', '', $ps->id), 0, 8)),
                                            'employee_name' => $u->name ?? 'Employee',
                                            'employee_code' => 'PD:' . ($u ? substr(preg_replace('/[^0-9]/', '', $u->id), 0, 4) : '55'),
                                            'cnic_no' => $u->national_id_number ?? '35202-8923411-9',
                                            'joining_date' => $u && $u->date_of_joining ? date('d-M-Y', strtotime($u->date_of_joining)) : '01-Nov-2023',
                                            'department' => $u->department->name ?? 'Operations',
                                            'designation' => $u->designation->title ?? 'Team Member',
                                            'salary_period' => $periodText,
                                            'pay_date' => $ps->pay_date ? date('d-M-Y', strtotime($ps->pay_date)) : date('d-M-Y', strtotime($ps->created_at ?? now())),
                                            'bank_name' => $u->bank_name ?? 'Direct Bank Deposit',
                                            'bank_account' => $u->bank_account_number ?? $u->bank_iban ?? 'PK89MEZN00992384102',
                                            'payment_method' => $ps->payment_method ?: ($u->payment_method ?: 'BANK_TRANSFER'),
                                            'template_style' => 'standard',
                                            'company_name' => $org->name ?? 'Corporate',
                                            'company_legal_name' => $org->legal_name ?? $org->name ?? 'Corporate Organization',
                                            'company_logo' => ($org && $org->logo_url) ? $org->logo_url : axecorp_logo_src(),
                                            'company_tax_no' => $org->tax_number ?? '',
                                            'company_address' => trim(($org->city ?? 'Karachi') . ', ' . ($org->country ?? 'Pakistan'), ', '),
                                            'company_email' => $org->email ?? 'hr@corporate.com',
                                            'company_phone' => $org->phone ?? '',
                                            'currency' => $org->currency ?? 'PKR',
                                            'base_salary' => (float)$ps->base_salary,
                                            'fuel_allowance' => $fuel,
                                            'special_allowance' => $special,
                                            'other_allowances' => $otherAllow,
                                            'gross_salary' => $grossSal,
                                            'advance_deduction' => $advanceDed,
                                            'late_deduction' => $lateDed,
                                            'late_days_deducted' => $lateDays,
                                            'absent_deduction' => $absentDed,
                                            'eobi_deduction' => $eobiDed,
                                            'other_deductions' => $otherDed,
                                            'tax' => (float)$ps->tax,
                                            'gross_deduction' => $grossDed,
                                            'net_pay' => (float)$ps->net_pay,
                                            'notes' => $ps->notes ?? '',
                                        ];
                                    }
                                @endphp
                                <tr class="hover:bg-slate-900/40 transition-colors">
                                    <!-- Employee info -->
                                    <td class="py-4 px-6 whitespace-nowrap">
                                        <div class="flex items-center space-x-3">
                                            <div class="w-8 h-8 rounded-xl bg-gradient-to-br from-indigo-600 to-indigo-800 text-white flex items-center justify-center font-bold text-xs shrink-0">
                                                @if($u && $u->avatar_url)
                                                    <img src="{{ $u->avatar_url }}" alt="{{ $u->name }}" class="w-full h-full object-cover rounded-xl">
                                                @else
                                                    {{ strtoupper(substr($u->name ?? 'U', 0, 2)) }}
                                                @endif
                                            </div>
                                            <div>
                                                <p class="font-bold text-white text-sm">{{ $u->name ?? 'Staff Member' }}</p>
                                                <p class="text-slate-400 text-[11px]">{{ $u->designation->title ?? 'Employee' }}</p>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Company & Dept -->
                                    <td class="py-4 px-6 whitespace-nowrap">
                                        <span class="inline-flex items-center space-x-1.5 px-2.5 py-0.5 bg-slate-900 border border-slate-800 rounded-lg text-slate-300 font-semibold text-[11px]">
                                            <i class="fa-solid fa-building text-[10px] text-indigo-400"></i>
                                            <span>{{ $org->name ?? 'Company' }}</span>
                                        </span>
                                        <p class="text-[11px] text-slate-400 pl-1">{{ $u->department->name ?? 'Operations' }}</p>
                                    </td>

                                    <!-- Requested Month -->
                                    <td class="py-4 px-6 whitespace-nowrap font-bold text-white font-mono">
                                        {{ date('F Y', strtotime($req->payment_month . '-01')) }}
                                    </td>

                                    <!-- Reason -->
                                    <td class="py-4 px-6 whitespace-nowrap text-slate-300">
                                        {{ $req->reason ?: 'Remuneration Record' }}
                                    </td>

                                    <!-- Submitted At -->
                                    <td class="py-4 px-6 whitespace-nowrap text-slate-400">
                                        {{ date('d-M-Y, h:i A', strtotime($req->created_at)) }}
                                    </td>

                                    <!-- Status Badge -->
                                    <td class="py-4 px-6 whitespace-nowrap">
                                        @if($req->status === 'APPROVED')
                                            <span class="inline-flex items-center space-x-1.5 px-3 py-1 rounded-full bg-emerald-500/10 border border-emerald-500/25 text-emerald-400 font-bold text-xs">
                                                <i class="fa-solid fa-circle-check text-[10px]"></i>
                                                <span>APPROVED</span>
                                            </span>
                                            <span class="text-[10px] text-slate-400 block mt-0.5">by {{ $req->reviewer->name ?? 'Admin' }}</span>
                                        @elseif($req->status === 'REJECTED')
                                            <span class="inline-flex items-center space-x-1.5 px-3 py-1 rounded-full bg-rose-500/10 border border-rose-500/25 text-rose-400 font-bold text-xs">
                                                <i class="fa-solid fa-circle-xmark text-[10px]"></i>
                                                <span>REJECTED</span>
                                            </span>
                                            <span class="text-[10px] text-rose-400/80 block mt-0.5">{{ $req->rejection_reason }}</span>
                                        @else
                                            <span class="inline-flex items-center space-x-1.5 px-3 py-1 rounded-full bg-amber-500/10 border border-amber-500/25 text-amber-400 font-bold text-xs">
                                                <i class="fa-solid fa-clock text-[10px]"></i>
                                                <span>PENDING REVIEW</span>
                                            </span>
                                        @endif
                                    </td>

                                    <!-- Clearance Actions -->
                                    <td class="py-4 px-6 text-right whitespace-nowrap">
                                        <div class="flex items-center justify-end space-x-2">
                                            @if($req->status === 'PENDING')
                                                <!-- Approve Button Form -->
                                                <form action="{{ route('payroll.payslip-requests.approve', $req->id) }}" method="POST">
                                                    @csrf
                                                    <button type="submit" class="px-3.5 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs rounded-xl shadow transition-all flex items-center space-x-1.5">
                                                        <i class="fa-solid fa-check text-xs"></i>
                                                        <span>Approve</span>
                                                    </button>
                                                </form>

                                                <!-- Reject Button -->
                                                <button @click="openRejectModal('{{ $req->id }}', '{{ addslashes($req->user->name ?? 'Staff') }}')" class="px-3 py-1.5 bg-rose-600/20 hover:bg-rose-600 text-rose-400 hover:text-white border border-rose-500/30 font-bold text-xs rounded-xl transition-all flex items-center space-x-1">
                                                    <i class="fa-solid fa-xmark text-xs"></i>
                                                    <span>Reject</span>
                                                </button>
                                            @elseif($req->status === 'APPROVED')
                                                @if(isset($slipPayload['id']))
                                                    <a href="{{ route('payroll.payslip.download', $slipPayload['id']) }}" target="_blank" class="px-3 py-1.5 bg-indigo-600/20 hover:bg-indigo-600 text-indigo-300 hover:text-white border border-indigo-500/30 font-bold text-xs rounded-xl transition-all flex items-center space-x-1.5 shadow-sm group" title="Download Official Salary Slip (PDF)">
                                                        <i class="fa-solid fa-file-pdf text-xs text-indigo-400 group-hover:text-white"></i>
                                                        <span>Salary Slip</span>
                                                    </a>
                                                @endif
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="py-12 text-center text-slate-500 font-medium">
                                        <i class="fa-solid fa-inbox text-2xl text-slate-600 mb-2 block"></i>
                                        No employee payslip requests currently in queue.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="p-4 border-t border-slate-800">
                    {{ $allPayslipRequests->links() }}
                </div>
            </div>
        @endif
    </div>

    <!-- ============================================================= -->
    <!-- TAB 3: PAYROLL HISTORY WITH RICH FILTERS & SUMMARY METRICS    -->
    <!-- ============================================================= -->
    @if(Auth::user()->canManagePayroll())
    <div x-show="activeTab === 'history'" class="space-y-6">
        
        <!-- Filter Toolbar -->
        <div class="bg-slate-950/60 border border-slate-800 rounded-3xl p-5 shadow-xl space-y-4">
            <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                <div class="flex items-center space-x-2.5">
                    <span class="w-8 h-8 rounded-lg bg-indigo-500/10 border border-indigo-500/20 text-indigo-400 flex items-center justify-center font-bold text-xs">
                        <i class="fa-solid fa-filter"></i>
                    </span>
                    <div>
                        <h3 class="text-sm font-bold text-white">Payroll History Filter Hub</h3>
                        <p class="text-[11px] text-slate-400">Filter past salary disbursements by month, employee, organization, and department.</p>
                    </div>
                </div>

                @if(!empty($historyMonth) || !empty($historyUserId) || !empty($historyOrgId) || !empty($historyDeptId) || !empty($historySearch))
                    <a href="{{ route('payroll.index', ['tab' => 'history']) }}" class="text-xs font-semibold text-rose-400 hover:underline flex items-center space-x-1">
                        <i class="fa-solid fa-rotate-left text-[10px]"></i>
                        <span>Reset All Filters</span>
                    </a>
                @endif
            </div>

            <form action="{{ route('payroll.index') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                <input type="hidden" name="tab" value="history">

                <!-- Filter by Month -->
                <div>
                    <label class="block text-[11px] font-bold text-slate-400 mb-1"><i class="fa-regular fa-calendar mr-1 text-indigo-400"></i>Month &amp; Year</label>
                    <input type="month" name="history_month" value="{{ $historyMonth ?? '' }}" onchange="this.form.submit()" class="w-full px-3 py-2 bg-slate-900 border border-slate-800 rounded-xl text-xs font-bold text-white focus:outline-none focus:border-indigo-500">
                </div>

                @if(Auth::user()->canManagePayroll())
                <!-- Filter by Employee -->
                <div>
                    <label class="block text-[11px] font-bold text-slate-400 mb-1"><i class="fa-solid fa-user mr-1 text-indigo-400"></i>Employee</label>
                    <select name="history_user_id" onchange="this.form.submit()" class="w-full px-3 py-2 bg-slate-900 border border-slate-800 rounded-xl text-xs font-bold text-white focus:outline-none focus:border-indigo-500">
                        <option value="">All Employees</option>
                        @foreach($employees as $e)
                            <option value="{{ $e->id }}" {{ ($historyUserId ?? '') == $e->id ? 'selected' : '' }}>{{ $e->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Filter by Company -->
                <div>
                    <label class="block text-[11px] font-bold text-slate-400 mb-1"><i class="fa-solid fa-building mr-1 text-indigo-400"></i>Company</label>
                    <select name="history_org_id" onchange="this.form.submit()" class="w-full px-3 py-2 bg-slate-900 border border-slate-800 rounded-xl text-xs font-bold text-white focus:outline-none focus:border-indigo-500">
                        <option value="">All Companies</option>
                        @foreach($organizations as $org)
                            <option value="{{ $org->id }}" {{ ($historyOrgId ?? '') == $org->id ? 'selected' : '' }}>{{ $org->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Filter by Department -->
                <div>
                    <label class="block text-[11px] font-bold text-slate-400 mb-1"><i class="fa-solid fa-sitemap mr-1 text-indigo-400"></i>Department</label>
                    <select name="history_dept_id" onchange="this.form.submit()" class="w-full px-3 py-2 bg-slate-900 border border-slate-800 rounded-xl text-xs font-bold text-white focus:outline-none focus:border-indigo-500">
                        <option value="">All Departments</option>
                        @foreach($departments as $d)
                            <option value="{{ $d->id }}" {{ ($historyDeptId ?? '') == $d->id ? 'selected' : '' }}>{{ $d->name }}</option>
                        @endforeach
                    </select>
                </div>
                @endif

                <!-- Search Input / Submit -->
                <div class="col-span-1 sm:col-span-2 lg:col-span-4">
                    <label class="block text-[11px] font-bold text-slate-400 mb-1"><i class="fa-solid fa-magnifying-glass mr-1 text-indigo-400"></i>Keyword Search</label>
                    <div class="flex items-center space-x-2">
                        <input type="text" name="history_search" value="{{ $historySearch ?? '' }}" placeholder="Name or email..." class="w-full px-3 py-2 bg-slate-900 border border-slate-800 rounded-xl text-xs font-medium text-white focus:outline-none focus:border-indigo-500">
                        <button type="submit" class="px-3.5 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-bold shrink-0">Filter</button>
                    </div>
                </div>
            </form>

            <!-- Filtered Summary KPI Banner -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 pt-3 border-t border-slate-800/80">
                <div class="p-3 bg-slate-900/60 rounded-xl border border-slate-800/80">
                    <span class="text-[10px] uppercase font-bold text-slate-400 block">Total Records</span>
                    <strong class="text-base font-black text-white">{{ $historyTotalCount ?? 0 }} Vouchers</strong>
                </div>
                <div class="p-3 bg-slate-900/60 rounded-xl border border-slate-800/80">
                    <span class="text-[10px] uppercase font-bold text-slate-400 block">Filtered Gross</span>
                    <strong class="text-base font-black text-cyan-400 font-mono">PKR {{ number_format($historyTotalGross ?? 0, 0) }}</strong>
                </div>
                <div class="p-3 bg-slate-900/60 rounded-xl border border-slate-800/80">
                    <span class="text-[10px] uppercase font-bold text-slate-400 block">Filtered Deductions</span>
                    <strong class="text-base font-black text-rose-400 font-mono">PKR {{ number_format($historyTotalDeductions ?? 0, 0) }}</strong>
                </div>
                <div class="p-3 bg-slate-900/60 rounded-xl border border-slate-800/80">
                    <span class="text-[10px] uppercase font-bold text-slate-400 block">Filtered Net Disbursed</span>
                    <strong class="text-base font-black text-emerald-400 font-mono">PKR {{ number_format($historyTotalNet ?? 0, 0) }}</strong>
                </div>
            </div>
        </div>

        <!-- Payslips Table -->
        <div @click.outside="closeRevealedRows()" class="bg-slate-950/60 border border-slate-800 rounded-3xl overflow-hidden shadow-2xl space-y-4">
            <div class="p-6 border-b border-slate-800 flex items-center justify-between">
                <div>
                    <h3 class="text-base font-bold text-white tracking-tight">{{ Auth::user()->isEmployee() ? 'My Issued Salary Slips' : 'Historical Employee Payslip Disbursements' }}</h3>
                    <p class="text-xs text-slate-400">Official company salary slips with individual earnings, deductions, and payment clearance records.</p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-900/80 border-b border-slate-800 text-[11px] font-bold text-slate-400 uppercase tracking-wider">
                            <th class="py-3.5 px-6 whitespace-nowrap min-w-[200px]">Employee</th>
                            <th class="py-3.5 px-6 whitespace-nowrap min-w-[150px]">Company</th>
                            <th class="py-3.5 px-6 whitespace-nowrap">Pay Period</th>
                            <th class="py-3.5 px-6 whitespace-nowrap">Basic Salary</th>
                            <th class="py-3.5 px-6 whitespace-nowrap">Allowances</th>
                            <th class="py-3.5 px-6 whitespace-nowrap">Deductions</th>
                            <th class="py-3.5 px-6 whitespace-nowrap">Net Payout</th>
                            <th class="py-3.5 px-6 text-right whitespace-nowrap min-w-[130px]">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/80 text-xs">
                        @forelse($payslips as $ps)
                            @php
                                $u = $ps->user;
                                $org = $u->organization ?? $ps->payrollRun->payroll->organization ?? null;
                                $fuel = (float)($ps->fuel_allowance ?? $u->fuel_allowance ?? 0);
                                $special = (float)($ps->special_allowance ?? $u->special_allowance ?? 0);
                                $otherAllow = (float)($ps->other_allowances ?? 0);
                                $advanceDed = (float)($ps->advance_deduction ?? 0);
                                $lateDed = (float)($ps->late_deduction ?? 0);
                                $lateDays = (int)($ps->late_days_deducted ?? 0);
                                $absentDed = (float)($ps->absent_deduction ?? 0);
                                $eobiDed = (float)($ps->eobi_deduction ?? $u->eobi_deduction ?? 0);
                                $otherDed = (float)($ps->other_deductions ?? 0);
                                $grossSal = (float)($ps->base_salary + $ps->allowances);
                                $grossDed = (float)($ps->deductions + $ps->tax);

                                $periodText = $ps->payment_month ? date('F Y', strtotime($ps->payment_month . '-01')) : ($ps->payrollRun ? date('d-M-Y', strtotime($ps->payrollRun->period_start)) . ' to ' . date('d-M-Y', strtotime($ps->payrollRun->period_end)) : date('F Y'));

                                $slipPayload = [
                                    'id' => $ps->id,
                                    'ref_no' => 'PSL-' . strtoupper(substr(str_replace('-', '', $ps->id), 0, 8)),
                                    'employee_name' => $u->name ?? 'Employee',
                                    'employee_code' => 'PD:' . ($u ? substr(preg_replace('/[^0-9]/', '', $u->id), 0, 4) : '55'),
                                    'cnic_no' => $u->national_id_number ?? '35202-8923411-9',
                                    'joining_date' => $u && $u->date_of_joining ? date('d-M-Y', strtotime($u->date_of_joining)) : '01-Nov-2023',
                                    'department' => $u->department->name ?? 'Operations',
                                    'designation' => $u->designation->title ?? 'Team Member',
                                    'salary_period' => $periodText,
                                    'pay_date' => $ps->pay_date ? date('d-M-Y', strtotime($ps->pay_date)) : date('d-M-Y', strtotime($ps->created_at ?? now())),
                                    'bank_name' => $u->bank_name ?? 'Direct Bank Deposit',
                                    'bank_account' => $u->bank_account_number ?? $u->bank_iban ?? 'PK89MEZN00992384102',
                                    'payment_method' => $ps->payment_method ?: ($u->payment_method ?: 'BANK_TRANSFER'),
                                    'template_style' => 'standard',
                                     'company_name' => $org->name ?? 'Corporate',
                                     'company_legal_name' => $org->legal_name ?? $org->name ?? 'Corporate Organization',
                                     'company_logo' => ($org && $org->logo_url) ? $org->logo_url : axecorp_logo_src(),
                                     'company_tax_no' => $org->tax_number ?? '',
                                     'company_address' => trim(($org->city ?? 'Karachi') . ', ' . ($org->country ?? 'Pakistan'), ', '),
                                     'company_email' => $org->email ?? 'hr@corporate.com',
                                     'company_phone' => $org->phone ?? '',
                                     'currency' => $org->currency ?? 'PKR',
                                    // Earnings
                                    'base_salary' => (float)$ps->base_salary,
                                    'fuel_allowance' => $fuel,
                                    'special_allowance' => $special,
                                    'other_allowances' => $otherAllow,
                                    'gross_salary' => $grossSal,
                                    // Deductions
                                    'advance_deduction' => $advanceDed,
                                    'late_deduction' => $lateDed,
                                    'late_days_deducted' => $lateDays,
                                    'absent_deduction' => $absentDed,
                                    'eobi_deduction' => $eobiDed,
                                    'other_deductions' => $otherDed,
                                    'tax' => (float)$ps->tax,
                                    'gross_deduction' => $grossDed,
                                    // Net
                                    'net_pay' => (float)$ps->net_pay,
                                    'notes' => $ps->notes ?? '',
                                ];
                            @endphp
                            <tr 
                                @click="togglePayslipRow('{{ $ps->id }}')" 
                                :class="revealedPayslipId === '{{ $ps->id }}' ? 'privacy-row-revealed border-l-4 border-indigo-500' : 'hover:bg-slate-900/40 border-l-4 border-transparent'"
                                class="transition-all duration-200 cursor-pointer group"
                                title="Click to toggle payslip privacy"
                            >
                                <td class="py-4 px-6 whitespace-nowrap">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-8 h-8 rounded-lg bg-indigo-600/20 text-indigo-400 flex items-center justify-center font-bold text-xs shrink-0">
                                            {{ strtoupper(substr($ps->user->name ?? 'U', 0, 1)) }}
                                        </div>
                                        <div>
                                            <div class="flex items-center space-x-2">
                                                <p class="font-bold text-white">{{ $ps->user->name ?? 'User' }}</p>
                                                <span x-show="revealedPayslipId === '{{ $ps->id }}'" x-cloak class="inline-flex items-center gap-1 text-[9px] font-bold text-emerald-400 bg-emerald-500/10 px-1.5 py-0.5 rounded border border-emerald-500/20">
                                                    <i class="fa-solid fa-eye text-[8px]"></i> Visible
                                                </span>
                                                <span x-show="revealedPayslipId !== '{{ $ps->id }}'" class="inline-flex items-center gap-1 text-[9px] font-medium text-slate-500 bg-slate-900/80 px-1.5 py-0.5 rounded border border-slate-800 group-hover:text-slate-400 transition-colors">
                                                    <i class="fa-solid fa-eye-slash text-[8px]"></i> Click to view
                                                </span>
                                            </div>
                                            <p class="text-slate-400 text-[11px] font-medium">{{ $ps->user->designation->title ?? 'Employee' }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-4 px-6 whitespace-nowrap">
                                    <div :class="revealedPayslipId === '{{ $ps->id }}' ? 'privacy-clear' : 'privacy-blur'">
                                        <span class="inline-flex items-center space-x-1.5 px-3 py-1 bg-slate-900 border border-slate-800 font-semibold text-slate-300 rounded-xl text-xs whitespace-nowrap shadow-sm">
                                            @if($org && $org->logo_url)
                                                <img src="{{ $org->logo_url }}" alt="{{ $org->name }}" class="w-4 h-4 object-contain rounded shrink-0">
                                            @else
                                                <i class="fa-solid fa-building text-[10px] text-indigo-400 shrink-0"></i>
                                            @endif
                                            <span>{{ $ps->user->organization->name ?? 'Unassigned' }}</span>
                                        </span>
                                    </div>
                                </td>
                                <td class="py-4 px-6 whitespace-nowrap font-medium text-slate-300">
                                    <div :class="revealedPayslipId === '{{ $ps->id }}' ? 'privacy-clear' : 'privacy-blur'">
                                        {{ $periodText }}
                                    </div>
                                </td>
                                <td class="py-4 px-6 whitespace-nowrap font-semibold text-slate-300 font-mono">
                                    <div :class="revealedPayslipId === '{{ $ps->id }}' ? 'privacy-clear' : 'privacy-blur'">
                                        {{ $org->currency ?? 'PKR' }} {{ number_format($ps->base_salary, 2) }}
                                    </div>
                                </td>
                                <td class="py-4 px-6 whitespace-nowrap font-semibold text-emerald-400 font-mono">
                                    <div :class="revealedPayslipId === '{{ $ps->id }}' ? 'privacy-clear' : 'privacy-blur'">
                                        +{{ number_format($ps->allowances, 2) }}
                                    </div>
                                </td>
                                <td class="py-4 px-6 whitespace-nowrap font-semibold text-rose-400 font-mono">
                                    <div :class="revealedPayslipId === '{{ $ps->id }}' ? 'privacy-clear' : 'privacy-blur'">
                                        -{{ number_format($grossDed, 2) }}
                                    </div>
                                </td>
                                <td class="py-4 px-6 whitespace-nowrap font-extrabold text-emerald-400 text-sm font-mono">
                                    <div :class="revealedPayslipId === '{{ $ps->id }}' ? 'privacy-clear' : 'privacy-blur'">
                                        {{ $org->currency ?? 'PKR' }} {{ number_format($ps->net_pay, 2) }}
                                    </div>
                                </td>
                                <td class="py-4 px-6 text-right whitespace-nowrap">
                                    <div :class="revealedPayslipId === '{{ $ps->id }}' ? 'privacy-clear' : 'privacy-blur'">
                                        <div class="flex items-center justify-end space-x-1.5">
                                            <a href="{{ route('payroll.payslip.download', $ps->id) }}" target="_blank" @click.stop class="inline-flex items-center justify-center px-3 py-1.5 bg-indigo-600/20 hover:bg-indigo-600 text-indigo-300 hover:text-white border border-indigo-500/30 rounded-xl font-bold text-xs transition-all shadow-sm space-x-1.5 whitespace-nowrap group" title="Download Official Salary Slip (PDF)">
                                                <i class="fa-solid fa-file-pdf shrink-0 text-xs text-indigo-400 group-hover:text-white"></i>
                                                <span class="whitespace-nowrap">Salary Slip</span>
                                            </a>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="py-12 text-center text-slate-500 font-medium">
                                    <i class="fa-solid fa-receipt text-2xl text-slate-600 mb-2 block"></i>
                                    No payslip records found matching the selected history filters.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="p-4 border-t border-slate-800">
                {{ $payslips->links() }}
            </div>
        </div>

    </div>
    @endif

    <!-- ============================================================= -->
    <!-- TAB 3: COMPREHENSIVE PAYROLL REPORTS & ANALYTICS              -->
    <!-- ============================================================= -->
    @if(Auth::user()->canManagePayroll())
    <div x-show="activeTab === 'reports'" class="space-y-6">
        
        <!-- Report Controls & Filter Card -->
        <div class="bg-slate-950/60 border border-slate-800 rounded-3xl p-6 shadow-xl flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
            <div>
                <h3 class="text-base font-bold text-white tracking-tight">Executive Payroll Expenditure &amp; Audit Report</h3>
                <p class="text-xs text-slate-400">Generate, print, or export comprehensive financial statements and detailed salary registers.</p>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <!-- Print Report Button -->
                <button onclick="window.print()" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs rounded-xl shadow-lg shadow-indigo-600/25 transition-all flex items-center space-x-2">
                    <i class="fa-solid fa-print"></i>
                    <span>Print Official Report</span>
                </button>

                <!-- Export CSV Button -->
                <a href="{{ route('payroll.export-report', ['report_month' => $reportMonth ?? date('Y-m'), 'report_org_id' => $reportOrgId ?? '', 'report_dept_id' => $reportDeptId ?? '']) }}" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs rounded-xl shadow-lg shadow-emerald-600/25 transition-all flex items-center space-x-2">
                    <i class="fa-solid fa-file-excel"></i>
                    <span>Export CSV / Excel</span>
                </a>
            </div>
        </div>

        <!-- Report Period Filter Form -->
        <div class="bg-slate-950/60 border border-slate-800 rounded-2xl p-4 shadow-xl">
            <form action="{{ route('payroll.index') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <input type="hidden" name="tab" value="reports">

                <div>
                    <label class="block text-[11px] font-bold text-slate-400 mb-1"><i class="fa-regular fa-calendar mr-1 text-indigo-400"></i>Report Period (Month)</label>
                    <input type="month" name="report_month" value="{{ $reportMonth ?? date('Y-m') }}" onchange="this.form.submit()" class="w-full px-3 py-2 bg-slate-900 border border-slate-800 rounded-xl text-xs font-bold text-white focus:outline-none focus:border-indigo-500">
                </div>

                <div>
                    <label class="block text-[11px] font-bold text-slate-400 mb-1"><i class="fa-solid fa-building mr-1 text-indigo-400"></i>Company</label>
                    <select name="report_org_id" onchange="this.form.submit()" class="w-full px-3 py-2 bg-slate-900 border border-slate-800 rounded-xl text-xs font-bold text-white focus:outline-none focus:border-indigo-500">
                        <option value="">All Companies</option>
                        @foreach($organizations as $org)
                            <option value="{{ $org->id }}" {{ ($reportOrgId ?? '') == $org->id ? 'selected' : '' }}>{{ $org->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-[11px] font-bold text-slate-400 mb-1"><i class="fa-solid fa-sitemap mr-1 text-indigo-400"></i>Department</label>
                    <select name="report_dept_id" onchange="this.form.submit()" class="w-full px-3 py-2 bg-slate-900 border border-slate-800 rounded-xl text-xs font-bold text-white focus:outline-none focus:border-indigo-500">
                        <option value="">All Departments</option>
                        @foreach($departments as $d)
                            <option value="{{ $d->id }}" {{ ($reportDeptId ?? '') == $d->id ? 'selected' : '' }}>{{ $d->name }}</option>
                        @endforeach
                    </select>
                </div>
            </form>
        </div>

        <!-- Printable Report Container -->
        <div id="printable-payroll-report" class="space-y-6 bg-slate-950/40 p-1 md:p-6 rounded-3xl border border-slate-800/80 shadow-2xl">
            
            <!-- Corporate Report Header (Clean Printable) -->
            <div class="bg-slate-900/90 border border-slate-800 rounded-2xl p-6 flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
                <div class="flex items-center space-x-4">
                    <div class="w-14 h-14 rounded-2xl bg-indigo-600/20 border border-indigo-500/30 flex items-center justify-center text-indigo-400 text-2xl font-black shrink-0">
                        <i class="fa-solid fa-file-invoice-dollar"></i>
                    </div>
                    <div>
                        <h2 class="text-xl font-black text-white uppercase tracking-tight">Monthly Corporate Payroll &amp; Expenditure Report</h2>
                        <p class="text-xs text-slate-400 font-medium">Reporting Pay Period: <strong class="text-indigo-400 font-bold">{{ date('F Y', strtotime(($reportMonth ?? date('Y-m')) . '-01')) }}</strong> &bull; Generated on: {{ date('d-M-Y H:i') }}</p>
                    </div>
                </div>

                <div class="text-left md:text-right">
                    <span class="px-3 py-1 bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 font-black text-xs rounded-xl uppercase tracking-wider">
                        AUDIT VERIFIED
                    </span>
                    <p class="text-xs text-slate-400 mt-1">Disbursed Staff: <strong class="text-white">{{ $reportTotalHeadcount ?? 0 }} Employees</strong></p>
                </div>
            </div>

            <!-- Financial Metrics Grid -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-slate-900/80 border border-slate-800 rounded-2xl p-4 space-y-1">
                    <span class="text-[11px] font-bold text-slate-400 uppercase">Gross Salary Expenditure</span>
                    <p class="text-xl font-black text-cyan-400 font-mono">PKR {{ number_format($reportTotalGross ?? 0, 2) }}</p>
                    <p class="text-[10px] text-slate-500">Basic: {{ number_format($reportTotalBasic ?? 0, 0) }} | Allow: {{ number_format($reportTotalAllowances ?? 0, 0) }}</p>
                </div>

                <div class="bg-slate-900/80 border border-slate-800 rounded-2xl p-4 space-y-1">
                    <span class="text-[11px] font-bold text-slate-400 uppercase">Advance Recoveries</span>
                    <p class="text-xl font-black text-amber-400 font-mono">PKR {{ number_format($reportTotalAdvances ?? 0, 2) }}</p>
                    <p class="text-[10px] text-slate-500">Mid-month loans deducted</p>
                </div>

                <div class="bg-slate-900/80 border border-slate-800 rounded-2xl p-4 space-y-1">
                    <span class="text-[11px] font-bold text-slate-400 uppercase">Statutory Deductions &amp; Tax</span>
                    <p class="text-xl font-black text-rose-400 font-mono">PKR {{ number_format(($reportTotalEOBI ?? 0) + ($reportTotalTax ?? 0) + ($reportTotalLate ?? 0), 2) }}</p>
                    <p class="text-[10px] text-slate-500">EOBI + FBR Tax + Late Penalties</p>
                </div>

                <div class="bg-slate-900/80 border border-slate-800 rounded-2xl p-4 space-y-1">
                    <span class="text-[11px] font-bold text-slate-400 uppercase">Net Disbursed Outflow</span>
                    <p class="text-2xl font-black text-emerald-400 font-mono">PKR {{ number_format($reportTotalNet ?? 0, 2) }}</p>
                    <p class="text-[10px] text-emerald-500 font-bold">Total direct deposit cleared</p>
                </div>
            </div>

            <!-- Department-wise Allocation Summary -->
            <div class="bg-slate-900/80 border border-slate-800 rounded-2xl overflow-hidden">
                <div class="p-4 border-b border-slate-800 flex items-center justify-between">
                    <h4 class="text-xs font-bold text-white uppercase tracking-wider">1. Department-Wise Payroll Allocation</h4>
                    <span class="text-[11px] text-slate-400">Aggregated by departmental cost center</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr class="bg-slate-950/60 border-b border-slate-800 text-[10px] font-bold text-slate-400 uppercase tracking-wider">
                                <th class="py-2.5 px-4">Department</th>
                                <th class="py-2.5 px-4">Staff Count</th>
                                <th class="py-2.5 px-4">Gross Salaries</th>
                                <th class="py-2.5 px-4">Total Deductions</th>
                                <th class="py-2.5 px-4">Net Payout</th>
                                <th class="py-2.5 px-4 text-right">Budget Share</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/60">
                            @forelse($departmentBreakdown as $dept)
                                <tr class="hover:bg-slate-800/30">
                                    <td class="py-3 px-4 font-bold text-white">{{ $dept['department'] }}</td>
                                    <td class="py-3 px-4 text-slate-300">{{ $dept['headcount'] }} Staff</td>
                                    <td class="py-3 px-4 font-mono text-slate-300">PKR {{ number_format($dept['gross'], 2) }}</td>
                                    <td class="py-3 px-4 font-mono text-rose-400">-{{ number_format($dept['deductions'], 2) }}</td>
                                    <td class="py-3 px-4 font-mono font-bold text-emerald-400">PKR {{ number_format($dept['net_pay'], 2) }}</td>
                                    <td class="py-3 px-4 text-right font-bold text-indigo-400">{{ $dept['percentage'] }}%</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="py-4 text-center text-slate-500">No departmental records found for this period.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Comprehensive Master Payroll Register Sheet -->
            <div class="bg-slate-900/80 border border-slate-800 rounded-2xl overflow-hidden">
                <div class="p-4 border-b border-slate-800 flex items-center justify-between">
                    <h4 class="text-xs font-bold text-white uppercase tracking-wider">2. Master Corporate Salary Register (Detailed Sheet)</h4>
                    <span class="text-[11px] text-slate-400">Itemized line items per employee</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr class="bg-slate-950/60 border-b border-slate-800 text-[10px] font-bold text-slate-400 uppercase tracking-wider">
                                <th class="py-2.5 px-3 whitespace-nowrap">S.No</th>
                                <th class="py-2.5 px-3 whitespace-nowrap">Employee &amp; ID</th>
                                <th class="py-2.5 px-3 whitespace-nowrap">Company</th>
                                <th class="py-2.5 px-3 whitespace-nowrap">Basic Pay</th>
                                <th class="py-2.5 px-3 whitespace-nowrap">Allowances</th>
                                <th class="py-2.5 px-3 whitespace-nowrap">Gross Pay</th>
                                <th class="py-2.5 px-3 whitespace-nowrap">Advance Ded.</th>
                                <th class="py-2.5 px-3 whitespace-nowrap">Late/Absent</th>
                                <th class="py-2.5 px-3 whitespace-nowrap">EOBI</th>
                                <th class="py-2.5 px-3 whitespace-nowrap">Tax (FBR)</th>
                                <th class="py-2.5 px-3 whitespace-nowrap">Total Ded.</th>
                                <th class="py-2.5 px-3 whitespace-nowrap">Net Payable</th>
                                <th class="py-2.5 px-3 whitespace-nowrap">Method</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/60">
                            @forelse($reportSlips as $index => $s)
                                @php
                                    $u = $s->user;
                                    $org = $u->organization ?? null;
                                    $gross = (float)($s->base_salary + $s->allowances);
                                    $penalties = (float)($s->late_deduction ?? 0) + (float)($s->absent_deduction ?? 0);
                                    $totDed = (float)($s->deductions + $s->tax);
                                @endphp
                                <tr class="hover:bg-slate-800/30">
                                    <td class="py-2.5 px-3 text-slate-500 font-mono">{{ $index + 1 }}</td>
                                    <td class="py-2.5 px-3 font-bold text-white whitespace-nowrap">
                                        {{ $u->name ?? 'Staff' }}
                                        <span class="block text-[10px] font-normal text-slate-400">{{ $u->designation->title ?? 'Role' }} &bull; {{ $u->department->name ?? 'Dept' }}</span>
                                    </td>
                                    <td class="py-2.5 px-3 text-slate-300 whitespace-nowrap">{{ $org->name ?? 'Company' }}</td>
                                    <td class="py-2.5 px-3 font-mono text-slate-300 whitespace-nowrap">{{ number_format($s->base_salary, 0) }}</td>
                                    <td class="py-2.5 px-3 font-mono text-emerald-400 whitespace-nowrap">+{{ number_format($s->allowances, 0) }}</td>
                                    <td class="py-2.5 px-3 font-mono font-bold text-white whitespace-nowrap">{{ number_format($gross, 0) }}</td>
                                    <td class="py-2.5 px-3 font-mono text-rose-400 whitespace-nowrap">{{ number_format($s->advance_deduction ?? 0, 0) }}</td>
                                    <td class="py-2.5 px-3 font-mono text-rose-400 whitespace-nowrap">{{ number_format($penalties, 0) }}</td>
                                    <td class="py-2.5 px-3 font-mono text-rose-400 whitespace-nowrap">{{ number_format($s->eobi_deduction ?? 0, 0) }}</td>
                                    <td class="py-2.5 px-3 font-mono text-rose-400 whitespace-nowrap">{{ number_format($s->tax ?? 0, 0) }}</td>
                                    <td class="py-2.5 px-3 font-mono font-bold text-rose-400 whitespace-nowrap">-{{ number_format($totDed, 0) }}</td>
                                    <td class="py-2.5 px-3 font-mono font-extrabold text-emerald-400 whitespace-nowrap">{{ number_format($s->net_pay, 0) }}</td>
                                    <td class="py-2.5 px-3 text-slate-400 text-[10px] whitespace-nowrap">{{ $s->payment_method ?: 'BANK' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="13" class="py-8 text-center text-slate-500">No salary slips recorded for the selected report month.</td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if(count($reportSlips) > 0)
                        <tfoot>
                            <tr class="bg-slate-950 font-black text-xs text-white border-t-2 border-slate-700">
                                <td colspan="3" class="py-3 px-3 uppercase tracking-wider">TOTALS ({{ count($reportSlips) }} Staff)</td>
                                <td class="py-3 px-3 font-mono">{{ number_format($reportTotalBasic ?? 0, 0) }}</td>
                                <td class="py-3 px-3 font-mono text-emerald-400">+{{ number_format($reportTotalAllowances ?? 0, 0) }}</td>
                                <td class="py-3 px-3 font-mono">{{ number_format($reportTotalGross ?? 0, 0) }}</td>
                                <td class="py-3 px-3 font-mono text-rose-400">-{{ number_format($reportTotalAdvances ?? 0, 0) }}</td>
                                <td class="py-3 px-3 font-mono text-rose-400">-{{ number_format(($reportTotalLate ?? 0) + ($reportTotalAbsent ?? 0), 0) }}</td>
                                <td class="py-3 px-3 font-mono text-rose-400">-{{ number_format($reportTotalEOBI ?? 0, 0) }}</td>
                                <td class="py-3 px-3 font-mono text-rose-400">-{{ number_format($reportTotalTax ?? 0, 0) }}</td>
                                <td class="py-3 px-3 font-mono text-rose-400">-{{ number_format($reportTotalDeductions ?? 0, 0) }}</td>
                                <td class="py-3 px-3 font-mono text-emerald-400 text-sm">PKR {{ number_format($reportTotalNet ?? 0, 0) }}</td>
                                <td></td>
                            </tr>
                        </tfoot>
                        @endif
                    </table>
                </div>
            </div>

            <!-- Official Signatory Block for Audit -->
            <div class="pt-8 border-t border-slate-800 grid grid-cols-3 gap-6 text-center text-xs text-slate-400 font-semibold">
                <div class="space-y-8">
                    <div class="h-6 flex items-end justify-center italic text-slate-500">Human Resources</div>
                    <div class="border-t border-slate-700 pt-1 font-bold text-slate-200">
                        Prepared By (HR Department)
                    </div>
                </div>
                <div class="space-y-8">
                    <div class="h-6 flex items-end justify-center italic text-slate-500">Accounts &amp; Finance</div>
                    <div class="border-t border-slate-700 pt-1 font-bold text-slate-200">
                        Verified By (Finance Controller)
                    </div>
                </div>
                <div class="space-y-8">
                    <div class="h-6 flex items-end justify-center italic text-slate-500">Chief Executive / Managing Director</div>
                    <div class="border-t border-slate-700 pt-1 font-bold text-slate-200">
                        Approved By (Executive Management)
                    </div>
                </div>
            </div>

        </div>

    </div>
    @endif

    <!-- ============================================================= -->
    <!-- TAB 4: COMPANY PAYROLL TIRES (ENGINES & BATCH RUNS)           -->
    <!-- ============================================================= -->
    @if(Auth::user()->canManagePayroll())
    <div x-show="activeTab === 'overview'" class="space-y-6">
        <!-- Company Filter Bar -->
        <div class="bg-slate-950/60 border border-slate-800 rounded-2xl p-4 flex flex-col md:flex-row items-center justify-between gap-4 shadow-xl">
            <form action="{{ route('payroll.index') }}" method="GET" class="w-full flex flex-col md:flex-row items-center gap-3">
                <input type="hidden" name="tab" value="overview">
                <div class="flex items-center space-x-2 w-full md:w-auto">
                    <span class="text-xs font-semibold text-slate-400 whitespace-nowrap"><i class="fa-solid fa-building mr-1.5 text-indigo-400"></i>Filter by Company:</span>
                    <select name="organization_id" onchange="this.form.submit()" class="w-full md:w-64 px-3 py-2 bg-slate-900 border border-slate-800 rounded-xl text-xs font-bold text-white focus:outline-none focus:border-indigo-500 transition-all">
                        <option value="">All Companies ({{ count($organizations) }})</option>
                        @foreach($organizations as $org)
                            <option value="{{ $org->id }}" {{ $selectedOrgId == $org->id ? 'selected' : '' }}>
                                {{ $org->name }} ({{ $org->currency }})
                            </option>
                        @endforeach
                    </select>
                </div>

                @if($selectedOrgId)
                    <a href="{{ route('payroll.index', ['tab' => 'overview']) }}" class="text-xs font-semibold text-rose-400 hover:underline">Show All Companies</a>
                @endif
            </form>

            <div class="text-xs text-slate-400 font-medium whitespace-nowrap">
                Active Payroll Profiles: <span class="font-bold text-white">{{ count($payrolls) }}</span>
            </div>
        </div>

        <!-- Company Payroll Cards Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            @forelse($payrolls as $pr)
                <div class="bg-slate-950/60 border border-slate-800 hover:border-indigo-500/40 rounded-3xl p-6 space-y-4 shadow-2xl transition-all flex flex-col justify-between group">
                    <div class="space-y-3">
                        <div class="flex items-start justify-between">
                            <span class="px-2.5 py-1 bg-indigo-500/10 border border-indigo-500/20 text-indigo-400 font-bold text-[11px] rounded-lg">
                                <i class="fa-solid fa-building mr-1"></i> {{ $pr->organization->name ?? 'Company' }}
                            </span>

                            <div class="flex items-center space-x-1">
                                <span class="px-2.5 py-0.5 rounded-full font-bold bg-slate-900 border border-slate-800 text-slate-300 text-[10px]">
                                    {{ $pr->pay_frequency }}
                                </span>
                                <span class="px-2 py-0.5 rounded-full font-bold {{ $pr->status == 'ACTIVE' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-rose-500/10 text-rose-400 border border-rose-500/20' }} text-[10px]">
                                    {{ $pr->status }}
                                </span>
                            </div>
                        </div>

                        <div>
                            <h3 class="text-lg font-extrabold text-white group-hover:text-indigo-300 transition-colors">{{ $pr->name }}</h3>
                            <p class="text-[11px] font-mono text-slate-500">{{ $pr->code ?: 'PAYROLL-' . substr($pr->id, 0, 6) }}</p>
                        </div>

                        <p class="text-xs text-slate-400 line-clamp-2">{{ $pr->description ?: 'Standard corporate payroll profile for assigned employees.' }}</p>

                        <div class="bg-slate-900/90 rounded-2xl p-3.5 border border-slate-800/90 space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="text-[11px] font-semibold text-slate-400">Total Company Outflow</span>
                                <span class="px-2 py-0.5 bg-indigo-500/20 text-indigo-300 font-extrabold rounded-md text-[11px]">
                                    <i class="fa-solid fa-users text-indigo-400 mr-1"></i>{{ $pr->employees_count ?? 0 }} Staff
                                </span>
                            </div>
                            <div class="flex items-baseline justify-between">
                                <p class="text-xl font-black text-emerald-400 tracking-tight">
                                    {{ $pr->currency }} {{ number_format($pr->total_monthly_outflow, 2) }}
                                </p>
                                <span class="text-[10px] text-slate-500 font-medium">Auto-Aggregated</span>
                            </div>
                        </div>

                        <div class="flex items-center justify-between text-[11px] text-slate-400 pt-1">
                            <span>Tax Rate: <strong class="text-amber-400">{{ number_format($pr->tax_rate, 1) }}%</strong></span>
                            <span>Overtime Rate: <strong class="text-cyan-400">{{ $pr->overtime_multiplier }}x</strong></span>
                        </div>
                    </div>

                    <div class="pt-4 border-t border-slate-800/80 flex items-center justify-between gap-2">
                        <button @click="activePayroll = {{ json_encode($pr) }}; targetPayrollId = '{{ $pr->id }}'; runModalOpen = true" class="w-full py-2 bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs rounded-xl shadow-lg shadow-emerald-600/20 transition-all flex items-center justify-center space-x-2">
                            <i class="fa-solid fa-play text-xs"></i>
                            <span>Execute Batch Run</span>
                        </button>

                        <button @click="activePayroll = {{ json_encode($pr) }}; editModalOpen = true" class="p-2 text-slate-400 hover:text-white hover:bg-slate-800 rounded-lg transition-colors" title="Edit Payroll">
                            <i class="fa-solid fa-pen-to-square"></i>
                        </button>

                        <form action="{{ route('payroll.destroy', $pr->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this payroll profile?');" class="inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="p-2 text-slate-400 hover:text-rose-400 hover:bg-rose-500/10 rounded-lg transition-colors" title="Delete">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </form>
                    </div>
                </div>
            @empty
                <div class="col-span-full bg-slate-950/60 border border-slate-800 rounded-3xl p-12 text-center space-y-3">
                    <div class="w-12 h-12 rounded-2xl bg-slate-900 border border-slate-800 text-slate-500 flex items-center justify-center mx-auto text-xl">
                        <i class="fa-solid fa-file-invoice-dollar"></i>
                    </div>
                    <p class="text-sm font-semibold text-slate-400">No active company payroll structures found.</p>
                    <button @click="createModalOpen = true" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs rounded-xl shadow-md">
                        Create First Company Payroll
                    </button>
                </div>
            @endforelse
        </div>
    </div>
    @endif

    <!-- ============================================================= -->
    <!-- TAB 5: ADVANCE SALARY & STAFF LOANS REGISTRY                  -->
    <!-- ============================================================= -->
    <div x-show="activeTab === 'advances'" class="space-y-6">
        <div @click.outside="closeRevealedRows()" class="bg-slate-950/60 border border-slate-800 rounded-3xl overflow-hidden shadow-2xl space-y-4">
            <div class="p-6 border-b border-slate-800 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div>
                    <h3 class="text-base font-bold text-white tracking-tight">Advance Salary &amp; Staff Loans Registry</h3>
                    <p class="text-xs text-slate-400">Manage mid-month advance salary disbursements. Approved advances are automatically deducted on the employee's salary slip.</p>
                </div>
                @if(Auth::user()->canManagePayroll())
                <button @click="grantAdvanceModalOpen = true" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs rounded-xl shadow-lg shadow-emerald-600/30 transition-all flex items-center space-x-2">
                    <i class="fa-solid fa-plus"></i>
                    <span>Issue Advance Salary</span>
                </button>
                @endif
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-900/80 border-b border-slate-800 text-[11px] font-bold text-slate-400 uppercase tracking-wider">
                            <th class="py-3.5 px-6 whitespace-nowrap">Employee</th>
                            <th class="py-3.5 px-6 whitespace-nowrap">Advance Amount</th>
                            <th class="py-3.5 px-6 whitespace-nowrap">Grant Date</th>
                            <th class="py-3.5 px-6 whitespace-nowrap">Deduction Month</th>
                            <th class="py-3.5 px-6 whitespace-nowrap">Reason / Purpose</th>
                            <th class="py-3.5 px-6 whitespace-nowrap">Status</th>
                            @if(Auth::user()->canManagePayroll())
                            <th class="py-3.5 px-6 text-right whitespace-nowrap">Actions</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/80 text-xs">
                        @forelse($advanceSalaries as $adv)
                            <tr 
                                @click="toggleAdvRow('{{ $adv->id }}')" 
                                :class="revealedAdvId === '{{ $adv->id }}' ? 'privacy-row-revealed border-l-4 border-indigo-500' : 'hover:bg-slate-900/40 border-l-4 border-transparent'"
                                class="transition-all duration-200 cursor-pointer group"
                                title="Click to toggle advance privacy"
                            >
                                <td class="py-4 px-6 whitespace-nowrap">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-8 h-8 rounded-lg bg-emerald-600/20 text-emerald-400 flex items-center justify-center font-bold text-xs shrink-0">
                                            <i class="fa-solid fa-hand-holding-dollar"></i>
                                        </div>
                                        <div>
                                            <div class="flex items-center space-x-2">
                                                <p class="font-bold text-white">{{ $adv->user->name ?? 'Employee' }}</p>
                                                <span x-show="revealedAdvId === '{{ $adv->id }}'" x-cloak class="inline-flex items-center gap-1 text-[9px] font-bold text-emerald-400 bg-emerald-500/10 px-1.5 py-0.5 rounded border border-emerald-500/20">
                                                    <i class="fa-solid fa-eye text-[8px]"></i> Visible
                                                </span>
                                                <span x-show="revealedAdvId !== '{{ $adv->id }}'" class="inline-flex items-center gap-1 text-[9px] font-medium text-slate-500 bg-slate-900/80 px-1.5 py-0.5 rounded border border-slate-800 group-hover:text-slate-400 transition-colors">
                                                    <i class="fa-solid fa-eye-slash text-[8px]"></i> Click to view
                                                </span>
                                            </div>
                                            <p class="text-slate-400 text-[11px] font-medium">{{ $adv->user->designation->title ?? 'Staff' }} &bull; {{ $adv->user->organization->name ?? 'Company' }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-4 px-6 whitespace-nowrap font-black text-rose-400 text-sm font-mono">
                                    <div :class="revealedAdvId === '{{ $adv->id }}' ? 'privacy-clear' : 'privacy-blur'">
                                        PKR {{ number_format($adv->amount, 2) }}
                                    </div>
                                </td>
                                <td class="py-4 px-6 whitespace-nowrap text-slate-300 font-medium">
                                    <div :class="revealedAdvId === '{{ $adv->id }}' ? 'privacy-clear' : 'privacy-blur'">
                                        {{ $adv->advance_date ? date('d-M-Y', strtotime($adv->advance_date)) : date('d-M-Y', strtotime($adv->created_at)) }}
                                    </div>
                                </td>
                                <td class="py-4 px-6 whitespace-nowrap">
                                    <div :class="revealedAdvId === '{{ $adv->id }}' ? 'privacy-clear' : 'privacy-blur'">
                                        <span class="px-2.5 py-1 rounded-lg bg-slate-900 border border-slate-800 text-indigo-300 font-bold text-[11px]">
                                            <i class="fa-regular fa-calendar mr-1"></i>{{ $adv->deduction_month ? date('F Y', strtotime($adv->deduction_month . '-01')) : 'Next Salary Day' }}
                                        </span>
                                    </div>
                                </td>
                                <td class="py-4 px-6 text-slate-400 max-w-xs truncate">
                                    <div :class="revealedAdvId === '{{ $adv->id }}' ? 'privacy-clear' : 'privacy-blur'">
                                        {{ $adv->reason ?: 'Emergency Advance' }}
                                    </div>
                                </td>
                                <td class="py-4 px-6 whitespace-nowrap">
                                    <div :class="revealedAdvId === '{{ $adv->id }}' ? 'privacy-clear' : 'privacy-blur'">
                                        @if($adv->status === 'DEDUCTED')
                                            <span class="px-2.5 py-1 rounded-full font-bold bg-indigo-500/10 text-indigo-400 border border-indigo-500/20 text-[10px]">
                                                <i class="fa-solid fa-circle-check mr-1"></i> DEDUCTED ON SALARY DAY
                                            </span>
                                        @elseif($adv->status === 'DISBURSED' || $adv->status === 'APPROVED')
                                            <span class="px-2.5 py-1 rounded-full font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 text-[10px]">
                                                <i class="fa-solid fa-clock mr-1"></i> APPROVED &amp; SCHEDULED
                                            </span>
                                        @elseif($adv->status === 'PENDING')
                                            <span class="px-2.5 py-1 rounded-full font-bold bg-amber-500/10 text-amber-400 border border-amber-500/20 text-[10px]">
                                                <i class="fa-solid fa-hourglass-half mr-1"></i> PENDING APPROVAL
                                            </span>
                                        @else
                                            <span class="px-2.5 py-1 rounded-full font-bold bg-rose-500/10 text-rose-400 border border-rose-500/20 text-[10px]">
                                                {{ $adv->status }}
                                            </span>
                                        @endif
                                    </div>
                                </td>
                                @if(Auth::user()->canManagePayroll())
                                <td class="py-4 px-6 text-right whitespace-nowrap">
                                    <div :class="revealedAdvId === '{{ $adv->id }}' ? 'privacy-clear' : 'privacy-blur'">
                                        <div class="flex items-center justify-end space-x-2">
                                            @if($adv->status === 'PENDING')
                                                <form action="{{ route('payroll.advance-salaries.status', $adv->id) }}" method="POST" class="inline">
                                                    @csrf
                                                    @method('PUT')
                                                    <input type="hidden" name="status" value="APPROVED">
                                                    <button type="submit" @click.stop class="px-2.5 py-1 bg-emerald-600/20 hover:bg-emerald-600 text-emerald-400 hover:text-white rounded-lg text-xs font-bold transition-colors">
                                                        Approve
                                                    </button>
                                                </form>
                                            @endif
                                            <form action="{{ route('payroll.advance-salaries.destroy', $adv->id) }}" method="POST" onsubmit="return confirm('Delete this advance salary record?');" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" @click.stop class="p-1.5 text-slate-400 hover:text-rose-400 hover:bg-rose-500/10 rounded-lg transition-colors" title="Delete">
                                                    <i class="fa-solid fa-trash text-xs"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-8 text-center text-slate-500 font-medium">
                                    No advance salary or loan records recorded.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>


    <!-- ============================================================= -->
    <!-- MODAL: DISBURSE SALARY TO A SINGLE EMPLOYEE                   -->
    <!-- ============================================================= -->
    <div x-show="payModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/85 backdrop-blur-md overflow-y-auto">
        <div @click.away="payModalOpen = false" class="bg-slate-900 border border-slate-800 rounded-3xl max-w-2xl w-full p-6 md:p-8 space-y-6 shadow-2xl max-h-[92vh] overflow-y-auto">
            
            <div class="flex items-center justify-between border-b border-slate-800 pb-4">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-2xl bg-emerald-600/20 border border-emerald-500/30 flex items-center justify-center text-emerald-400 text-lg shadow-inner">
                        <i class="fa-solid fa-hand-holding-dollar"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-black text-white">Disburse Salary to Employee</h3>
                        <p class="text-xs text-slate-400">Review compensation, apply deductions, and disburse official salary.</p>
                    </div>
                </div>
                <button @click="payModalOpen = false" class="text-slate-400 hover:text-white p-2 hover:bg-slate-800 rounded-xl transition-colors">
                    <i class="fa-solid fa-xmark text-sm"></i>
                </button>
            </div>

            <!-- Employee Snapshot Card -->
            <div class="p-4 bg-slate-950 border border-slate-800 rounded-2xl flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-xl bg-indigo-600 text-white flex items-center justify-center font-bold text-sm shadow">
                        <span x-text="selectedEmployee.name ? selectedEmployee.name.substring(0, 2).toUpperCase() : 'EM'"></span>
                    </div>
                    <div>
                        <h4 class="text-sm font-black text-white" x-text="selectedEmployee.name"></h4>
                        <p class="text-[11px] text-slate-400" x-text="(selectedEmployee.designation?.title || 'Staff') + ' • ' + (selectedEmployee.department?.name || 'Department')"></p>
                    </div>
                </div>
                <div class="text-left sm:text-right">
                    <span class="inline-flex items-center space-x-1.5 px-2.5 py-1 bg-slate-900 border border-slate-800 rounded-lg text-slate-300 font-bold text-[11px]">
                        <i class="fa-solid fa-building text-indigo-400 text-[10px]"></i>
                        <span x-text="selectedEmployee.organization?.name || 'Company'"></span>
                    </span>
                    <p class="text-[10px] text-slate-500 mt-0.5" x-text="'Bank: ' + (selectedEmployee.bank_name || 'Bank Transfer')"></p>
                </div>
            </div>

            <!-- Live Dual Compensation Structure Banner (Bank Transfer vs Cash in Hand) -->
            <div class="p-4 bg-gradient-to-r from-slate-950 via-slate-900 to-indigo-950/50 border border-slate-800 rounded-2xl space-y-3">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <!-- Bank Transfer Component -->
                    <div class="p-3 bg-slate-900/90 border border-slate-800 rounded-xl space-y-1">
                        <span class="text-[10px] uppercase font-bold text-slate-400 flex items-center space-x-1">
                            <i class="fa-solid fa-building-columns text-indigo-400"></i>
                            <span>1. Bank Transfer (Net)</span>
                        </span>
                        <h4 class="text-base font-black text-indigo-300 font-mono" x-text="(selectedEmployee.organization?.currency || 'PKR') + ' ' + calculateNet().toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})"></h4>
                        <p class="text-[10px] text-slate-500">Gross (<span x-text="calculateGross().toLocaleString()"></span>) - Ded. (<span x-text="calculateDeductions().toLocaleString()"></span>)</p>
                    </div>

                    <!-- Cash in Hand Component -->
                    <div class="p-3 bg-amber-500/10 border border-amber-500/25 rounded-xl space-y-1">
                        <span class="text-[10px] uppercase font-bold text-amber-400 flex items-center space-x-1">
                            <i class="fa-solid fa-hand-holding-dollar"></i>
                            <span>2. Cash in Hand</span>
                        </span>
                        <h4 class="text-base font-black text-amber-400 font-mono" x-text="(selectedEmployee.organization?.currency || 'PKR') + ' ' + Number(payForm.cash_allowance || 0).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})"></h4>
                        <p class="text-[10px] text-amber-300/80">Confidential (HR &amp; System Admin only)</p>
                    </div>

                    <!-- Total Package Remuneration -->
                    <div class="p-3 bg-emerald-500/10 border border-emerald-500/25 rounded-xl space-y-1">
                        <span class="text-[10px] uppercase font-bold text-emerald-400 flex items-center space-x-1">
                            <i class="fa-solid fa-calculator"></i>
                            <span>Total Remuneration</span>
                        </span>
                        <h4 class="text-lg font-black text-emerald-400 font-mono" x-text="(selectedEmployee.organization?.currency || 'PKR') + ' ' + (calculateNet() + Number(payForm.cash_allowance || 0)).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})"></h4>
                        <p class="text-[10px] text-emerald-300/80">Bank Transfer + Cash in Hand</p>
                    </div>
                </div>

                <div class="flex items-center space-x-2 text-[11px] text-slate-400 px-1">
                    <i class="fa-solid fa-shield-halved text-amber-400 text-xs"></i>
                    <span><strong>Confidential Policy:</strong> Official payslip vouchers only reflect the Bank Transfer component. Cash in Hand is maintained strictly for executive payroll accounting.</span>
                </div>
            </div>

            <form action="{{ route('payroll.pay-single') }}" method="POST" class="space-y-4 text-xs">
                @csrf
                <input type="hidden" name="user_id" :value="payForm.user_id">

                <!-- Pay Period & Disbursement Date -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                        <label class="block font-semibold text-slate-300 mb-1">Period Start *</label>
                        <input type="date" name="period_start" x-model="payForm.period_start" required class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-300 mb-1">Period End *</label>
                        <input type="date" name="period_end" x-model="payForm.period_end" required class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-300 mb-1">Disbursement Date *</label>
                        <input type="date" name="pay_date" x-model="payForm.pay_date" required class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white">
                    </div>
                </div>

                <!-- Earnings Breakdown -->
                <div class="p-3.5 bg-slate-950 border border-slate-800 rounded-2xl space-y-3">
                    <div class="flex items-center justify-between border-b border-slate-800/80 pb-2">
                        <span class="font-bold text-emerald-400 text-xs uppercase tracking-wider flex items-center space-x-1.5">
                            <i class="fa-solid fa-plus-circle"></i>
                            <span>1. Earnings &amp; Allowances</span>
                        </span>
                        <span class="font-mono font-bold text-emerald-400 text-xs" x-text="'Total: PKR ' + calculateGross().toLocaleString()"></span>
                    </div>

                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                        <div>
                            <label class="block font-semibold text-slate-300 mb-1">Basic Salary *</label>
                            <input type="number" step="0.01" name="base_salary" x-model.number="payForm.base_salary" @input="recalculateTax()" required class="w-full px-3 py-2 bg-slate-900 border border-slate-800 rounded-xl text-white font-mono font-bold">
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-300 mb-1">Fuel Allowance</label>
                            <input type="number" step="0.01" name="fuel_allowance" x-model.number="payForm.fuel_allowance" @input="recalculateTax()" class="w-full px-3 py-2 bg-slate-900 border border-slate-800 rounded-xl text-white font-mono">
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-300 mb-1">Special Allowance</label>
                            <input type="number" step="0.01" name="special_allowance" x-model.number="payForm.special_allowance" @input="recalculateTax()" class="w-full px-3 py-2 bg-slate-900 border border-slate-800 rounded-xl text-white font-mono">
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-300 mb-1">Bonus / Arrears</label>
                            <input type="number" step="0.01" name="other_allowances" x-model.number="payForm.other_allowances" class="w-full px-3 py-2 bg-slate-900 border border-slate-800 rounded-xl text-white font-mono">
                        </div>
                    </div>
                </div>

                <!-- Deductions Breakdown -->
                <div class="p-3.5 bg-slate-950 border border-slate-800 rounded-2xl space-y-3">
                    <div class="flex items-center justify-between border-b border-slate-800/80 pb-2">
                        <span class="font-bold text-rose-400 text-xs uppercase tracking-wider flex items-center space-x-1.5">
                            <i class="fa-solid fa-minus-circle"></i>
                            <span>2. Deductions &amp; Recoveries</span>
                        </span>
                        <span class="font-mono font-bold text-rose-400 text-xs" x-text="'Total: PKR ' + calculateDeductions().toLocaleString()"></span>
                    </div>

                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                        <div>
                            <label class="block font-semibold text-slate-300 mb-1">Advance Recovered</label>
                            <input type="number" step="0.01" name="advance_deduction" x-model.number="payForm.advance_deduction" class="w-full px-3 py-2 bg-slate-900 border border-slate-800 rounded-xl text-rose-300 font-mono font-bold">
                            <span x-show="selectedEmployee.pending_advance_amount > 0" class="text-[10px] text-amber-400 mt-0.5 block" x-text="'Pending loan: PKR ' + selectedEmployee.pending_advance_amount"></span>
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-300 mb-1">Late Arrival Penalty</label>
                            <input type="number" step="0.01" name="late_deduction" x-model.number="payForm.late_deduction" class="w-full px-3 py-2 bg-slate-900 border border-slate-800 rounded-xl text-rose-300 font-mono">
                            <input type="hidden" name="late_days_deducted" :value="payForm.late_days_deducted">
                            <span x-show="selectedEmployee.current_month_lates > 0" class="text-[10px] text-slate-400 mt-0.5 block" x-text="selectedEmployee.current_month_lates + ' lates detected in month'"></span>
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-300 mb-1">Absent Days Penalty</label>
                            <input type="number" step="0.01" name="absent_deduction" x-model.number="payForm.absent_deduction" class="w-full px-3 py-2 bg-slate-900 border border-slate-800 rounded-xl text-rose-300 font-mono">
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-300 mb-1">EOBI / Social Security</label>
                            <input type="number" step="0.01" name="eobi_deduction" x-model.number="payForm.eobi_deduction" @input="recalculateTax()" class="w-full px-3 py-2 bg-slate-900 border border-slate-800 rounded-xl text-rose-300 font-mono">
                        </div>
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label class="font-semibold text-slate-300">Income Tax Withheld</label>
                                <span class="text-[10px] text-amber-400 font-bold" x-text="'Rate: ' + (currentTaxRate || 12) + '%'"></span>
                            </div>
                            <input type="number" step="0.01" name="tax" x-model.number="payForm.tax" class="w-full px-3 py-2 bg-slate-900 border border-slate-800 rounded-xl text-rose-300 font-mono font-bold">
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-300 mb-1">Other Authorized Ded.</label>
                            <input type="number" step="0.01" name="other_deductions" x-model.number="payForm.other_deductions" class="w-full px-3 py-2 bg-slate-900 border border-slate-800 rounded-xl text-rose-300 font-mono">
                        </div>
                    </div>
                </div>

                <!-- Payment Method & Payslip Template Selection -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div class="md:col-span-2">
                        <label class="block font-semibold text-slate-300 mb-1">Disbursement Channel *</label>
                        <select name="payment_method" x-model="payForm.payment_method" class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white font-semibold">
                            <option value="BANK_TRANSFER">Direct Bank Deposit / Transfer</option>
                            <option value="CASH">Cash in Hand</option>
                            <option value="CHEQUE">Corporate Cheque</option>
                        </select>
                        <input type="hidden" name="template_style" value="standard">
                    </div>
                </div>

                <div>
                    <label class="block font-semibold text-slate-300 mb-1">Disbursement Notes / Transaction Ref</label>
                    <input type="text" name="notes" x-model="payForm.notes" placeholder="e.g. Cleared via direct deposit Ref # TXN-99824" class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white focus:outline-none focus:border-indigo-500">
                </div>

                <!-- Live Net Pay Preview Box -->
                <div class="p-3.5 bg-slate-950/80 border border-slate-800 rounded-2xl flex items-center justify-between">
                    <div>
                        <span class="text-xs font-bold text-slate-300 block uppercase tracking-wider">Net Take-Home Pay (After Tax &amp; Deductions)</span>
                        <span class="text-[11px] text-slate-400">Gross: PKR <span x-text="calculateGross().toLocaleString()"></span> &minus; Deductions &amp; Tax: PKR <span x-text="calculateDeductions().toLocaleString()"></span></span>
                    </div>
                    <div class="text-right">
                        <span class="font-mono font-black text-emerald-400 text-lg" x-text="'PKR ' + calculateNet().toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})"></span>
                    </div>
                </div>

                <div class="pt-3 flex justify-end space-x-3 border-t border-slate-800">
                    <button type="button" @click="payModalOpen = false" class="px-4 py-2 bg-slate-800 text-slate-300 font-semibold rounded-xl">Cancel</button>
                    <button type="submit" class="px-5 py-2 bg-emerald-600 hover:bg-emerald-500 text-white font-bold rounded-xl shadow-lg shadow-emerald-600/30">Confirm &amp; Disburse Salary</button>
                </div>
            </form>

        </div>
    </div>


    <!-- ============================================================= -->
    <!-- MODAL: SALARY VOUCHER WITH 4 COMPANY-TAILORED TEMPLATES       -->
    <!-- ============================================================= -->
    <div x-show="viewPayslipModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/85 backdrop-blur-md overflow-y-auto">
        <div @click.away="viewPayslipModalOpen = false" class="bg-slate-900 border border-slate-800 rounded-3xl max-w-4xl w-full p-6 md:p-8 space-y-6 shadow-2xl max-h-[95vh] overflow-y-auto relative">
            
            <!-- Modal Top Control Toolbar (Hidden during Print) -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-800 pb-4 no-print w-full">
                <div class="space-y-1">
                    <div class="flex items-center space-x-2">
                        <span class="w-8 h-8 rounded-xl bg-indigo-600/20 border border-indigo-500/30 text-indigo-400 flex items-center justify-center font-bold text-xs">
                            <i class="fa-solid fa-file-invoice"></i>
                        </span>
                        <h3 class="text-base font-black text-white">Official Salary Voucher</h3>
                    </div>
                    <p class="text-xs text-slate-400">Official verified remuneration voucher with corporate digital stamp.</p>
                </div>

                <div class="flex items-center space-x-2.5 shrink-0">
                    <a :href="'/payroll/payslips/' + currentPayslip?.id + '/download'" target="_blank" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs rounded-xl shadow-lg shadow-emerald-600/30 transition-all flex items-center space-x-2">
                        <i class="fa-solid fa-download"></i>
                        <span>Download PDF / Print</span>
                    </a>

                    <button type="button" @click="window.open('/payroll/payslips/' + currentPayslip?.id + '/download', '_blank')" class="px-3.5 py-2 bg-slate-800 hover:bg-slate-700 text-slate-200 hover:text-white border border-slate-700 font-bold text-xs rounded-xl transition-all flex items-center space-x-1.5">
                        <i class="fa-solid fa-print"></i>
                        <span>Print Preview</span>
                    </button>

                    <button type="button" @click="viewPayslipModalOpen = false" class="p-2 text-slate-400 hover:text-white bg-slate-800 hover:bg-slate-700 rounded-xl transition-colors">
                        <i class="fa-solid fa-xmark text-sm"></i>
                    </button>
                </div>
            </div>

            <!-- Print Document Root -->
            <template x-if="currentPayslip">
                <div id="printable-payslip-voucher">
                    <div class="bg-slate-50 text-slate-900 rounded-3xl p-6 md:p-10 space-y-6 shadow-2xl border border-emerald-500/20 font-sans">
                        <!-- Modern Header -->
                        <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200 flex flex-col md:flex-row items-center justify-between gap-6 text-center md:text-left">
                            <div class="flex flex-col md:flex-row items-center space-y-3 md:space-y-0 md:space-x-5">
                                <div>
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-emerald-100 text-emerald-800 uppercase tracking-widest">Confidential Remuneration Voucher</span>
                                    <h2 class="text-2xl font-black text-slate-900 tracking-tight mt-1" x-text="currentPayslip.company_name"></h2>
                                    <p class="text-xs text-slate-500" x-text="currentPayslip.company_address + (currentPayslip.company_tax_no ? ' • NTN: ' + currentPayslip.company_tax_no : '')"></p>
                                </div>
                            </div>
                            <div class="bg-emerald-50 border border-emerald-200 rounded-2xl p-4 text-center md:text-right space-y-1 shrink-0">
                                <span class="text-[10px] font-black uppercase tracking-wider text-emerald-800">Direct Deposit Statement</span>
                                <h4 class="text-base font-black text-emerald-950 font-mono" x-text="currentPayslip.ref_no"></h4>
                                <span class="text-xs font-bold text-slate-600" x-text="currentPayslip.salary_period"></span>
                            </div>
                        </div>

                        <!-- Card-based Employee Details -->
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                            <div class="bg-white p-3.5 rounded-2xl border border-slate-200 shadow-sm">
                                <span class="text-[10px] font-bold text-slate-400 uppercase block">Employee Name</span>
                                <strong class="text-sm font-black text-slate-900" x-text="currentPayslip.employee_name"></strong>
                            </div>
                            <div class="bg-white p-3.5 rounded-2xl border border-slate-200 shadow-sm">
                                <span class="text-[10px] font-bold text-slate-400 uppercase block">Employee Code / Role</span>
                                <strong class="text-sm font-black text-slate-900" x-text="currentPayslip.employee_code + ' • ' + currentPayslip.designation"></strong>
                            </div>
                            <div class="bg-white p-3.5 rounded-2xl border border-slate-200 shadow-sm">
                                <span class="text-[10px] font-bold text-slate-400 uppercase block">Disbursement Channel</span>
                                <strong class="text-sm font-black text-emerald-700" x-text="currentPayslip.payment_method"></strong>
                            </div>
                            <div class="bg-white p-3.5 rounded-2xl border border-slate-200 shadow-sm">
                                <span class="text-[10px] font-bold text-slate-400 uppercase block">Clearance Date</span>
                                <strong class="text-sm font-black text-slate-900" x-text="currentPayslip.pay_date"></strong>
                            </div>
                        </div>

                        <!-- Modern Pill Table -->
                        <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200 space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Earnings Pills -->
                                <div class="space-y-3">
                                    <h4 class="text-xs font-black uppercase tracking-wider text-emerald-800 flex items-center justify-between border-b border-emerald-100 pb-2">
                                        <span>Earnings &amp; Compensation</span>
                                        <span x-text="(currentPayslip.currency || 'PKR') + ' ' + Number(currentPayslip.gross_salary || 0).toLocaleString('en-US', {minimumFractionDigits: 2})"></span>
                                    </h4>
                                    <div class="flex justify-between items-center p-2 rounded-xl bg-slate-50 text-xs">
                                        <span class="font-semibold text-slate-700">Basic Salary</span>
                                        <span class="font-bold font-mono text-slate-900" x-text="Number(currentPayslip.base_salary || 0).toLocaleString('en-US', {minimumFractionDigits: 2})"></span>
                                    </div>
                                    <div class="flex justify-between items-center p-2 rounded-xl bg-emerald-50/60 text-xs">
                                        <span class="font-semibold text-emerald-900">Fuel &amp; Conveyance</span>
                                        <span class="font-bold font-mono text-emerald-900" x-text="Number(currentPayslip.fuel_allowance || 0).toLocaleString('en-US', {minimumFractionDigits: 2})"></span>
                                    </div>
                                    <div class="flex justify-between items-center p-2 rounded-xl bg-emerald-50/60 text-xs">
                                        <span class="font-semibold text-emerald-900">Special Allowance</span>
                                        <span class="font-bold font-mono text-emerald-900" x-text="Number(currentPayslip.special_allowance || 0).toLocaleString('en-US', {minimumFractionDigits: 2})"></span>
                                    </div>
                                    <div class="flex justify-between items-center p-2 rounded-xl bg-slate-50 text-xs">
                                        <span class="font-semibold text-slate-700">Performance / Other</span>
                                        <span class="font-bold font-mono text-slate-900" x-text="Number(currentPayslip.other_allowances || 0).toLocaleString('en-US', {minimumFractionDigits: 2})"></span>
                                    </div>
                                </div>

                                <!-- Deductions Pills -->
                                <div class="space-y-3">
                                    <h4 class="text-xs font-black uppercase tracking-wider text-rose-800 flex items-center justify-between border-b border-rose-100 pb-2">
                                        <span>Deductions &amp; Statutory</span>
                                        <span x-text="(currentPayslip.currency || 'PKR') + ' ' + Number(currentPayslip.gross_deduction || 0).toLocaleString('en-US', {minimumFractionDigits: 2})"></span>
                                    </h4>
                                    <div class="flex justify-between items-center p-2 rounded-xl bg-amber-50 text-xs">
                                        <span class="font-semibold text-amber-900">Advance Recovered</span>
                                        <span class="font-bold font-mono text-amber-900" x-text="Number(currentPayslip.advance_deduction || 0).toLocaleString('en-US', {minimumFractionDigits: 2})"></span>
                                    </div>
                                    <div class="flex justify-between items-center p-2 rounded-xl bg-rose-50/60 text-xs">
                                        <span class="font-semibold text-rose-900">Late &amp; Attendance Penalty</span>
                                        <span class="font-bold font-mono text-rose-900" x-text="Number(currentPayslip.late_deduction || 0).toLocaleString('en-US', {minimumFractionDigits: 2})"></span>
                                    </div>
                                    <div class="flex justify-between items-center p-2 rounded-xl bg-rose-50/60 text-xs">
                                        <span class="font-semibold text-rose-900">EOBI &amp; Social Security</span>
                                        <span class="font-bold font-mono text-rose-900" x-text="Number(currentPayslip.eobi_deduction || 0).toLocaleString('en-US', {minimumFractionDigits: 2})"></span>
                                    </div>
                                    <div class="flex justify-between items-center p-2 rounded-xl bg-rose-50/60 text-xs">
                                        <span class="font-semibold text-rose-900">Tax Withholding</span>
                                        <span class="font-bold font-mono text-rose-900" x-text="Number(currentPayslip.tax || 0).toLocaleString('en-US', {minimumFractionDigits: 2})"></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Modern Floating Net Banner -->
                        <div class="bg-gradient-to-r from-emerald-600 to-teal-600 text-white rounded-2xl p-6 flex flex-col sm:flex-row items-center justify-between gap-4 shadow-xl">
                            <div>
                                <span class="text-xs font-black uppercase tracking-widest text-emerald-100 block">Total Net Salary Credited</span>
                                <p class="text-xs text-emerald-100/90" x-text="'Account: ' + currentPayslip.bank_account"></p>
                            </div>
                            <h3 class="text-3xl font-black font-mono tracking-tight" x-text="(currentPayslip.currency || 'PKR') + ' ' + Number(currentPayslip.net_pay || 0).toLocaleString('en-US', {minimumFractionDigits: 2})"></h3>
                        </div>

                        <!-- Clean Corporate Footer -->
                        <div class="pt-4 border-t border-slate-200 flex flex-col sm:flex-row items-center justify-between text-xs text-slate-500 gap-2">
                            <span x-text="currentPayslip.company_name + ' • Official Payroll Statement'"></span>
                            <span>Authorized Digital Certificate • System Generated Voucher</span>
                        </div>

                        <!-- Signature Lines for Print -->
                        <div class="pt-6 grid grid-cols-2 gap-12 text-center text-xs text-slate-700">
                            <div class="border-t border-slate-400 pt-1 font-medium">Prepared By (Finance / Accounts)</div>
                            <div class="border-t border-slate-400 pt-1 font-medium">Authorized Corporate Signatory</div>
                        </div>
                    </div>
                </div>
            </template>

        </div>
    </div>


    <!-- ============================================================= -->
    <!-- EXISTING MODALS: GRANT ADVANCE, CREATE, EDIT, RUN, ASSIGN     -->
    <!-- ============================================================= -->

    <!-- Grant Advance Salary Modal -->
    <div x-show="grantAdvanceModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-md overflow-y-auto">
        <div @click.away="grantAdvanceModalOpen = false" class="bg-slate-900 border border-slate-800 rounded-3xl max-w-lg w-full p-6 md:p-8 space-y-5 shadow-2xl max-h-[92vh] overflow-y-auto">
            <div class="flex items-center justify-between border-b border-slate-800 pb-4">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-2xl bg-emerald-600/20 border border-emerald-500/30 flex items-center justify-center text-emerald-400">
                        <i class="fa-solid fa-hand-holding-dollar text-base"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-black text-white">Issue Advance Salary / Loan</h3>
                        <p class="text-xs text-slate-400">Advance will automatically deduct on the specified salary day.</p>
                    </div>
                </div>
                <button @click="grantAdvanceModalOpen = false" class="text-slate-400 hover:text-white p-2 hover:bg-slate-800 rounded-xl transition-colors"><i class="fa-solid fa-xmark text-sm"></i></button>
            </div>

            <form action="{{ route('payroll.advance-salaries.store') }}" method="POST" class="space-y-4 text-xs">
                @csrf
                <div>
                    <label class="block font-semibold text-slate-300 mb-1">Select Employee *</label>
                    <select name="user_id" required class="w-full px-3 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-white focus:outline-none focus:border-indigo-500">
                        <option value="">-- Choose Employee --</option>
                        @foreach($employees as $emp)
                            <option value="{{ $emp->id }}">{{ $emp->name }} ({{ $emp->designation->title ?? 'Staff' }} - {{ $emp->organization->name ?? 'Company' }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-semibold text-slate-300 mb-1">Advance Amount (PKR) *</label>
                        <input type="number" step="0.01" name="amount" required placeholder="e.g. 15000" class="w-full px-3 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-white font-bold focus:outline-none focus:border-indigo-500">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-300 mb-1">Disbursement Date *</label>
                        <input type="date" name="advance_date" value="{{ date('Y-m-d') }}" required class="w-full px-3 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-white focus:outline-none focus:border-indigo-500">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-semibold text-slate-300 mb-1">Deduct On Salary Month *</label>
                        <input type="month" name="deduction_month" value="{{ date('Y-m') }}" required class="w-full px-3 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-white font-bold focus:outline-none focus:border-indigo-500">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-300 mb-1">Status *</label>
                        <select name="status" required class="w-full px-3 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-white">
                            <option value="APPROVED">Approved (Ready for Deduction)</option>
                            <option value="DISBURSED">Disbursed (Cash/Transfer Given)</option>
                            <option value="PENDING">Pending Review</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block font-semibold text-slate-300 mb-1">Reason / Purpose</label>
                    <input type="text" name="reason" placeholder="e.g. Emergency medical expenses / Family requirement" class="w-full px-3 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-white focus:outline-none focus:border-indigo-500">
                </div>

                <div class="p-3 bg-slate-950 border border-slate-800 rounded-2xl flex items-center space-x-3 text-[11px] text-slate-400">
                    <i class="fa-solid fa-circle-info text-emerald-400 text-sm shrink-0"></i>
                    <span>This amount will be placed under the <strong>Advance</strong> column on the employee's salary slip and deducted from their Gross Salary on salary day.</span>
                </div>

                <div class="pt-3 flex justify-end space-x-3">
                    <button type="button" @click="grantAdvanceModalOpen = false" class="px-4 py-2 bg-slate-800 text-slate-300 font-semibold rounded-xl">Cancel</button>
                    <button type="submit" class="px-5 py-2 bg-emerald-600 hover:bg-emerald-500 text-white font-bold rounded-xl shadow-lg shadow-emerald-600/30">Grant Advance</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Create Company Payroll Modal -->
    <div x-show="createModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm">
        <div @click.away="createModalOpen = false" class="bg-slate-900 border border-slate-800 rounded-3xl max-w-xl w-full p-6 space-y-5 shadow-2xl max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between border-b border-slate-800 pb-4">
                <h3 class="text-base font-bold text-white">Create New Company Payroll Structure</h3>
                <button @click="createModalOpen = false" class="text-slate-400 hover:text-white"><i class="fa-solid fa-xmark"></i></button>
            </div>

            <form action="{{ route('payroll.store') }}" method="POST" class="space-y-4 text-xs">
                @csrf
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-semibold text-slate-300 mb-1">Target Company *</label>
                        <select name="organization_id" required class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white">
                            @foreach($organizations as $org)
                                <option value="{{ $org->id }}" {{ $selectedOrgId == $org->id ? 'selected' : '' }}>{{ $org->name }} ({{ $org->currency }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-300 mb-1">Payroll Name *</label>
                        <input type="text" name="name" required placeholder="e.g. Senior Tech Staff Payroll" class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white focus:outline-none focus:border-indigo-500">
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-3">
                    <div>
                        <label class="block font-semibold text-slate-300 mb-1">Pay Frequency *</label>
                        <select name="pay_frequency" required class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white">
                            <option value="MONTHLY">Monthly</option>
                            <option value="BI_WEEKLY">Bi-Weekly</option>
                            <option value="WEEKLY">Weekly</option>
                        </select>
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-300 mb-1">Currency *</label>
                        <select name="currency" required class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white">
                            <option value="PKR" selected>PKR (Rs)</option>
                            <option value="USD">USD ($)</option>
                            <option value="EUR">EUR (€)</option>
                            <option value="GBP">GBP (£)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-300 mb-1">Default Working Days</label>
                        <input type="number" name="default_working_days" value="22" required class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white">
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-3">
                    <div>
                        <label class="block font-semibold text-slate-300 mb-1">Tax Rate % *</label>
                        <input type="number" step="0.1" name="tax_rate" value="12.0" required class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white">
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-300 mb-1">Overtime Multiplier</label>
                        <input type="number" step="0.1" name="overtime_multiplier" value="1.5" class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white">
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-300 mb-1">Status</label>
                        <select name="status" required class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white">
                            <option value="ACTIVE">Active</option>
                            <option value="INACTIVE">Inactive</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block font-semibold text-slate-300 mb-1">Payroll Description</label>
                    <textarea name="description" rows="2" placeholder="Corporate description of this payroll tier..." class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white focus:outline-none focus:border-indigo-500"></textarea>
                </div>

                <div class="pt-3 flex justify-end space-x-3">
                    <button type="button" @click="createModalOpen = false" class="px-4 py-2 bg-slate-800 text-slate-300 font-semibold rounded-xl">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white font-bold rounded-xl shadow-lg">Save Payroll</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Company Payroll Modal -->
    <div x-show="editModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm">
        <div @click.away="editModalOpen = false" class="bg-slate-900 border border-slate-800 rounded-3xl max-w-xl w-full p-6 space-y-5 shadow-2xl max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between border-b border-slate-800 pb-4">
                <h3 class="text-base font-bold text-white">Edit Company Payroll Structure</h3>
                <button @click="editModalOpen = false" class="text-slate-400 hover:text-white"><i class="fa-solid fa-xmark"></i></button>
            </div>

            <form :action="'{{ url('payroll') }}/' + activePayroll.id" method="POST" class="space-y-4 text-xs">
                @csrf
                @method('PUT')
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-semibold text-slate-300 mb-1">Target Company *</label>
                        <select name="organization_id" x-model="activePayroll.organization_id" required class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white">
                            @foreach($organizations as $org)
                                <option value="{{ $org->id }}">{{ $org->name }} ({{ $org->currency }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-300 mb-1">Payroll Name *</label>
                        <input type="text" name="name" x-model="activePayroll.name" required class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white">
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-3">
                    <div>
                        <label class="block font-semibold text-slate-300 mb-1">Pay Frequency *</label>
                        <select name="pay_frequency" x-model="activePayroll.pay_frequency" required class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white">
                            <option value="MONTHLY">Monthly</option>
                            <option value="BI_WEEKLY">Bi-Weekly</option>
                            <option value="WEEKLY">Weekly</option>
                        </select>
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-300 mb-1">Currency *</label>
                        <select name="currency" x-model="activePayroll.currency" required class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white">
                            <option value="PKR">PKR (Rs)</option>
                            <option value="USD">USD ($)</option>
                            <option value="EUR">EUR (€)</option>
                            <option value="GBP">GBP (£)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-300 mb-1">Working Days</label>
                        <input type="number" name="default_working_days" x-model="activePayroll.default_working_days" required class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white">
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-3">
                    <div>
                        <label class="block font-semibold text-slate-300 mb-1">Tax Rate % *</label>
                        <input type="number" step="0.1" name="tax_rate" x-model="activePayroll.tax_rate" required class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white">
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-300 mb-1">Overtime Multiplier</label>
                        <input type="number" step="0.1" name="overtime_multiplier" x-model="activePayroll.overtime_multiplier" class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white">
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-300 mb-1">Status</label>
                        <select name="status" x-model="activePayroll.status" required class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white">
                            <option value="ACTIVE">Active</option>
                            <option value="INACTIVE">Inactive</option>
                        </select>
                    </div>
                </div>

                <div class="pt-3 flex justify-end space-x-3">
                    <button type="button" @click="editModalOpen = false" class="px-4 py-2 bg-slate-800 text-slate-300 font-semibold rounded-xl">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white font-bold rounded-xl shadow-lg">Update Payroll</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Execute Payroll Run Modal -->
    <div x-show="runModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm">
        <div @click.away="runModalOpen = false" class="bg-slate-900 border border-slate-800 rounded-3xl max-w-md w-full p-6 space-y-5 shadow-2xl">
            <div class="flex items-center justify-between border-b border-slate-800 pb-4">
                <div class="flex items-center space-x-2">
                    <span class="w-8 h-8 rounded-lg bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 flex items-center justify-center font-bold text-xs">
                        <i class="fa-solid fa-play"></i>
                    </span>
                    <div>
                        <h3 class="text-base font-bold text-white">Execute Monthly Payroll Run</h3>
                        <p class="text-[11px] text-slate-400" x-text="activePayroll.name"></p>
                    </div>
                </div>
                <button @click="runModalOpen = false" class="text-slate-400 hover:text-white"><i class="fa-solid fa-xmark"></i></button>
            </div>

            <form :action="'{{ url('payroll') }}/' + targetPayrollId + '/run'" method="POST" class="space-y-4 text-xs">
                @csrf
                <div class="p-3.5 bg-slate-950 border border-slate-800 rounded-2xl space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-slate-400">Total Enrolled Workforce:</span>
                        <strong class="text-white font-bold" x-text="(activePayroll.employees_count || 0) + ' Employees'"></strong>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-slate-400">Estimated Total Payout Outflow:</span>
                        <strong class="text-emerald-400 font-black text-sm" x-text="(activePayroll.currency || 'PKR') + ' ' + Number(activePayroll.total_monthly_outflow || 0).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})"></strong>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-semibold text-slate-300 mb-1">Period Start *</label>
                        <input type="date" name="period_start" value="{{ date('Y-m-01') }}" required class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white">
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-300 mb-1">Period End (Pay Date) *</label>
                        <input type="date" name="period_end" value="{{ date('Y-m-t') }}" required class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white">
                    </div>
                </div>

                <div class="pt-3 flex justify-end space-x-3">
                    <button type="button" @click="runModalOpen = false" class="px-4 py-2 bg-slate-800 text-slate-300 font-semibold rounded-xl">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white font-bold rounded-xl shadow-lg shadow-emerald-600/30">Confirm &amp; Execute</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Assign Employees Modal -->
    <div x-show="assignModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm">
        <div @click.away="assignModalOpen = false" class="bg-slate-900 border border-slate-800 rounded-3xl max-w-lg w-full p-6 space-y-5 shadow-2xl max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between border-b border-slate-800 pb-4">
                <div class="flex items-center space-x-2">
                    <span class="w-8 h-8 rounded-lg bg-indigo-500/10 border border-indigo-500/20 text-indigo-400 flex items-center justify-center font-bold text-xs">
                        <i class="fa-solid fa-user-gear"></i>
                    </span>
                    <div>
                        <h3 class="text-base font-bold text-white">Assign Employees to Payroll Tier</h3>
                        <p class="text-[11px] text-slate-400">Bulk enroll staff into corporate payroll engine.</p>
                    </div>
                </div>
                <button @click="assignModalOpen = false" class="text-slate-400 hover:text-white"><i class="fa-solid fa-xmark"></i></button>
            </div>

            <form action="{{ route('payroll.assign-employees') }}" method="POST" class="space-y-4 text-xs">
                @csrf
                <div>
                    <label class="block font-semibold text-slate-300 mb-1">Target Payroll Profile *</label>
                    <select name="payroll_id" required class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white">
                        <option value="">-- Choose Company Payroll --</option>
                        @foreach($payrolls as $pr)
                            <option value="{{ $pr->id }}">[{{ $pr->organization->name ?? 'Company' }}] {{ $pr->name }} ({{ $pr->pay_frequency }})</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block font-semibold text-slate-300 mb-2">Select Employees to Assign *</label>
                    <div class="max-h-60 overflow-y-auto space-y-2 p-3 bg-slate-950 border border-slate-800 rounded-2xl">
                        @foreach($employees as $emp)
                            <label class="flex items-center justify-between p-2 rounded-xl hover:bg-slate-900 cursor-pointer">
                                <div class="flex items-center space-x-3">
                                    <input type="checkbox" name="user_ids[]" value="{{ $emp->id }}" class="rounded bg-slate-900 border-slate-700 text-indigo-600 focus:ring-indigo-500">
                                    <div>
                                        <p class="font-bold text-white">{{ $emp->name }}</p>
                                        <p class="text-[11px] text-slate-400">{{ $emp->organization->name ?? 'No Org' }} • {{ $emp->email }}</p>
                                    </div>
                                </div>
                                <span class="text-[10px] px-2 py-0.5 rounded bg-slate-900 border border-slate-800 text-indigo-400">
                                    {{ $emp->payroll->name ?? 'Unassigned' }}
                                </span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="pt-3 flex justify-end space-x-3">
                    <button type="button" @click="assignModalOpen = false" class="px-4 py-2 bg-slate-800 text-slate-300 font-semibold rounded-xl">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white font-bold rounded-xl shadow-lg">Assign Selected Employees</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ============================================================= -->
    <!-- MODAL: REJECT PAYSLIP REQUEST (HR / SUPER ADMIN)              -->
    <!-- ============================================================= -->
    <div x-show="rejectModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm">
        <div @click.away="rejectModalOpen = false" class="bg-slate-900 border border-slate-800 rounded-3xl max-w-md w-full p-6 space-y-4 shadow-2xl">
            <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                <div class="flex items-center space-x-2.5">
                    <span class="w-9 h-9 rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-400 flex items-center justify-center font-bold text-sm">
                        <i class="fa-solid fa-ban"></i>
                    </span>
                    <div>
                        <h3 class="text-base font-bold text-white">Decline Payslip Request</h3>
                        <p class="text-[11px] text-slate-400" x-text="'Employee: ' + rejectEmployeeName"></p>
                    </div>
                </div>
                <button @click="rejectModalOpen = false" class="text-slate-400 hover:text-white p-1.5 hover:bg-slate-800 rounded-xl transition-colors">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <form :action="'{{ url('payroll/payslip-requests') }}/' + rejectRequestId + '/reject'" method="POST" class="space-y-4 text-xs">
                @csrf
                <div>
                    <label class="block font-bold text-slate-300 mb-1.5">Reason for Declining (Optional / Sent to Employee)</label>
                    <textarea name="rejection_reason" rows="3" placeholder="e.g. Salary voucher for this month is still undergoing accounts audit. Please contact HR." class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white text-xs focus:outline-none focus:border-rose-500"></textarea>
                </div>
                <div class="pt-2 flex justify-end space-x-2">
                    <button type="button" @click="rejectModalOpen = false" class="px-4 py-2 bg-slate-800 text-slate-300 font-semibold rounded-xl">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-rose-600 hover:bg-rose-500 text-white font-bold rounded-xl shadow-lg shadow-rose-600/30">Confirm Rejection</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ============================================================= -->
    <!-- MODAL: REQUEST PAYSLIP APPLICATION (EMPLOYEE / QUICK ACTION)   -->
    <!-- ============================================================= -->
    <div x-show="requestPayslipModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm"
         x-data="{
             modalSelectedMonths: [],
             modalEligibleMonths: {{ json_encode(($myEligibleSalaryMonths ?? collect())->where('can_request', true)->pluck('payment_month')->values()) }},
             toggleModalAll() {
                 if (this.modalSelectedMonths.length === this.modalEligibleMonths.length) {
                     this.modalSelectedMonths = [];
                 } else {
                     this.modalSelectedMonths = [...this.modalEligibleMonths];
                 }
             }
         }">
        <div @click.away="requestPayslipModalOpen = false" class="bg-slate-900 border border-slate-800 rounded-3xl max-w-lg w-full p-6 space-y-4 shadow-2xl">
            <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                <div class="flex items-center space-x-2.5">
                    <span class="w-9 h-9 rounded-xl bg-indigo-500/10 border border-indigo-500/20 text-indigo-400 flex items-center justify-center font-bold text-sm">
                        <i class="fa-solid fa-file-invoice"></i>
                    </span>
                    <div>
                        <h3 class="text-base font-bold text-white">Apply for Payslip Voucher</h3>
                        <p class="text-[11px] text-slate-400">Request will be routed to HR and System Admin for clearance.</p>
                    </div>
                </div>
                <button @click="requestPayslipModalOpen = false" class="text-slate-400 hover:text-white p-1.5 hover:bg-slate-800 rounded-xl transition-colors">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            @if(!isset($myEligibleSalaryMonths) || $myEligibleSalaryMonths->isEmpty())
                <div class="p-4 rounded-2xl bg-amber-500/10 border border-amber-500/30 text-amber-400 text-xs flex items-start space-x-3">
                    <div class="w-8 h-8 rounded-xl bg-amber-500/20 border border-amber-500/30 text-amber-400 flex items-center justify-center text-sm shrink-0 mt-0.5">
                        <i class="fa-solid fa-clock-rotate-left"></i>
                    </div>
                    <div class="space-y-1">
                        <strong class="font-bold text-amber-300">No Disbursed Salary Records Found:</strong>
                        <p class="text-slate-300 leading-relaxed">
                            Salary vouchers can strictly be requested for months where your salary has already been calculated, approved, and disbursed. Once your monthly salary is paid, eligible months will automatically appear here.
                        </p>
                    </div>
                </div>
                <div class="pt-2 flex justify-end">
                    <button type="button" @click="requestPayslipModalOpen = false" class="px-4 py-2 bg-slate-800 text-slate-300 font-semibold rounded-xl text-xs">Close</button>
                </div>
            @else
                <form action="{{ route('payroll.payslip-requests.store') }}" method="POST" class="space-y-4 text-xs">
                    @csrf
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label class="block font-bold text-slate-300">Select Salary Month(s) *</label>
                            @if($myEligibleSalaryMonths->where('can_request', true)->isNotEmpty())
                                <button type="button" @click="toggleModalAll()" class="text-[11px] font-bold text-indigo-400 hover:text-indigo-300">
                                    <span x-text="modalSelectedMonths.length === modalEligibleMonths.length ? 'Deselect All' : 'Select All'"></span>
                                </button>
                            @endif
                        </div>
                        
                        <div class="max-h-48 overflow-y-auto space-y-2 pr-1">
                            @foreach($myEligibleSalaryMonths as $item)
                                @php
                                    $isPending = ($item['request_status'] === 'PENDING');
                                    $isApproved = ($item['request_status'] === 'APPROVED');
                                    $isCanRequest = $item['can_request'];
                                @endphp
                                <label class="flex items-center p-2.5 rounded-xl border transition-all cursor-pointer select-none"
                                       :class="[
                                           modalSelectedMonths.includes('{{ $item['payment_month'] }}')
                                               ? 'bg-indigo-950/50 border-indigo-500 ring-1 ring-indigo-500/50 shadow-sm'
                                               : 'bg-slate-950 border-slate-800 hover:border-slate-700'
                                       ]"
                                       style="{{ !$isCanRequest ? 'opacity: 0.6; cursor: default;' : '' }}">
                                    
                                    @if($isCanRequest)
                                        <input type="checkbox" name="payment_months[]" value="{{ $item['payment_month'] }}"
                                               x-model="modalSelectedMonths"
                                               class="w-4 h-4 rounded text-indigo-600 bg-slate-950 border-slate-700 focus:ring-indigo-500 mr-2.5 shrink-0">
                                    @else
                                        <div class="w-4 h-4 mr-2.5 shrink-0 flex items-center justify-center">
                                            @if($isApproved)
                                                <i class="fa-solid fa-circle-check text-emerald-400 text-xs"></i>
                                            @else
                                                <i class="fa-solid fa-clock text-amber-400 text-xs"></i>
                                            @endif
                                        </div>
                                    @endif

                                    <div class="flex-1 min-w-0 flex items-center justify-between">
                                        <div>
                                            <span class="font-bold text-white text-xs block">{{ $item['month_label'] }}</span>
                                            <span class="text-[10px] text-slate-400">{{ $item['pay_date'] }}</span>
                                        </div>
                                        <div class="text-right">
                                            <span class="font-mono text-emerald-400 font-semibold block text-xs">{{ $item['currency'] }} {{ number_format($item['net_pay'], 2) }}</span>
                                            @if($isApproved)
                                                <span class="text-[9px] font-bold text-emerald-400">Cleared</span>
                                            @elseif($isPending)
                                                <span class="text-[9px] font-bold text-amber-400">In Review</span>
                                            @else
                                                <span class="text-[9px] font-bold text-indigo-400">Eligible</span>
                                            @endif
                                        </div>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div>
                        <label class="block font-bold text-slate-300 mb-1.5">Purpose / Remarks (Optional)</label>
                        <textarea name="reason" rows="2" placeholder="e.g. For visa application, banking verification, or personal financial records..." class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white text-xs focus:outline-none focus:border-indigo-500 shadow-inner"></textarea>
                    </div>

                    <div class="pt-2 flex justify-end space-x-2">
                        <button type="button" @click="requestPayslipModalOpen = false" class="px-4 py-2 bg-slate-800 text-slate-300 font-semibold rounded-xl text-xs">Cancel</button>
                        <button type="submit"
                                :disabled="modalSelectedMonths.length === 0"
                                :class="modalSelectedMonths.length === 0 ? 'opacity-50 cursor-not-allowed bg-indigo-600/50' : 'bg-indigo-600 hover:bg-indigo-500 shadow-lg shadow-indigo-600/30'"
                                class="px-4 py-2 text-white font-bold rounded-xl transition-all flex items-center space-x-1.5 text-xs">
                            <i class="fa-solid fa-paper-plane text-[10px]"></i>
                            <span x-text="modalSelectedMonths.length > 0 ? 'Submit Request (' + modalSelectedMonths.length + ')' : 'Select Month(s)'"></span>
                        </button>
                    </div>
                </form>
            @endif
        </div>
    </div>


    <!-- ============================================================= -->
    <!-- DEDICATED PRINT STYLESHEET (VOUCHERS & REPORTS)               -->
    <!-- ============================================================= -->
    <style>
    /* Anti-Screenshot Glass Blur Privacy Shield */
    .privacy-blur {
        filter: blur(8px) !important;
        opacity: 0.18 !important;
        user-select: none !important;
        -webkit-user-select: none !important;
        -moz-user-select: none !important;
        -ms-user-select: none !important;
        pointer-events: none !important;
        transition: filter 0.25s cubic-bezier(0.4, 0, 0.2, 1), opacity 0.25s cubic-bezier(0.4, 0, 0.2, 1) !important;
    }
    .privacy-clear {
        filter: none !important;
        opacity: 1 !important;
        user-select: auto !important;
        -webkit-user-select: auto !important;
        -moz-user-select: auto !important;
        -ms-user-select: auto !important;
        pointer-events: auto !important;
        transition: filter 0.25s cubic-bezier(0.4, 0, 0.2, 1), opacity 0.25s cubic-bezier(0.4, 0, 0.2, 1) !important;
    }
    .privacy-row-revealed {
        background-color: rgba(30, 27, 75, 0.5) !important;
        box-shadow: inset 0 0 0 1px rgba(99, 102, 241, 0.45), 0 4px 20px -2px rgba(0, 0, 0, 0.4) !important;
    }

    @media print {
        html, body {
            background: #ffffff !important;
            color: #0f172a !important;
            overflow: visible !important;
            height: auto !important;
            margin: 0 !important;
            padding: 0 !important;
        }

        .no-print, header, nav, aside, footer, #sidebar, [class*="navbar"], [class*="sidebar"] {
            display: none !important;
        }

        /* Unbind modal styles during direct browser print so content is visible */
        div[x-show="viewPayslipModalOpen"] {
            position: static !important;
            display: block !important;
            background: transparent !important;
            backdrop-filter: none !important;
            -webkit-backdrop-filter: none !important;
            padding: 0 !important;
            margin: 0 !important;
            overflow: visible !important;
            max-height: none !important;
            height: auto !important;
            width: 100% !important;
            box-shadow: none !important;
            border: none !important;
        }

        div[x-show="viewPayslipModalOpen"] > div {
            background: transparent !important;
            border: none !important;
            box-shadow: none !important;
            padding: 0 !important;
            margin: 0 !important;
            max-height: none !important;
            height: auto !important;
            overflow: visible !important;
            width: 100% !important;
            max-width: 100% !important;
        }

        #printable-payslip-voucher {
            display: block !important;
            visibility: visible !important;
            position: static !important;
            width: 100% !important;
            height: auto !important;
            margin: 0 !important;
            padding: 10px !important;
            background: #ffffff !important;
            color: #0f172a !important;
            border: none !important;
            box-shadow: none !important;
        }

        #printable-payslip-voucher * {
            visibility: visible !important;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        #printable-payroll-report {
            display: block !important;
            visibility: visible !important;
            position: static !important;
            width: 100% !important;
            height: auto !important;
            margin: 0 !important;
            padding: 10px !important;
            background: #ffffff !important;
            color: #0f172a !important;
            border: none !important;
            box-shadow: none !important;
        }

        #printable-payroll-report * {
            visibility: visible !important;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        #printable-payroll-report .bg-slate-900\/90,
        #printable-payroll-report .bg-slate-900\/80,
        #printable-payroll-report .bg-slate-950\/60,
        #printable-payroll-report .bg-slate-950 {
            background: #ffffff !important;
            color: #0f172a !important;
            border: 1px solid #cbd5e1 !important;
        }

        #printable-payroll-report h2,
        #printable-payroll-report h3,
        #printable-payroll-report h4,
        #printable-payroll-report strong,
        #printable-payroll-report td {
            color: #0f172a !important;
        }
    }
    </style>

</div>

<script>
function printOfficialSalaryVoucher() {
    var el = document.getElementById('printable-payslip-voucher');
    if (!el) {
        window.print();
        return;
    }

    try {
        var printFrame = document.createElement('iframe');
        printFrame.style.position = 'fixed';
        printFrame.style.right = '0';
        printFrame.style.bottom = '0';
        printFrame.style.width = '0';
        printFrame.style.height = '0';
        printFrame.style.border = '0';
        document.body.appendChild(printFrame);

        var frameDoc = printFrame.contentWindow.document;
        frameDoc.open();
        frameDoc.write('<!DOCTYPE html><html><head><title>Official Salary Voucher</title><script src="https://cdn.tailwindcss.com"><\/script><style>@page { size: A4 portrait; margin: 10mm; } body { background: #ffffff !important; color: #0f172a !important; font-family: ui-sans-serif, system-ui, -apple-system, sans-serif; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; padding: 12px; margin: 0; } .shadow-2xl, .shadow-xl, .shadow-sm, .shadow-inner { box-shadow: none !important; } .rounded-3xl { border-radius: 1.5rem !important; } .rounded-2xl { border-radius: 1rem !important; }</style></head><body>' + el.innerHTML + '</body></html>');
        frameDoc.close();

        setTimeout(function() {
            printFrame.contentWindow.focus();
            printFrame.contentWindow.print();
            setTimeout(function() {
                if (document.body.contains(printFrame)) {
                    document.body.removeChild(printFrame);
                }
            }, 3000);
        }, 600);
    } catch (e) {
        console.error('Print error, falling back to window.print():', e);
        window.print();
    }
}
</script>

@endsection
@endif
