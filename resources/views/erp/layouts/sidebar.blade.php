<aside id="erp-app-sidebar">
    <div id="sidebar-brand">
        <a href="/admin/dashboard" id="sidebar-brand-link" aria-label="Ir al dashboard">
            <img src="/images/opzio-logo-wide-purple-transparent.webp" id="sidebar-opzio-logo-wide" alt="Opzio">
            <img src="/images/opzio-monogram-purple-transparent.webp" id="sidebar-opzio-logo-compact" alt="Opzio">
        </a>
    </div>
    <button id="sidebar-toggle-btn" class="sidebar-toggle-btn" type="button" aria-label="Colapsar menú" aria-expanded="true" aria-controls="sidebar-navigation">
        <i class="fa-light fa-chevron-left" aria-hidden="true"></i>
    </button>
    <nav id="sidebar-navigation" aria-label="Navegación principal">
        <ul id="sidebar-menu">
            <li class="sidebar-menu-item{{ request()->is('admin/dashboard*') ? ' selected' : '' }}">
                <a href="/admin/dashboard" class="sidebar-menu-item-link">
                    <i class="fa-light fa-gauge-high sidebar-menu-item-icon" aria-hidden="true"></i>
                    <p class="align-self-center sidebar-menu-item-text">Dashboard</p>
                </a>
            </li>
            @if(collect(session('permissions'))->firstWhere('user_permission_id', 1)!=null)
            <li class="sidebar-menu-item{{ str_contains(request()->url(), '/admin/users')?' selected':'' }}">
                <a href="/admin/users" class="sidebar-menu-item-link">
                    <i class="fa-light fa-users sidebar-menu-item-icon" aria-hidden="true"></i>
                    <p class="align-self-center sidebar-menu-item-text">Usuarios</p>
                </a>
            </li>
            @endif
            @php($contacts_permission = collect(session('app_permissions'))->firstWhere('url', 'admin/contacts/'))
            @if($contacts_permission && collect(session('permissions'))->firstWhere('user_permission_id', $contacts_permission->id)!=null)
            <li class="sidebar-menu-item{{ str_contains(request()->url(), '/admin/clients')?' selected':'' }}">
                <a href="/admin/clients" class="sidebar-menu-item-link">
                    <i class="fa-light fa-address-card sidebar-menu-item-icon" aria-hidden="true"></i>
                    <p class="align-self-center sidebar-menu-item-text">Clientes</p>
                </a>
            </li>
            @endif
            @if(collect(session('permissions'))->firstWhere('user_permission_id', 3)!=null)
            <li class="sidebar-menu-item{{ str_contains(request()->url(), '/admin/employees')?' selected':'' }}">
                <a href="/admin/employees" class="sidebar-menu-item-link">
                    <i class="fa-light fa-user-tie sidebar-menu-item-icon" aria-hidden="true"></i>
                    <p class="align-self-center sidebar-menu-item-text">Empleados</p>
                </a>
            </li>
            @endif
            @if(collect(session('permissions'))->firstWhere('user_permission_id', 4)!=null)
            <li class="sidebar-menu-item{{ str_contains(request()->url(), '/admin/providers')?' selected':'' }}">
                <a href="/admin/providers" class="sidebar-menu-item-link">
                    <i class="fa-light fa-truck sidebar-menu-item-icon" aria-hidden="true"></i>
                    <p class="align-self-center sidebar-menu-item-text">Proveedores</p>
                </a>
            </li>
            @endif
            @if(collect(session('permissions'))->firstWhere('user_permission_id', 5)!=null)
            <li class="sidebar-menu-item{{ str_contains(request()->url(), '/admin/departments')?' selected':'' }}">
                <a href="/admin/departments" class="sidebar-menu-item-link">
                    <i class="fa-light fa-sitemap sidebar-menu-item-icon" aria-hidden="true"></i>
                    <p class="align-self-center sidebar-menu-item-text">Departamentos</p>
                </a>
            </li>
            @endif
            @if(collect(session('permissions'))->firstWhere('user_permission_id', 6)!=null)
            <li class="sidebar-menu-item{{ str_contains(request()->url(), '/admin/licenses')?' selected':'' }}">
                <a href="/admin/licenses" class="sidebar-menu-item-link">
                    <i class="fa-light fa-file-certificate sidebar-menu-item-icon" aria-hidden="true"></i>
                    <p class="align-self-center sidebar-menu-item-text">Licencias</p>
                </a>
            </li>
            @endif
            @php($contracts_permission = collect(session('app_permissions'))->firstWhere('url', 'admin/contracts/'))
            @if($contracts_permission && collect(session('permissions'))->firstWhere('user_permission_id', $contracts_permission->id)!=null)
            <li class="sidebar-menu-item{{ str_contains(request()->url(), '/admin/contracts')?' selected':'' }}">
                <a href="/admin/contracts" class="sidebar-menu-item-link">
                    <i class="fa-light fa-file-signature sidebar-menu-item-icon" aria-hidden="true"></i>
                    <p class="align-self-center sidebar-menu-item-text">Contratos</p>
                </a>
            </li>
            @endif
            @php($notifications_permission = collect(session('app_permissions'))->firstWhere('url', 'admin/notifications/'))
            @if($notifications_permission && collect(session('permissions'))->firstWhere('user_permission_id', $notifications_permission->id)!=null)
            <li class="sidebar-menu-item{{ str_contains(request()->url(), '/admin/notifications')?' selected':'' }}">
                <a href="/admin/notifications" class="sidebar-menu-item-link">
                    <i class="fa-light fa-bell sidebar-menu-item-icon" aria-hidden="true"></i>
                    <p class="align-self-center sidebar-menu-item-text">Notificaciones</p>
                    <span class="sidebar-whatsapp-unread d-none" data-whatsapp-unread>0</span>
                </a>
            </li>
            @endif
            @if(collect(session('permissions'))->firstWhere('user_permission_id', 2)!=null)
            <li class="sidebar-menu-item{{ str_contains(request()->url(), '/admin/contacts')?' selected':'' }}">
                <a href="/admin/contacts" class="sidebar-menu-item-link">
                    <i class="fa-light fa-address-book sidebar-menu-item-icon" aria-hidden="true"></i>
                    <p class="align-self-center sidebar-menu-item-text">Contactos</p>
                </a>
            </li>
            @endif
            @php($licitaciones_permission = collect(session('app_permissions'))->firstWhere('url', 'admin/tenders/'))
            @if($licitaciones_permission && collect(session('permissions'))->firstWhere('user_permission_id', $licitaciones_permission->id)!=null)
            <li class="sidebar-menu-item{{ str_contains(request()->url(), '/admin/tenders')?' selected':'' }}">
                <a href="/admin/tenders" class="sidebar-menu-item-link">
                    <i class="fa-light fa-gavel sidebar-menu-item-icon" aria-hidden="true"></i>
                    <p class="align-self-center sidebar-menu-item-text">Licitaciones</p>
                </a>
            </li>
            @endif
            @if(collect(session('permissions'))->firstWhere('user_permission_id', 7)!=null)
            <li class="sidebar-menu-item{{ str_contains(request()->url(), '/admin/incomes')?' selected':'' }}">
                <a href="/admin/incomes" class="sidebar-menu-item-link">
                    <i class="fa-light fa-arrow-trend-up sidebar-menu-item-icon" aria-hidden="true"></i>
                    <p class="align-self-center sidebar-menu-item-text">Ingresos</p>
                </a>
            </li>
            @endif
            @if(collect(session('permissions'))->firstWhere('user_permission_id', 8)!=null)
            <li class="sidebar-menu-item{{ str_contains(request()->url(), '/admin/outcomes')?' selected':'' }}">
                <a href="/admin/outcomes" class="sidebar-menu-item-link">
                    <i class="fa-light fa-arrow-trend-down sidebar-menu-item-icon" aria-hidden="true"></i>
                    <p class="align-self-center sidebar-menu-item-text">Egresos</p>
                </a>
            </li>
            @endif
            @if(collect(session('permissions'))->firstWhere('user_permission_id', 9)!=null)
            <li class="sidebar-menu-item{{ str_contains(request()->url(), '/admin/reports')?' selected':'' }}">
                <a href="/admin/reports" class="sidebar-menu-item-link">
                    <i class="fa-light fa-chart-bar sidebar-menu-item-icon" aria-hidden="true"></i>
                    <p class="align-self-center sidebar-menu-item-text">Reportes</p>
                </a>
            </li>
            @endif
            @php($servers_permission = collect(session('app_permissions'))->firstWhere('url', 'admin/servers/'))
            @if($servers_permission && collect(session('permissions'))->firstWhere('user_permission_id', $servers_permission->id)!=null)
            <li class="sidebar-menu-item{{ str_contains(request()->url(), '/admin/servers')?' selected':'' }}">
                <a href="/admin/servers" class="sidebar-menu-item-link">
                    <i class="fa-light fa-server sidebar-menu-item-icon" aria-hidden="true"></i>
                    <p class="align-self-center sidebar-menu-item-text">Servidores</p>
                </a>
            </li>
            @endif
            @php($jira_permission = collect(session('app_permissions'))->firstWhere('url', 'admin/jira/'))
            @if($jira_permission && collect(session('permissions'))->firstWhere('user_permission_id', $jira_permission->id)!=null)
            <li class="sidebar-menu-item{{ str_contains(request()->url(), '/admin/jira')?' selected':'' }}">
                <a href="/admin/jira" class="sidebar-menu-item-link">
                    <i class="fa-brands fa-jira sidebar-menu-item-icon" aria-hidden="true"></i>
                    <p class="align-self-center sidebar-menu-item-text">Jira</p>
                </a>
            </li>
            @endif
            @php($github_permission = collect(session('app_permissions'))->firstWhere('url', 'admin/github/'))
            @if($github_permission && collect(session('permissions'))->firstWhere('user_permission_id', $github_permission->id)!=null)
            <li class="sidebar-menu-item{{ str_contains(request()->url(), '/admin/github')?' selected':'' }}">
                <a href="/admin/github" class="sidebar-menu-item-link">
                    <i class="fa-brands fa-github sidebar-menu-item-icon" aria-hidden="true"></i>
                    <p class="align-self-center sidebar-menu-item-text">GitHub</p>
                </a>
            </li>
            @endif
        </ul>
    </nav>
</aside>