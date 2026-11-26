<?php

use App\Models\Agent;
use App\Models\OffRequest;
use App\Models\OperationalRequirement;
use App\Models\SchedulePeriod;
use App\Models\Section;
use App\Models\Shift;
use App\Services\GeneticAlgorithmScheduler;

function makeSection(string $nama): Section
{
    return Section::create(['nama_section' => $nama]);
}

function makeShift(string $kode, string $masuk, string $pulang): Shift
{
    return Shift::create(['kode_shift' => $kode, 'jam_masuk' => $masuk, 'jam_pulang' => $pulang]);
}

function makeAgent(string $nama, int $level, string $status = 'aktif'): Agent
{
    return Agent::create(['nama_agent' => $nama, 'level' => (string) $level, 'status' => $status]);
}

function makePeriod(): SchedulePeriod
{
    return SchedulePeriod::create([
        'nama_periode' => 'Periode Test',
        'tanggal_mulai' => '2026-08-03', // Senin
        'tanggal_selesai' => '2026-08-09', // Minggu
        'status' => 'draft',
    ]);
}

function makeRequirement(SchedulePeriod $period, string $hari, Shift $shift, Section $section, int $jumlah): OperationalRequirement
{
    return OperationalRequirement::create([
        'schedule_period_id' => $period->id,
        'hari_ke' => $hari,
        'shift_id' => $shift->id,
        'section_id' => $section->id,
        'jumlah_kebutuhan' => $jumlah,
    ]);
}

/**
 * Akses method/property protected lewat reflection untuk unit test.
 */
function schedulerReflection(): array
{
    $scheduler = new GeneticAlgorithmScheduler;

    return [
        'scheduler' => $scheduler,
        'ref' => new ReflectionClass($scheduler),
    ];
}

function invokeProtected(ReflectionClass $ref, object $obj, string $method, mixed ...$args): mixed
{
    $m = $ref->getMethod($method);
    $m->setAccessible(true);

    return $m->invokeArgs($obj, $args);
}

function setProtected(ReflectionClass $ref, object $obj, string $property, mixed $value): void
{
    $p = $ref->getProperty($property);
    $p->setAccessible(true);
    $p->setValue($obj, $value);
}

test('GA menghasilkan kromosom tanpa double-booking dan hanya menugaskan agent aktif', function () {
    $inbound = makeSection('Inbound');
    $website = makeSection('Website');
    $chat = makeSection('Chat');

    $shiftB = makeShift('B', '09:00:00', '17:00:00');
    $shiftD = makeShift('D', '11:00:00', '19:00:00');
    $shiftA = makeShift('A', '08:00:00', '16:00:00');

    $l1a = makeAgent('L1-A', 1);
    $l1b = makeAgent('L1-B', 1);
    $l2a = makeAgent('L2-A', 2);
    $l2b = makeAgent('L2-B', 2);
    $l3a = makeAgent('L3-A', 3);
    $l3b = makeAgent('L3-B', 3);
    $skorsing = makeAgent('SKORSING', 3, 'skorsing');

    $period = makePeriod();

    makeRequirement($period, 'senin', $shiftB, $inbound, 2);
    makeRequirement($period, 'senin', $shiftD, $website, 1);
    makeRequirement($period, 'selasa', $shiftA, $chat, 1);

    $result = (new GeneticAlgorithmScheduler)->run($period);

    expect($result['total_slots'])->toBe(4);
    expect($result['chromosome'])->toHaveCount(4);

    $assignedPerAgentDay = [];

    foreach ($result['chromosome'] as $gene) {
        if ($gene['agent_id'] === null) {
            continue;
        }

        $key = $gene['agent_id'].'-'.$gene['hari_index'];

        expect(isset($assignedPerAgentDay[$key]))
            ->toBeFalse('terdeteksi double-booking: agent ditugaskan 2x pada hari yang sama');

        $assignedPerAgentDay[$key] = true;

        expect($gene['agent_id'])->not->toBe($skorsing->id);
    }
});

test('penugasan menghormati HC3 (level sesuai section) dan HC6 (shift A7/A hanya Chat)', function () {
    $inbound = makeSection('Inbound');
    $website = makeSection('Website');
    $chat = makeSection('Chat');
    $informasi = makeSection('Informasi');

    $shiftA = makeShift('A', '08:00:00', '16:00:00');
    $shiftA7 = makeShift('A7', '07:00:00', '15:00:00');
    $shiftB = makeShift('B', '09:00:00', '17:00:00');

    foreach (range(1, 4) as $i) {
        makeAgent("L1-$i", 1);
    }
    foreach (range(1, 4) as $i) {
        makeAgent("L2-$i", 2);
    }
    foreach (range(1, 4) as $i) {
        makeAgent("L3-$i", 3);
    }

    $period = makePeriod();

    // Website hanya boleh level 3
    makeRequirement($period, 'senin', $shiftB, $website, 3);
    // Chat + A7 hanya boleh level 3
    makeRequirement($period, 'selasa', $shiftA7, $chat, 2);

    $result = (new GeneticAlgorithmScheduler)->run($period);

    expect($result['unfilled_count'])->toBe(0);

    $agentsById = Agent::pluck('level', 'id');
    $sectionNamaById = Section::pluck('nama_section', 'id');
    $shiftKodeById = Shift::pluck('kode_shift', 'id');

    foreach ($result['chromosome'] as $gene) {
        if ($gene['agent_id'] === null) {
            continue;
        }

        $level = (int) $agentsById[$gene['agent_id']];
        $section = strtolower($sectionNamaById[$gene['section_id']]);
        $shiftKode = $shiftKodeById[$gene['shift_id']];

        $levelAllowed = match ($section) {
            'inbound' => in_array($level, [1, 2, 3], true),
            'informasi' => in_array($level, [2, 3], true),
            'website' => $level === 3,
            'chat' => $level === 3,
            default => false,
        };

        expect($levelAllowed)->toBeTrue("level {$level} tidak boleh di section {$section}");

        if (in_array($shiftKode, ['A7', 'A'], true)) {
            expect($section)->toBe('chat', 'shift A7/A hanya boleh di section Chat');
        }
    }
});

