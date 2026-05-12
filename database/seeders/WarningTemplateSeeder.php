<?php

namespace Database\Seeders;

use App\Models\Hr\WarningTemplate;
use App\Models\Admin\User;
use Illuminate\Database\Seeder;

class WarningTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $adminUser = User::where('role', 'super_admin')->first();
        $createdBy = $adminUser?->id ?? 1;

        $templates = [
            [
                'sp_level' => 1,
                'name'     => 'Template SP1 — Surat Peringatan Pertama',
                'content_html' => $this->templateSp(1),
            ],
            [
                'sp_level' => 2,
                'name'     => 'Template SP2 — Surat Peringatan Kedua',
                'content_html' => $this->templateSp(2),
            ],
            [
                'sp_level' => 3,
                'name'     => 'Template SP3 — Surat Peringatan Ketiga',
                'content_html' => $this->templateSp(3),
            ],
            [
                'sp_level' => 4,
                'name'     => 'Template SP4 — Surat Peringatan Keempat (Terakhir)',
                'content_html' => $this->templateSp(4),
            ],
        ];

        foreach ($templates as $tmpl) {
            WarningTemplate::updateOrCreate(
                ['sp_level' => $tmpl['sp_level'], 'is_active' => true],
                array_merge($tmpl, ['version' => 1, 'is_active' => true, 'created_by' => $createdBy])
            );
        }

        $this->command->info('WarningTemplate seeded: 4 default templates (SP1–SP4).');
    }

    private function templateSp(int $level): string
    {
        $levelLabel  = ['', 'Pertama', 'Kedua', 'Ketiga', 'Keempat (Terakhir)'][$level];
        $levelNote   = $level === 4
            ? '<p><strong>Ini adalah surat peringatan terakhir. Apabila pelanggaran terulang kembali, perusahaan berhak untuk mengakhiri hubungan kerja sesuai ketentuan yang berlaku.</strong></p>'
            : '';

        return <<<HTML
<div style="font-family: Arial, sans-serif; font-size: 12pt; color: #000; padding: 40px;">

  <div style="text-align:center; margin-bottom: 20px;">
    <h2 style="margin:0;">{{ \$company_name ?? 'PT. DECOR CREATIVE MOMENTUM' }}</h2>
    <p style="margin:4px 0;">{{ \$company_address ?? 'Jl. Raya Industri, Kota - Provinsi' }}</p>
    <hr style="border:1px solid #000;">
  </div>

  <h3 style="text-align:center; text-decoration:underline;">
    SURAT PERINGATAN {$level} ({$levelLabel})<br>
    No: {{ \$letter_number }}
  </h3>

  <p style="margin-top:20px;">Kepada Yth.<br>
  <strong>{{ \$employee_name }}</strong><br>
  Jabatan: {{ \$employee_position }}<br>
  Departemen: {{ \$department_name }}</p>

  <p>Dengan hormat,</p>

  <p>Berdasarkan hasil evaluasi dan pemantauan kinerja serta perilaku Saudara/i, bersama surat ini kami menyampaikan
  <strong>Surat Peringatan {$level} ({$levelLabel})</strong> atas pelanggaran yang telah dilakukan, yaitu:</p>

  <table style="width:100%; border-collapse:collapse; margin:12px 0;">
    <tr><td style="width:180px; padding:4px 0;"><strong>Kategori Pelanggaran</strong></td><td>: {{ \$violation_category }}</td></tr>
    <tr><td style="padding:4px 0;"><strong>Tanggal Pelanggaran</strong></td><td>: {{ \$violation_date }}</td></tr>
    <tr><td style="padding:4px 0;"><strong>Uraian Pelanggaran</strong></td><td>: {{ \$reason }}</td></tr>
    <tr><td style="padding:4px 0;"><strong>Tanggal Surat</strong></td><td>: {{ \$issued_date }}</td></tr>
    <tr><td style="padding:4px 0;"><strong>Berlaku Hingga</strong></td><td>: {{ \$valid_until }}</td></tr>
  </table>

  <p>Surat peringatan ini berlaku selama <strong>6 (enam) bulan</strong> terhitung sejak tanggal penerbitan.
  Apabila dalam masa berlaku surat ini Saudara/i melakukan pelanggaran kembali, maka akan diambil tindakan lebih lanjut sesuai peraturan perusahaan.</p>

  {$levelNote}

  <p>Demikian surat peringatan ini dibuat agar menjadi perhatian dan dipatuhi sebagaimana mestinya.</p>

  <div style="margin-top:40px; display:flex; justify-content:space-between;">
    <div style="text-align:center;">
      <p>Mengetahui,<br><br><br><br>
      <strong>Human Resources</strong></p>
    </div>
    <div style="text-align:center;">
      <p>Menyatakan Telah Menerima,<br><br><br><br>
      <strong>{{ \$employee_name }}</strong></p>
    </div>
  </div>

</div>
HTML;
    }
}
