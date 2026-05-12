<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; font-size: 12pt; color: #000; margin: 0; padding: 30px; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #000; padding-bottom: 10px; }
        .header h2 { margin: 0 0 4px 0; font-size: 14pt; }
        .header p { margin: 2px 0; font-size: 10pt; }
        h3 { text-align: center; font-size: 13pt; text-decoration: underline; margin: 20px 0; }
        table.info { width: 100%; border-collapse: collapse; margin: 12px 0; }
        table.info td { padding: 4px 6px; vertical-align: top; }
        table.info td:first-child { width: 200px; font-weight: normal; }
        .reason-box { border: 1px solid #ccc; padding: 10px; background: #fafafa; margin: 12px 0; min-height: 60px; }
        .signatures { margin-top: 50px; }
        .signatures table { width: 100%; }
        .signatures td { text-align: center; padding: 0 20px; vertical-align: top; }
        .sig-line { border-top: 1px solid #000; margin-top: 60px; padding-top: 4px; }
        .sp-badge { display: inline-block; padding: 4px 12px; border-radius: 4px; font-weight: bold; font-size: 11pt; }
        .sp1 { background: #d1ecf1; color: #0c5460; }
        .sp2 { background: #fff3cd; color: #856404; }
        .sp3 { background: #ffe5b4; color: #7d4e00; }
        .sp4 { background: #f8d7da; color: #842029; }
        .footer-note { margin-top: 20px; font-size: 10pt; color: #555; border-top: 1px solid #ddd; padding-top: 10px; }
        .final-warning { background: #f8d7da; border: 1px solid #f5c2c7; padding: 10px; margin: 12px 0; font-weight: bold; color: #842029; }
    </style>
</head>
<body>

    <div class="header">
        <h2>{{ $company_name }}</h2>
        <p>{{ $company_address }}</p>
    </div>

    <h3>
        WARNING LETTER {{ $letter->sp_level }}<br>
        <span class="sp-badge sp{{ $letter->sp_level }}">SP{{ $letter->sp_level }}</span>
    </h3>

    <p style="text-align:right; margin-bottom: 16px;">No: <strong>{{ $letter_number }}</strong></p>

    <p>To:<br>
    <strong>{{ $employee_name }}</strong><br>
    Position: {{ $employee_position }}<br>
    Department: {{ $department_name }}</p>

    <p>Dear Sir/Madam,</p>

    <p>
        Based on our evaluation and monitoring, we hereby issue this
        <strong>Warning Letter {{ $letter->sp_level }}</strong> to you for the violation described below:
    </p>

    <table class="info">
        <tr><td>Violation Category</td><td>: <strong>{{ $violation_category }}</strong></td></tr>
        <tr><td>Violation Date</td><td>: {{ $violation_date }}</td></tr>
        <tr><td>Letter Date</td><td>: {{ $issued_date }}</td></tr>
        <tr><td>Valid Until</td><td>: {{ $valid_until }}</td></tr>
    </table>

    <p><strong>Violation Description:</strong></p>
    <div class="reason-box">{{ $reason }}</div>

    <p>
        This warning letter is valid for <strong>6 (six) months</strong> from the date of issuance.
        Should another violation occur during the validity period, further disciplinary action
        will be taken in accordance with the applicable company regulations.
    </p>

    @if($letter->sp_level === 4)
    <div class="final-warning">
        ⚠ THIS IS A FINAL WARNING LETTER. Any further violation will entitle the company to
        terminate the employment contract in accordance with the applicable laws and regulations.
    </div>
    @endif

    <p>This warning letter is issued in good faith for your acknowledgment and attention.</p>

    <div class="signatures">
        <table>
            <tr>
                <td>
                    <p>Acknowledged &amp; Approved,</p>
                    <div class="sig-line"><strong>Human Resources</strong></div>
                </td>
                <td>
                    <p>Receipt Confirmed,</p>
                    <div class="sig-line"><strong>{{ $employee_name }}</strong></div>
                </td>
            </tr>
        </table>
    </div>

    <div class="footer-note">
        This document is officially issued by {{ $company_name }}. No: {{ $letter_number }}.
        @if($letter->acknowledgment)
            Digitally acknowledged on {{ $letter->acknowledgment->acknowledged_at->format('d F Y, H:i') }}.
        @endif
    </div>

</body>
</html>
