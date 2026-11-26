<?php

use App\Models\Agent;
use App\Models\Schedule;
use App\Models\SchedulePeriod;
use App\Models\Section;
use App\Models\Shift;
use App\Models\ShiftSwap;
use App\Models\User;

function swapMakeSection(string $nama): Section
{
    return Section::create(['nama_section' => $nama]);
}

function swapMakeShift(string $kode): Shift
{
    return Shift::create(['kode_shift' => $kode, 'jam_masuk' => '09:00:00', 'jam_pulang' => '17:00:00']);
}

function swapMakeAgent(string $nama): array
{
    $user = User::create([
        'name' => $nama,
        'email' => strtolower($nama).'@test.com',
        'password' => 'secret',
        'role' => 'agent',
    ]);

    $agent = Agent::create([
        'nama_agent' => $nama,
        'level' => '1',
        'status' => 'aktif',
        'user_id' => $user->id,
    ]);

    return ['user' => $user, 'agent' => $agent];
}

function swapMakePeriod(): SchedulePeriod
{
    $mulai = today()->toDateString();

    return SchedulePeriod::create([
        'nama_periode' => 'Periode Swap',
        'tanggal_mulai' => $mulai,
        'tanggal_selesai' => today()->addDays(6)->toDateString(),
        'status' => 'final',
    ]);
}

function swapMakeSchedule(SchedulePeriod $period, Agent $agent, Shift $shift, Section $section, string $tanggal): Schedule
{
    return Schedule::create([
        'schedule_period_id' => $period->id,
        'agent_id' => $agent->id,
        'tanggal' => $tanggal,
        'shift_id' => $shift->id,
        'section_id' => $section->id,
        'status' => 'kerja',
    ]);
}

test('getResponderSchedules menolak akses jadwal milik agent lain', function () {
    [$userA, $agentA] = array_values(swapMakeAgent('Alpha'));
    [$userB, $agentB] = array_values(swapMakeAgent('Bravo'));

    $section = swapMakeSection('Inbound');
    $shift = swapMakeShift('B');
    $period = swapMakePeriod();

    $schedA = swapMakeSchedule($period, $agentA, $shift, $section, today()->addDays(1)->toDateString());
    $schedB = swapMakeSchedule($period, $agentB, $shift, $section, today()->addDays(2)->toDateString());

    // User A mencoba membuka jadwal milik B sebagai basis pencarian -> 403.
    $this->actingAs($userA)
        ->get(route('agent.shift-swaps.get-responder-schedules', [
            'my_schedule_id' => $schedB->id,
            'responder_id' => $agentB->id,
        ]))
        ->assertForbidden();
});

test('getResponderSchedules hanya mengembalikan jadwal rekan yang tidak bentrok', function () {
    [$userA, $agentA] = array_values(swapMakeAgent('Alpha'));
    [$userB, $agentB] = array_values(swapMakeAgent('Bravo'));

    $section = swapMakeSection('Inbound');
    $shift = swapMakeShift('B');
    $period = swapMakePeriod();

    $tanggalA = today()->addDays(1)->toDateString();
    $tanggalB = today()->addDays(2)->toDateString();

    $schedA = swapMakeSchedule($period, $agentA, $shift, $section, $tanggalA);
    $schedB = swapMakeSchedule($period, $agentB, $shift, $section, $tanggalB);

    // Agent A juga sudah kerja di tanggal B -> jadwal B tidak boleh ditawarkan.
    swapMakeSchedule($period, $agentA, $shift, $section, $tanggalB);

    $response = $this->actingAs($userA)
        ->get(route('agent.shift-swaps.get-responder-schedules', [
            'my_schedule_id' => $schedA->id,
            'responder_id' => $agentB->id,
        ]));

    $response->assertOk();

    $json = $response->json();

    expect($json)->toBe([]);
});