test('off request prioritas tinggi membuat agent tidak eligible pada hari tersebut', function () {
    $inbound = makeSection('Inbound');
    $shiftB = makeShift('B', '09:00:00', '17:00:00');

    $offAgent = makeAgent('L3-Off', 3);
    makeAgent('L3-Lain', 3);

    $period = makePeriod();

    OffRequest::create([
        'agent_id' => $offAgent->id,
        'tanggal' => '2026-08-03',
        'jenis' => 'prioritas_tinggi',
        'status_approval' => 'diterima',
    ]);

    [$scheduler, $ref] = array_values(schedulerReflection());
    setProtected($ref, $scheduler, 'period', $period);
    invokeProtected($ref, $scheduler, 'loadData');

    $slot = [
        'hari_index' => 0,
        'shift_id' => $shiftB->id,
        'section_id' => $inbound->id,
        'slot_ke' => 0,
    ];

    $eligible = invokeProtected($ref, $scheduler, 'getEligibleAgentIds', $slot);

    expect($eligible)->not->toContain($offAgent->id);
});

test('buildOffRequestsIndex hanya mempertimbangkan off request berstatus diterima', function () {
    $inbound = makeSection('Inbound');
    $shiftB = makeShift('B', '09:00:00', '17:00:00');

    $diterima = makeAgent('L3-Diterima', 3);
    $pending = makeAgent('L3-Pending', 3);
    $ditolak = makeAgent('L3-Ditolak', 3);

    $period = makePeriod();

    OffRequest::create(['agent_id' => $diterima->id, 'tanggal' => '2026-08-03', 'jenis' => 'prioritas_tinggi', 'status_approval' => 'diterima']);
    OffRequest::create(['agent_id' => $pending->id, 'tanggal' => '2026-08-03', 'jenis' => 'prioritas_tinggi', 'status_approval' => 'pending']);
    OffRequest::create(['agent_id' => $ditolak->id, 'tanggal' => '2026-08-03', 'jenis' => 'prioritas_tinggi', 'status_approval' => 'ditolak']);

    [$scheduler, $ref] = array_values(schedulerReflection());
    setProtected($ref, $scheduler, 'period', $period);
    invokeProtected($ref, $scheduler, 'loadData');

    $index = invokeProtected($ref, $scheduler, 'buildOffRequestsIndex');

    expect($index)->toHaveCount(1);
    expect($index)->toHaveKey($diterima->id.'-0');
    expect($index)->not->toHaveKey($pending->id.'-0');
    expect($index)->not->toHaveKey($ditolak->id.'-0');
});

test('repairChromosome menghapus double-booking', function () {
    $inbound = makeSection('Inbound');
    $shiftB = makeShift('B', '09:00:00', '17:00:00');

    $a = makeAgent('L1-A', 1);
    $b = makeAgent('L1-B', 1);

    $period = makePeriod();

    [$scheduler, $ref] = array_values(schedulerReflection());
    setProtected($ref, $scheduler, 'period', $period);
    invokeProtected($ref, $scheduler, 'loadData');

    // Dua gen double-booking: agent yang sama di hari yang sama
    $chromosome = [
        ['hari_index' => 0, 'shift_id' => $shiftB->id, 'section_id' => $inbound->id, 'slot_ke' => 0, 'agent_id' => $a->id],
        ['hari_index' => 0, 'shift_id' => $shiftB->id, 'section_id' => $inbound->id, 'slot_ke' => 1, 'agent_id' => $a->id],
        ['hari_index' => 1, 'shift_id' => $shiftB->id, 'section_id' => $inbound->id, 'slot_ke' => 0, 'agent_id' => $b->id],
    ];

    $repaired = invokeProtected($ref, $scheduler, 'repairChromosome', $chromosome);

    $assigned = [];
    foreach ($repaired as $gene) {
        if ($gene['agent_id'] === null) {
            continue;
        }
        $key = $gene['agent_id'].'-'.$gene['hari_index'];
        expect(isset($assigned[$key]))->toBeFalse();
        $assigned[$key] = true;
    }
});
