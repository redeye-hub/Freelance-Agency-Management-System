<nav x-data="{ open: false }" class="bg-white border-b border-gray-100">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}">
                        <x-application-logo class="block h-9 w-auto fill-current text-gray-800" />
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden space-x-3 sm:-my-px sm:ms-4 sm:flex min-w-0 text-xs">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                        {{ __('Dashboard') }}
                    </x-nav-link>
                    @role('client')
    <x-nav-link :href="route('portal.invoices')" :active="request()->routeIs('portal.*')">My Invoices</x-nav-link>
@endrole
                    
@hasanyrole('super-admin|project-manager')
    <x-nav-link :href="route('leads.index')" :active="request()->routeIs('leads.*')">Leads</x-nav-link>
    <x-nav-link :href="route('clients.index')" :active="request()->routeIs('clients.*')">Clients</x-nav-link>
    <x-nav-link :href="route('freelancers.index')" :active="request()->routeIs('freelancers.*')">Freelancers</x-nav-link>
    <x-nav-link :href="route('projects.index')" :active="request()->routeIs('projects.*')">Projects</x-nav-link>
    <x-nav-link :href="route('timesheets.review')" :active="request()->routeIs('timesheets.review')">Timesheets</x-nav-link>
@endhasanyrole
@hasanyrole('super-admin|finance')
<div class="hidden sm:flex sm:items-center">
    <x-dropdown align="left" width="48">
        <x-slot name="trigger">
            <button class="inline-flex items-center px-1 pt-1 text-sm font-medium text-gray-500 hover:text-gray-700 focus:outline-none">
                Finance
                <svg class="ms-1 h-4 w-4 fill-current" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                </svg>
            </button>
        </x-slot>
        <x-slot name="content">
            <x-dropdown-link :href="route('invoices.index')">Invoices</x-dropdown-link>
            <x-dropdown-link :href="route('payouts.index')">Payouts</x-dropdown-link>
            <x-dropdown-link :href="route('reports.finance')">Finance reports</x-dropdown-link>
        </x-slot>
    </x-dropdown>
</div>
@endhasanyrole

@role('super-admin')
    <x-nav-link :href="route('admin.users.index')" :active="request()->routeIs('admin.users.*')">Users</x-nav-link>
@endrole

@hasanyrole('super-admin|hr')
    <x-nav-link :href="route('hr.candidates.index')" :active="request()->routeIs('hr.candidates.*')">Recruitment</x-nav-link>
    <x-nav-link :href="route('hr.offboarding.index')" :active="request()->routeIs('hr.offboarding.*')">Offboarding</x-nav-link>
    <x-nav-link :href="route('hr.leave.index')" :active="request()->routeIs('hr.leave.*')">Leave</x-nav-link>
    <x-nav-link :href="route('hr.workload.index')" :active="request()->routeIs('hr.workload.*')">Workload</x-nav-link>
    <x-nav-link :href="route('hr.contracts.index')" :active="request()->routeIs('hr.contracts.*')">Contracts</x-nav-link>
    <x-nav-link :href="route('hr.scorecards.index')" :active="request()->routeIs('hr.scorecards.*')">Scorecards</x-nav-link>
    <x-nav-link :href="route('hr.reports.index')" :active="request()->routeIs('hr.reports.*')">Reports</x-nav-link>
@endhasanyrole

@role('freelancer')
    <x-nav-link :href="route('freelancer.profile.edit')" :active="request()->routeIs('freelancer.profile.*')">My Skills & Rates</x-nav-link>
    <x-nav-link :href="route('my.projects')" :active="request()->routeIs('my.projects') || request()->routeIs('projects.board') || request()->routeIs('projects.tasks.*')">My Projects</x-nav-link>
    <x-nav-link :href="route('timesheet.index')" :active="request()->routeIs('timesheet.*')">Timesheet</x-nav-link>
    <x-nav-link :href="route('leave.index')" :active="request()->routeIs('leave.*')">Leave</x-nav-link>
    <x-nav-link :href="route('my.contracts')" :active="request()->routeIs('my.contracts*')">Contracts</x-nav-link>
    <x-nav-link :href="route('earnings.index')" :active="request()->routeIs('earnings.*')">Earnings</x-nav-link>
