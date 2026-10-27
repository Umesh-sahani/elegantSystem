<aside class="sidebar" id="sidebar">
    <div class="sb-brand">
        <span class="sb-brand-icon"><i class="bi bi-box-seam"></i></span>
        <span class="sb-brand-text">Elegant System</span>
    </div>

    <nav class="sb-nav">
        <div class="sb-section-label">Main</div>
        <a class="sb-item {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}" title="Dashboard">
            <i class="bi bi-speedometer2"></i>
            <span class="sb-label">Dashboard</span>
        </a>

        <div class="sb-section-label mt-2">Inventory</div>
        <a class="sb-item {{ request()->routeIs('product.*') ? 'active' : '' }}" href="{{ route('product.list') }}" title="Products">
            <i class="bi bi-box"></i>
            <span class="sb-label">Products</span>
        </a>
        <a class="sb-item {{ request()->routeIs('order.*') ? 'active' : '' }}" href="{{ route('order.list') }}" title="Orders">
            <i class="bi bi-receipt"></i>
            <span class="sb-label">Orders</span>
        </a>
    </nav>
</aside>
