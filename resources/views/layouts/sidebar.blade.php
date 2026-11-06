@php($isMentor = auth()->user()->isMentor())
      <!-- ========== Left Sidebar Start ========== -->
      <div class="left side-menu">
          <div class="slimscroll-menu" id="remove-scroll">

              <!--- Sidemenu -->
              <div id="sidebar-menu">

                  <!-- Left Menu Start -->
                  <ul class="metismenu" id="side-menu">
                      <li class="menu-title">{{ __('global.main') }}</li>
                      <li class="">
                          <a href="{{ route('admin') }}"
                              class="waves-effect {{ request()->is('admin') || request()->is('admin/*') ? 'mm active' : '' }}">
                              <i class="ti-home"></i> <span> {{ __('global.dashboard') }} </span>
                          </a>
                      </li>
                      <li class="">
                          <a href="/users"
                              class="waves-effect {{ request()->is('users') || request()->is('users/*') ? 'mm active' : '' }}">
                              <i class="ti-user"></i> <span> {{ __('global.users') }} </span>
                          </a>
                      </li>
@unless ($isMentor)
                      <li class="">
                          <a href="{{ route('admin.magang.dashboard') }}"
                              class="waves-effect {{ request()->is('admin/magang') || request()->is('admin/magang/*') ? 'mm active' : '' }}">
                              <i class="ti-write"></i> <span> Pendaftaran Magang </span>
                          </a>
                      </li>
@endunless

                      <li class="menu-title">{{ __('global.management') }}</li>

                      <li class="">
                          <a href="/sheet-report"
                              class="waves-effect {{ request()->is('sheet-report') || request()->is('sheet-report/*') ? 'mm active' : '' }}">
                              <i class="dripicons-to-do"></i> <span> {{ __('global.sheet_report') }} </span>
                          </a>
                      </li>
@unless ($isMentor)
                      <li class="">
                          <a href="{{ route('absence-requests.index', ['type' => 'all']) }}"
                              class="waves-effect {{ request()->is('absence-requests') || request()->is('absence-requests/*') ? 'mm active' : '' }}">
                              <i class="ti-alert"></i> <span> Ketidakhadiran </span>
                          </a>
                      </li>
@endunless

                      <li class="">
                          <a href="/attendance"
                              class="waves-effect {{ request()->is('attendance') || request()->is('attendance/*') ? 'mm active' : '' }}">
                              <i class="ti-calendar"></i> <span> {{ __('global.attendance_logs') }} </span>
                          </a>
                      </li>
                      <li class="">
                          <a href="{{ route('work-logs.index') }}"
                              class="waves-effect {{ request()->is('work-logs') || request()->is('work-logs/*') ? 'mm active' : '' }}">
                              <i class="ti-clipboard"></i> <span> Log Pekerjaan </span>
                          </a>
                      </li>
                      <li class="">
                          <a href="/monthly-report"
                              class="waves-effect {{ request()->is('monthly-report') || request()->is('monthly-report/*') ? 'mm active' : '' }}">
                              <i class="ti-folder"></i> <span> {{ __('global.monthly_report') }} </span>
                          </a>
                      </li>
                      <li class="">
                          <a href="/final-report"
                              class="waves-effect {{ request()->is('final-report') || request()->is('final-report/*') ? 'mm active' : '' }}">
                              <i class="ti-clip"></i> <span> {{ __('global.final_report') }} </span>
                          </a>
                      </li>
@unless ($isMentor)
                      <li class="">
                          <a href="/attendance-drafts"
                              class="waves-effect {{ request()->is('attendance-drafts') || request()->is('attendance-drafts/*') ? 'mm active' : '' }}">
                              <i class="ti-files"></i> <span> Draft Presensi </span>
                          </a>
                      </li>
                      <li class="">
                          <a href="/internship-draft-documents"
                              class="waves-effect {{ request()->is('internship-draft-documents') || request()->is('internship-draft-documents/*') ? 'mm active' : '' }}">
                              <i class="ti-agenda"></i> <span> Draft Dokumen Magang </span>
                          </a>
                      </li>
@endunless
@unless ($isMentor)
                      <li class="menu-title">{{ __('global.tools') }}</li>
                      <li class="">
                          <a href="/divisions"
                              class="waves-effect {{ request()->is('divisions') || request()->is('divisions/*') ? 'mm active' : '' }}">
                              <i class="ti-layout-grid2"></i> <span> Divisi </span>
                          </a>
                      </li>
                      <li class="">
                          <a href="/mentors"
                              class="waves-effect {{ request()->is('mentors') || request()->is('mentors/*') ? 'mm active' : '' }}">
                              <i class="ti-user"></i> <span> Mentor </span>
                          </a>
                      </li>
                      <li class="">
                          <a href="/national-holidays"
                              class="waves-effect {{ request()->is('national-holidays') || request()->is('national-holidays/*') ? 'mm active' : '' }}">
                              <i class="ti-calendar"></i> <span> Cuti Nasional </span>
                          </a>
                      </li>
@endunless

                      @if (auth()->user()->hasRole('super_admin'))
                          <li class="menu-title">Super Admin</li>
                          <li class="">
                              <a href="{{ route('accounts.index') }}"
                                  class="waves-effect {{ request()->is('account-management') || request()->is('account-management/*') ? 'mm active' : '' }}">
                                  <i class="ti-id-badge"></i> <span> Manajemen Akun </span>
                              </a>
                          </li>
                          <li class="">
                              <a href="{{ route('slides.index') }}"
                                  class="waves-effect {{ request()->is('slide-management') || request()->is('slide-management/*') ? 'mm active' : '' }}">
                                  <i class="ti-layout-slider"></i> <span> Manajemen Slide </span>
                              </a>
                          </li>
                      @endif

                  </ul>

              </div>
              <!-- Sidebar -->
              <div class="clearfix"></div>

          </div>
          <!-- Sidebar -left -->

      </div>
      <!-- Left Sidebar End -->