@endrole

                </div>
            </div>

            <!-- Settings Dropdown -->
            <div class="hidden sm:flex sm:items-center sm:ms-6">
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150">
                            <div>{{ Auth::user()->name }}</div>

                            <div class="ms-1">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">
                            {{ __('Profile') }}
                        </x-dropdown-link>

                        <!-- Authentication -->
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf

                            <x-dropdown-link :href="route('logout')"
                                    onclick="event.preventDefault();
                                                this.closest('form').submit();">
                                {{ __('Log Out') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                {{ __('Dashboard') }}
            </x-responsive-nav-link>

@hasanyrole('super-admin|finance')
    <x-nav-link :href="route('invoices.index')" :active="request()->routeIs('invoices.*')">Invoices</x-nav-link>
    <x-nav-link :href="route('payouts.index')" :active="request()->routeIs('payouts.*')">Payouts</x-nav-link>
    <x-nav-link :href="route('reports.finance')" :active="request()->routeIs('reports.finance*')">Finance</x-nav-link>
@endhasanyrole
            
@hasanyrole('super-admin|hr')
    <x-responsive-nav-link :href="route('hr.candidates.index')" :active="request()->routeIs('hr.candidates.*')">Recruitment</x-responsive-nav-link>
    <x-responsive-nav-link :href="route('hr.offboarding.index')" :active="request()->routeIs('hr.offboarding.*')">Offboarding</x-responsive-nav-link>
    <x-responsive-nav-link :href="route('hr.leave.index')" :active="request()->routeIs('hr.leave.*')">Leave</x-responsive-nav-link>
    <x-responsive-nav-link :href="route('hr.workload.index')" :active="request()->routeIs('hr.workload.*')">Workload</x-responsive-nav-link>
    <x-responsive-nav-link :href="route('hr.contracts.index')" :active="request()->routeIs('hr.contracts.*')">Contracts</x-responsive-nav-link>
    <x-responsive-nav-link :href="route('hr.scorecards.index')" :active="request()->routeIs('hr.scorecards.*')">Scorecards</x-responsive-nav-link>
    <x-nav-link :href="route('hr.reports.index')" :active="request()->routeIs('hr.reports.*')">Reports</x-nav-link>
@endhasanyrole

@role('super-admin')
    <x-responsive-nav-link :href="route('admin.users.index')" :active="request()->routeIs('admin.users.*')">Users</x-responsive-nav-link>
@endrole

@role('freelancer')
    <x-responsive-nav-link :href="route('freelancer.profile.edit')" :active="request()->routeIs('freelancer.profile.*')">My Skills & Rates</x-responsive-nav-link>
    <x-responsive-nav-link :href="route('my.projects')" :active="request()->routeIs('my.projects') || request()->routeIs('projects.board') || request()->routeIs('projects.tasks.*')">My Projects</x-responsive-nav-link>
    <x-responsive-nav-link :href="route('timesheet.index')" :active="request()->routeIs('timesheet.*')">Timesheet</x-responsive-nav-link>
    <x-responsive-nav-link :href="route('leave.index')" :active="request()->routeIs('leave.*')">Leave</x-responsive-nav-link>
    <x-responsive-nav-link :href="route('my.contracts')" :active="request()->routeIs('my.contracts*')">Contracts</x-responsive-nav-link>
    <x-nav-link :href="route('earnings.index')" :active="request()->routeIs('earnings.*')">Earnings</x-nav-link>
@endrole

        </div>

        <!-- Responsive Settings Options -->
        <div class="pt-4 pb-1 border-t border-gray-200">
            <div class="px-4">
                <div class="font-medium text-base text-gray-800">{{ Auth::user()->name }}</div>
                <div class="font-medium text-sm text-gray-500">{{ Auth::user()->email }}</div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')">
                    {{ __('Profile') }}
                </x-responsive-nav-link>

                <!-- Authentication -->
                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <x-responsive-nav-link :href="route('logout')"
                            onclick="event.preventDefault();
                                        this.closest('form').submit();">
                        {{ __('Log Out') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>
