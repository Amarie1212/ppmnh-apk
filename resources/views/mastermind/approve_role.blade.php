@extends('layouts.app')

@section('title', 'Approve Role')

<link rel="stylesheet" href="{{ asset('css/master.css') }}">

@section('content')
<div class="approve-container">
    <div class="approve-header">
        <h3>Daftar User Pending Approve</h3>
    </div>

    @if(session('success'))
    <div class="approve-alert-success" id="approve-success-alert" style="display: none;">
        {{ session('success') }}
    </div>
    @endif

    <div class="approve-table-wrapper fade-anim">
        <table class="approve-table">
            <thead>
                <tr>
                    <th>Nama</th>
                    <th>Role</th>
                    <th>Kelas</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($pending as $index => $user)
                <tr class="table-row-appear" style="animation-delay: {{ $index * 0.05 }}s;">
                    <td data-label="Nama">{{ $user->full_name }}</td>
                    <td data-label="Role">
                        <div class="custom-select-popup-wrapper" data-popup-id="role-{{ $user->id }}">
                            <button type="button" class="custom-select-popup-trigger" data-current-value="{{ $user->role ?? '' }}">
                                {{ $user->role ? ucfirst($user->role) : '-- Pilih Role --' }}
                            </button>
                            <div class="custom-select-popup-menu">
                                <div class="custom-select-popup-item" data-value="santri">Santri</div>
                                <div class="custom-select-popup-item" data-value="penerobos">Penerobos</div>
                                <div class="custom-select-popup-item" data-value="dewanguru">Dewan Guru</div>
                                <div class="custom-select-popup-item" data-value="masteradmin">Master Admin</div>
                            </div>
                            <input type="hidden" name="role_hidden_input_{{ $user->id }}" value="{{ $user->role ?? '' }}" class="custom-select-popup-hidden-input">
                        </div>
                    </td>
                    <td data-label="Kelas">
                        <div class="custom-select-popup-wrapper" data-popup-id="kelas-{{ $user->id }}">
                            <button type="button" class="custom-select-popup-trigger" data-current-value="{{ $user->kelas ?? '' }}">
                                {{ $user->kelas ? ucfirst($user->kelas) : '-- Pilih Kelas --' }}
                            </button>
                            <div class="custom-select-popup-menu">
                                <div class="custom-select-popup-item" data-value="cepatan">Cepatan</div>
                                <div class="custom-select-popup-item" data-value="mt">MT</div>
                                <div class="custom-select-popup-item" data-value="lambatan">Lambatan</div>
                                <div class="custom-select-popup-item" data-value="guru">Dewan Guru</div>
                            </div>
                            <input type="hidden" name="kelas_hidden_input_{{ $user->id }}" value="{{ $user->kelas ?? '' }}" class="custom-select-popup-hidden-input">
                        </div>
                    </td>
                    <td data-label="Aksi">
                        <form method="POST" action="{{ route('mastermind.approve.post', $user->id) }}" class="approve-form">
                            @csrf
                            <input type="hidden" name="role" class="submit-role-input">
                            <input type="hidden" name="kelas" class="submit-kelas-input">
                            <button type="submit" class="btn-approve">Approve</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" data-label="Status" style="text-align: center;">Tidak ada user yang menunggu persetujuan.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<script>
    let openPopupMenu = null;

    document.addEventListener('DOMContentLoaded', function() {
        const successAlert = document.getElementById('approve-success-alert');
        if (successAlert) {
            successAlert.style.display = 'block';
            void successAlert.offsetWidth;

            successAlert.classList.add('show-alert');

            setTimeout(() => {
                successAlert.classList.remove('show-alert');
                successAlert.classList.add('hide-alert');

                successAlert.addEventListener('transitionend', function handler() {
                    successAlert.removeEventListener('transitionend', handler);
                    successAlert.style.display = 'none';
                });
            }, 3000);
        }

        document.querySelectorAll('.custom-select-popup-trigger').forEach(trigger => {
            trigger.addEventListener('click', function(event) {
                event.stopPropagation();

                const wrapper = this.closest('.custom-select-popup-wrapper');
                const menu = wrapper.querySelector('.custom-select-popup-menu');
                const triggerRect = this.getBoundingClientRect();

                if (openPopupMenu && openPopupMenu !== menu) {
                    const currentOpenWrapper = document.querySelector(`.custom-select-popup-wrapper[data-popup-id="${openPopupMenu.dataset.popupId}"]`);
                    if (currentOpenWrapper && openPopupMenu.parentNode === document.body) {
                        currentOpenWrapper.appendChild(openPopupMenu);
                        currentOpenWrapper.classList.remove('active-popup');
                        const row = currentOpenWrapper.closest('tr');
                        if (row) row.classList.remove('has-active-popup');
                    }
                    openPopupMenu.classList.remove('active');
                }

                if (menu.classList.contains('active')) {
                    menu.classList.remove('active');
                    if (menu.parentNode === document.body) {
                        wrapper.appendChild(menu);
                        wrapper.classList.remove('active-popup');
                        const row = wrapper.closest('tr');
                        if (row) row.classList.remove('has-active-popup');
                    }
                    openPopupMenu = null;
                    return;
                }

                const popupId = wrapper.dataset.popupId;
                menu.dataset.popupId = popupId;

                document.body.appendChild(menu);

                menu.style.position = 'fixed';
                menu.style.top = `${triggerRect.bottom}px`;
                menu.style.left = `${triggerRect.left}px`;
                menu.style.minWidth = `${triggerRect.width}px`;
                menu.style.maxWidth = `${window.innerWidth - triggerRect.left - 10}px`;
                menu.style.zIndex = 2000;

                menu.classList.add('active');
                wrapper.classList.add('active-popup');
                const row = wrapper.closest('tr');
                if (row) row.classList.add('has-active-popup');

                openPopupMenu = menu;
            });
        });

        document.querySelectorAll('.custom-select-popup-item').forEach(item => {
            item.addEventListener('click', function(event) {
                event.stopPropagation();

                const value = this.dataset.value;
                const text = this.textContent;

                const menu = this.closest('.custom-select-popup-menu');
                const wrapper = document.querySelector(`.custom-select-popup-wrapper[data-popup-id="${menu.dataset.popupId}"]`);
                if (!wrapper) return;

                const trigger = wrapper.querySelector('.custom-select-popup-trigger');
                const hiddenInput = wrapper.querySelector('.custom-select-popup-hidden-input');
                const row = wrapper.closest('tr');

                trigger.textContent = text;
                trigger.dataset.currentValue = value;

                hiddenInput.value = value;

                menu.classList.remove('active');
                if (menu.parentNode === document.body) {
                    wrapper.appendChild(menu);
                }
                wrapper.classList.remove('active-popup');
                if (row) row.classList.remove('has-active-popup');
                openPopupMenu = null;

                if (row) {
                    const submitRoleInput = row.querySelector('.submit-role-input');
                    const submitKelasInput = row.querySelector('.submit-kelas-input');

                    if (hiddenInput.name.includes('role_hidden_input')) {
                        if (submitRoleInput) submitRoleInput.value = value;
                    } else if (hiddenInput.name.includes('kelas_hidden_input')) {
                        if (submitKelasInput) submitKelasInput.value = value;
                    }
                }
            });
        });

        document.addEventListener('click', function(event) {
            if (openPopupMenu) {
                const wrapper = document.querySelector(`.custom-select-popup-wrapper[data-popup-id="${openPopupMenu.dataset.popupId}"]`);
                if (wrapper && !wrapper.contains(event.target) && !openPopupMenu.contains(event.target)) {
                    openPopupMenu.classList.remove('active');
                    if (openPopupMenu.parentNode === document.body) {
                        wrapper.appendChild(openPopupMenu);
                    }
                    wrapper.classList.remove('active-popup');
                    const row = wrapper.closest('tr');
                    if (row) row.classList.remove('has-active-popup');
                    openPopupMenu = null;
                }
            }
        });

        document.querySelectorAll('.custom-select-popup-wrapper').forEach(wrapper => {
            if (!wrapper.dataset.popupId) {
                wrapper.dataset.popupId = `popup-${Math.random().toString(36).substr(2, 9)}`;
            }
            wrapper.querySelector('.custom-select-popup-menu').dataset.popupId = wrapper.dataset.popupId;

            const trigger = wrapper.querySelector('.custom-select-popup-trigger');
            const hiddenInput = wrapper.querySelector('.custom-select-popup-hidden-input');
            if (hiddenInput && hiddenInput.value) {
                const selectedItemText = wrapper.querySelector(`.custom-select-popup-item[data-value="${hiddenInput.value}"]`);
                if (selectedItemText) {
                    trigger.textContent = selectedItemText.textContent;
                    trigger.dataset.currentValue = hiddenInput.value;
                } else {
                    trigger.textContent = trigger.textContent.includes('Pilih') ? trigger.textContent : 'Pilih';
                }
            } else {
                trigger.textContent = trigger.textContent.includes('Pilih') ? trigger.textContent : 'Pilih';
            }

            const row = wrapper.closest('tr');
            if (row) {
                const submitRoleInput = row.querySelector('.submit-role-input');
                const submitKelasInput = row.querySelector('.submit-kelas-input');
                if (hiddenInput.name.includes('role_hidden_input') && submitRoleInput) {
                    submitRoleInput.value = hiddenInput.value;
                } else if (hiddenInput.name.includes('kelas_hidden_input') && submitKelasInput) {
                    submitKelasInput.value = hiddenInput.value;
                }
            }
        });
    });
</script>
@endsection
