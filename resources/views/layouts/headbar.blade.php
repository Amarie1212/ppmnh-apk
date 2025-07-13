<div class="headbar">
    {{-- KIRI: Logo + Breadcrumb --}}
    <div class="left">
        <div class="logo-area">
            <img src="{{ asset('logo ppm.png') }}" alt="Logo PPM" class="logo-ppm" />
            <span class="logo-text">PPM Nurul Hakim</span>
        </div>
    </div>

    {{-- KANAN: Profil Dropdown --}}
    <div class="right">
        @php $user = Auth::user(); @endphp

        @if ($user)
            <div class="profile-dropdown">
                <div class="user-label" onclick="toggleProfileDropdown()">
                    {{-- <span class="user-name">{{ $user->full_name ?? $user->name }}</span> --}} <button class="profile-trigger">
                        <i data-lucide="settings" class="settings-icon"></i>
                    </button>
                </div>
                <div class="profile-popup" id="profileDropdown">
                    <div class="profile-info">
                        <strong>{{ $user->full_name ?? $user->name }}</strong><br>
                        <br><small>{{ $user->email }}</small>
                    </div>
                    <hr>
                    <a href="{{ route('profile.edit') }}">Account Preferences</a>
                    <hr>
                    <div class="theme-section">
                        <label>Theme</label>
                        <div class="theme-options">
                            <button onclick="setTheme('dark')">Dark</button>
                            <button onclick="setTheme('light')">Light</button>
                            <button onclick="setTheme('system')">System</button>
                        </div>
                    </div>
                    <hr>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="logout-btn">
                            <i data-lucide="log-out"></i>
                            <span>Log out</span>
                        </button>
                    </form>
                </div>
            </div>
        @endif
    </div>
</div>

<script>
    // Toggle profile dropdown
    function toggleProfileDropdown() {
        const el = document.getElementById('profileDropdown');
        el.classList.toggle('show');
        document.querySelector('.settings-icon').classList.toggle('rotate');
    }

    // Close dropdown when clicking outside
    document.addEventListener('click', function (event) {
        const trigger = document.querySelector('.profile-trigger');
        const dropdown = document.getElementById('profileDropdown');
        const userLabel = document.querySelector('.user-label'); // Include user-label in click detection
        if (!userLabel.contains(event.target) && !dropdown.contains(event.target)) {
            dropdown.classList.remove('show');
            document.querySelector('.settings-icon').classList.remove('rotate'); // Also remove rotate
        }
    });

    // Set theme (dark, light, or system preference)
    function setTheme(theme) {
        document.body.classList.remove('dark', 'light');

        if (theme === 'dark') {
            document.body.classList.add('dark');
        } else if (theme === 'light') {
            document.body.classList.add('light');
        } else if (theme === 'system') {
            // Check for system preference and apply
            if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
                document.body.classList.add('dark');
            } else {
                document.body.classList.add('light');
            }
        }

        // Save the selected theme in localStorage
        localStorage.setItem('theme', theme);
    }

    // Load saved theme on page load
    document.addEventListener('DOMContentLoaded', () => {
        const savedTheme = localStorage.getItem('theme') || 'system';
        setTheme(savedTheme);
    });
</script>
