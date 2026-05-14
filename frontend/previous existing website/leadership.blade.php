@php
  $bgImage1 = asset('public/frontend/images/background/IMG_1711-Background.jpeg');
  $bgImage2 = asset('public/frontend/images/background/bg-image-9.jpg');
@endphp
@include('frontend.header')

      <section class="section-40 section-md-60 section-lg-90 section-xl-120 bg-gray-dark page-title-wrap overlay-5" style="background-image: url('{{ $bgImage1 }}');">
        <div class="container">
          <div class="page-title text-center">
            <h2>Our Leadership</h2>
          </div>
        </div>
      </section>

      <section class="section-top-66 section-bottom-30 section-md-bottom-40">
        <div class="container">
          <h3 class="text-center">Our Leaders</h3>
          <div class="row row-60">
            <div class="col-sm-6 col-md-4 col-lg-3">
              <div class="thumbnail thumbnail-variant-1">
                <div class="thumbnail-image"><img src="{{ asset('public/frontend/images/potraits/Bola_Tinubu_portrait.jpg') }}" alt="" style="height: 185px; width: 185px;" />
                  <div class="thumbnail-image-inner"><a class="icon icon-md material-icons-local_phone link-primary-inverse-v2" href="tel:#"></a><a class="icon icon-md-smaller fa-envelope-o link-white" href="mailto:#"></a></div>
                </div>
                <div class="thumbnail-caption">
                  <h5 class="header"><a href="#">Bola Ahmed Tinubu</a></h5>
                  <p class="text-gray-05">President of Nigeria</p>
                </div>
              </div>
            </div>
            <div class="col-sm-6 col-md-4 col-lg-3">
              <div class="thumbnail thumbnail-variant-1">
                <div class="thumbnail-image"><img src="{{ asset('public/frontend/images/potraits/dapo-abiodun.jpeg') }}" alt=""  style="height: 185px; width: 185px;" />
                  <div class="thumbnail-image-inner"><a class="icon icon-md material-icons-local_phone link-primary-inverse-v2" href="tel:#"></a><a class="icon icon-md-smaller fa-envelope-o link-white" href="mailto:#"></a></div>
                </div>
                <div class="thumbnail-caption">
                  <h5 class="header"><a href="#">Prince Dapo Abiodun</a></h5>
                  <p class="text-gray-05">Ogun state governor</p>
                </div>
              </div>
            </div>
            <div class="col-sm-6 col-md-4 col-lg-3">
              <div class="thumbnail thumbnail-variant-1">
                <div class="thumbnail-image"><img src="{{ asset('public/frontend/images/potraits/naimot-salako.jpeg') }}" alt=""  style="height: 185px; width: 185px;"/>
                  <div class="thumbnail-image-inner"><a class="icon icon-md material-icons-local_phone link-primary-inverse-v2" href="tel:#"></a><a class="icon icon-md-smaller fa-envelope-o link-white" href="mailto:#"></a></div>
                </div>
                <div class="thumbnail-caption">
                  <h5 class="header"><a href="#">Naimat Salako Oyedele</a></h5>
                  <p class="text-gray-05">Ogun state deputy governor </p>
                </div>
              </div>
            </div>
            <div class="col-sm-6 col-md-4 col-lg-3">
              <div class="thumbnail thumbnail-variant-1"> 
                <div class="thumbnail-image"><img src="{{ asset('public/frontend/images/potraits/director-coperative-service.png') }}" alt="" style="height: 185px; width: 185px;"/>
                  <div class="thumbnail-image-inner"><a class="icon icon-md material-icons-local_phone link-primary-inverse-v2" href="tel:#"></a><a class="icon icon-md-smaller fa-envelope-o link-white" href="mailto:#"></a></div>
                </div>
                <div class="thumbnail-caption">
                  <h5 class="header"><a href="#">Pst. Sam Mustapha</a></h5>
                  <p class="text-gray-05">Director of Cooperative services ogun state</p>
                </div>
              </div>
            </div>
            <div class="col-sm-6 col-md-4 col-lg-3">
              <div class="thumbnail thumbnail-variant-1">
                <div class="thumbnail-image"><img src="{{ asset('public/frontend/images/potraits/president-ogun-coperative.png') }}"  style="height: 185px; width: 185px;" />
                  <div class="thumbnail-image-inner"><a class="icon icon-md material-icons-local_phone link-primary-inverse-v2" href="tel:#"></a><a class="icon icon-md-smaller fa-envelope-o link-white" href="mailto:#"></a></div>
                </div>
                <div class="thumbnail-caption">
                  <h5 class="header"><a href="#">Alhaji Wasiu Olaleye FICP</a></h5>
                  <p class="text-gray-05">President Ogun State Cooperative Federation Limited.</p>
                </div>
              </div>
            </div>
            <div class="col-sm-6 col-md-4 col-lg-3">
              <div class="thumbnail thumbnail-variant-1">
                <div class="thumbnail-image"><img src="{{ asset('public/frontend/images/potraits/director lagos.png') }}" alt="" style="height: 185px; width: 185px;"/>
                  <div class="thumbnail-image-inner"><a class="icon icon-md material-icons-local_phone link-primary-inverse-v2" href="tel:#"></a><a class="icon icon-md-smaller fa-envelope-o link-white" href="mailto:#"></a></div>
                </div>
                <div class="thumbnail-caption">
                  <h5 class="header"><a href="#">Mrs Zulikha Olufunmilayo </a></h5>
                  <p class="text-gray-05">Director Cooperative services Lagos State</p>
                </div>
              </div>
            </div>
            <div class="col-sm-6 col-md-4 col-lg-3">
              <div class="thumbnail thumbnail-variant-1">
                <div class="thumbnail-image"><img src="{{ asset('public/frontend/images/potraits/ica.jpeg') }}" alt="" style="height: 185px; width: 185px;"/>
                  <div class="thumbnail-image-inner"><a class="icon icon-md material-icons-local_phone link-primary-inverse-v2" href="tel:#"></a><a class="icon icon-md-smaller fa-envelope-o link-white" href="mailto:#"></a></div>
                </div>
                <div class="thumbnail-caption">
                  <h5 class="header"><a href="#">Chief Ayeola Tajudeen </a></h5>
                  <p class="text-gray-05">President, International Cooperative Alliance
                  </p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>
      <section class="section-top-30 section-md-top-40 section-bottom-66 section-md-bottom-90 section-xl-bottom-120">
        <div class="container">
          <h3 class="text-center">Meet Our Team</h3>
          <div class="row row-40">
            
            <div class="col-sm-6 col-md-4 col-lg-4">
              <div class="thumbnail-variant-2-wrap">
                <div class="thumbnail thumbnail-variant-2">
                  <figure class="thumbnail-image"><img src="{{ asset('public/frontend/images/potraits/President-Oreoluwapo-Ibafo.jpeg') }}" alt=""  style="height: 200px; width: 250px;"/>
                  </figure>
                  <div class="thumbnail-inner">
                    <!-- <div class="link-group"><span class="icon icon-xxs icon-primary material-icons-local_phone"></span><a class="link-white" href="tel:#">+1 (409) 987–5874</a></div> -->
                    <!-- <div class="link-group"><span class="icon icon-xxs icon-primary fa-envelope-o"></span><a class="link-white" href="mailto:#">info@demolink.org</a></div> -->
                  </div>
                  <div class="thumbnail-caption">
                    <p class="text-header"><a href="">Alhaji Fatoki Aderemi Ganiu</a></p>
                    <div class="divider divider-md"></div>
                    <p class="text-caption">President Oreoluwapo Ibafo.</p>
                  </div>
                </div>
              </div>
            </div>
            <div class="col-sm-6 col-md-4 col-lg-4">
              <div class="thumbnail-variant-2-wrap">
                <div class="thumbnail thumbnail-variant-2">
                  <figure class="thumbnail-image"><img src="{{ asset('public/frontend/images/potraits/President-Oreoluwapo-Ilaro.jpeg') }}" alt="" style="height: 200px; width: 250px;"/>
                  </figure>
                  <div class="thumbnail-inner">
                    <!-- <div class="link-group"><span class="icon icon-xxs icon-primary material-icons-local_phone"></span><a class="link-white" href="tel:#">+1 (409) 987–5874</a></div> -->
                    <!-- <div class="link-group"><span class="icon icon-xxs icon-primary fa-envelope-o"></span><a class="link-white" href="mailto:#">info@demolink.org</a></div> -->
                  </div>
                  <div class="thumbnail-caption">
                    <p class="text-header"><a href="#">Adegbite Adewale Abiodun</a></p>
                    <div class="divider divider-md"></div>
                    <p class="text-caption">President Oreoluwapo Ilaro</p>
                  </div>
                </div>
              </div>
            </div>
            <div class="col-sm-6 col-md-4 col-lg-4">
              <div class="thumbnail-variant-2-wrap">
                <div class="thumbnail thumbnail-variant-2">
                  <figure class="thumbnail-image"><img src="{{ asset('public/frontend/images/potraits/director-general-oyosowapo.jpeg') }}" alt=""  style="height: 200px; width: 250px;"/>
                  </figure>
                  <div class="thumbnail-inner">
                    <!-- <div class="link-group"><span class="icon icon-xxs icon-primary material-icons-local_phone"></span><a class="link-white" href="tel:#">+1 (409) 987–5874</a></div> -->
                    <!-- <div class="link-group"><span class="icon icon-xxs icon-primary fa-envelope-o"></span><a class="link-white" href="mailto:#">info@demolink.org</a></div> -->
                  </div>
                  <div class="thumbnail-caption">
                    <p class="text-header"><a href="#">Alhaji Dr Abdul-Lateef Akinola</a></p>
                    <div class="divider divider-md"></div>
                    <p class="text-caption">DG. Oyosowapo group of cooperative</p>
                  </div>
                </div>
              </div>
            </div>
    

          </div>
        </div>
      </section>

      @include('frontend.footer')