<?php

namespace App\Http\Controllers;

use App\Models\Shift;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ShiftController extends Controller
{
    /**
     * Tampilkan daftar semua shift.
     */
    public function index()
    {
        // Hitung jumlah referensi tiap shift di tiga tabel pemakai,
        // dipakai UI untuk menonaktifkan tombol hapus pada shift yang
        // masih terpakai (lihat destroy()).
        $shifts = Shift::query()
            ->withCount(['schedules', 'operationalRequirements', 'operationalTemplateDetails'])
            ->orderBy('jam_masuk')
            ->orderBy('kode_shift')
            ->get();

        return view('shifts.index', [
            'shifts' => $shifts,
        ]);
    }

    /**
     * Simpan shift baru.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'kode_shift' => ['required', 'string', 'max:10', 'unique:shifts,kode_shift'],
            'jam_masuk'  => ['required', 'date_format:H:i,H:i:s'],
            'jam_pulang' => [
                'required',
                'date_format:H:i,H:i:s',
                // Shift overnight (melewati tengah malam) DIPERBOLEHKAN --
                // misalnya 17:00 -> 01:00. Yang ditolak hanya jam pulang
                // yang SAMA dengan jam masuk (durasi 0/ambigu).
                function (string $attribute, mixed $value, \Closure $fail) use ($request) {
                    if ($this->timeToMinutes((string) $value) === $this->timeToMinutes((string) $request->input('jam_masuk'))) {
                        $fail('Jam pulang tidak boleh sama dengan jam masuk.');
                    }
                },
            ],
        ]);

        // Normalisasi ke huruf besar supaya aturan yang bergantung pada
        // kode shift tertentu (mis. HC6: A7/A eksklusif Chat) tetap
        // cocok walau PIC mengetik "a7".
        $validated['kode_shift'] = strtoupper(trim($validated['kode_shift']));

        Shift::create($validated);

        return redirect()->route('shifts.index')
            ->with('success', "Shift {$validated['kode_shift']} berhasil ditambahkan.");
    }

    /**
     * Update data shift yang sudah ada.
     */
    public function update(Request $request, Shift $shift)
    {
        $validated = $request->validate([
            'kode_shift' => ['required', 'string', 'max:10', Rule::unique('shifts', 'kode_shift')->ignore($shift->id)],
            'jam_masuk'  => ['required', 'date_format:H:i,H:i:s'],
            'jam_pulang' => [
                'required',
                'date_format:H:i,H:i:s',
                function (string $attribute, mixed $value, \Closure $fail) use ($request) {
                    if ($this->timeToMinutes((string) $value) === $this->timeToMinutes((string) $request->input('jam_masuk'))) {
                        $fail('Jam pulang tidak boleh sama dengan jam masuk.');
                    }
                },
            ],
        ]);

        $validated['kode_shift'] = strtoupper(trim($validated['kode_shift']));

        $shift->update($validated);

        return redirect()->route('shifts.index')
            ->with('success', "Shift {$validated['kode_shift']} berhasil diperbarui.");
    }

    /**
     * Hapus shift.
     */
    public function destroy(Shift $shift)
    {
        // Shift yang masih dipakai jadwal/template/kebutuhan operasional
        // tidak boleh dihapus -- menghapusnya akan merusak histori jadwal
        // dan data master operasional yang sudah ada.
        $dipakai = collect([
            'jadwal'          => $shift->schedules()->exists(),
            'template operasional' => $shift->operationalTemplateDetails()->exists(),
            'kebutuhan operasional' => $shift->operationalRequirements()->exists(),
        ])->filter()->keys();

        if ($dipakai->isNotEmpty()) {
            return redirect()->route('shifts.index')
                ->with('error', "Shift {$shift->kode_shift} tidak bisa dihapus karena masih dipakai pada " . $dipakai->implode(', ') . '.');
        }

        $shift->delete();

        return redirect()->route('shifts.index')
            ->with('success', "Shift {$shift->kode_shift} berhasil dihapus.");
    }

    /**
     * Konversi "H:i[:s]" ke menit sejak tengah malam untuk pembandingan.
     */
    protected function timeToMinutes(string $time): ?int
    {
        $parts = explode(':', $time);

        if (count($parts) < 2) {
            return null;
        }

        return ((int) $parts[0]) * 60 + (int) $parts[1];
    }
}
