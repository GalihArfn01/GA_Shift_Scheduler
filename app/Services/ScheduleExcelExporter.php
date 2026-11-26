<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\Schedule;
use App\Models\SchedulePeriod;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/**
 * ScheduleExcelExporter
 *
 * Mengisi template Excel (storage/app/templates/template-scheduling.xlsx)
 * dengan data jadwal satu periode, mengikuti format kertas yang sudah
 * biasa dipakai PT XYZ.
 *
 * Struktur template:
 * - Baris 3 : header (A3=No, B3=Inbound/Live Chat/Web, C3..I3=Tanggal kosong)
 * - Baris 4-35 : 32 baris agent, kolom A (No) & B (Nama) SUDAH terisi
 *   sesuai urutan asli (sama dengan urutan AgentSeeder / id), kolom C-I
 *   (7 hari) masih kosong -- inilah yang diisi oleh service ini.
 *
 * PENTING: urutan baris di template ini mengikuti urutan LEVEL (3,2,1
 * -- level lebih tinggi ditampilkan lebih dulu), dan di dalam level yang
 * sama diurutkan berdasarkan ID (urutan seeder/dibuat). Ini BUKAN urutan
 * ID murni, dan BUKAN urutan level->nama (alfabetis) yang dipakai di
 * halaman web. Maka agent diambil dengan orderByDesc('level')->orderBy('id').
 */
class ScheduleExcelExporter
{
    protected const TEMPLATE_PATH = 'templates/template-scheduling.xlsx';

    protected const LABEL_SECTION_MAP = [
        'Informasi' => 'INFO',
        'Website'   => 'WEB',
        'Chat'      => 'CHAT',
    ];

    protected const FIRST_DATA_ROW = 4;
    protected const FIRST_DATA_COL = 3; // kolom C
    protected const HEADER_ROW = 3;
    protected const TITLE_ROW = 2;

    protected const SKORSING_FILL_COLOR = 'FFE0B2'; // oranye muda

    public function export(SchedulePeriod $period): \PhpOffice\PhpSpreadsheet\Spreadsheet
    {
        $templatePath = storage_path('app/' . self::TEMPLATE_PATH);

        $spreadsheet = IOFactory::load($templatePath);
        $sheet = $spreadsheet->getActiveSheet();

        $this->writeTitle($sheet, $period);
        $this->writeDateHeaders($sheet, $period);
        $this->writeAgentRows($sheet, $period);

        return $spreadsheet;
    }

    protected function writeTitle($sheet, SchedulePeriod $period): void
    {
        $tanggalMulai = Carbon::parse($period->tanggal_mulai);
        $tanggalSelesai = Carbon::parse($period->tanggal_selesai);

        $title = $tanggalMulai->translatedFormat('d') . ' - ' . $tanggalSelesai->translatedFormat('d F Y');

        // Jika bulan mulai dan selesai berbeda, tulis lengkap kedua sisi
        if ($tanggalMulai->format('m') !== $tanggalSelesai->format('m')) {
            $title = $tanggalMulai->translatedFormat('d F') . ' - ' . $tanggalSelesai->translatedFormat('d F Y');
        }

        // Sel ini (A2, merge A2:I2) sudah punya styling bold+center dari
        // template asli, jadi cukup timpa value-nya saja tanpa atur ulang style.
        $sheet->setCellValue('A' . self::TITLE_ROW, $title);
    }

    protected function writeDateHeaders($sheet, SchedulePeriod $period): void
    {
        $tanggalMulai = Carbon::parse($period->tanggal_mulai);

        for ($i = 0; $i < 7; $i++) {
            $col = self::FIRST_DATA_COL + $i;
            $tanggal = $tanggalMulai->copy()->addDays($i);
            $columnLetter = $this->columnLetter($col);

            $sheet->setCellValue($columnLetter . self::HEADER_ROW, (int) $tanggal->format('j'));
        }
    }

    /**
     * Jika jumlah agent sekarang LEBIH BANYAK dari kapasitas baris template
     * asli, tambahkan baris baru dengan meniru style (border, font, dll)
     * dari baris terakhir template, supaya tampilan tetap konsisten.
     */
    /** Jumlah baris data asli di template (32 agent), dipakai sebagai
     *  referensi style untuk baris tambahan jika jumlah agent bertambah.
     *  Tidak pakai getHighestDataRow() karena template ini punya dimension
     *  sampai baris 36 (termasuk baris kosong tanpa border/style apa pun
     *  yang ikut tersimpan saat file template dibuat), sehingga deteksi
     *  otomatis salah menganggap baris kosong itu sebagai "data terakhir".
     */
    protected const TEMPLATE_LAST_DATA_ROW = 35;

