<!-- Title & Period Bar -->
<div class="slip-title-row">
    <h1 class="slip-heading">EMPLOYEE PAYSLIP</h1>
    <div class="slip-period-badge">Pay Period : {{ $slipPayload['salary_period'] }}</div>
</div>

<!-- Employee Particulars Panel -->
<div class="slip-info-panel">
    <div class="slip-info-grid">
        <div class="slip-info-item">
            <span class="slip-info-lbl">Employee ID</span>
            <span class="slip-info-col">:</span>
            <span class="slip-info-txt mono bold">{{ $slipPayload['employee_code'] }}</span>
        </div>
        <div class="slip-info-item">
            <span class="slip-info-lbl">Date of Joining</span>
            <span class="slip-info-col">:</span>
            <span class="slip-info-txt">{{ $slipPayload['joining_date'] }}</span>
        </div>
        <div class="slip-info-item">
            <span class="slip-info-lbl">Employee Name</span>
            <span class="slip-info-col">:</span>
            <span class="slip-info-txt bold">{{ $slipPayload['employee_name'] }}</span>
        </div>
        <div class="slip-info-item">
            <span class="slip-info-lbl">CNIC / ID</span>
            <span class="slip-info-col">:</span>
            <span class="slip-info-txt mono">{{ $slipPayload['cnic_no'] }}</span>
        </div>
        <div class="slip-info-item">
            <span class="slip-info-lbl">Designation</span>
            <span class="slip-info-col">:</span>
            <span class="slip-info-txt">{{ $slipPayload['designation'] }}</span>
        </div>
        <div class="slip-info-item">
            <span class="slip-info-lbl">Bank Name</span>
            <span class="slip-info-col">:</span>
            <span class="slip-info-txt">{{ $slipPayload['bank_name'] }}</span>
        </div>
        <div class="slip-info-item">
            <span class="slip-info-lbl">Department</span>
            <span class="slip-info-col">:</span>
            <span class="slip-info-txt">{{ $slipPayload['department'] }}</span>
        </div>
        <div class="slip-info-item">
            <span class="slip-info-lbl">Account / IBAN</span>
            <span class="slip-info-col">:</span>
            <span class="slip-info-txt mono">{{ $slipPayload['bank_account'] }}</span>
        </div>
    </div>
</div>

