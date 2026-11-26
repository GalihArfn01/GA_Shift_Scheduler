<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\FormRequestWindow;
use App\Models\OffRequest;
use App\Notifications\OffRequestBaruMasuk;
use App\Notifications\OffRequestDiproses;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OffRequestController extends Controller
{
    /**
     * Tampilkan off request dalam 2 TAB:
     * - Tab "Rendah": Form Request saja (read-only, self-governing
     *   lewat kuota, tidak butuh aksi PIC apa pun).
     * - Tab "Tinggi": gabungan Prioritas Tinggi (dari Agent, butuh
     *   approve/tolak) DAN PIC Manual (diinput langsung PIC, otomatis
     *   diterima) -- keduanya diperlakukan sama sebagai SC1 di GA, jadi
     *   ditampilkan bersamaan di tab ini, termasuk form "Tambah" untuk
     *   PIC input manual.
     *
     * Satu halaman tanpa mode toggle: seluruh riwayat ditampilkan
     * (tanpa batasan rentang tanggal), 10 item per halaman, urutan
     * terbaru ke terlama. Tab "Tinggi" punya filter: cari nama
     * agent/keterangan, status approval, sumber (agent/PIC), dan
     * rentang tanggal.
     */
    protected const KUOTA_PER_KELOMPOK = 3;
    protected const NAMA_HARI = ['senin', 'selasa', 'rabu', 'kamis', 'jumat', 'sabtu', 'minggu'];

    public function index(Request $request)
    {
        $tab = in_array($request->query('tab'), ['rendah', 'tinggi'], true)
            ? $request->query('tab')
            : 'rendah';

        $formRequests = OffRequest::with('agent')
            ->formRequest()
            ->notFinalized()
            ->orderByDesc('tanggal')
            ->orderByDesc('id')
            ->paginate(10, ['*'], 'page_rendah')
            ->withQueryString();

        $tinggiQuery = OffRequest::with('agent')
            ->prioritasTinggiGabungan()
            ->notFinalized();

        if ($request->filled('search')) {
            $search = '%' . $request->search . '%';
            $tinggiQuery->where(function ($q) use ($search) {
                $q->whereHas('agent', function ($qAgent) use ($search) {
                    $qAgent->where('nama_agent', 'like', $search);
                })->orWhere('keterangan', 'like', $search);
            });
        }

        if ($request->filled('status')) {
            $tinggiQuery->where('status_approval', $request->status);
        }

        if ($request->filled('sumber')) {
            $tinggiQuery->where('jenis', $request->sumber);
        }

        if ($request->filled('tanggal_dari')) {
            $tinggiQuery->whereDate('tanggal', '>=', $request->tanggal_dari);
        }

        if ($request->filled('tanggal_sampai')) {
            $tinggiQuery->whereDate('tanggal', '<=', $request->tanggal_sampai);
        }

        $prioritasTinggiGabungan = $tinggiQuery
            ->orderByDesc('tanggal')
            ->orderByDesc('id')
            ->paginate(10, ['*'], 'page_tinggi')
            ->withQueryString();

        $window = FormRequestWindow::where('closes_at', '>', now())
            ->orderByDesc('id')
            ->first();

        return view('off-requests.index', [
            'formRequests'            => $formRequests,
            'prioritasTinggiGabungan' => $prioritasTinggiGabungan,
            'tab'                     => $tab,
            'agents'                  => Agent::aktif()->orderBy('nama_agent')->get(),
            'window'                  => $window,
            'slotPreview'             => $this->buildSlotPreview($window),
        ]);
    }

    /**
     * Bangun data tabel preview slot kuota (3 baris x 7 kolom) untuk
     * kedua kelompok section. Setiap sel berisi nama agent yang sudah
     * mengisi slot, atau null kalau masih kosong. Kolom yang tidak aktif
     * ditandai 'aktif' => false supaya view dapat menampilkan blok penutup.
     */
    protected function buildSlotPreview(?FormRequestWindow $window): array
    {
        $tanggalMulai = $window
            ? $window->tanggal_mulai->copy()
            : Carbon::today();

        $kolomList = [];
        for ($i = 0; $i < 7; $i++) {
            $tanggal  = $tanggalMulai->copy()->addDays($i);
            $namaHari = self::NAMA_HARI[$i];
            $aktif    = $window && in_array($namaHari, $window->hari_aktif, true);

            $kolomList[] = [
                'tanggal_label' => $tanggal->translatedFormat('d M'),
                'tanggal_raw'   => $tanggal->toDateString(),
                'aktif'         => $aktif,
            ];
        }

        $hasil = [];
        foreach (['inbound_informasi', 'website_chat'] as $kelompok) {
            $kolomData = [];
            foreach ($kolomList as $kolom) {
                $pengisi = $kolom['aktif']
                    ? OffRequest::where('tanggal', $kolom['tanggal_raw'])
                        ->where('jenis', 'form_request')
                        ->where('kelompok_section', $kelompok)
                        ->where('status_approval', 'diterima')
                        ->with('agent')
                        ->orderBy('id')
                        ->get()
                        ->pluck('agent.nama_agent')
                    : collect();

                $kolomData[] = [
                    'tanggal_label' => $kolom['tanggal_label'],
                    'tanggal_raw'   => $kolom['tanggal_raw'],
                    'aktif'         => $kolom['aktif'],
                    'pengisi'       => $pengisi,
                ];
            }
            $hasil[$kelompok] = $kolomData;
        }

        return $hasil;
    }

    /**
     * PIC menyetujui atau menolak off request berjenis prioritas_tinggi.
     * Tidak berlaku untuk form_request (otomatis 'diterima' lewat kuota)
     * maupun pic_manual (otomatis 'diterima' saat PIC input, tidak perlu
     * approve diri sendiri).
     */
    public function updateApproval(Request $request, OffRequest $offRequest)
    {
        if ($offRequest->jenis !== 'prioritas_tinggi') {
            abort(403, 'Hanya off request prioritas tinggi (dari Agent) yang bisa di-approve/tolak PIC.');
        }

        $validated = $request->validate([
            'status_approval' => ['required', Rule::in(['diterima', 'ditolak'])],
            'catatan_pic'     => ['nullable', 'string', 'max:500'],
        ]);

        $offRequest->update([
            'status_approval' => $validated['status_approval'],
            'catatan_pic'     => $validated['catatan_pic'] ?? null,
            'approved_at'     => now(),
        ]);

        // Kirim notifikasi ke Agent pengaju
        if ($offRequest->agent && $offRequest->agent->user) {
            $offRequest->agent->user->notify(new OffRequestDiproses($offRequest));
        }

        $label = $validated['status_approval'] === 'diterima' ? 'disetujui' : 'ditolak';

        return redirect()->route('off-requests.index', ['tab' => 'tinggi'])
            ->with('success', "Off request berhasil {$label}.");
    }

    /**
     * PIC menambahkan Form Request (prioritas rendah) mewakili agent
     * tertentu. Berlaku untuk satu agent per submit, bisa banyak tanggal.
     * Mengikuti aturan kuota yang sama dengan self-service Agent (maks 3
     * slot/hari per kelompok_section). Kelompok section ditentukan otomatis
     * dari level agent (sama seperti resolveKelompokSection di AgentOffRequestController).
     */
    public function storeFormRequestPic(Request $request)
    {
        $validated = $request->validate([
            'agent_id'  => ['required', 'exists:agents,id'],
            'tanggal'   => ['required', 'array', 'min:1'],
            'tanggal.*' => ['required', 'date'],
        ]);

        $agent   = Agent::findOrFail($validated['agent_id']);
        $kelompok = $agent->level == 3 ? 'website_chat' : 'inbound_informasi';

        $addedCount   = 0;
        $skippedCount = 0;
        $messages     = [];

        foreach ($validated['tanggal'] as $tgl) {
            $sudahAda = OffRequest::where('agent_id', $agent->id)
                ->where('tanggal', $tgl)
                ->exists();

            if ($sudahAda) {
                $skippedCount++;
                $messages[] = $tgl . ' (sudah ada off request untuk tanggal ini)';
                continue;
            }

            $terisi = OffRequest::where('tanggal', $tgl)
                ->where('jenis', 'form_request')
                ->where('kelompok_section', $kelompok)
                ->where('status_approval', 'diterima')
                ->count();

            if ($terisi >= self::KUOTA_PER_KELOMPOK) {
                $skippedCount++;
                $messages[] = $tgl . ' (kuota penuh)';
                continue;
            }

            OffRequest::create([
                'agent_id'         => $agent->id,
                'tanggal'          => $tgl,
                'jenis'            => 'form_request',
                'status_approval'  => 'diterima',
                'kelompok_section' => $kelompok,
            ]);
            $addedCount++;
        }

        if ($addedCount === 0) {
            return redirect()->route('off-requests.index', ['tab' => 'rendah'])
                ->with('error', 'Semua tanggal gagal ditambahkan: ' . implode(', ', $messages));
        }

        $msg = "Berhasil menambahkan {$addedCount} form request untuk {$agent->nama_agent}.";
        if ($skippedCount > 0) {
            $msg .= " (Dilewati {$skippedCount}: " . implode(', ', $messages) . ')';
        }

        return redirect()->route('off-requests.index', ['tab' => 'rendah'])->with('success', $msg);
    }

    /**
     * PIC menghapus form request (prioritas rendah) milik agent tertentu.
     */
    public function destroyFormRequest(OffRequest $offRequest)
    {
        if ($offRequest->jenis !== 'form_request') {
            abort(403, 'Hanya form request yang dapat dihapus lewat endpoint ini.');
        }

        $offRequest->delete();

        return redirect()->route('off-requests.index', ['tab' => 'rendah'])
            ->with('success', 'Form request berhasil dihapus.');
    }

    /**
     * PIC input off request manual mewakili agent -- dipakai untuk
     * kasus yang tidak masuk 2 jalur self-service Agent, misalnya untuk
     * mempertahankan constraint "maksimal 20 hari kerja/bulan". Otomatis
     * status_approval='diterima' (PIC tidak perlu approve diri sendiri),
     * dan diperlakukan sebagai prioritas TINGGI di GA (SC1).
     */
    public function storePicManual(Request $request)
    {
        $validated = $request->validate([
            'agent_ids'   => ['required', 'array', 'min:1'],
            'agent_ids.*' => ['required', 'exists:agents,id'],
            'tanggal'     => ['required', 'array', 'min:1'],
            'tanggal.*'   => ['required', 'date'],
            'keterangan'  => ['required', 'string', 'min:5', 'max:500'],
        ]);

        $addedCount = 0;
        $skippedCount = 0;

        foreach ($validated['agent_ids'] as $agentId) {
            foreach ($validated['tanggal'] as $tgl) {
                $sudahAda = OffRequest::where('agent_id', $agentId)
                    ->where('tanggal', $tgl)
                    ->exists();

                if (!$sudahAda) {
                    OffRequest::create([
                        'agent_id'        => $agentId,
                        'tanggal'         => $tgl,
                        'jenis'           => 'pic_manual',
                        'status_approval' => 'diterima',
                        'keterangan'      => $validated['keterangan'],
                    ]);
                    $addedCount++;
                } else {
                    $skippedCount++;
                }
            }
        }

        $msg = "Berhasil menambahkan {$addedCount} off request manual.";
        if ($skippedCount > 0) {
            $msg .= " (Dilewati {$skippedCount} karena sudah ada).";
        }

        return redirect()->route('off-requests.index', ['tab' => 'tinggi'])->with('success', $msg);
    }

    /**
     * PIC menghapus off request berjenis prioritas_tinggi atau pic_manual.
     */
    public function destroy(OffRequest $offRequest)
    {
        if (!in_array($offRequest->jenis, ['prioritas_tinggi', 'pic_manual'], true)) {
            abort(403, 'Hanya off request prioritas tinggi atau manual yang dapat dihapus.');
        }

        $offRequest->delete();

        return redirect()->route('off-requests.index', ['tab' => 'tinggi'])
            ->with('success', 'Off request berhasil dihapus.');
    }

    /**
     * Hapus massal off request.
     */
    public function bulkDestroy(Request $request)
    {
        $validated = $request->validate([
            'ids'   => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'exists:off_requests,id'],
        ]);

        $deletedCount = OffRequest::whereIn('id', $validated['ids'])
            ->whereIn('jenis', ['prioritas_tinggi', 'pic_manual'])
            ->delete();

        return redirect()->route('off-requests.index', ['tab' => 'tinggi'])
            ->with('success', "{$deletedCount} off request berhasil dihapus.");
    }
}
