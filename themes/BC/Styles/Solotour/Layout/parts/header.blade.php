<header id="header" class="header-style-8">
    <div class="container">
        <div class="header-sticky-menu header header--8">
            <a href="{{ url(app_get_locale(false, '/')) }}" class="toggle-menu">
                <i class="input-icon bc_border-radius field-icon fa">
                    <svg width="20" height="14" viewBox="0 0 20 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <rect width="20" height="2.40008" rx="1" fill="#123A32"></rect>
                        <rect y="5.7998" width="20" height="2.40008" rx="1" fill="#123A32"></rect>
                        <rect y="11.6001" width="20" height="2.40008" rx="1" fill="#123A32"></rect>
                    </svg>
                </i></a>
            <div class="header-left header-left--8 ">
                <a href="{{ url(app_get_locale(false, '/')) }}" class="logo hidden-xs">
                    @php
                        $logo_id = setting_item('solotour_logo_id');
                            if (!empty($row->custom_logo)) {
                                $logo_id = $row->custom_logo;
                            }
                    @endphp
                    @if ($logo_id)
                        <?php $logo = get_file_url($logo_id, 'full'); ?>
                        <img src="{{ $logo }}" alt="{{ setting_item('site_title') }}" width="211" height="54">
                    @endif
                </a>


                <nav id="bc_main-menu" class="bc_main--menu8">
                    <a href="#" class="back-menu">
                        <i class="input-icon bc_border-radius field-icon fa"><svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M0.50543 1.37553L7.11068 7.98078L0.505484 14.586C0.265374 14.8261 0.265374 15.2154 0.505483 15.4555C0.745593 15.6956 1.13489 15.6956 1.37499 15.4555L7.98019 8.8503L14.5855 15.4556C14.8256 15.6957 15.2149 15.6957 15.455 15.4556C15.6951 15.2155 15.6951 14.8262 15.455 14.5861L8.84971 7.98078L15.4551 1.37543C15.6952 1.13532 15.6952 0.746027 15.4551 0.505918C15.215 0.265808 14.8257 0.265809 14.5855 0.505918L7.98019 7.11127L1.37494 0.50602C1.13483 0.265911 0.745538 0.265911 0.505429 0.506021C0.26532 0.74613 0.265321 1.13542 0.50543 1.37553Z" fill="#123A32" stroke="#123A32" stroke-width="0.4"></path>
                            </svg>
                        </i>
                    </a>
                    @php
                        generate_menu('primary', [
                            'walker' => \Themes\BC\Styles\Solotour\Core\Walkers\SoloTourMenuWalker::class,
                            'custom_class' => 'menu main-menu',
                        ]);
                    @endphp
                </nav>
                <ul class="bc_list-mobile">
                </ul>
            </div>
            <a href="/" class="toggle-menu--user"><i class="input-icon bc_border-radius field-icon fa"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 0C8.51067 0 5.67188 2.8388 5.67188 6.32812C5.67188 9.81745 8.51067 12.6562 12 12.6562C15.4893 12.6562 18.3281 9.81745 18.3281 6.32812C18.3281 2.8388 15.4893 0 12 0Z" fill="#123A32"></path>
                        <path d="M19.8734 16.7904C18.1409 15.0313 15.8442 14.0625 13.4062 14.0625H10.5938C8.15588 14.0625 5.85909 15.0313 4.12659 16.7904C2.40258 18.5409 1.45312 20.8515 1.45312 23.2969C1.45312 23.6852 1.76794 24 2.15625 24H21.8438C22.2321 24 22.5469 23.6852 22.5469 23.2969C22.5469 20.8515 21.5974 18.5409 19.8734 16.7904Z" fill="#123A32"></path>
                    </svg>
                </i></a>

            <div class="header-login--mobile">
                <a href="#" class="back-menu--login"><i class="fa fa-angle-left"></i></a>
                <ul class="bc_list">
                    <li class="topbar-item login-item">
                        <a href="#" class="login" data-toggle="modal" data-target="#st-login-form">Login</a>
                    </li>

                    <li class="topbar-item signup-item">
                        <a href="#" class="signup" data-toggle="modal" data-target="#st-register-form">Sign Up</a>
                    </li>

                </ul>
            </div>
            <div class="header-right header-right--8 d-flex">

                {!! clean(setting_item_with_lang('solotour_topbar_text')) !!}   

                <ul class="bc_list">
                   
                    <li class="topbar-item login-item hidden-xs hidden-sm">
                        <a href="#" class="login" data-toggle="modal" data-target="#st-login-form">Login</a>
                    </li>

                    <li class="topbar-item signup-item hidden-xs hidden-sm">
                        <a href="#" class="signup" data-toggle="modal" data-target="#st-register-form">Sign Up</a>
                    </li>


                </ul>
            </div>
        </div>
    </div>
</header>