<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Absensi;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;


class AbsensiController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        $bulan = $request->input('bulan', now()->format('Y-m'));
        $nama = $request->input('nama');
        $mingguAktif = (int) $request->input('minggu', 1);
        $kategori = $request->input('kategori', 'apel_ggs');

        $year = substr($bulan, 0, 4);
        $month = substr($bulan, 5, 2);

        $firstDayOfSelectedMonth = Carbon::createFromDate($year, $month, 1)->startOfMonth();

        $firstSundayOfRelevantPeriod = $firstDayOfSelectedMonth->copy()->startOfWeek(Carbon::SUNDAY);

        $currentWeekStart = $firstSundayOfRelevantPeriod->copy()->addWeeks($mingguAktif - 1);
        $currentWeekEnd = $currentWeekStart->copy()->endOfWeek(Carbon::SATURDAY);

        $jumlahMinggu = $this->getJumlahMingguDalamBulan($year, $month);

        $absensiDataUntukMingguIni = Absensi::whereBetween('tanggal', [$currentWeekStart->format('Y-m-d'), $currentWeekEnd->format('Y-m-d')])
            ->get();

        $userIdsDenganAbsensi = $absensiDataUntukMingguIni->pluck('user_id')->unique();

        $usersQuery = User::whereIn('role', ['santri', 'penerobos'])
            ->whereIn('id', $userIdsDenganAbsensi);

        switch ($kategori) {
            case 'apel_ggs':
                $usersQuery->where('jenis_kelamin', 'laki-laki');
                break;
            case 'apel_qa':
                $usersQuery->where('jenis_kelamin', 'perempuan');
                break;
            case 'lambatan_ggs':
                $usersQuery->where('jenis_kelamin', 'laki-laki')->where('kelas', 'lambatan');
                break;
            case 'lambatan_qa':
                $usersQuery->where('jenis_kelamin', 'perempuan')->where('kelas', 'lambatan');
                break;
            case 'cepatan':
                $usersQuery->where('kelas', 'cepatan');
                break;
            case 'mt':
                $usersQuery->where('kelas', 'mt');
                break;
            default:
                $usersQuery->where('jenis_kelamin', 'laki-laki');
                break;
        }

        if ($nama) {
            $usersQuery->where('full_name', 'like', '%' . $nama . '%');
        }

        $users = $usersQuery->orderBy('full_name')->get();

        $tanggalInfo = $this->getTanggalHariDalamMinggu($year, $month, $mingguAktif);

        $absensi = $users->map(function ($user) use ($absensiDataUntukMingguIni, $currentWeekStart) {
            $data = [
                'user' => $user,
                'minggu' => '-', 'minggu_m' => '-',
                'senin' => '-', 'senin_m' => '-',
                'selasa' => '-', 'selasa_m' => '-',
                'rabu' => '-', 'rabu_m' => '-',
                'kamis' => '-', 'kamis_m' => '-',
                'jumat' => '-', 'jumat_m' => '-',
                'sabtu' => '-',
            ];

            $hariMapping = [
                0 => 'minggu',
                1 => 'senin',
                2 => 'selasa',
                3 => 'rabu',
                4 => 'kamis',
                5 => 'jumat',
                6 => 'sabtu',
            ];

            $currentUserAbsensi = $absensiDataUntukMingguIni->where('user_id', $user->id);

            for ($i = 0; $i < 7; $i++) {
                $tanggalHariLoop = $currentWeekStart->copy()->addDays($i)->format('Y-m-d');
                $namaHariClean = $hariMapping[$i];

                $absenHariIni = $currentUserAbsensi->filter(function ($absenRecord) use ($tanggalHariLoop) {
                    return $absenRecord->tanggal == $tanggalHariLoop;
                });

                foreach ($absenHariIni as $absen) {
                    if ($absen->kegiatan === 'Ngaji Subuh' || $absen->kegiatan === 'Apel') {
                        $data[$namaHariClean] = $absen->status;
                    } elseif ($absen->kegiatan === 'Ngaji Maghrib') {
                        if ($namaHariClean === 'sabtu') {
                            $data['sabtu_m'] = $absen->status;
                        } else {
                            $data[$namaHariClean . '_m'] = $absen->status;
                        }
                    }
                }
            }
            return (object)$data;
        });

        $bolehTambahAbsensi = ($user->role === 'masteradmin') ||
                              ($user->role === 'penerobos' && $user->boleh_tambah_absen);

        return view('absensi.index', compact(
            'absensi',
            'jumlahMinggu',
            'bulan',
            'nama',
            'kategori',
            'mingguAktif',
            'tanggalInfo',
            'bolehTambahAbsensi'
        ));
    }

    private function getJumlahMingguDalamBulan(int $year, int $month): int
    {
        $startOfMonth = Carbon::createFromDate($year, $month, 1);
        $endOfMonth = $startOfMonth->copy()->endOfMonth();

        $firstSundayOfPeriod = $startOfMonth->copy()->startOfWeek(Carbon::SUNDAY);

        $lastSaturdayOfPeriod = $endOfMonth->copy()->endOfWeek(Carbon::SATURDAY);

        return $firstSundayOfPeriod->diffInWeeks($lastSaturdayOfPeriod) + 1;
    }

    private function getTanggalHariDalamMinggu(int $year, int $month, int $mingguKe): array
    {
        $startOfMonth = Carbon::createFromDate($year, $month, 1);

        $calendarFirstSunday = $startOfMonth->copy()->startOfWeek(Carbon::SUNDAY);

        $currentWeekStart = $calendarFirstSunday->copy()->addWeeks($mingguKe - 1);
        $currentWeekEnd = $currentWeekStart->copy()->endOfWeek(Carbon::SATURDAY);

        $tanggalHari = [];
        for ($i = 0; $i < 7; $i++) {
            $tanggalHari[] = (clone $currentWeekStart)->addDays($i)->format('d');
        }

        $rentangTanggal = $currentWeekStart->format('d M') . ' - ' . $currentWeekEnd->format('d M Y');

        return [
            'tanggalHari' => $tanggalHari,
            'rentang' => $rentangTanggal
        ];
    }

    public function create(Request $request)
    {
        $kategori = $request->input('kategori', 'lambatan_qa');
        $kegiatan = $request->input('kegiatan', 'Ngaji Subuh');

        $allRelevantUsers = User::whereIn('role', ['santri', 'penerobos'])
                                 ->orderBy('full_name')
                                 ->get();

        $absensiDataBulanan = Absensi::whereYear('tanggal', now()->year)
                                     ->whereMonth('tanggal', now()->month)
                                     ->get();

        return view('absensi.create', compact('kategori', 'kegiatan', 'allRelevantUsers', 'absensiDataBulanan'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'kategori' => 'required|string',
            'kegiatan' => 'required|string',
            'tanggal' => 'required|date',
            'attendances' => 'required|array',
            'attendances.*.user_id' => 'required|exists:users,id',
            'attendances.*.kelas' => 'required|string|in:cepatan,lambatan,mt',
            'attendances.*.status' => 'required|in:hadir,telat,izin,alpha,sakit',
            'attendances.*.keterangan' => 'nullable|string',
        ]);

        $tanggal = $request->input('tanggal');
        $kegiatan = $request->input('kegiatan');
        $kategoriAbsensi = $request->input('kategori');

        try {
            foreach ($request->input('attendances') as $attendanceData) {
                $userId = $attendanceData['user_id'];
                $status = $attendanceData['status'];
                $kelas = $attendanceData['kelas'];
                $keterangan = $attendanceData['keterangan'] ?? null;

                Absensi::updateOrCreate(
                    [
                        'user_id' => $userId,
                        'tanggal' => $tanggal,
                        'kegiatan' => $kegiatan,
                    ],
                    [
                        'status' => $status,
                        'kelas' => $kelas,
                        'keterangan' => $keterangan,
                        'kategori_absensi' => $kategoriAbsensi,
                    ]
                );
            }

            return redirect()->back()->with('success', 'Absensi berhasil disimpan!');

        } catch (\Exception $e) {
            Log::error('Error saving absensi: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return redirect()->back()->with('error', 'Gagal simpan absensi! Silakan coba lagi. Pesan Error: ' . $e->getMessage());
        }
    }

    private function getKelasOptions($kategori)
    {
        switch ($kategori) {
            case 'apel_ggs': return ['cepatan', 'lambatan', 'mt'];
            case 'apel_qa': return ['cepatan', 'lambatan', 'mt'];
            case 'lambatan_ggs': return ['lambatan'];
            case 'lambatan_qa': return ['lambatan'];
            case 'cepatan': return ['cepatan'];
            case 'mt': return ['mt'];
            default: return ['cepatan', 'lambatan', 'mt'];
        }
    }

    public function edit(Request $request, User $user)
    {
        $request->validate([
            'bulan' => 'required|string|date_format:Y-m',
            'minggu' => 'required|integer|min:1',
            'kategori' => 'required|string',
        ]);

        $bulanParam = $request->input('bulan');
        $mingguAktif = (int) $request->input('minggu');
        $kategori = $request->input('kategori');

        Carbon::setLocale('id');

        list($year, $month) = explode('-', $bulanParam);
        $year = (int) $year;
        $month = (int) $month;

        $startOfMonth = Carbon::create($year, $month, 1)->startOfDay();
        $firstSundayOfRelevantPeriod = $startOfMonth->copy()->startOfWeek(Carbon::SUNDAY);
        $currentWeekStart = $firstSundayOfRelevantPeriod->copy()->addWeeks($mingguAktif - 1);
        $currentWeekEnd = $currentWeekStart->copy()->endOfWeek(Carbon::SATURDAY);

        Log::info("--- DEBUG EDIT ABSENSI ---");
        Log::info("Parameters: UserID={$user->id}, Kategori={$kategori}, Bulan={$bulanParam}, Minggu={$mingguAktif}");
        Log::info("Calculated Week Range: {$currentWeekStart->format('Y-m-d')} to {$currentWeekEnd->format('Y-m-d')}");

        $kegiatanFilter = [];
        $kategoriAbsensiFilter = [$kategori];

        if (in_array($kategori, ['apel_ggs', 'apel_qa'])) {
            $kegiatanFilter = ['Apel'];
        } else {
            $kegiatanFilter = ['Ngaji Subuh', 'Ngaji Maghrib'];
        }

        Log::info("Applied Filters: Kegiatan=" . implode(', ', $kegiatanFilter) . ", Kategori_Absensi=" . implode(', ', $kategoriAbsensiFilter));

        $absensiRecords = Absensi::where('user_id', $user->id)
                                 ->whereBetween('tanggal', [$currentWeekStart->format('Y-m-d'), $currentWeekEnd->format('Y-m-d')])
                                 ->whereIn('kegiatan', $kegiatanFilter)
                                 ->whereIn('kategori_absensi', $kategoriAbsensiFilter)
                                 ->get()
                                 ->keyBy(function($item) {
                                     return Carbon::parse($item->tanggal)->format('Y-m-d') . '_' . $item->kegiatan;
                                 });

        Log::info("Absensi Records Fetched from DB (" . $absensiRecords->count() . " records): " . json_encode($absensiRecords->toArray()));

        $editData = [];
        $tempDate = $currentWeekStart->copy();

        for ($i = 0; $i < 7; $i++) {
            $dateKey = $tempDate->format('Y-m-d');
            $dayOfWeek = $tempDate->dayOfWeek;

            $editData[$dateKey] = [
                'tanggal_display' => $tempDate->translatedFormat('d M Y'),
                'hari' => strtolower($tempDate->translatedFormat('l')),
                'date_carbon' => $tempDate->copy(),
            ];

            if (in_array($kategori, ['apel_ggs', 'apel_qa'])) {
                if ($dayOfWeek !== Carbon::SUNDAY) {
                    $editData[$dateKey]['apel'] = ['status' => '-', 'id' => null];
                } else {
                    $editData[$dateKey]['apel'] = null;
                }
            } else {
                $editData[$dateKey]['ngaji_subuh'] = ['status' => '-', 'id' => null];
                $editData[$dateKey]['ngaji_maghrib'] = ['status' => '-', 'id' => null];

                if ($dayOfWeek === Carbon::SUNDAY) {
                    $editData[$dateKey]['ngaji_subuh'] = null;
                }
                if ($dayOfWeek === Carbon::SATURDAY) {
                    $editData[$dateKey]['ngaji_maghrib'] = null;
                }
            }
            $tempDate->addDay();
        }

        foreach ($editData as $date => &$dayData) {
            if (isset($dayData['apel']) && !is_null($dayData['apel'])) {
                $recordKey = $date . '_Apel';
                if ($absensiRecords->has($recordKey)) {
                    $record = $absensiRecords->get($recordKey);
                    $dayData['apel']['status'] = $record->status;
                    $dayData['apel']['id'] = $record->id;
                }
            }

            if (isset($dayData['ngaji_subuh']) && !is_null($dayData['ngaji_subuh'])) {
                $recordKey = $date . '_Ngaji Subuh';
                if ($absensiRecords->has($recordKey)) {
                    $record = $absensiRecords->get($recordKey);
                    $dayData['ngaji_subuh']['status'] = $record->status;
                    $dayData['ngaji_subuh']['id'] = $record->id;
                }
            }

            if (isset($dayData['ngaji_maghrib']) && !is_null($dayData['ngaji_maghrib'])) {
                $recordKey = $date . '_Ngaji Maghrib';
                if ($absensiRecords->has($recordKey)) {
                    $record = $absensiRecords->get($recordKey);
                    $dayData['ngaji_maghrib']['status'] = $record->status;
                    $dayData['ngaji_maghrib']['id'] = $record->id;
                }
            }
        }
        unset($dayData);

        Log::info("EditData Final (sent to Blade): " . json_encode($editData));

        $editDataJson = '{}';
        try {
            $editDataJson = json_encode($editData, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            Log::error("JSON encoding failed for \$editData in AbsensiController@edit: " . $e->getMessage(), ['exception' => $e]);
            session()->flash('error', 'Terjadi kesalahan teknis saat memuat data absensi. Mohon coba lagi.');
        } catch (\Exception $e) {
            Log::error("Unexpected error during JSON encoding of \$editData: " . $e->getMessage(), ['exception' => $e]);
            session()->flash('error', 'Terjadi kesalahan tidak terduga saat memuat data absensi.');
        }

        return view('absensi.edit', compact(
            'user',
            'bulanParam',
            'mingguAktif',
            'kategori',
            'currentWeekStart',
            'currentWeekEnd',
            'editDataJson'
        ));
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'status' => 'required|array',
            'status.*.*' => 'nullable|string|in:P,T,I,S,A,-',
            'id' => 'required|array',
            'id.*.*' => 'nullable|exists:absensi,id',
            'bulan' => 'required|date_format:Y-m',
            'minggu' => 'required|integer',
            'kategori' => 'required|string',
        ]);

        $bulan = $request->input('bulan');
        $mingguAktif = (int) $request->input('minggu');
        $kategori = $request->input('kategori');
        $statusData = $request->input('status');
        $idData = $request->input('id');

        DB::beginTransaction();
        try {
            foreach ($statusData as $date => $kegiatanStatuses) {
                $carbonDate = Carbon::parse($date);

                if (in_array($kategori, ['apel_ggs', 'apel_qa'])) {
                    if (isset($kegiatanStatuses['apel'])) {
                        $absensiId = !empty($idData[$date]['apel']) ? $idData[$date]['apel'] : null;
                        $this->updateOrCreateAbsensi(
                            $user->id,
                            $carbonDate,
                            'Apel',
                            $kategori,
                            $kegiatanStatuses['apel'],
                            $absensiId
                        );
                    }
                } else {
                    if (isset($kegiatanStatuses['ngaji_subuh'])) {
                        $absensiId = !empty($idData[$date]['ngaji_subuh']) ? $idData[$date]['ngaji_subuh'] : null;
                        $this->updateOrCreateAbsensi(
                            $user->id,
                            $carbonDate,
                            'Ngaji Subuh',
                            $kategori,
                            $kegiatanStatuses['ngaji_subuh'],
                            $absensiId
                        );
                    }
                    if (isset($kegiatanStatuses['ngaji_maghrib'])) {
                        $absensiId = !empty($idData[$date]['ngaji_maghrib']) ? $idData[$date]['ngaji_maghrib'] : null;
                        $this->updateOrCreateAbsensi(
                            $user->id,
                            $carbonDate,
                            'Ngaji Maghrib',
                            $kategori,
                            $kegiatanStatuses['ngaji_maghrib'],
                            $absensiId
                        );
                    }
                }
            }

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Absensi mingguan ' . ($user->full_name ?? $user->name) . ' berhasil diperbarui!',
                'redirect_url' => route('absensi.index', ['bulan' => $bulan, 'minggu' => $mingguAktif, 'kategori' => $kategori])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating weekly attendance for user ' . $user->id . ': ' . $e->getMessage(), ['exception' => $e]);
            return response()->json(['success' => false, 'message' => 'Gagal memperbarui absensi: ' . $e->getMessage()], 500);
        }
    }

    public function deleteDailyAbsensi(Request $request, User $user)
    {
        $request->validate([
            'dates_to_delete' => 'required|array',
            'dates_to_delete.*' => 'date_format:Y-m-d',
            'kategori' => 'required|string',
            'bulan' => 'required|date_format:Y-m',
            'minggu' => 'required|integer',
            'activities_to_delete' => 'nullable|array',
            'activities_to_delete.*' => 'string|in:Apel,Ngaji Subuh,Ngaji Maghrib',
        ]);

        $datesToDelete = $request->input('dates_to_delete');
        $kategoriAbsensiFromFrontend = $request->input('kategori');
        $activitiesToDelete = $request->input('activities_to_delete');

        Log::info("DELETE Request received for user {$user->id}. Dates: " . implode(', ', $datesToDelete) . ", Kategori: {$kategoriAbsensiFromFrontend}, Activities: " . json_encode($activitiesToDelete));

        if (empty($activitiesToDelete)) {
            if (in_array($kategoriAbsensiFromFrontend, ['apel_ggs', 'apel_qa'])) {
                $activitiesToDelete = ['Apel'];
                Log::info("Kategori Apel detected, activities_to_delete was empty, set to ['Apel'].");
            } else {
                return response()->json(['success' => false, 'message' => 'Pilih setidaknya satu kegiatan untuk dihapus.'], 400);
            }
        }

        DB::beginTransaction();
        try {
            $deletedCount = 0;
            foreach ($datesToDelete as $date) {
                Log::info("Attempting to delete for Date: {$date}, Kegiatan: " . implode(', ', $activitiesToDelete) . ", Kategori_Absensi: {$kategoriAbsensiFromFrontend}");

                $query = Absensi::where('user_id', $user->id)
                                 ->where('tanggal', $date)
                                 ->whereIn('kegiatan', $activitiesToDelete)
                                 ->where('kategori_absensi', $kategoriAbsensiFromFrontend);

                Log::info("Delete query builder: " . $query->toSql() . " with bindings: " . json_encode($query->getBindings()));

                $deletedCount += $query->delete();
            }

            DB::commit();
            Log::info("Delete successful. Total deleted records: {$deletedCount}");

            if ($deletedCount > 0) {
                return response()->json(['success' => true, 'message' => 'Absensi berhasil dihapus.']);
            } else {
                return response()->json(['success' => false, 'message' => 'Tidak ada absensi yang cocok ditemukan untuk dihapus.'], 404);
            }

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting daily attendance for user ' . $user->id . ': ' . $e->getMessage(), ['exception' => $e]);
            return response()->json(['success' => false, 'message' => 'Gagal menghapus absensi: ' . $e->getMessage()], 500);
        }
    }

    private function updateOrCreateAbsensi($userId, $date, $kegiatan, $kategoriAbsensi, $status, $absensiId = null)
    {
        if ($status === '') {
            $status = '-';
        }

        $statusMap = [
            'P' => 'hadir',
            'T' => 'telat',
            'I' => 'izin',
            'S' => 'sakit',
            'A' => 'alpha',
            '-' => '-',
        ];
        $formattedStatusForDb = $statusMap[$status] ?? $status;

        if ($formattedStatusForDb === '-') {
            if ($absensiId) {
                Absensi::destroy($absensiId);
                Log::info("Deleted Absensi record with ID: {$absensiId}");
            } else {
                Absensi::where([
                    'user_id' => $userId,
                    'tanggal' => $date->format('Y-m-d'),
                    'kegiatan' => $kegiatan,
                    'kategori_absensi' => $kategoriAbsensi,
                ])->delete();
                Log::info("Attempted to delete Absensi record for user {$userId} on {$date->format('Y-m-d')} for {$kegiatan} with '-' status (no specific ID).");
            }
        } else {
            Absensi::updateOrCreate(
                array_filter([
                    'id' => $absensiId,
                    'user_id' => $userId,
                    'tanggal' => $date->format('Y-m-d'),
                    'kegiatan' => $kegiatan,
                    'kategori_absensi' => $kategoriAbsensi,
                ]),
                [
                    'status' => $formattedStatusForDb,
                ]
            );
            Log::info("Updated/Created Absensi record for user {$userId} on {$date->format('Y-m-d')} for {$kegiatan} with status {$formattedStatusForDb}. ID used: {$absensiId}");
        }
    }
}