    protected function ensureRowCapacity($sheet, int $totalAgents): void
    {
        $lastTemplateRow = self::TEMPLATE_LAST_DATA_ROW;
        $neededLastRow = self::FIRST_DATA_ROW + $totalAgents - 1;

        if ($neededLastRow <= $lastTemplateRow) {
            return; // kapasitas template sudah cukup
        }

        $lastColIndex = self::FIRST_DATA_COL + 6; // kolom I

        // Ambil style baris referensi (baris terakhir template) per kolom,
        // lalu terapkan eksplisit ke setiap kolom yang sama pada baris baru.
        // Pendekatan manual ini dipakai karena duplicateStyle() pada range
        // tidak selalu menyalin border dengan benar di semua kondisi.
        for ($row = $lastTemplateRow + 1; $row <= $neededLastRow; $row++) {
            // Buat border 'thin' langsung untuk seluruh baris (A sampai I),
            // sesuai style baris data asli template. Tidak menyalin dari sel
            // sumber (duplicateStyle/applyFromArray ternyata tidak konsisten
            // menyalin border pada kondisi ini), melainkan didefinisikan
            // ulang secara eksplisit.
            $rangeRef = 'A' . $row . ':' . $this->columnLetter($lastColIndex) . $row;
            $sheet->getStyle($rangeRef)->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => '000000'],
                    ],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ]);

            // Kolom B (nama) rata kiri, beda dari kolom lain yang rata tengah.
            $sheet->getStyle('B' . $row)->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_LEFT);

            // Tinggi baris ikut disamakan dengan baris referensi, supaya
            // baris tambahan tidak terlihat "lebih pendek/tinggi" dari yang lain.
            $sheet->getRowDimension($row)->setRowHeight(
                $sheet->getRowDimension($lastTemplateRow)->getRowHeight()
            );
        }
    }

    protected function writeAgentRows($sheet, SchedulePeriod $period): void
    {
        $tanggalMulai = Carbon::parse($period->tanggal_mulai);

        $schedules = Schedule::where('schedule_period_id', $period->id)
            ->with(['shift', 'section'])
            ->get();

        // Hanya agent yang BENAR-BENAR punya baris schedule di periode ini
        // yang ditampilkan -- baik masih aktif maupun sudah di-soft-delete
        // (resign), supaya histori akurat tanpa baris "X semua" untuk agent
        // yang tidak relevan dengan periode ini (misal: belum terdaftar
        // saat periode ini dibuat, atau sudah resign sebelum periode ini).
        $agentIdsInPeriod = $schedules->pluck('agent_id')->unique()->values();

        $agents = Agent::withTrashed()
            ->where(function ($query) use ($agentIdsInPeriod) {
                $query->whereNull('deleted_at');
                if ($agentIdsInPeriod->isNotEmpty()) {
                    $query->orWhereIn('id', $agentIdsInPeriod);
                }
            })
            ->orderByDesc('level')
            ->orderBy('id')
            ->get();

        $this->ensureRowCapacity($sheet, count($agents));

        // grid[agent_id][hari_index] = Schedule
        $grid = [];
        foreach ($schedules as $s) {
            $hariIndex = $tanggalMulai->diffInDays(Carbon::parse($s->tanggal));
            $grid[$s->agent_id][(int) $hariIndex] = $s;
        }

        $row = self::FIRST_DATA_ROW;
        $no = 1;

        foreach ($agents as $agent) {
            $sheet->setCellValue('A' . $row, $no);
            $sheet->setCellValue('B' . $row, $agent->nama_agent);
            $no++;

            if ($agent->status !== 'aktif') {
                $this->writeSkorsingRow($sheet, $row);
                $row++;
                continue;
            }

            for ($hariIndex = 0; $hariIndex < 7; $hariIndex++) {
                $col = self::FIRST_DATA_COL + $hariIndex;
                $columnLetter = $this->columnLetter($col);
                $cellRef = $columnLetter . $row;

                $assignment = $grid[$agent->id][$hariIndex] ?? null;

                if ($assignment && $assignment->status === 'kerja') {
                    $sheet->setCellValue($cellRef, $this->formatAssignmentText($assignment));
                } else {
                    $sheet->setCellValue($cellRef, 'X');
                }
            }

            $row++;
        }

        $this->clearLeftoverRows($sheet, $row);
    }

    /**
     * Bersihkan baris-baris sisa di bawah data agent terakhir, sampai baris
     * terakhir yang pernah dipakai template (max_row). Ini perlu karena
     * jumlah agent bisa BERKURANG dibanding saat template awal dibuat --
     * tanpa pembersihan ini, baris sisa akan menampilkan nama/data agent
     * yang sudah dihapus dari database.
     */
    protected function clearLeftoverRows($sheet, int $startRow): void
    {
        $lastCol = $this->columnLetter(self::FIRST_DATA_COL + 6); // kolom I
        $maxRow = max(self::TEMPLATE_LAST_DATA_ROW, $sheet->getHighestDataRow());

        for ($row = $startRow; $row <= $maxRow; $row++) {
            $sheet->getStyle("A{$row}:{$lastCol}{$row}")->getFill()->setFillType(Fill::FILL_NONE);
            for ($col = 1; $col <= self::FIRST_DATA_COL + 6; $col++) {
                $sheet->setCellValue($this->columnLetter($col) . $row, null);
            }

            // Lepaskan merge cell SKORSING jika ada sisa dari baris ini
            foreach ($sheet->getMergeCells() as $mergeRange) {
                if (str_starts_with($mergeRange, "C{$row}:")) {
                    $sheet->unmergeCells($mergeRange);
                }
            }
        }
    }

    protected function formatAssignmentText(Schedule $assignment): string
    {
        $kodeShift = $assignment->shift->kode_shift ?? '-';
        $namaSection = $assignment->section->nama_section ?? '';
        $labelSection = self::LABEL_SECTION_MAP[$namaSection] ?? null;

        return $labelSection ? "{$kodeShift}/{$labelSection}" : $kodeShift;
    }

    protected function writeSkorsingRow($sheet, int $row): void
    {
        $firstCol = $this->columnLetter(self::FIRST_DATA_COL);
        $lastCol = $this->columnLetter(self::FIRST_DATA_COL + 6);

        $range = "{$firstCol}{$row}:{$lastCol}{$row}";

        $sheet->mergeCells($range);
        $sheet->setCellValue($firstCol . $row, 'SKORSING');

        $sheet->getStyle($range)->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);

        $sheet->getStyle($range)->getFont()->setBold(true);

        $sheet->getStyle($range)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB(self::SKORSING_FILL_COLOR);
    }

    protected function columnLetter(int $columnIndex): string
    {
        return \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($columnIndex);
    }
}
