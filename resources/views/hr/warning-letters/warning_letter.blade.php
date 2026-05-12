<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <style>
        @font-face {
            font-family: 'Calibri';
            src: url('C:/Windows/Fonts/calibri.ttf') format('truetype');
            font-weight: normal;
            font-style: normal;
        }
        @font-face {
            font-family: 'Calibri';
            src: url('C:/Windows/Fonts/calibrib.ttf') format('truetype');
            font-weight: bold;
            font-style: normal;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Calibri', Arial, sans-serif;
            font-size: 10pt;
            color: #000;
            padding: 40px 50px;
            line-height: 1.6;
        }
        .kop {
            border-bottom: 3px double #000;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .kop img { width: 100%; max-height: 90px; object-fit: contain; }

        .perihal-row { margin-bottom: 4px; }
        .perihal-row table td { vertical-align: top; padding: 1px 0; }
        .perihal-row table td:first-child { width: 80px; }
        .perihal-row table td:nth-child(2) { width: 12px; }

        .judul {
            text-align: center;
            font-size: 10pt;
            font-weight: bold;
            text-decoration: underline;
            text-transform: uppercase;
            margin: 18px 0 0;
        }

        .penerima { width: 100%; border-collapse: collapse; margin: 12px 0; }
        .penerima td { padding: 2px 0; vertical-align: top; }
        .penerima td:first-child { width: 90px; }
        .penerima td:nth-child(2) { width: 12px; }

        .body-text { margin: 10px 0; text-align: justify; }
        ol.ketentuan { margin: 8px 0 8px 18px; }
        ol.ketentuan li { margin-bottom: 6px; text-align: justify; }

        .ttd-wrap { margin-top: 36px; }
        .ttd-line { margin-top: 60px; font-weight: bold; }
        .ttd-jabatan { font-size: 10pt; }

        .footer-note {
            margin-top: 16px;
            font-size: 8.5pt;
            color: #666;
            border-top: 1px solid #ccc;
            padding-top: 6px;
        }
    </style>
</head>
<body>

@php
    $spWords = [1 => 'SATU', 2 => 'DUA', 3 => 'TIGA', 4 => 'EMPAT'];
    $spWord  = $spWords[$letter->sp_level] ?? $letter->sp_level;
    $spWordTitle = [1 => 'Pertama', 2 => 'Kedua', 3 => 'Ketiga', 4 => 'Keempat'];
    $spWordT = $spWordTitle[$letter->sp_level] ?? $letter->sp_level;
    $nextSp  = $letter->sp_level + 1;
    $nextSpWord = $spWordTitle[$nextSp] ?? $nextSp;

    $idMonths = ['','Januari','Februari','Maret','April','Mei','Juni',
                 'Juli','Agustus','September','Oktober','November','Desember'];

    $fmtId = function($carbon) use ($idMonths) {
        if (!$carbon) return '-';
        return $carbon->day . ' ' . $idMonths[$carbon->month] . ' ' . $carbon->year;
    };

    $issuedFmt  = $fmtId($letter->issued_date);
    $validFmt   = $fmtId($letter->valid_until);
    $issuedCity = config('app.company_city', 'Batam');
@endphp

{{-- Kop Surat --}}
@php
    $logoPath = public_path('images/logo-costume.png');
    $logoSrc  = 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath));
@endphp
<div class="kop">
    <img src="{{ $logoSrc }}" alt="PT. The Costume MagnifiQue">
</div>

{{-- Perihal --}}
<div class="perihal-row">
    <table>
        <tr>
            <td>Perihal</td>
            <td>:</td>
            <td><strong>Surat Peringatan</strong></td>
        </tr>
    </table>
</div>

{{-- Judul --}}
<div class="judul">
    Surat Peringatan Ke-{{ $letter->sp_level }} (SP {{ $spWord }})
</div>
<p style="text-align:center; margin:0 0 16px 0; font-weight:bold; font-size:10pt;">
    NOMOR : {{ $letter_number }}
</p>

{{-- Penerima --}}
<p style="margin-bottom:6px;">Surat Peringatan ini ditujukan kepada :</p>
<table class="penerima">
    <tr>
        <td>Nama</td>
        <td>:</td>
        <td>{{ $employee_name }}</td>
    </tr>
    <tr>
        <td>Jabatan</td>
        <td>:</td>
        <td>{{ $employee_position ? ucwords(strtolower($employee_position)) : '-' }}</td>
    </tr>
    <tr>
        <td>NIK</td>
        <td>:</td>
        <td>{{ $letter->employee->employee_no }}</td>
    </tr>
</table>

{{-- Isi Surat --}}
<p class="body-text">
    Surat ini dikeluarkan sehubungan dengan kesalahan atas pelanggaran yaitu
    <strong>{{ $reason }}</strong>,
    maka dengan ini kami memberikan surat peringatan Ke {{ $letter->sp_level }} ( {{ $spWordT }} ) dengan ketentuan:
</p>

<ol class="ketentuan">
    <li>
        Surat peringatan ke {{ $letter->sp_level }} ( {{ $spWordT }} ) berlaku untuk 6 (Enam) bulan ke depan
        sejak diterbitkan yakni mulai tanggal <strong>{{ $issuedFmt }}</strong>
        hingga <strong>{{ $validFmt }}</strong>.
    </li>
    <li>
        Apabila selama kurun waktu 6 (Enam) bulan sejak tanggal penerbitan surat peringatan
        ke {{ $letter->sp_level }} ({{ $spWordT }}) saudara tidak melakukan pelanggaran yang menjadi dasar
        atas diterbitkannya surat peringatan ini, maka surat peringatan ke {{ $letter->sp_level }} ({{ $spWordT }})
        saudara dinyatakan sudah tidak berlaku.
    </li>
    @if($letter->sp_level < 4)
    <li>
        Surat peringatan {{ $nextSp }} ({{ $nextSpWord }}) akan dikeluarkan jika dalam kurun waktu
        6 (Enam) bulan saudara kembali melakukan tindakan pelanggaran kembali.
    </li>
    @else
    <li>
        Surat peringatan ini merupakan peringatan terakhir. Pelanggaran berikutnya dapat
        mengakibatkan Pemutusan Hubungan Kerja (PHK) sesuai peraturan yang berlaku.
    </li>
    @endif
</ol>

<p class="body-text">
    Demikian surat peringatan ini dibuat untuk dapat diperhatikan dan dilaksanakan
    sebaik mungkin kepada yang bersangkutan.
</p>

{{-- Tanda Tangan --}}
<div class="ttd-wrap">
    <p>{{ $issuedCity }}, {{ $issuedFmt }}</p>
    <br>
    <div class="ttd-line">Noorjahan Katu Bte Iqbal Khan Surattee</div>
    <div class="ttd-jabatan">Direktur</div>
</div>

<br><br>
<table style="width:100%; border-collapse:collapse;">
    <tr>
        <td style="width:90px; vertical-align:top;">Perihal</td>
        <td style="width:12px; vertical-align:top;">:</td>
        <td style="vertical-align:top;"><strong>Surat Peringatan</strong></td>
    </tr>
</table>


</body>
</html>
