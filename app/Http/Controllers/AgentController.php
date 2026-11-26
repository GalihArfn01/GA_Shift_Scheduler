<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AgentController extends Controller
{
    /**
     * Tampilkan daftar semua agent.
     */
    public function index(Request $request)
    {
        $query = Agent::query();

        if ($request->filled('search')) {
            $search = '%' . $request->search . '%';
            $query->where(function ($q) use ($search) {
                $q->where('nama_agent', 'like', $search)
                  ->orWhereHas('user', function ($qUser) use ($search) {
                      $qUser->where('email', 'like', $search);
                  });
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('level')) {
            $query->where('level', $request->level);
        }

        if ($request->filled('jenis_kelamin')) {
            $query->where('jenis_kelamin', $request->jenis_kelamin);
        }

        $agents = $query
            ->with('user:id,email')
            ->orderBy('level')
            ->orderBy('nama_agent')
            ->paginate(10)
            ->withQueryString();

        return view('agents.index', [
            'agents' => $agents,
        ]);
    }

    /**
     * Simpan agent baru.
     */
    /** Password default untuk akun agent baru -- sama dengan yang
     *  dipakai AgentUserSeeder, supaya konsisten. Agent dianjurkan
     *  (tidak dipaksa) mengganti ini sendiri lewat halaman Profile.
     */
    protected const DEFAULT_PASSWORD = 'password123';

    public function store(Request $request)
    {
        // Select mengirim "" (string kosong) saat "Belum diisi" dipilih;
        // ubah ke null supaya lolos validasi nullable + Rule::in.
        $request->merge(['jenis_kelamin' => $request->input('jenis_kelamin') ?: null]);

        $validated = $request->validate([
            'nama_agent'    => ['required', 'string', 'max:255'],
            'level'         => ['required', 'in:0,1,2,3'],
            'status'        => ['required', Rule::in(['aktif', 'skorsing'])],
            'jenis_kelamin' => ['nullable', Rule::in(['L', 'P'])],
            'email'         => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
        ]);

        $user = User::create([
            'name'     => $validated['nama_agent'],
            'email'    => $validated['email'],
            'password' => self::DEFAULT_PASSWORD,
            'role'     => 'agent',
        ]);

        Agent::create([
            'nama_agent'    => $validated['nama_agent'],
            'level'         => $validated['level'],
            'status'        => $validated['status'],
            'jenis_kelamin' => $validated['jenis_kelamin'] ?? null,
            'user_id'       => $user->id,
        ]);

        return redirect()->route('agents.index')
            ->with('success', "Agent berhasil ditambahkan. Akun login: {$validated['email']}, password default: " . self::DEFAULT_PASSWORD);
    }

    /**
     * Update data agent yang sudah ada.
     */
    public function update(Request $request, Agent $agent)
    {
        $request->merge(['jenis_kelamin' => $request->input('jenis_kelamin') ?: null]);

        $validated = $request->validate([
            'nama_agent'    => ['required', 'string', 'max:255'],
            'level'         => ['required', 'in:0,1,2,3'],
            'status'        => ['required', Rule::in(['aktif', 'skorsing'])],
            'jenis_kelamin' => ['nullable', Rule::in(['L', 'P'])],
        ]);

        $validated['jenis_kelamin'] = $validated['jenis_kelamin'] ?? null;

        $agent->update($validated);

        return redirect()->route('agents.index')
            ->with('success', 'Data agent berhasil diperbarui.');
    }

    /**
     * Hapus agent.
     */
    public function destroy(Agent $agent)
    {
        // Soft delete akun User juga -- agent yang resign tidak boleh
        // bisa login lagi, konsisten dengan soft delete Agent itu sendiri.
        $agent->user?->delete();

        $agent->delete();

        return redirect()->route('agents.index')
            ->with('success', 'Agent berhasil dihapus. Akun login miliknya juga otomatis dinonaktifkan.');
    }

    /**
     * Toggle status agent antara 'aktif' dan 'skorsing'.
     * Dipanggil langsung dari tombol di tabel, tanpa form/modal.
     */
    public function toggleStatus(Agent $agent)
    {
        $agent->update([
            'status' => $agent->status === 'aktif' ? 'skorsing' : 'aktif',
        ]);

        if (request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'status' => $agent->status,
                'message' => "Status {$agent->nama_agent} berhasil diubah menjadi {$agent->status}."
            ]);
        }

        return redirect()->route('agents.index')
            ->with('success', "Status {$agent->nama_agent} berhasil diubah menjadi {$agent->status}.");
    }
}