test('updateApproval menolak persetujuan ketika salah satu jadwal sudah dihapus', function () {
    $pic = User::create(['name' => 'PIC', 'email' => 'pic@test.com', 'password' => 'secret', 'role' => 'pic']);

    [$userA, $agentA] = array_values(swapMakeAgent('Alpha'));
    [$userB, $agentB] = array_values(swapMakeAgent('Bravo'));

    $section = swapMakeSection('Inbound');
    $shift = swapMakeShift('B');
    $period = swapMakePeriod();

    $schedA = swapMakeSchedule($period, $agentA, $shift, $section, today()->addDays(1)->toDateString());
    $schedB = swapMakeSchedule($period, $agentB, $shift, $section, today()->addDays(2)->toDateString());

    $swap = ShiftSwap::create([
        'schedule_period_id' => $period->id,
        'requester_id' => $agentA->id,
        'responder_id' => $agentB->id,
        'requester_schedule_id' => $schedA->id,
        'responder_schedule_id' => $schedB->id,
        'status' => 'menunggu_pic',
        'alasan' => 'Uji coba',
    ]);

    // Jadwal B dihapus sebelum PIC memutuskan. FK schedule pada shift_swaps
    // memakai cascadeOnDelete, sehingga swap ikut terhapus -> 404.
    $schedB->delete();

    $this->actingAs($pic)
        ->from('/shift-swaps')
        ->post(route('pic.shift-swaps.update-approval', $swap), ['status' => 'disetujui_pic'])
        ->assertNotFound();

    // Status swap tidak berubah dan agent_id jadwal yang tersisa tetap.
    expect($schedA->fresh()->agent_id)->toBe($agentA->id);
    expect(ShiftSwap::find($swap->id))->toBeNull();
});

test('updateApproval menolak persetujuan jika ada tabrakan jadwal saat approve', function () {
    $pic = User::create(['name' => 'PIC', 'email' => 'pic@test.com', 'password' => 'secret', 'role' => 'pic']);

    [$userA, $agentA] = array_values(swapMakeAgent('Alpha'));
    [$userB, $agentB] = array_values(swapMakeAgent('Bravo'));

    $section = swapMakeSection('Inbound');
    $shift = swapMakeShift('B');
    $period = swapMakePeriod();

    $tanggalA = today()->addDays(1)->toDateString();
    $tanggalB = today()->addDays(2)->toDateString();

    $schedA = swapMakeSchedule($period, $agentA, $shift, $section, $tanggalA);
    $schedB = swapMakeSchedule($period, $agentB, $shift, $section, $tanggalB);

    // Agent A sudah kerja di tanggal B -> setelah swap akan double-booking.
    swapMakeSchedule($period, $agentA, $shift, $section, $tanggalB);

    $swap = ShiftSwap::create([
        'schedule_period_id' => $period->id,
        'requester_id' => $agentA->id,
        'responder_id' => $agentB->id,
        'requester_schedule_id' => $schedA->id,
        'responder_schedule_id' => $schedB->id,
        'status' => 'menunggu_pic',
        'alasan' => 'Uji coba',
    ]);

    $response = $this->actingAs($pic)
        ->from('/shift-swaps')
        ->post(route('pic.shift-swaps.update-approval', $swap), ['status' => 'disetujui_pic']);

    $response
        ->assertRedirect('/shift-swaps')
        ->assertSessionHas('error');

    expect($swap->fresh()->status)->toBe('menunggu_pic');
    expect($schedA->fresh()->agent_id)->toBe($agentA->id);
    expect($schedB->fresh()->agent_id)->toBe($agentB->id);
});

test('updateApproval menyetujui tukar jadwal dan menukar agent_id', function () {
    $pic = User::create(['name' => 'PIC', 'email' => 'pic@test.com', 'password' => 'secret', 'role' => 'pic']);

    [$userA, $agentA] = array_values(swapMakeAgent('Alpha'));
    [$userB, $agentB] = array_values(swapMakeAgent('Bravo'));

    $section = swapMakeSection('Inbound');
    $shift = swapMakeShift('B');
    $period = swapMakePeriod();

    $schedA = swapMakeSchedule($period, $agentA, $shift, $section, today()->addDays(1)->toDateString());
    $schedB = swapMakeSchedule($period, $agentB, $shift, $section, today()->addDays(2)->toDateString());

    $swap = ShiftSwap::create([
        'schedule_period_id' => $period->id,
        'requester_id' => $agentA->id,
        'responder_id' => $agentB->id,
        'requester_schedule_id' => $schedA->id,
        'responder_schedule_id' => $schedB->id,
        'status' => 'menunggu_pic',
        'alasan' => 'Uji coba',
    ]);

    $this->actingAs($pic)
        ->from('/shift-swaps')
        ->post(route('pic.shift-swaps.update-approval', $swap), ['status' => 'disetujui_pic'])
        ->assertRedirect('/shift-swaps')
        ->assertSessionHas('success');

    expect($swap->fresh()->status)->toBe('disetujui_pic');
    expect($schedA->fresh()->agent_id)->toBe($agentB->id);
    expect($schedB->fresh()->agent_id)->toBe($agentA->id);
});
