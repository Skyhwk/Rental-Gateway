<aside class="main-sidebar sidebar-dark-primary elevation-4">
    <a href="{{ route('dashboard') }}" class="brand-link">
        <span class="brand-text font-weight-light">{{ config('app.name') }}</span>
    </a>

    <div class="sidebar">
        <div class="user-panel mt-3 pb-3 mb-3 d-flex">
            <div class="info">
                <a href="#" class="d-block">{{ Auth::user()->name }}</a>
                <small class="text-muted">{{ Auth::user()->getRoleNames()->first() ?? 'User' }}</small>
            </div>
        </div>

        <nav class="mt-2">
            <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                @can('dashboard.view')
                <li class="nav-item">
                    <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-tachometer-alt"></i>
                        <p>Dasbor</p>
                    </a>
                </li>
                @endcan

                <li class="nav-item">
                    <a href="{{ route('manual-book.index') }}" class="nav-link {{ request()->routeIs('manual-book.*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-book"></i>
                        <p>Manual Book</p>
                    </a>
                </li>

                @if(auth()->user()->can('viewAny', App\Models\Client::class) || auth()->user()->can('viewAny', App\Models\Application::class))
                <li class="nav-header">MANAJEMEN</li>
                @endif

                @can('viewAny', App\Models\Client::class)
                <li class="nav-item">
                    <a href="{{ route('clients.index') }}" class="nav-link {{ request()->routeIs('clients.*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-building"></i>
                        <p>Client</p>
                    </a>
                </li>
                @endcan

                @can('viewAny', App\Models\Application::class)
                <li class="nav-item">
                    <a href="{{ route('applications.index') }}" class="nav-link {{ request()->routeIs('applications.*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-server"></i>
                        <p>Aplikasi</p>
                    </a>
                </li>
                @endcan

                @if(auth()->user()->can('viewAny', App\Models\Invoice::class) || auth()->user()->can('viewAny', App\Models\Subscription::class))
                <li class="nav-header">TAGIHAN</li>
                @endif

                @can('viewAny', App\Models\Subscription::class)
                <li class="nav-item">
                    <a href="{{ route('billing.subscriptions.index') }}" class="nav-link {{ request()->routeIs('billing.subscriptions.*') || request()->routeIs('billing.plans.*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-calendar-check"></i>
                        <p>Langganan</p>
                    </a>
                </li>
                @endcan

                @can('viewAny', App\Models\Invoice::class)
                <li class="nav-item">
                    <a href="{{ route('billing.invoices.index') }}" class="nav-link {{ request()->routeIs('billing.invoices.*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-file-invoice-dollar"></i>
                        <p>Invoice</p>
                    </a>
                </li>
                @endcan

                @can('viewAny', App\Models\Payment::class)
                <li class="nav-item">
                    <a href="{{ route('billing.payments.index') }}" class="nav-link {{ request()->routeIs('billing.payments.*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-money-bill-wave"></i>
                        <p>Pembayaran</p>
                    </a>
                </li>
                @endcan
                @if(auth()->user()->can('monitoring.view') || auth()->user()->can('gateway.sync'))
                <li class="nav-header">OPERASI</li>
                @endif

                @can('monitoring.view')
                <li class="nav-item">
                    <a href="{{ route('monitoring.index') }}" class="nav-link {{ request()->routeIs('monitoring.*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-heartbeat"></i>
                        <p>Monitoring</p>
                    </a>
                </li>
                @endcan

                @can('gateway.sync')
                <li class="nav-item">
                    <a href="{{ route('gateway.index') }}" class="nav-link {{ request()->routeIs('gateway.*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-network-wired"></i>
                        <p>Gateway</p>
                    </a>
                </li>
                @endcan
            </ul>
        </nav>
    </div>
</aside>