<!-- Ledger Section -->
<div class="slip-ledger-wrap">

    <!-- Earnings Ledger -->
    <div class="slip-sec-bar">
        <span class="slip-sec-name">EARNINGS</span>
        <div class="slip-sec-divider"></div>
    </div>
    <table class="slip-table">
        <tbody>
            <tr>
                <td class="col-head">Basic Salary</td>
                <td class="col-num mono">{{ $slipPayload['currency'] }} {{ number_format($slipPayload['base_salary'], 2) }}</td>
            </tr>
            @if(($slipPayload['fuel_allowance'] ?? 0) > 0)
            <tr>
                <td class="col-head">Conveyance &amp; Fuel Allowance</td>
                <td class="col-num mono">{{ $slipPayload['currency'] }} {{ number_format($slipPayload['fuel_allowance'], 2) }}</td>
            </tr>
            @endif
            @if(($slipPayload['special_allowance'] ?? 0) > 0)
            <tr>
                <td class="col-head">Special / Executive Allowance</td>
                <td class="col-num mono">{{ $slipPayload['currency'] }} {{ number_format($slipPayload['special_allowance'], 2) }}</td>
            </tr>
            @endif
            @if(($slipPayload['other_allowances'] ?? 0) > 0)
            <tr>
                <td class="col-head">Other Allowances &amp; Incentives</td>
                <td class="col-num mono">{{ $slipPayload['currency'] }} {{ number_format($slipPayload['other_allowances'], 2) }}</td>
            </tr>
            @endif
            @if(($slipPayload['fuel_allowance'] ?? 0) == 0 && ($slipPayload['special_allowance'] ?? 0) == 0 && ($slipPayload['other_allowances'] ?? 0) == 0)
            <tr>
                <td class="col-head">Additional Allowances</td>
                <td class="col-num mono">{{ $slipPayload['currency'] }} 0.00</td>
            </tr>
            @endif
            <tr class="total-bar">
                <td class="col-head">Total Earnings</td>
                <td class="col-num mono">{{ $slipPayload['currency'] }} {{ number_format($slipPayload['gross_salary'], 2) }}</td>
            </tr>
        </tbody>
    </table>

    <!-- Deductions Ledger -->
    <div class="slip-sec-bar">
        <span class="slip-sec-name">DEDUCTIONS</span>
        <div class="slip-sec-divider"></div>
    </div>
    <table class="slip-table">
        <tbody>
            <tr>
                <td class="col-head">Income Tax Withholding</td>
                <td class="col-num mono">{{ $slipPayload['currency'] }} {{ number_format($slipPayload['tax'], 2) }}</td>
            </tr>
            @if(($slipPayload['eobi_deduction'] ?? 0) > 0)
            <tr>
                <td class="col-head">EOBI / Social Security Contribution</td>
                <td class="col-num mono">{{ $slipPayload['currency'] }} {{ number_format($slipPayload['eobi_deduction'], 2) }}</td>
            </tr>
            @endif
            @if(($slipPayload['advance_deduction'] ?? 0) > 0)
            <tr>
                <td class="col-head">Advance Salary Recovery</td>
                <td class="col-num mono">{{ $slipPayload['currency'] }} {{ number_format($slipPayload['advance_deduction'], 2) }}</td>
            </tr>
            @endif
            @if(($slipPayload['late_deduction'] ?? 0) > 0)
            <tr>
                <td class="col-head">Late Attendance Deduction ({{ $slipPayload['late_days_deducted'] ?? 0 }} Days)</td>
                <td class="col-num mono">{{ $slipPayload['currency'] }} {{ number_format($slipPayload['late_deduction'], 2) }}</td>
            </tr>
            @endif
            @if(($slipPayload['absent_deduction'] ?? 0) > 0)
            <tr>
                <td class="col-head">Unpaid Absence / Loss of Pay</td>
                <td class="col-num mono">{{ $slipPayload['currency'] }} {{ number_format($slipPayload['absent_deduction'], 2) }}</td>
            </tr>
            @endif
            @if(($slipPayload['other_deductions'] ?? 0) > 0)
            <tr>
                <td class="col-head">Other Deductions / Adjustments</td>
                <td class="col-num mono">{{ $slipPayload['currency'] }} {{ number_format($slipPayload['other_deductions'], 2) }}</td>
            </tr>
            @endif
            @if(($slipPayload['eobi_deduction'] ?? 0) == 0 && ($slipPayload['advance_deduction'] ?? 0) == 0 && ($slipPayload['late_deduction'] ?? 0) == 0 && ($slipPayload['absent_deduction'] ?? 0) == 0 && ($slipPayload['other_deductions'] ?? 0) == 0)
            <tr>
                <td class="col-head">Other Deductions</td>
                <td class="col-num mono">{{ $slipPayload['currency'] }} 0.00</td>
            </tr>
            @endif
            <tr class="total-bar">
                <td class="col-head">Total Deductions</td>
                <td class="col-num mono">{{ $slipPayload['currency'] }} {{ number_format($slipPayload['gross_deduction'], 2) }}</td>
            </tr>
        </tbody>
    </table>

</div>

<!-- Net Pay Box -->
<div class="slip-net-panel">
    <div class="slip-net-left">
        <div class="slip-net-title">NET REMUNERATION PAYABLE</div>
        @if(!empty($slipPayload['net_pay_in_words']))
        <div class="slip-net-words">Amount: {{ $slipPayload['net_pay_in_words'] }}</div>
        @endif
        <div class="slip-net-meta">Payment Mode: {{ $slipPayload['payment_method'] }} • Ref: <span class="mono">{{ $slipPayload['ref_no'] }}</span></div>
    </div>
    <div class="slip-net-right">
        <span class="slip-net-tag">NET SALARY ({{ $slipPayload['currency'] }})</span>
        <span class="slip-net-sum mono">{{ number_format($slipPayload['net_pay'], 2) }}</span>
    </div>
</div>

<!-- Signatures -->
<div class="slip-sign-grid">
    <div class="slip-sign-col">
        <div class="slip-sign-rule"></div>
        <span class="slip-sign-name">Prepared By</span>
        <span class="slip-sign-role">Accounts &amp; Payroll Officer</span>
    </div>
    <div class="slip-sign-col">
        <div class="slip-sign-rule"></div>
        <span class="slip-sign-name">Authorized Signatory</span>
        <span class="slip-sign-role">Finance / HR Directorate</span>
    </div>
    <div class="slip-sign-col">
        <div class="slip-sign-rule"></div>
        <span class="slip-sign-name">Employee Signature</span>
        <span class="slip-sign-role">Acknowledgement of Receipt</span>
    </div>
</div>
