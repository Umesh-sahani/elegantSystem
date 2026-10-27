<header class="topbar">
    <div class="tb-left">
        <button class="sb-toggle-btn" id="sbToggleBtn" title="Toggle Sidebar">
            <i class="bi bi-list"></i>
        </button>
        <div class="text-truncate">
            <div class="tb-page">@yield('pageTitle', 'Dashboard')</div>
            @stack('breadcrumb')
        </div>
    </div>

    <div class="tb-right">
        <div class="tb-user">
            <span class="tb-avatar">{{ strtoupper(substr(Auth::user()->name,0,1)) }}</span>
            <span class="tb-user-name small fw-semibold">{{ Auth::user()->name }}</span>
        </div>
        <button type="button" class="btn btn-outline-dark btn-sm logoutButton" title="Logout">
            <i class="bi bi-box-arrow-right"></i> <span class="d-none d-sm-inline">Logout</span>
        </button>
    </div>
</header>
