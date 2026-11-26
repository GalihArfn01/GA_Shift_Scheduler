{{-- Badge status + stepper proses tukar jadwal. Wajib menerima variabel: $status --}}
@php
    $rejected = in_array($status, ['ditolak_rekan', 'ditolak_pic']);
    $label = match ($status) {
        'menunggu_rekan' => 'Menunggu Rekan',
        'menunggu_pic'   => 'Menunggu PIC',
        'disetujui_pic'  => 'Disetujui',
        'ditolak_rekan'  => 'Ditolak Rekan',
        'ditolak_pic'    => 'Ditolak PIC',
        default          => str_replace('_', ' ', ucwords(str_replace('_', ' ', $status))),
    };
    $badgeStyle = match ($status) {
        'menunggu_rekan' => 'background-color:#FFFBEB;color:#D97706;border:1px solid #FDE68A;',
        'menunggu_pic'   => 'background-color:#FFF1F0;color:#C01A12;border:1px solid #FFC5C0;',
        'disetujui_pic'  => 'background-color:#ECFDF5;color:#047857;border:1px solid #A7F3D0;',
        default          => 'background-color:#FEF2F2;color:#B91C1C;border:1px solid #FECACA;',
    };
    $steps = [
        ['key' => 'menunggu_rekan', 'nama' => 'Rekan'],
        ['key' => 'menunggu_pic',   'nama' => 'PIC'],
        ['key' => 'disetujui_pic',  'nama' => 'Selesai'],
    ];
    $currentIdx = array_search($status, array_column($steps, 'key'));
@endphp

<div class="flex flex-col items-center gap-2">
    <span class="inline-flex px-3 py-1 text-[10px] font-bold rounded-full uppercase tracking-wider"
          style="{{ $badgeStyle }}">
        {{ $label }}
    </span>

    @if (!$rejected)
        <div class="flex items-center">
            @foreach ($steps as $i => $step)
                @if (!$loop->first)
                    <span class="w-4 h-px mx-0.5" style="background-color:{{ $i <= $currentIdx ? '#10B981' : '#D2D3D6' }};"></span>
                @endif
                @php
                    $done   = $i < $currentIdx || ($status === 'disetujui_pic' && $i === $currentIdx);
                    $active = $i === $currentIdx && $status !== 'disetujui_pic';
                @endphp
                <span class="flex flex-col items-center gap-0.5">
                    <span class="w-2 h-2 rounded-full"
                          style="background-color:{{ $done ? '#10B981' : ($active ? '#E2231A' : '#D2D3D6') }};"></span>
                    <span class="text-[8px] font-bold uppercase tracking-wide"
                          style="color:{{ $done ? '#059669' : ($active ? '#E2231A' : '#AEB0B6') }};">{{ $step['nama'] }}</span>
                </span>
            @endforeach
        </div>
    @endif
</div>
