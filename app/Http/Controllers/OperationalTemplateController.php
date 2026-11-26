<?php

namespace App\Http\Controllers;

use App\Models\OperationalTemplate;
use App\Models\Section;
use App\Models\Shift;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OperationalTemplateController extends Controller
{
    /**
     * Tampilkan daftar semua template.
     * Mendukung pencarian (nama/keterangan), filter section, dan pagination.
     */
    public function index(Request $request)
    {
        $query = OperationalTemplate::with(['details.shift', 'details.section'])
            ->withCount('details')
            ->withSum('details', 'jumlah_kebutuhan');

        if ($q = trim((string) $request->query('q'))) {
            $query->where(function ($sub) use ($q) {
                $sub->where('nama_template', 'like', "%{$q}%")
                    ->orWhere('keterangan', 'like', "%{$q}%");
            });
        }

        if ($sectionId = $request->query('section_id')) {
            $query->whereHas('details', fn ($sub) => $sub->where('section_id', $sectionId));
        }

        $templates = $query->orderBy('nama_template')->paginate(15)->withQueryString();

        return view('operational-templates.index', [
            'templates' => $templates,
            'sections'  => Section::orderBy('nama_section')->get(),
        ]);
    }

    /**
     * Tampilkan form buat template baru.
     */
    public function create()
    {
        $sectionOrder = ['Chat', 'Website', 'Informasi', 'Inbound'];
        $sections = Section::all()->sortBy(function($s) use ($sectionOrder) {
            $pos = array_search($s->nama_section, $sectionOrder);
            return $pos === false ? 99 : $pos;
        })->values();

        return view('operational-templates.create', [
            'shifts'   => Shift::orderBy('jam_masuk')->get(),
            'sections' => $sections,
        ]);
    }

    /**
     * Simpan template baru beserta detailnya.
     * Detail diurutkan dulu sesuai aturan operasional (Section lalu Shift)
     * sebelum disimpan, jadi urutan di DB selalu konsisten.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_template' => ['required', 'string', 'max:255'],
            'keterangan'    => ['nullable', 'string'],
            'details'                    => ['required', 'array', 'min:1'],
            'details.*.shift_id'         => ['required', 'exists:shifts,id'],
            'details.*.section_id'       => ['required', 'exists:sections,id'],
            'details.*.jumlah_kebutuhan' => ['required', 'integer', 'min:1'],
        ]);

        $this->validateShiftSectionRule($validated['details']);

        $validated['details'] = $this->sortDetails($validated['details']);

        $template = OperationalTemplate::create([
            'nama_template' => $validated['nama_template'],
            'keterangan'    => $validated['keterangan'] ?? null,
        ]);

        foreach ($validated['details'] as $detail) {
            $template->details()->create($detail);
        }

        return redirect()->route('operational-templates.index')
            ->with('success', 'Template berhasil dibuat.');
    }

    /**
     * Tampilkan form edit template.
     */
    public function edit(OperationalTemplate $operationalTemplate)
    {
        $operationalTemplate->load('details');

        $sectionOrder = ['Chat', 'Website', 'Informasi', 'Inbound'];
        $sections = Section::all()->sortBy(function($s) use ($sectionOrder) {
            $pos = array_search($s->nama_section, $sectionOrder);
            return $pos === false ? 99 : $pos;
        })->values();

        // Baris tampil terurut (Section lalu Shift) saat edit.
        $details = $this->sortDetails(
            $operationalTemplate->details->map(fn ($d) => [
                'shift_id'         => (string) $d->shift_id,
                'section_id'       => (string) $d->section_id,
                'jumlah_kebutuhan' => $d->jumlah_kebutuhan,
            ])->all()
        );

        return view('operational-templates.edit', [
            'template' => $operationalTemplate,
            'details'  => $details,
            'shifts'   => Shift::orderBy('jam_masuk')->get(),
            'sections' => $sections,
        ]);
    }

    /**
     * Update template beserta detailnya.
     * Strategi: hapus semua detail lama, lalu insert ulang dari form.
     * Lebih sederhana & aman daripada mencocokkan baris lama vs baru satu-satu.
     */
    public function update(Request $request, OperationalTemplate $operationalTemplate)
    {
        $validated = $request->validate([
            'nama_template' => ['required', 'string', 'max:255'],
            'keterangan'    => ['nullable', 'string'],
            'details'                    => ['required', 'array', 'min:1'],
            'details.*.shift_id'         => ['required', 'exists:shifts,id'],
            'details.*.section_id'       => ['required', 'exists:sections,id'],
            'details.*.jumlah_kebutuhan' => ['required', 'integer', 'min:1'],
        ]);

        $this->validateShiftSectionRule($validated['details']);

        $validated['details'] = $this->sortDetails($validated['details']);

        $operationalTemplate->update([
            'nama_template' => $validated['nama_template'],
            'keterangan'    => $validated['keterangan'] ?? null,
        ]);

        // Hapus-lalu-insert detail dalam satu transaksi: kalau proses terputus
        // di tengah, template tidak akan kehilangan seluruh detailnya.
        DB::transaction(function () use ($operationalTemplate, $validated) {
            $operationalTemplate->details()->delete();

            foreach ($validated['details'] as $detail) {
                $operationalTemplate->details()->create($detail);
            }
        });

        return redirect()->route('operational-templates.index')
            ->with('success', 'Template berhasil diperbarui.');
    }

    /**
     * Hapus template (detail ikut terhapus via cascadeOnDelete di migration).
     */
    public function destroy(OperationalTemplate $operationalTemplate)
    {
        $operationalTemplate->delete();

        return redirect()->route('operational-templates.index')
            ->with('success', 'Template berhasil dihapus.');
    }

    /**
     * Duplikat template: salin header + semua detail ke template baru
     * dengan nama "... (Salinan)", lalu arahkan ke halaman edit agar
     * PIC bisa langsung menyesuaikan nama & isinya.
     */
    public function duplicate(OperationalTemplate $operationalTemplate)
    {
        $operationalTemplate->load('details');

        $copy = OperationalTemplate::create([
            'nama_template' => $operationalTemplate->nama_template . ' (Salinan)',
            'keterangan'    => $operationalTemplate->keterangan,
        ]);

        foreach ($operationalTemplate->details as $detail) {
            $copy->details()->create([
                'shift_id'         => $detail->shift_id,
                'section_id'       => $detail->section_id,
                'jumlah_kebutuhan' => $detail->jumlah_kebutuhan,
            ]);
        }

        return redirect()->route('operational-templates.edit', $copy)
            ->with('success', 'Template berhasil diduplikasi. Sesuaikan nama & isinya lalu simpan.');
    }

    /**
     * Urutkan detail template mengikuti aturan operasional:
     * Section (Chat > Website > Informasi > Inbound), lalu Shift (jam masuk).
     * Satu sumber kebenaran untuk store/update dan tampilan edit.
     */
    private function sortDetails(array $details): array
    {
        $sectionOrder = ['Chat', 'Website', 'Informasi', 'Inbound'];
        $sectionNames = Section::pluck('nama_section', 'id');
        $sectionRank  = array_flip($sectionOrder);
        $shiftRank    = Shift::orderBy('jam_masuk')->pluck('id')->flip();

        return collect($details)
            ->sortBy(function (array $detail) use ($sectionNames, $sectionRank, $shiftRank): int {
                $secIdx   = $sectionRank[$sectionNames[$detail['section_id']] ?? ''] ?? 99;
                $shiftIdx = $shiftRank[$detail['shift_id']] ?? 999;
                return $secIdx * 1000 + $shiftIdx;
            })
            ->values()
            ->all();
    }

    /**
     * HC6: shift A7 dan A hanya boleh dipasangkan dengan section Chat.
     * Aturan tetap/permanen, ditegakkan di sini (bukan di tabel database).
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    private function validateShiftSectionRule(array $details): void
    {
        $shiftsKhusus = Shift::whereIn('kode_shift', ['A7', 'A'])->pluck('id', 'kode_shift');
        $sectionChat = Section::where('nama_section', 'Chat')->value('id');

        foreach ($details as $index => $detail) {
            $isShiftKhusus = in_array($detail['shift_id'], $shiftsKhusus->all(), true);

            if ($isShiftKhusus && (int) $detail['section_id'] !== (int) $sectionChat) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    "details.$index.section_id" => 'Shift A7 dan A hanya boleh dipasangkan dengan section Chat.',
                ]);
            }
        }
    }
}
