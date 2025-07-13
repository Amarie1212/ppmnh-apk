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
    /**
     * Menampilkan riwayat absensi.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View|\Illuminate\Http\JsonResponse
     */
   public function index(Request $request)
    {
        $user = Auth::user(); // User yang sedang login

        $bulan = $request->input('bulan', now()->format('Y-m'));
        $nama = $request->input('nama');
        $mingguAktif = (int) $request->input('minggu', 1);

        // --- Definisi Semua Tab ---
        $allTabs = [
            'apel_ggs' => 'Apel GGS',
            'apel_qa' => 'Apel QA',
            'cepatan' => 'Cepatan',
            'lambatan_ggs' => 'Lambatan GGS',
            'lambatan_qa' => 'Lambatan QA',
            'mt' => 'MT',
        ];

        // --- Tentukan Tab yang Diizinkan berdasarkan Role ---
        $allowedTabs = [];
        if ($user->role === 'santri') {
            if ($user->jenis_kelamin === 'laki-laki') {
                $allowedTabs['apel_ggs'] = 'Apel GGS';
                if ($user->kelas === 'lambatan') {
                    $allowedTabs['lambatan_ggs'] = 'Lambatan GGS';
                }
                if ($user->kelas === 'cepatan') { // Added for santri role
                    $allowedTabs['cepatan'] = 'Cepatan';
                }
                if ($user->kelas === 'mt') { // Added for santri role
                    $allowedTabs['mt'] = 'MT';
                }
            } elseif ($user->jenis_kelamin === 'perempuan') {
                $allowedTabs['apel_qa'] = 'Apel QA';
                if ($user->kelas === 'lambatan') {
                    $allowedTabs['lambatan_qa'] = 'Lambatan QA';
                }
                if ($user->kelas === 'cepatan') { // Added for santri role
                    $allowedTabs['cepatan'] = 'Cepatan';
                }
                if ($user->kelas === 'mt') { // Added for santri role
                    $allowedTabs['mt'] = 'MT';
                }
            }

            // Pastikan kategori yang dipilih atau default valid untuk santri
            $kategori = $request->input('kategori');
            if (!isset($allowedTabs[$kategori])) {
                $kategori = array_key_first($allowedTabs) ?: 'apel_ggs';
            }
        } else {
            $allowedTabs = $allTabs;
            $kategori = $request->input('kategori', 'apel_ggs');
        }
        // --- Akhir Penentuan Tab yang Diizinkan ---


        // --- Perhitungan Tanggal & Minggu yang Lebih Akurat ---
        $year = (int) substr($bulan, 0, 4);
        $month = (int) substr($bulan, 5, 2);

        // Carbon instance untuk awal bulan yang dipilih
        $startOfMonth = Carbon::createFromDate($year, $month, 1)->startOfDay();

        // Cari hari Minggu pertama yang jatuh dalam/sebelum awal bulan
        $firstDayOfCalendarWeek = $startOfMonth->copy()->startOfWeek(Carbon::SUNDAY);

        // Hitung total jumlah minggu yang akan ditampilkan untuk bulan ini
        // Menggunakan fungsi pembantu yang lebih akurat
        $jumlahMinggu = $this->getJumlahMingguUntukTampilan($startOfMonth);

        // Tentukan tanggal mulai dan akhir untuk minggu yang aktif
        $currentWeekStart = $firstDayOfCalendarWeek->copy()->addWeeks($mingguAktif - 1);
        $currentWeekEnd = $currentWeekStart->copy()->endOfWeek(Carbon::SATURDAY);

        // Pastikan mingguAktif tidak melebihi jumlahMinggu yang valid
        if ($mingguAktif > $jumlahMinggu) {
            $mingguAktif = $jumlahMinggu;
            // Recalculate currentWeekStart and currentWeekEnd for the adjusted mingguAktif
            $currentWeekStart = $firstDayOfCalendarWeek->copy()->addWeeks($mingguAktif - 1);
            $currentWeekEnd = $currentWeekStart->copy()->endOfWeek(Carbon::SATURDAY);
        }
        // --- Akhir Perhitungan Tanggal & Minggu ---

        // Dapatkan tanggal-tanggal spesifik (angka) dan rentang untuk header tabel
        $tanggalInfo = $this->getTanggalHariDalamMinggu($startOfMonth, $mingguAktif); // Mengirim $startOfMonth

        // --- Filtering User yang Akan Ditampilkan di Tabel ---
        $usersQuery = User::query();
        if ($user->role === 'santri') {
            $usersQuery->where('id', $user->id);
        } else {
            $usersQuery->whereIn('role', ['santri', 'penerobos']);
        }

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
        }

        if ($nama) {
            $usersQuery->where('full_name', 'like', '%' . $nama . '%');
        }

        $finalUsersToDisplay = $usersQuery->orderBy('full_name')->get();
        $filteredUserIds = $finalUsersToDisplay->pluck('id');


        // Ambil data absensi hanya untuk user yang sudah difilter dan dalam rentang tanggal
        $absensiDataUntukMingguIni = Absensi::whereBetween('tanggal', [$currentWeekStart->format('Y-m-d'), $currentWeekEnd->format('Y-m-d')])
            ->whereIn('user_id', $filteredUserIds)
            ->get();

        // Map absensi data ke user
        $absensi = $finalUsersToDisplay->map(function ($userItem) use ($absensiDataUntukMingguIni, $currentWeekStart) {
            $data = [
                'user' => $userItem,
                // Default values
                'minggu_m' => '-', // Minggu Maghrib
                'senin' => '-', 'senin_m' => '-',
                'selasa' => '-', 'selasa_m' => '-',
                'rabu' => '-', 'rabu_m' => '-',
                'kamis' => '-', 'kamis_m' => '-',
                'jumat' => '-', 'jumat_m' => '-',
                'sabtu' => '-', // Sabtu Subuh/Apel
            ];

            // Mapping nama kolom berdasarkan dayOfWeek (0=Minggu, 1=Senin, ..., 6=Sabtu)
            // Ini untuk kegiatan pagi/siang (Apel/Subuh)
            $dayColumnMapping = [
                0 => 'minggu_m', // Minggu hanya ada Maghrib
                1 => 'senin',
                2 => 'selasa',
                3 => 'rabu',
                4 => 'kamis',
                5 => 'jumat',
                6 => 'sabtu',
            ];

            // Mapping nama kolom untuk kegiatan Ngaji Maghrib (Senin-Jumat)
            $maghribColumnMapping = [
                1 => 'senin_m',
                2 => 'selasa_m',
                3 => 'rabu_m',
                4 => 'kamis_m',
                5 => 'jumat_m',
            ];


            $currentUserAbsensi = $absensiDataUntukMingguIni->where('user_id', $userItem->id);

            for ($i = 0; $i < 7; $i++) {
                $tanggalHariLoop = $currentWeekStart->copy()->addDays($i)->format('Y-m-d');
                $dayOfWeekIndex = $currentWeekStart->copy()->addDays($i)->dayOfWeek; // 0=Sunday, 1=Monday...6=Saturday

                $absenHariIni = $currentUserAbsensi->filter(function ($absenRecord) use ($tanggalHariLoop) {
                    return $absenRecord->tanggal == $tanggalHariLoop;
                });

                foreach ($absenHariIni as $absen) {
                    if ($absen->kegiatan === 'Ngaji Subuh' || $absen->kegiatan === 'Apel') {
                        // Untuk Apel/Subuh, langsung ke kolom hari yang sesuai
                        if (isset($dayColumnMapping[$dayOfWeekIndex]) && $dayOfWeekIndex !== Carbon::SUNDAY) { // Minggu tidak ada Apel/Subuh
                            $data[$dayColumnMapping[$dayOfWeekIndex]] = $absen->status;
                        }
                    } elseif ($absen->kegiatan === 'Ngaji Maghrib') {
                        // Untuk Maghrib
                        if ($dayOfWeekIndex === Carbon::SUNDAY) { // Minggu Maghrib
                            $data['minggu_m'] = $absen->status;
                        } elseif (isset($maghribColumnMapping[$dayOfWeekIndex])) { // Senin-Jumat Maghrib
                            $data[$maghribColumnMapping[$dayOfWeekIndex]] = $absen->status;
                        }
                        // Sabtu Maghrib tidak ada di sini (sesuai struktur tabel)
                    }
                }
            }
            return (object)$data;
        });

        // Logika untuk tombol "Presensi" (Add Absensi)
        $bolehTambahAbsensi = ($user->role === 'masteradmin') ||
                              ($user->role === 'penerobos' && $user->boleh_tambah_absen);

        // Logika untuk tombol "Edit" di tabel (Edit Absensi Record)
        // Dibuat variabel terpisah untuk fleksibilitas izin di masa depan
        $canEditAbsensiRecord = ($user->role === 'masteradmin') ||
                                ($user->role === 'penerobos' && $user->boleh_tambah_absen); // Saat ini logikanya sama dengan bolehTambahAbsensi

        // Periksa apakah ini permintaan AJAX. Jika ya, hanya render bagian tabel.
        if ($request->ajax()) {
            return view('absensi._absensi_table_partial', compact(
                'absensi',
                'jumlahMinggu',
                'mingguAktif',
                'tanggalInfo',
                'kategori', // Perlu dikirim agar Blade bisa menentukan colspan
                'bolehTambahAbsensi', // Dilewatkan ke partial
                'canEditAbsensiRecord' // <<< DITAMBAHKAN DI SINI
            ));
        }

        return view('absensi.index', compact(
            'absensi',
            'jumlahMinggu',
            'bulan',
            'nama',
            'kategori',
            'mingguAktif',
            'tanggalInfo',
            'bolehTambahAbsensi',
            'allowedTabs',
            'canEditAbsensiRecord' // <<< DITAMBAHKAN DI SINI
        ));
    }

    /**
     * Fungsi pembantu untuk menghitung jumlah minggu dalam bulan untuk tampilan.
     * Minggu dihitung dari hari Minggu pertama yang jatuh dalam/sebelum awal bulan
     * sampai hari Sabtu terakhir yang jatuh dalam/setelah akhir bulan.
     *
     * @param Carbon $monthStart Carbon instance dari tanggal 1 di bulan yang diminta.
     * @return int
     */
 private function getJumlahMingguUntukTampilan(Carbon $monthStart): int
    {
        $startOfFirstWeekInCalendar = $monthStart->copy()->startOfWeek(Carbon::SUNDAY);
        $endOfMonth = $monthStart->copy()->endOfMonth();
        $endOfLastWeekInCalendar = $endOfMonth->copy()->endOfWeek(Carbon::SATURDAY);

        // Hitung selisih hari antara awal minggu pertama di tampilan kalender dan akhir minggu terakhir
        // Kemudian bagi 7 untuk mendapatkan jumlah minggu. Tambah 1 untuk inklusif.
        $totalDays = $startOfFirstWeekInCalendar->diffInDays($endOfLastWeekInCalendar) + 1;
        return (int) ceil($totalDays / 7);
    }

    /**
     * Fungsi pembantu untuk mendapatkan array tanggal (angka hari) dan rentang tanggal
     * untuk minggu aktif yang diminta.
     *
     * @param Carbon $monthStart Carbon instance dari tanggal 1 di bulan yang diminta.
     * @param int $mingguKe Nomor minggu yang aktif (1, 2, 3, dst.)
     * @return array Array berisi 'tanggalHari' (angka tanggal) dan 'rentang' (string rentang tanggal).
     */
    private function getTanggalHariDalamMinggu(Carbon $monthStart, int $mingguKe): array
    {
        $firstDayOfCalendarWeek = $monthStart->copy()->startOfWeek(Carbon::SUNDAY); // Minggu pertama yang relevan

        // Majukan ke minggu yang benar berdasarkan $mingguKe
        $currentWeekStart = $firstDayOfCalendarWeek->copy()->addWeeks($mingguKe - 1);
        $currentWeekEnd = $currentWeekStart->copy()->endOfWeek(Carbon::SATURDAY);

        $tanggalHari = [];
        for ($i = 0; $i < 7; $i++) {
            $tanggalHari[$i] = $currentWeekStart->copy()->addDays($i)->day; // Mengambil angka hari saja
        }

        $rentangTanggal = $currentWeekStart->format('d M') . ' - ' . $currentWeekEnd->format('d M Y');

        return [
            'tanggalHari' => $tanggalHari,
            'rentang' => $rentangTanggal,
        ];
    }

    public function create(Request $request)
    {
        $kategori = $request->input('kategori', 'lambatan_qa'); // Default ini akan di-override oleh JS
        $kegiatan = $request->input('kegiatan', 'Ngaji Subuh'); // Default ini akan di-override oleh JS

        // Pastikan Anda mendapatkan semua pengguna yang relevan
        $allRelevantUsers = User::whereIn('role', ['santri', 'penerobos'])
                                     ->orderBy('full_name')
                                     ->get();

        // Ambil absensi data bulanan dari bulan saat ini untuk pre-fill status di form tambah
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
            // 'attendances.*.kelas' => 'required|string|in:cepatan,lambatan,mt', // 'kelas' can be null for non-santri users
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
                // Ensure 'kelas' is optional or handle if it's not always present for all users
                $kelas = $attendanceData['kelas'] ?? null; // Make kelas nullable
                $keterangan = $attendanceData['keterangan'] ?? null;

                Absensi::updateOrCreate(
                    [
                        'user_id' => $userId,
                        'tanggal' => $tanggal,
                        'kegiatan' => $kegiatan,
                    ],
                    [
                        'status' => $status,
                        'kelas' => $kelas, // Save kelas (can be null)
                        'keterangan' => $keterangan,
                        'kategori_absensi' => $kategoriAbsensi,
                    ]
                );
            }

            // Flash session data for redirection to index and auto-select tab/month/week
            $bulan_redirect = Carbon::parse($tanggal)->format('Y-m');
            $minggu_redirect = $this->getMingguKe($tanggal, $bulan_redirect); // Helper to find which week the date falls into

            return redirect()->route('absensi.index', [
                'bulan' => $bulan_redirect,
                'minggu' => $minggu_redirect,
                'kategori' => $kategoriAbsensi
            ])->with([
                'success' => 'Absensi berhasil disimpan!',
                'last_added_kategori' => $kategoriAbsensi,
                'last_added_bulan' => $bulan_redirect,
                'last_added_minggu' => $minggu_redirect,
            ]);

        } catch (\Exception $e) {
            Log::error('Error saving absensi: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return redirect()->back()->withInput($request->all())->with('error', 'Gagal simpan absensi! Silakan coba lagi. Pesan Error: ' . $e->getMessage());
        }
    }

    /**
     * Helper to determine which week number a given date falls into for a month.
     * Assumes weeks start on Sunday.
     *
     * @param string $dateString YYYY-MM-DD format
     * @param string $monthString YYYY-MM format
     * @return int
     */
    private function getMingguKe(string $dateString, string $monthString): int
    {
        $targetDate = Carbon::parse($dateString);
        $monthStart = Carbon::parse($monthString)->startOfDay();
        $firstDayOfCalendarWeek = $monthStart->copy()->startOfWeek(Carbon::SUNDAY);

        // Calculate the difference in weeks from the first Sunday of the calendar view
        return $firstDayOfCalendarWeek->diffInWeeks($targetDate, false) + 1;
    }


    // Fungsi ini tidak digunakan di kode Anda, tapi saya tetap mempertahankan strukturnya
    // berdasarkan context sebelumnya.
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

        Carbon::setLocale('id'); // Pastikan locale diset untuk format tanggal terjemahan

        $startOfMonth = Carbon::createFromFormat('Y-m', $bulanParam)->startOfDay();
        $firstDayOfCalendarWeek = $startOfMonth->copy()->startOfWeek(Carbon::SUNDAY);
        $currentWeekStart = $firstDayOfCalendarWeek->copy()->addWeeks($mingguAktif - 1);
        $currentWeekEnd = $currentWeekStart->copy()->endOfWeek(Carbon::SATURDAY);

        Log::info("--- DEBUG EDIT ABSENSI ---");
        Log::info("Parameters: UserID={$user->id}, Kategori={$kategori}, Bulan={$bulanParam}, Minggu={$mingguAktif}");
        Log::info("Calculated Week Range: {$currentWeekStart->format('Y-m-d')} to {$currentWeekEnd->format('Y-m-d')}");

        $kegiatanFilter = [];
        // No need for $kategoriAbsensiFilter as it's passed directly to Absensi record
        // $kategoriAbsensiFilter = [$kategori]; // Removed, as we already filter users by category

        if (in_array($kategori, ['apel_ggs', 'apel_qa'])) {
            $kegiatanFilter = ['Apel'];
        } else {
            $kegiatanFilter = ['Ngaji Subuh', 'Ngaji Maghrib'];
        }

        Log::info("Applied Filters: Kegiatan=" . implode(', ', $kegiatanFilter)); // Removed kategori_absensi filter from log, as it's not a direct query filter here

        $absensiRecords = Absensi::where('user_id', $user->id)
                                     ->whereBetween('tanggal', [$currentWeekStart->format('Y-m-d'), $currentWeekEnd->format('Y-m-d')])
                                     ->whereIn('kegiatan', $kegiatanFilter)
                                     ->where('kategori_absensi', $kategori) // Filter by kategori_absensi column in Absensi table
                                     ->get()
                                     ->keyBy(function($item) {
                                         return Carbon::parse($item->tanggal)->format('Y-m-d') . '_' . $item->kegiatan;
                                     });

        Log::info("Absensi Records Fetched from DB (" . $absensiRecords->count() . " records): " . json_encode($absensiRecords->toArray()));

        $editData = [];
        $tempDate = $currentWeekStart->copy();

        for ($i = 0; $i < 7; $i++) {
            $dateKey = $tempDate->format('Y-m-d');
            $dayOfWeek = $tempDate->dayOfWeek; // 0 for Sunday, 1 for Monday, etc.

            $editData[$dateKey] = [
                'tanggal_display' => $tempDate->translatedFormat('d M Y'),
                'hari' => strtolower($tempDate->translatedFormat('l')), // e.g., "senin", "minggu"
                'date_carbon' => $tempDate->copy(),
            ];

            if (in_array($kategori, ['apel_ggs', 'apel_qa'])) {
                // Apel hanya ada dari Senin-Sabtu
                if ($dayOfWeek !== Carbon::SUNDAY) { // Not Sunday
                    $editData[$dateKey]['apel'] = ['status' => '-', 'id' => null];
                } else {
                    $editData[$dateKey]['apel'] = null; // Apel tidak ada di hari Minggu
                }
            } else { // Kategori selain Apel (lambatan, cepatan, mt)
                $editData[$dateKey]['ngaji_subuh'] = ['status' => '-', 'id' => null];
                $editData[$dateKey]['ngaji_maghrib'] = ['status' => '-', 'id' => null];

                if ($dayOfWeek === Carbon::SUNDAY) {
                    $editData[$dateKey]['ngaji_subuh'] = null; // Ngaji Subuh tidak ada di hari Minggu
                }
                if ($dayOfWeek === Carbon::SATURDAY) {
                    $editData[$dateKey]['ngaji_maghrib'] = null; // Ngaji Maghrib tidak ada di hari Sabtu
                }
            }
            $tempDate->addDay();
        }

        foreach ($editData as $date => &$dayData) {
            // Check for 'Apel' activity
            if (isset($dayData['apel']) && !is_null($dayData['apel'])) {
                $recordKey = $date . '_Apel';
                if ($absensiRecords->has($recordKey)) {
                    $record = $absensiRecords->get($recordKey);
                    $dayData['apel']['status'] = $record->status;
                    $dayData['apel']['id'] = $record->id;
                }
            }

            // Check for 'Ngaji Subuh' activity
            if (isset($dayData['ngaji_subuh']) && !is_null($dayData['ngaji_subuh'])) {
                $recordKey = $date . '_Ngaji Subuh';
                if ($absensiRecords->has($recordKey)) {
                    $record = $absensiRecords->get($recordKey);
                    $dayData['ngaji_subuh']['status'] = $record->status;
                    $dayData['ngaji_subuh']['id'] = $record->id;
                }
            }

            // Check for 'Ngaji Maghrib' activity
            if (isset($dayData['ngaji_maghrib']) && !is_null($dayData['ngaji_maghrib'])) {
                $recordKey = $date . '_Ngaji Maghrib';
                if ($absensiRecords->has($recordKey)) {
                    $record = $absensiRecords->get($recordKey);
                    $dayData['ngaji_maghrib']['status'] = $record->status;
                    $dayData['ngaji_maghrib']['id'] = $record->id;
                }
            }
        }
        unset($dayData); // Unset reference to avoid unexpected behavior

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
                } else { // Kategori selain apel (lambatan, cepatan, mt)
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

        // If activitiesToDelete is empty, set default based on category for deletion context
        if (empty($activitiesToDelete)) {
            if (in_array($kategoriAbsensiFromFrontend, ['apel_ggs', 'apel_qa'])) {
                $activitiesToDelete = ['Apel'];
                Log::info("Kategori Apel detected, activities_to_delete was empty, set to ['Apel'].");
            } else {
                // For other categories, default to both Ngaji Subuh and Ngaji Maghrib if not specified
                $activitiesToDelete = ['Ngaji Subuh', 'Ngaji Maghrib'];
                Log::info("Non-Apel category detected, activities_to_delete was empty, set to ['Ngaji Subuh', 'Ngaji Maghrib'].");
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

    /**
     * Helper function to update or create an Absensi record.
     *
     * @param int $userId
     * @param Carbon $date
     * @param string $kegiatan
     * @param string $kategoriAbsensi
     * @param string $status
     * @param int|null $absensiId
     * @return void
     */
    private function updateOrCreateAbsensi($userId, $date, $kegiatan, $kategoriAbsensi, $status, $absensiId = null)
    {
        // Convert 'P', 'T', etc. back to 'hadir', 'telat', etc. for database storage
        $statusMap = [
            'P' => 'hadir',
            'T' => 'telat',
            'I' => 'izin',
            'S' => 'sakit',
            'A' => 'alpha',
            '-' => '-', // Representing no attendance for deletion/reset
        ];
        $formattedStatusForDb = $statusMap[$status] ?? $status;

        if ($formattedStatusForDb === '-') {
            // If status is '-', it means we should delete the record if it exists
            if ($absensiId) {
                Absensi::destroy($absensiId);
                Log::info("Deleted Absensi record with ID: {$absensiId}");
            } else {
                // Fallback delete if ID is not provided but unique keys match
                Absensi::where([
                    'user_id' => $userId,
                    'tanggal' => $date->format('Y-m-d'),
                    'kegiatan' => $kegiatan,
                    'kategori_absensi' => $kategoriAbsensi,
                ])->delete();
                Log::info("Attempted to delete Absensi record for user {$userId} on {$date->format('Y-m-d')} for {$kegiatan} (no specific ID).");
            }
        } else {
            // Update or create if status is valid (not '-')
            $attributes = [
                'user_id' => $userId,
                'tanggal' => $date->format('Y-m-d'),
                'kegiatan' => $kegiatan,
                'kategori_absensi' => $kategoriAbsensi, // Ensure kategori_absensi is part of the unique key
            ];

            // If an ID is provided, include it for direct update
            if ($absensiId) {
                $attributes['id'] = $absensiId;
            }

            Absensi::updateOrCreate(
                $attributes,
                [
                    'status' => $formattedStatusForDb,
                ]
            );
            Log::info("Updated/Created Absensi record for user {$userId} on {$date->format('Y-m-d')} for {$kegiatan} with status {$formattedStatusForDb}. ID used: {$absensiId}");
        }
    }
}
