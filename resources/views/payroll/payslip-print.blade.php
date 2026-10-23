@php
    if (!isset($slipPayload) || empty($slipPayload)) {
        if (isset($payslip)) {
            $emp = $payslip->user ?? null;
            $org = $payslip->organization ?? optional($emp)->organization ?? optional($payslip->payrollRun)->payroll?->organization;
            $currency = $org->currency ?? 'PKR';
            $fuel = (float)($payslip->fuel_allowance ?? 0);
            $special = (float)($payslip->special_allowance ?? 0);
            $otherAllow = max(0, (float)($payslip->allowances ?? 0) - $fuel - $special);
            $grossSal = (float)($payslip->base_salary ?? 0) + (float)($payslip->allowances ?? 0);
            $advanceDed = (float)($payslip->advance_salary_deduction ?? 0);
            $lateDed = (float)($payslip->late_deduction ?? 0);
            $lateDays = (int)($payslip->late_days_deducted ?? 0);
            $absentDed = (float)($payslip->absent_deduction ?? 0);
            $eobiDed = (float)($payslip->eobi_deduction ?? 0);
            $otherDed = max(0, (float)($payslip->deductions ?? 0) - $advanceDed - $lateDed - $absentDed - $eobiDed);
            $grossDed = (float)($payslip->deductions ?? 0) + (float)($payslip->tax ?? 0);
            $salaryPeriod = !empty($payslip->salary_month) ? $payslip->salary_month : (!empty($payslip->payment_month) ? date('F Y', strtotime($payslip->payment_month . '-01')) : date('F Y'));
            $payDate = !empty($payslip->pay_date) ? date('d M Y', strtotime($payslip->pay_date)) : date('d M Y');
            $cleanId = strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', (string)$payslip->id), 0, 6));
            $refNo = 'AXE-PAY-' . date('Ym') . '-' . $cleanId;
            $empCode = optional($emp)->employee_id ?? ('EMP-' . strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', (string)(optional($emp)->id ?? $payslip->user_id)), 0, 4)));

            $compNameRaw = $org->name ?? optional($payslip->payrollRun)->payroll?->name ?? 'AxeCorp';
            $compNameLower = strtolower($compNameRaw);

            if (str_contains($compNameLower, 'spectra')) {
                $defName = 'Design Spectra Technologies';
                $defPhone = '03363178353';
                $defEmail = 'Info@designspectratech.com';
                $defWebsite = 'www.designspectratech.com';
                $defAddress = 'C3, C4, 2nd Floor Amaan Chamber, Shaheed-e-millat road, karachi';
            } elseif (str_contains($compNameLower, 'falcon')) {
                $defName = 'FalconCore Technologies';
                $defPhone = '0331 2424035';
                $defEmail = 'Info@falconcoretechnologies.com';
                $defWebsite = 'www.falconcoretechnologies.com';
                $defAddress = 'C3 2nd floor Amaan chambar, shaheed-e-Millat Road karachi';
            } elseif (str_contains($compNameLower, 'wave')) {
                $defName = 'DesignWave Technologies';
                $defPhone = '+92 332 2600982';
                $defEmail = 'Info@designwavetechnologies.com';
                $defWebsite = 'www.designwavetechnologies.com';
                $defAddress = 'C3/C4 2nd floor Amaan chambar, shaheed-e-Millat Road karachi';
            } else {
                $defName = optional($org)->name ?? 'AxeCorp Tech';
                $defPhone = '021-34534002';
                $defEmail = 'accounts@axecorptechnologies.com';
                $defWebsite = 'AxecorpTechnologies.com';
                $defAddress = 'C3, 2nd floor Aman Chamber, Shaheed-e-Millat Road, Karachi';
            }

            $slipPayload = [
                'id' => $payslip->id,
                'ref_no' => $refNo,
                'salary_period' => $salaryPeriod,
                'pay_date' => $payDate,
                'employee_name' => optional($emp)->name ?? 'Employee',
                'employee_code' => $empCode,
                'designation' => optional(optional($emp)->designation)->title ?? (optional($emp)->designation_name ?? 'Staff Member'),
                'department' => optional(optional($emp)->department)->name ?? 'General Staff',
                'cnic_no' => optional($emp)->national_id_number ?? (optional($emp)->cnic ?? '42101-*******-1'),
                'joining_date' => optional($emp)->date_of_joining ? date('d-M-Y', strtotime($emp->date_of_joining)) : '01-Jan-2024',
                'bank_name' => optional($emp)->bank_name ?? 'Corporate Payroll Account',
                'bank_account' => optional($emp)->bank_account_number ?? 'Direct Bank Transfer',
                'payment_method' => $payslip->payment_method ?? 'Direct Bank Transfer',
                'working_days' => 22,
                'paid_days' => max(0, 22 - $lateDays),
                'lop_days' => $lateDays,
                'currency' => $currency,
                'base_salary' => (float)($payslip->base_salary ?? 0),
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
                'tax' => (float)($payslip->tax ?? 0),
                'gross_deduction' => $grossDed,
                'net_pay' => (float)($payslip->net_pay ?? 0),
                'net_pay_in_words' => '',
                'notes' => $payslip->notes ?? '',
                'company_legal_name' => optional($org)->name ?? $defName,
                'company_logo' => optional($org)->logo_url ?? asset('images/logo.png'),
                'company_address' => optional($org)->address ?? $defAddress,
                'company_phone' => optional($org)->phone ?? $defPhone,
                'company_email' => optional($org)->email ?? $defEmail,
                'company_website' => optional($org)->website ?? $defWebsite,
                'company_tax_no' => 'NTN: ' . (optional($org)->tax_number ?? '4829104-7'),
            ];
        } else {
            $slipPayload = [];
        }
    }

    $primaryColor = '#00a8ff';
    $primaryHover = '#0284c7';
    $docTypeTitle = 'Salary Slip';

    $axecorpTemplatePath = public_path('images/axecorp-salaryslip-template.png');
    $axecorpTemplateSrc = file_exists($axecorpTemplatePath)
        ? 'data:image/png;base64,' . base64_encode(file_get_contents($axecorpTemplatePath))
        : asset('images/axecorp-salaryslip-template.png');
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Salary Slip - {{ $slipPayload['employee_name'] ?? 'Employee' }} - {{ $slipPayload['salary_period'] ?? '' }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@700;800;900&family=Merriweather:wght@700;900&family=Inter:wght@400;500;600;700;800;900&family=JetBrains+Mono:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: #0f172a;
            color: #0f172a;
            padding: 24px 16px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-start;
            min-height: 100vh;
            -webkit-font-smoothing: antialiased;
        }
        .mono {
            font-family: 'JetBrains Mono', 'Courier New', monospace;
        }
        
        /* Top Screen Control Toolbar */
        .screen-toolbar {
            width: 100%;
            max-width: 820px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 16px;
            padding: 12px 20px;
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 12px;
            box-shadow: 0 10px 25px -5px rgba(0,0,0,0.4);
        }
        .screen-toolbar h1 {
            color: #f8fafc;
            font-size: 13px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 8px;
            letter-spacing: 0.3px;
        }
        .screen-toolbar h1 i {
            color: {{ $primaryColor }};
        }
        .toolbar-actions {
            display: flex;
            gap: 10px;
        }
        .t-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 7px 16px;
            font-size: 11.5px;
            font-weight: 700;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.15s ease-in-out;
            border: none;
            text-decoration: none;
        }
        .t-btn-primary {
            background: {{ $primaryColor }};
            color: #ffffff;
            box-shadow: 0 2px 10px rgba(0, 168, 255, 0.35);
        }
        .t-btn-primary:hover {
            background: {{ $primaryHover }};
            box-shadow: 0 4px 14px rgba(0, 168, 255, 0.5);
        }
        .t-btn-secondary {
            background: #334155;
            color: #cbd5e1;
        }
        .t-btn-secondary:hover {
            background: #475569;
            color: #ffffff;
        }

        /* Standard Sheet Canvas */
        .sheet-doc {
            width: 100%;
            max-width: 820px;
            min-height: 1160px;
            height: 1160px;
            background: #ffffff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 20px 50px -10px rgba(0, 0, 0, 0.3);
            position: relative;
            color: #1e293b;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        /* Common Structure Inside Payslips */
        .doc-inner-body {
            position: relative;
            z-index: 2;
            padding: 0 48px 12px 48px;
            flex: 1 1 auto;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .slip-title-row {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            border-bottom: 2px solid #00a8ff;
            padding-bottom: 6px;
            margin-bottom: 12px;
        }
        .slip-heading {
            font-size: 21px;
            font-weight: 900;
            color: #0b1a30;
            letter-spacing: 0.8px;
            text-transform: uppercase;
            line-height: 1;
        }
        .slip-period-badge {
            font-size: 11px;
            font-weight: 800;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            background: #f0f9ff;
            padding: 3.5px 12px;
            border-radius: 6px;
            border: 1px solid #bae6fd;
        }

        .slip-info-panel {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 11px 18px;
            margin-bottom: 12px;
        }
        .slip-info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            row-gap: 7px;
            column-gap: 36px;
            font-size: 11px;
        }
        .slip-info-item {
            display: flex;
            align-items: baseline;
        }
        .slip-info-lbl {
            width: 120px;
            font-weight: 700;
            color: #1e293b;
            flex-shrink: 0;
        }
        .slip-info-col {
            width: 14px;
            color: #94a3b8;
            font-weight: 600;
            text-align: center;
            flex-shrink: 0;
        }
        .slip-info-txt {
            color: #334155;
            font-weight: 500;
        }
        .slip-info-txt.bold {
            font-weight: 700;
            color: #0f172a;
        }

        .slip-sec-bar {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 5px;
        }
        .slip-sec-name {
            font-size: 12px;
            font-weight: 800;
            color: #0b1a30;
            letter-spacing: 0.8px;
            text-transform: uppercase;
            flex-shrink: 0;
        }
        .slip-sec-divider {
            flex: 1;
            height: 1.5px;
            background: #e2e8f0;
        }
        .slip-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10.5px;
            margin-bottom: 10px;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            overflow: hidden;
        }
        .slip-table td {
            padding: 6px 12px;
            vertical-align: middle;
            border-bottom: 1px solid #f1f5f9;
        }
        .slip-table tr:nth-child(odd) td {
            background-color: #f8fafc;
        }
        .slip-table tr:nth-child(even) td {
            background-color: #ffffff;
        }
        .slip-table td.col-head {
            color: #334155;
            font-weight: 500;
            width: 68%;
        }
        .slip-table td.col-num {
            color: #0f172a;
            font-weight: 600;
            text-align: right;
            width: 32%;
        }
        .slip-table tr.total-bar td {
            background-color: #0b1a30 !important;
            color: #ffffff !important;
            font-weight: 700;
            font-size: 11px;
            padding: 6.5px 12px;
        }
        .slip-table tr.total-bar td.col-head {
            color: #ffffff !important;
        }
        .slip-table tr.total-bar td.col-num {
            color: #ffffff !important;
            font-weight: 800;
        }

        .slip-net-panel {
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
            border: 1.5px solid #00a8ff;
            border-radius: 8px;
            padding: 9px 16px;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .slip-net-left {
            flex: 1;
        }
        .slip-net-title {
            font-size: 10.5px;
            font-weight: 800;
            text-transform: uppercase;
            color: #0b1a30;
            letter-spacing: 0.5px;
        }
        .slip-net-words {
            font-size: 9.5px;
            font-weight: 600;
            color: #475569;
            margin-top: 2px;
            font-style: italic;
        }
        .slip-net-meta {
            font-size: 8.5px;
            font-weight: 500;
            color: #64748b;
            margin-top: 2px;
        }
        .slip-net-right {
            text-align: right;
            padding-left: 16px;
        }
        .slip-net-tag {
            font-size: 8.5px;
            font-weight: 700;
            color: #00a8ff;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: block;
        }
        .slip-net-sum {
            font-size: 18px;
            font-weight: 900;
            color: #0f172a;
            letter-spacing: -0.3px;
        }

        .slip-sign-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 24px;
            margin-top: 6px;
            margin-bottom: 4px;
            text-align: center;
        }
        .slip-sign-col {
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .slip-sign-rule {
            width: 100%;
            border-top: 1px solid #94a3b8;
            margin-bottom: 4px;
            height: 20px;
            display: flex;
            align-items: flex-end;
            justify-content: center;
        }
        .slip-sign-name {
            font-size: 8.5px;
            font-weight: 800;
            color: #0b1a30;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        .slip-sign-role {
            font-size: 8px;
            color: #64748b;
            margin-top: 1px;
        }

        /* ============================================================ */
        /* 1. AXECORP TECH PRIMARY SALARY SLIP (OFFICIAL PDF TEMPLATE)  */
        /* ============================================================ */
        .sheet-doc-axecorp {
            position: relative;
            width: 100%;
            max-width: 820px;
            height: 1160px;
            min-height: 1160px;
            max-height: 1160px;
            background-color: #ffffff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 20px 50px -10px rgba(0, 0, 0, 0.3);
            margin: 0 auto;
            display: block;
        }
        .axecorp-pdf-template {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: fill;
            z-index: 1;
            pointer-events: none;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
        .axecorp-salary-body {
            position: relative;
            z-index: 2;
            width: 100%;
            height: 100%;
            box-sizing: border-box;
            padding: 140px 46px 146px 46px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .axecorp-salary-body .slip-info-panel {
            background: rgba(248, 250, 252, 0.94);
            border: 1px solid #cbd5e1;
            box-shadow: 0 1px 3px rgba(0,0,0,0.03);
        }
        .axecorp-salary-body .slip-table {
            border: 1px solid #cbd5e1;
        }
        .axecorp-salary-body .slip-table tr:nth-child(odd) td {
            background-color: rgba(248, 250, 252, 0.94);
        }
        .axecorp-salary-body .slip-table tr:nth-child(even) td {
            background-color: rgba(255, 255, 255, 0.94);
        }
        .axecorp-salary-body .slip-net-panel {
            background: linear-gradient(135deg, rgba(240, 249, 255, 0.95) 0%, rgba(224, 242, 254, 0.95) 100%);
            border: 1.5px solid #00a8ff;
            box-shadow: 0 2px 6px rgba(0, 168, 255, 0.08);
        }


        /* Print Specific Optimization */
        @media print {
            body {
                background: #ffffff !important;
                padding: 0 !important;
                margin: 0 !important;
                color: #0f172a !important;
                display: block !important;
                width: 100% !important;
                height: 100% !important;
            }
            .no-print, .screen-toolbar {
                display: none !important;
            }
            .sheet-doc {
                box-shadow: none !important;
                max-width: 100% !important;
                width: 100% !important;
                height: 297mm !important;
                min-height: 297mm !important;
                max-height: 297mm !important;
                display: flex !important;
                flex-direction: column !important;
                justify-content: space-between !important;
                page-break-inside: avoid !important;
                page-break-after: avoid !important;
                overflow: hidden !important;
                margin: 0 auto !important;
                border-radius: 0 !important;
            }
            .spectra-top-waves svg, .spectra-bottom-waves svg,
            .falcon-top-banner, .falcon-footer-bar, .wave-top-band, .wave-footer-band {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            .spectra-contact-badge {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            .sheet-doc-axecorp {
                box-shadow: none !important;
                max-width: 100% !important;
                width: 100% !important;
                height: 297mm !important;
                min-height: 297mm !important;
                max-height: 297mm !important;
                display: block !important;
                page-break-inside: avoid !important;
                page-break-after: avoid !important;
                overflow: hidden !important;
                margin: 0 auto !important;
                border-radius: 0 !important;
                position: relative !important;
            }
            .axecorp-pdf-template {
                position: absolute !important;
                top: 0 !important;
                left: 0 !important;
                width: 100% !important;
                height: 297mm !important;
                min-height: 297mm !important;
                max-height: 297mm !important;
                object-fit: fill !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            .axecorp-salary-body {
                position: relative !important;
                z-index: 2 !important;
                width: 100% !important;
                height: 297mm !important;
                min-height: 297mm !important;
                max-height: 297mm !important;
                padding: 36mm 12mm 38mm 12mm !important;
                box-sizing: border-box !important;
                display: flex !important;
                flex-direction: column !important;
                justify-content: space-between !important;
            }
            .axecorp-salary-body .slip-table tr.total-bar td {
                background-color: #0b1a30 !important;
                color: #ffffff !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            .axecorp-salary-body .slip-table tr:nth-child(odd) td {
                background-color: rgba(248, 250, 252, 0.95) !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            .axecorp-salary-body .slip-net-panel {
                background: rgba(240, 249, 255, 0.95) !important;
                border: 1.5px solid #00a8ff !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            .axecorp-salary-body .slip-info-panel {
                background: rgba(248, 250, 252, 0.95) !important;
                border: 1px solid #cbd5e1 !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            .slip-table tr.total-bar td {
                background-color: #0b1a30 !important;
                color: #ffffff !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            .slip-table tr:nth-child(odd) td {
                background-color: #f8fafc !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            .slip-net-panel {
                background: #f0f9ff !important;
                border: 1.5px solid #00a8ff !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            .slip-info-panel {
                background: #f8fafc !important;
                border: 1px solid #e2e8f0 !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            @page {
                size: A4 portrait;
                margin: 0;
            }
        }
    </style>
</head>
<body>

    <!-- Screen Toolbar -->
    <div class="screen-toolbar no-print">
        <h1>
            <i class="fa-solid fa-file-invoice"></i>
            <span>{{ $docTypeTitle }} • {{ $slipPayload['employee_name'] }} ({{ $slipPayload['ref_no'] }})</span>
        </h1>
        <div class="toolbar-actions">
            <button onclick="window.print()" class="t-btn t-btn-primary">
                <i class="fa-solid fa-print"></i>
                <span>Print / Save as PDF</span>
            </button>
            <button onclick="window.close()" class="t-btn t-btn-secondary">
                <i class="fa-solid fa-xmark"></i>
                <span>Close</span>
            </button>
        </div>
    </div>

    <!-- ============================================================== -->
    <!-- OFFICIAL PRIMARY SALARY SLIP (DIRECT OFFICIAL PDF TEMPLATE)     -->
    <!-- No external/extra logo tags added - uses authentic letterhead   -->
    <!-- ============================================================== -->
    <div class="sheet-doc sheet-doc-axecorp">

        <!-- Authentic Official AxeCorp Letterhead Background Image (Direct from PDF) -->
        <img src="{{ $axecorpTemplateSrc }}" class="axecorp-pdf-template" alt="AxeCorp Official Letterhead" />

        <!-- Overlay Pure Salary Data -->
        <div class="axecorp-salary-body">
            @include('payroll.partials.payslip-body', ['slipPayload' => $slipPayload])
        </div>

    </div>

    @if($autoPrint ?? false)
    <script>
        window.addEventListener('DOMContentLoaded', function() {
            setTimeout(function() {
                window.print();
            }, 500);
        });
    </script>
    @endif

</body>
</html>
