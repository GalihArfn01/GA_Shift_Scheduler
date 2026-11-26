<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\FormRequestWindow;
use App\Models\OffRequest;
use App\Models\User;
use App\Notifications\OffRequestBaruMasuk;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AgentOffRequestController extends Controller
{
    protected const KUOTA_PER_KELOMPOK = 3;
    protected const NAMA_HARI = ['senin', 'selasa', 'rabu', 'kamis', 'jumat', 'sabtu', 'minggu'];

    /**
     * Tampilkan riwayat off request milik Agent (kedua jenis), plus
     * form untuk mengajukan baru dan tabel preview slot Form Request.
     */
    public function index()
    {
        $agent = Auth::user()->agent;

        if (!$agent) {
            return view('agent.off-requests-index', ['agent' => null]);
        }

        $formRequests = OffRequest::where('agent_id', $agent->id)
            ->formRequest()
            ->notFinalized()
            ->orderByDesc('tanggal')
            ->get();

        $prioritasTinggi = OffRequest::where('agent_id', $agent->id)
            ->prioritasTinggi()
            ->notFinalized()
            ->orderByDesc('tanggal')
            ->get();

        $window = FormRequestWindow::where('closes_at', '>', now())
            ->orderByDesc('id')
            ->first();

        return view('agent.off-requests-index', [
            'agent'            => $agent,
            'formRequests'     => $formRequests,
            'prioritasTinggi'  => $prioritasTinggi,
            'kelompokSaya'     => $this->resolveKelompokSection($agent),
            'window'           => $window,
            'slotPreview'      => $this->buildSlotPreview($window),
        ]);
    }

    /**
     * Bangun data tabel preview slot kuota (3 baris x 7 kolom) untuk
     * kedua kelompok section. Setiap sel berisi nama agent yang sudah
     * mengisi slot itu, atau null kalau masih kosong. Kolom yang TIDAK
     * AKTIF (karena tidak ada window sama sekali, atau hari itu tidak
     * dicentang PIC) ditandai 'tutup' => true, supaya view bisa
     * menampilkan blok visual penutup.
     */
    protected function buildSlotPreview(?FormRequestWindow $window): array
    {
        $tanggalMulai = $window
            ? $window->tanggal_mulai->copy()
            : Carbon::today(); // fallback kalau tidak ada window aktif sama sekali

        $kolomList = [];
        for ($i = 0; $i < 7; $i++) {
            $tanggal = $tanggalMulai->copy()->addDays($i);
            $namaHari = self::NAMA_HARI[$i];
            $aktif = $window && in_array($namaHari, $window->hari_aktif, true);

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
                    'aktif'         => $kolom['aktif'],
                    'pengisi'       => $pengisi, // collection nama agent, max KUOTA_PER_KELOMPOK
                ];
            }
            $hasil[$kelompok] = $kolomData;
        }

        return $hasil;
    }

    /**
     * Ajukan Form Request (Jalur 1, prioritas rendah) -- self-service,
     * dibatasi kuota 3 slot/hari per kelompok_section, otomatis
     * status_approval='diterima' begitu lolos kuota (tanpa approval PIC).
     * Hanya bisa diajukan untuk tanggal yang termasuk window aktif
     * (dibuka PIC di Step 4 Wizard, dan hari itu dicentang aktif).
     */
    public function storeFormRequest(Request $request)
    {
        $agent = Auth::user()->agent;

        if (!$agent) {
            abort(403);
        }

        $validated = $request->validate([
            'tanggal'   => ['required', 'array', 'min:1'],
            'tanggal.*' => ['date'],
        ]);

        $kelompok = $this->resolveKelompokSection($agent);

        $tanggalGagal = [];

        foreach ($validated['tanggal'] as $tanggal) {
            $tanggalCarbon = Carbon::parse($tanggal);

            $window = FormRequestWindow::findActiveForDate($tanggalCarbon);

            if (!$window) {
                $tanggalGagal[] = $tanggal . ' (Form Request belum/tidak dibuka untuk tanggal ini)';
                continue;
            }

            // Cegah agent mengajukan off request lebih dari sekali untuk
            // tanggal yang sama, TERLEPAS jenisnya (form_request maupun
            // prioritas_tinggi) -- satu tanggal hanya boleh punya SATU
            // off request aktif per agent.
            $sudahAda = OffRequest::where('agent_id', $agent->id)
                ->where('tanggal', $tanggal)
                ->exists();

            if ($sudahAda) {
                $tanggalGagal[] = $tanggal . ' (sudah ada off request untuk tanggal ini)';
                continue;
            }

            $terisi = OffRequest::where('tanggal', $tanggal)
                ->where('jenis', 'form_request')
                ->where('kelompok_section', $kelompok)
                ->where('status_approval', 'diterima')
                ->count();

            if ($terisi >= self::KUOTA_PER_KELOMPOK) {
                $tanggalGagal[] = $tanggal . ' (kuota penuh)';
                continue;
            }

            OffRequest::create([
                'agent_id'         => $agent->id,
                'tanggal'          => $tanggal,
                'jenis'            => 'form_request',
                'status_approval'  => 'diterima',
                'kelompok_section' => $kelompok,
            ]);
        }

        if (!empty($tanggalGagal)) {
            return back()->with('error', 'Sebagian tanggal gagal diajukan: ' . implode(', ', $tanggalGagal))
                ->with('success', count($validated['tanggal']) > count($tanggalGagal)
                    ? 'Tanggal lainnya berhasil diajukan.'
                    : null);
        }

        return back()->with('success', 'Form Request berhasil diajukan untuk semua tanggal yang dipilih.');
    }

    /**
     * Hapus Form Request milik sendiri. Tanpa konfirmasi tambahan di
     * level backend (konfirmasi dilakukan di frontend) -- PIC baru
     * benar-benar membutuhkan data ini saat generate jadwal, sehingga
     * pembatalan sebelum itu tidak berisiko mengganggu proses apa pun.
     */
    public function destroyFormRequest(OffRequest $offRequest)
    {
        $agent = Auth::user()->agent;

        if (!$agent || $offRequest->agent_id !== $agent->id || $offRequest->jenis !== 'form_request') {
            abort(403);
        }

        $offRequest->delete();

        return back()->with('success', 'Form Request berhasil dihapus.');
    }

    /**
     * Ajukan Request Prioritas Tinggi (Jalur 2) -- wajib alasan, tidak
     * ada kuota, status awal 'pending' menunggu keputusan PIC.
     */
    public function storePrioritasTinggi(Request $request)
    {
        $agent = Auth::user()->agent;

        if (!$agent) {
            abort(403);
        }

        $validated = $request->validate([
            'tanggal'    => ['required', 'date', 'after_or_equal:today'],
            'keterangan' => ['required', 'string', 'min:10', 'max:500'],
        ]);

        // Cegah pengajuan jika jadwal periode yang mencakup tanggal ini
        // sudah final — off request tidak akan bisa dipakai lagi oleh GA
        // karena periode sudah dikunci.
        $jadwalSudahFinal = \App\Models\Schedule::where('agent_id', $agent->id)
            ->where('tanggal', $validated['tanggal'])
            ->whereHas('schedulePeriod', fn ($q) => $q->where('status', 'final'))
            ->exists();

        if ($jadwalSudahFinal) {
            return back()->with('error', 'Tidak dapat mengajukan off request untuk tanggal ini karena jadwal periode tersebut sudah final. Hubungi PIC untuk perubahan jadwal.');
        }

        // Cegah agent mengajukan off request lebih dari sekali untuk
        // tanggal yang sama, TERLEPAS jenisnya (form_request maupun
        // prioritas_tinggi) -- konsisten dengan aturan di storeFormRequest.
        $sudahAda = OffRequest::where('agent_id', $agent->id)
            ->where('tanggal', $validated['tanggal'])
            ->exists();

        if ($sudahAda) {
            return back()->with('error', 'Kamu sudah punya off request untuk tanggal tersebut.');
        }

        OffRequest::create([
            'agent_id'        => $agent->id,
            'tanggal'         => $validated['tanggal'],
            'jenis'           => 'prioritas_tinggi',
            'status_approval' => 'pending',
            'keterangan'      => $validated['keterangan'],
        ]);

        // Kirim notifikasi ke semua akun PIC
        $offReqBaru = OffRequest::where('agent_id', $agent->id)
            ->where('tanggal', $validated['tanggal'])
            ->where('jenis', 'prioritas_tinggi')
            ->latest()->first();

        if ($offReqBaru) {
            User::where('role', 'pic')->each(function ($pic) use ($offReqBaru) {
                $pic->notify(new OffRequestBaruMasuk($offReqBaru));
            });
        }

        return back()->with('success', 'Request prioritas tinggi berhasil diajukan, menunggu keputusan PIC.');
    }

    /**
     * Tentukan kelompok section yang berlaku untuk agent ini. Level 1&2
     * otomatis masuk 'inbound_informasi'. Level 3 otomatis masuk
     * 'website_chat'. Tidak ada level yang bisa memilih bebas -- ini
     * murni untuk keperluan kuota Form Request, TIDAK mempengaruhi
     * eligibility section saat GA generate jadwal (level 3 tetap eligible
     * ke semua section sesuai aturan HC3 yang sudah ada).
     */
    protected function resolveKelompokSection(Agent $agent): string
    {
        return $agent->level == 3 ? 'website_chat' : 'inbound_informasi';
    }
}
