
<!DOCTYPE html>
<html class="wide wow-animation" lang="en">
  <head>
    <title>Home</title>
    <meta name="format-detection" content="telephone=no">
    <meta name="viewport" content="width=device-width, height=device-height, initial-scale=1.0, maximum-scale=1.0, user-scalable=0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta charset="utf-8">
    <link rel="icon" href="{{ asset('public/frontend/images/logo.png') }}" type="image/x-icon">
    <link rel="stylesheet" type="text/css" href="https://fonts.googleapis.com/css?family=Arvo:400,700%7COpen+Sans:300,300italic,400,400italic,700italic,800%7CUbuntu:500">
    <link rel="stylesheet" href="{{ asset('public/frontend/css/bootstrap.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/fontawesome.min.css" integrity="sha512-SgaqKKxJDQ/tAUAAXzvxZz33rmn7leYDYfBP+YoMRSENhf3zJyx3SBASt/OfeQwBHA1nxMis7mM3EV/oYT6Fdw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="{{ asset('public/frontend/css/style.css') }}">

  </head>
  <body>
    <div class="page">
      <div class="page-loader page-loader-variant-1">
        <div><a class="brand brand-md brand-inverse" href="{{ route('index') }}"><img src="{{ asset('public/frontend/images/logo.png') }}" alt="" style="height: 150px; width: 150px;"/></a>
          <div class="page-loader-body">
            <div id="spinningSquaresG">
              <div class="spinningSquaresG" id="spinningSquaresG_1"></div>
              <div class="spinningSquaresG" id="spinningSquaresG_2"></div>
              <div class="spinningSquaresG" id="spinningSquaresG_3"></div>
              <div class="spinningSquaresG" id="spinningSquaresG_4"></div>
              <div class="spinningSquaresG" id="spinningSquaresG_5"></div>
              <div class="spinningSquaresG" id="spinningSquaresG_6"></div>
              <div class="spinningSquaresG" id="spinningSquaresG_7"></div>
              <div class="spinningSquaresG" id="spinningSquaresG_8"></div>
            </div>
          </div>
        </div>
      </div>
      <header class="page-head">
        <div class="rd-navbar-wrap">
          <nav class="rd-navbar rd-navbar-corporate-light" data-layout="rd-navbar-fixed" data-sm-layout="rd-navbar-fixed" data-md-layout="rd-navbar-fixed" data-md-device-layout="rd-navbar-fixed" data-lg-layout="rd-navbar-static" data-lg-device-layout="rd-navbar-static" data-xl-layout="rd-navbar-static" data-xl-device-layout="rd-navbar-static" data-xxl-layout="rd-navbar-static" data-xxl-device-layout="rd-navbar-static" data-lg-stick-up-offset="53px" data-xl-stick-up-offset="53px" data-xxl-stick-up-offset="53px" data-lg-stick-up="true" data-xl-stick-up="true" data-xxl-stick-up="true">
            <div class="bg-ebony-clay context-dark">
              <div class="rd-navbar-inner">
                <div class="rd-navbar-aside-wrap">
                  <div class="rd-navbar-aside">
                    <div class="rd-navbar-aside-toggle" data-rd-navbar-toggle=".rd-navbar-aside"><span></span></div>
                    <div class="rd-navbar-aside-content">
                      <ul class="rd-navbar-aside-group list-units">
                        <li>
                          <div class="unit flex-row unit-spacing-xs align-items-center">
                            <div class="unit-left"><span class="icon icon-xxs icon-primary fa-map-marker"></span></div>
                            <div class="unit-body"><a class="link-secondary" href="{{ route('index') }}#"> Udoji Road, along Odo-Aje Road, opposite Okobo Joint, Ilaro, Ogun State.</a></div>
                          </div>
                        </li>
                        <li>
                          <div class="unit flex-row unit-spacing-xs align-items-center">
                            <div class="unit-left"><span class="icon icon-xxs icon-primary fa-clock-o"></span></div>
                            <div class="unit-body"><span class="time">Mon – Sat: 9:00am–06:00pm. Sunday CLOSED</span></div>
                          </div>
                        </li>
                        <li>
                          <div class="unit flex-row unit-spacing-xs align-items-center">
                            <div class="unit-left"><span class="icon icon-xxs icon-primary fa-phone"></span></div>
                            <div class="unit-body"><a class="link-secondary" href="tel:#"> +234 815 127 3635, +234 8060957070</a></div>
                          </div>
                        </li>
                      </ul>
                      <div class="rd-navbar-aside-group">
                      <b>RCN: 14043</b>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div class="rd-navbar-inner">
              <div class="rd-navbar-group">
                <div class="rd-navbar-panel">
                  <button class="rd-navbar-toggle" data-rd-navbar-toggle=".rd-navbar-nav-wrap"><span></span></button><a class="rd-navbar-brand brand" href="{{ route('index') }}"><img src="{{ asset('public/frontend/images/logo.png') }}" alt="" style="height: 80px; width: 85px;" /></a>
                </div>
                <div class="rd-navbar-group-asside">
                  <div class="rd-navbar-nav-wrap">
                    <div class="rd-navbar-nav-inner">
                      <ul class="rd-navbar-nav">
                        <li class="{{ request()->path() == '/' ? 'active' : '' }}"><a href="{{ route('index') }}">Home</a>
                        </li>
                        <li class="{{ request()->path() == 'aboutUs' ? 'active' : '' }}"><a href="{{ route('about_us') }}">About us</a>
                        <li class="{{ request()->path() == 'ourHistory' ? 'active' : '' }}"><a href="{{ route('our_history') }}">Our History</a>
                        </li>
                        <li class="{{ request()->path() == 'leadership' ? 'active' : '' }}"><a href="{{ route('leadership') }}">Our Leadership</a>
                        </li>
                        <li class="{{ request()->path() == 'blogs' ? 'active' : '' }}"><a href="{{ route('blogs') }}">Blogs</a>
                        </li>
                        <li class="{{ request()->path() == 'contactUs' ? 'active' : '' }}"><a href="{{ route('contact_us') }}">Contact us</a>
                        </li>
                        <li ><a href="https://dashboard.oreoluwapoilarocoop.com.ng/">Login</a>
                        </li>
                      </ul>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </nav>
        </div>
      </header>
