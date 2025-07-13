@extends('layouts.app')

@section('title', 'Kelola Izin Absen')
<link rel="stylesheet" href="{{ asset('css/master.css') }}">

@section('content')
<div class="izinakses-container">
    <div class="izinakses-header">
        <h3>Kelola Izin Tambah Absen</h3>
    </div>

    {{-- Notifikasi Toast --}}
    <div id="izin-toast" class="izin-toast-local" style="display: none;">
        <i id="izinToastIcon" class="izin-toast-icon" data-lucide=""></i> {{-- Ikon akan diatur oleh JS --}}
        <span id="izinToastMessage"></span> {{-- Pesan akan diatur oleh JS (akan disembunyikan untuk ikon-only) --}}
    </div>

    {{-- Check if there are any users to display across all classes --}}
    @if($usersByKelas->isEmpty())
        {{-- If no data at all, display a simplified empty state --}}
        <div class="izinakses-empty-state">
            <p>Tidak ada data untuk ditampilkan.</p>
        </div>
    @else
        {{-- If there is data, display tabs and tables --}}
        <div class="izinakses-tab-nav">
            @foreach($usersByKelas as $kelas => $users)
                <button class="izinakses-tab {{ $loop->first ? 'active' : '' }}" data-target="tab-{{ Str::slug($kelas) }}">
                    {{ strtolower($kelas) === 'mt' ? 'MT' : Str::title($kelas) }}
                </button>
            @endforeach
            <div class="izinakses-tab-indicator"></div>
        </div>

        @foreach($usersByKelas as $kelas => $users)
            <div class="izinakses-tab-content {{ $loop->first ? 'active' : '' }}" id="tab-{{ Str::slug($kelas) }}">
                <div class="izinakses-wrapper fade-anim">
                    <table class="izinakses-table">
                        <thead>
                            <tr>
                                <th>Nama</th>
                                <th style="text-align: right">Izin Tambah Absen</th>
                            </tr>
                        </thead>
                        <tbody class="izinakses-tbody">
                            @forelse($users as $index => $user)
                                <tr class="table-row-appear" style="animation-delay: {{ $index * 0.05 }}s;">
                                    <td data-label="Nama">{{ $user->full_name ?? $user->name }}</td>
                                    <td data-label="Izin Tambah Absen">
                                        <div class="izinakses-toggle-container">
                                            <label class="switch">
                                                <input type="checkbox"
                                                       class="izin-toggle"
                                                       data-user-id="{{ $user->id }}"
                                                       {{ $user->boleh_tambah_absen ? 'checked' : '' }}>
                                                <span class="slider round"></span>
                                            </label>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2" style="padding: 16px; color: var(--text-secondary); text-align: center;">Tidak ada data.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endforeach
    @endif

    <div class="izinakses-footer">
        <button id="saveAllPermissions" class="btn-approve" {{ $usersByKelas->isEmpty() ? 'disabled' : '' }}>Simpan</button>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener("DOMContentLoaded", () => {
    const tabButtons = document.querySelectorAll(".izinakses-tab");
    const tabContents = document.querySelectorAll(".izinakses-tab-content");
    const indicator = document.querySelector(".izinakses-tab-indicator");
    const toastElement = document.getElementById("izin-toast");
    const izinToastIcon = document.getElementById("izinToastIcon"); // Get the icon element
    const izinToastMessage = document.getElementById("izinToastMessage"); // Get the message element
    const saveButton = document.getElementById("saveAllPermissions");

    let currentIndex = 0;

    const lastTab = localStorage.getItem("izinakses_active_tab");
    const lastScroll = localStorage.getItem("izinakses_scroll");

    // Only attempt to set active tabs and indicator if there are tabs (i.e., not empty state)
    if (tabButtons.length > 0) {
        tabContents.forEach(tc => tc.classList.remove("active"));
        tabButtons.forEach(b => b.classList.remove("active"));

        if (lastTab) {
            let activated = false;
            tabButtons.forEach((btn, i) => {
                if (btn.dataset.target === lastTab) {
                    btn.classList.add("active");
                    document.getElementById(lastTab).classList.add("active");
                    updateIndicator(btn);
                    currentIndex = i;
                    activated = true;
                }
            });
            if (!activated && tabButtons.length > 0) {
                tabButtons[0].classList.add("active");
                tabContents[0].classList.add("active");
                updateIndicator(tabButtons[0]);
            }
        } else {
            tabButtons[0].classList.add("active");
            tabContents[0].classList.add("active");
            updateIndicator(tabButtons[0]);
        }
    }


    if (lastScroll) {
        window.scrollTo(0, parseInt(lastScroll));
    }

    // Add event listeners only if tabs exist
    if (tabButtons.length > 0) {
        tabButtons.forEach((btn, index) => {
            btn.addEventListener("click", () => {
                if (btn.classList.contains("active")) return;
                const direction = index > currentIndex ? 'slide-left' : 'slide-right';
                currentIndex = index;
                tabButtons.forEach(b => b.classList.remove("active"));
                tabContents.forEach(tc => tc.classList.remove("active"));
                btn.classList.add("active");
                const targetId = btn.dataset.target;
                const targetContent = document.getElementById(targetId);
                targetContent.classList.add("active");
                localStorage.setItem("izinakses_active_tab", targetId);

                const wrapper = targetContent.querySelector(".izinakses-wrapper");
                if (wrapper) {
                    wrapper.classList.remove('fade-anim');
                    void wrapper.offsetWidth;
                    wrapper.classList.add('fade-anim');
                }

                document.querySelectorAll(".izinakses-tbody").forEach(t => {
                    t.classList.remove("slide-left", "slide-right");
                });
                const tbody = targetContent.querySelector(".izinakses-tbody");
                if (tbody) {
                    void tbody.offsetWidth;
                    tbody.classList.add(direction);
                }
                updateIndicator(btn);
            });
        });
    }

    window.addEventListener("beforeunload", () => {
        localStorage.setItem("izinakses_scroll", window.scrollY);
    });

    window.addEventListener("resize", () => {
        const active = document.querySelector(".izinakses-tab.active");
        if (active) updateIndicator(active);
    });

    function updateIndicator(el) {
        // Only update indicator if it exists (it won't in the empty state)
        if (indicator && el) {
            indicator.style.width = el.offsetWidth + "px";
            indicator.style.left = el.offsetLeft + "px";
        }
    }

    // Only attach save button listener if it's not disabled by default (i.e., not empty state)
    if (!saveButton.disabled) {
        saveButton.addEventListener("click", async () => {
            saveButton.disabled = true;
            saveButton.textContent = 'Menyimpan...';

            const allToggles = document.querySelectorAll(".izin-toggle");
            const permissionsToUpdate = {};

            allToggles.forEach(toggle => {
                const userId = toggle.dataset.userId;
                permissionsToUpdate[userId] = toggle.checked;
            });

            try {
                const response = await fetch("{{ route('mastermind.izinabsen.batchUpdate') }}", {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        permissions: permissionsToUpdate
                    })
                });

                const data = await response.json();

                if (response.ok) {
                    showToast('Izin absen berhasil diperbarui.', 'success'); // Pesan ini akan disembunyikan
                } else {
                    showToast(data.message || 'Terjadi kesalahan saat menyimpan perubahan.', 'error');
                    console.error('Batch update failed:', data.errors || data.message);
                }
            } catch (error) {
                console.error('Network or unexpected error during batch update:', error);
                showToast('Terjadi kesalahan jaringan atau server tidak merespons.', 'error');
            } finally {
                saveButton.disabled = false;
                saveButton.textContent = 'Simpan';
            }
        });
    }


    function showToast(message, type = 'success') {
        toastElement.classList.remove('hide-alert');
        toastElement.classList.remove('success', 'error'); // Remove old type classes
        toastElement.classList.add(type); // Add new type class

        // Set icon based on type
        if (type === 'success') {
            izinToastIcon.setAttribute('data-lucide', 'check-circle');
        } else if (type === 'error') {
            izinToastIcon.setAttribute('data-lucide', 'x-circle');
        }
        // Re-render Lucide icons if they are dynamically added/changed
        if (window.lucide) {
            window.lucide.createIcons();
        }

        izinToastMessage.textContent = message; // Set message text
        izinToastMessage.style.display = 'none'; // Hide text for icon-only display

        toastElement.style.display = 'flex'; // Use flex to center icon
        void toastElement.offsetWidth; // Trigger reflow
        toastElement.classList.add('show-alert');

        let displayDuration = 3000;
        if (type === 'success') {
            displayDuration = 1000; // Success toast disappears faster
        }

        setTimeout(() => {
            toastElement.classList.remove('show-alert');
            toastElement.classList.add('hide-alert');

            const hideCompletely = () => {
                toastElement.style.display = 'none';
                toastElement.removeEventListener('transitionend', hideCompletely);
            };
            toastElement.addEventListener('transitionend', hideCompletely);

        }, displayDuration);
    }

    // Check for session messages on page load
    @if(session('success'))
        showToast("{{ session('success') }}", 'success');
    @elseif(session('error'))
        showToast("{{ session('error') }}", 'error');
    @endif
});

</script>
@endpush
