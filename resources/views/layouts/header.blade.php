   <!-- Top Bar Start -->
   <div class="topbar">

       <!-- LOGO -->
       <div class="topbar-left">
           <a href="/" class="logo">
               <span class="logo-text">
                   <span class="cs-brand">
                       @include('layouts.brand-logo')
                   </span>
               </span>
               <i class="logo-text-mini">
                   <span class="cs-brand-mark"><img src="{{ asset('assets/images/logo-pln1.png') }}" alt="Logo PLN"></span>
               </i>
           </a>
       </div>

       <nav class="navbar-custom">
           <ul class="navbar-right d-flex list-inline float-right mb-0">
               <li class="dropdown notification-list">
                   <div class="dropdown notification-list nav-pro-img">
                       <a class="dropdown-toggle nav-link arrow-none waves-effect nav-user" data-toggle="dropdown"
                        href="#" role="button" aria-haspopup="false" aria-expanded="false">
                            @if(Auth::user()->profile_photo_path)
                                <img src="{{ asset('storage/' . Auth::user()->profile_photo_path) }}" 
                                    alt="User Profile" 
                                    class="rounded-circle noti-icon" 
                                    style="width: 32px; height: 32px; object-fit: cover; vertical-align: middle;">
                            @else
                                <i class="mdi mdi-account-circle noti-icon" style="font-size: 28px;"></i>
                            @endif
                        </a>
                       <div class="dropdown-menu dropdown-menu-right profile-dropdown ">
                           <!-- item-->
                           <a class="dropdown-item" href="{{ route('admin.profile') }}">
                                @if(Auth::user()->profile_photo_path)
                                    <img src="{{ asset('storage/' . Auth::user()->profile_photo_path) }}" 
                                        alt="Profile" 
                                        class="rounded-circle m-r-5" 
                                        style="width: 20px; height: 20px; object-fit: cover;">
                                @else
                                    <i class="mdi mdi-account-circle m-r-5"></i>
                                @endif
                                Profil
                            </a>

                           {{-- <a class="dropdown-item d-block" href="#"><span class="badge badge-success float-right">11</span><i class="mdi mdi-settings m-r-5"></i> Settings</a> --}}
                           <div class="dropdown-divider"></div>
                           <a class="dropdown-item text-danger" href="{{ route('logout') }}"
                               onclick="event.preventDefault();
                    document.getElementById('logout-form').submit();"><i
                                   class="mdi mdi-power text-danger"></i> {{ __('global.logout') }}</a>
                           <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                               @csrf
                           </form>
                       </div>
                   </div>
               </li>
           </ul>
           
           <ul class="list-inline menu-left mb-0">
               <li class="float-left">
                   <button class="button-menu-mobile open-left waves-effect">
                       <i class="mdi mdi-menu"></i>
                   </button>
               </li>
           </ul>
        </nav>
   </div>
   