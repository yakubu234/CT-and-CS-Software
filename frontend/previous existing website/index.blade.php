@php
  $bgImage1 = asset('public/frontend/images/background/bg-image-12.jpg');
  $bgImage2 = asset('public/frontend/images/background/bg-image-9.jpg');
@endphp
@include('frontend.header')

      <section>
        <div class="swiper-container swiper-slider swiper-variant-1 bg-black" data-loop="true" data-autoplay="5000" data-simulate-touch="true">
          <div class="swiper-wrapper text-center">
            <div class="swiper-slide swiper-slide-bottom overlay-5" data-slide-bg="{{ asset('public/frontend/images/slider/IMG_1636.jpeg') }}">
              <div class="swiper-slide-caption">
                <div class="container">
                  <div class="row justify-content-md-center">
                    <div class="col-md-11 col-lg-10 col-xl-9">
                      <div class="shilder-header-with-divider" data-caption-animate="fadeInUp" data-caption-delay="0s">Over 135 Thriving Cooperative Societies</div>
                      <h2 class="slider-header" data-caption-animate="fadeInUp" data-caption-delay="100s">Empowering Communities<br>Through Cooperative Growth</h2><a class="button button-icon button-icon-right button-primary big" data-caption-animate="fadeInUp" data-caption-delay="250" href="{{ route('index') }}#"><span class="icon icon-xs fa-angle-right"></span>Join Us Today</a>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div class="swiper-slide overlay-5" data-slide-bg="{{ asset('public/frontend/images/slider/IMG_1709.jpeg') }}">
              <div class="swiper-slide-caption">
                <div class="container">
                  <div class="row justify-content-md-center">
                    <div class="col-xl-1 d-none d-xl-inline-block d-xxl-none"></div>
                    <div class="col-md-11 col-lg-10 col-xl-9">
                      <h2 class="slider-header" data-caption-animate="fadeInUp" data-caption-delay="0s">Need Financial Growth & Security?</h2>
                      <p class="text-bigger text-regular slider-text" data-caption-animate="fadeInUp" data-caption-delay="100">Oreoluwapo Ilaro provides trusted cooperative financial solutions
                        with transparency and integrity.</p><a class="button button-icon button-icon-right button-primary big" data-caption-animate="fadeInUp" data-caption-delay="250" href="{{ route('index') }}#"><span class="icon icon-xs fa-angle-right"></span>Get Started</a>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div class="swiper-slide swiper-slide-bottom overlay-5" data-slide-bg="{{ asset('public/frontend/images/slider/IMG_1711.jpeg') }}">
              <div class="swiper-slide-caption">
                <div class="container">
                  <div class="row justify-content-md-center">
                    <div class="col-md-11 col-lg-10 col-xl-9">
                      <div class="shilder-header-with-divider" data-caption-animate="fadeInUp" data-caption-delay="0s">A Cooperative Society You Can Trust</div>
                      <h2 class="slider-header" data-caption-animate="fadeInUp" data-caption-delay="100s"> Building Wealth & Opportunities<br>For Our Members</h2><a class="button button-icon button-icon-right button-primary big" data-caption-animate="fadeInUp" data-caption-delay="250" href="{{ route('index') }}#"><span class="icon icon-xs fa-angle-right"></span>Learn More</a>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="swiper-scrollbar d-xl-none"></div>
          <div class="swiper-nav-wrap">
            <div class="swiper-button-prev"></div>
            <div class="swiper-button-next"></div>
          </div>
        </div>
      </section>

      <section class="section-50 section-md-90">
        <div class="container text-center">
          <h3>Our Services</h3>
          <div class="row row-40 row-offset-3">
            <div class="col-md-6 col-lg-4 height-fill">
              <article class="icon-box">
                <div class="box-top">
                  <div class="box-icon"><span class="icon icon-primary icon-lg-bigger material-icons-archive"></span></div>
                  <div class="box-header">
                    <h5><a href="{{ route('index') }}#">Weekly Contributions</a></h5>
                  </div>
                </div>
                <div class="divider"></div>
                <div class="box-body">
                  <p class="text-gray-05">These kinds of societies operate once in week (depends on the day you want)</p>
                </div>
              </article>
            </div>
            <div class="col-md-6 col-lg-4 height-fill">
              <article class="icon-box">
                <div class="box-top">
                  <div class="box-icon"><span class="icon icon-primary icon-lg-bigger  material-icons-beenhere"></span></div>
                  <div class="box-header">
                    <h5><a href="{{ route('index') }}#">15/15 days Contributions</a></h5>
                  </div>
                </div>
                <div class="divider"></div>
                <div class="box-body">
                  <p class="text-gray-05">These kinds of societies operate every two/two week</p>
                </div>
              </article>
            </div>
            <div class="col-md-6 col-lg-4 height-fill">
              <article class="icon-box">
                <div class="box-top">
                  <div class="box-icon"><span class="icon icon-primary icon-lg-bigger material-icons-account_balance_wallet"></span></div>
                  <div class="box-header">
                    <h5><a href="{{ route('index') }}#">Monthly Contributions</a></h5>
                  </div>
                </div>
                <div class="divider"></div>
                <div class="box-body">
                  <p class="text-gray-05">These kinds of societies operate once in a month. (Every last Saturday of the month)</p>
                </div>
              </article>
            </div>
          </div>
        </div>
      </section>

      <section class="bg-whisperapprox">
        <div class="container">
          <div class="row justify-content-sm-center justify-content-md-start">
            <div class="col-md-6 context-dark section-image-aside section-image-aside-left pos-relative-before-sm section-60 section-md-0 d-md-flex">
              <div class="section-image-aside-img" style="background-image: url('{{ $bgImage2 }}')" >
                <div class="section-bordered-inside"></div>
              </div>
              <div class="row align-items-md-center offset-top-0">
                <div class="col-lg-10 col-xl-9 to-front">
                  <div class="inset-lg-left-5">
                    <h3>Who We Are!</h3>
                    <div class="unit  flex-sm-row">
                      <div class="unit-body">
                        <h6>Ore-Oluwapo Ilaro Cooperative Society</h6>
                        <p class="text-white-05 justify" style="text-align: justify;">We are a dynamic and growing cooperative society dedicated to fostering financial growth, trust, and community development. Established in 2018 with just two societies under the umbrella of Oreoluwapo Ibafo, we officially became a recognized union on October 18, 2021, with eighteen societies.<br><br>

                          Today, we have expanded significantly, boasting at least 135 cooperative societies across various local government areas in Ogun State and beyond, with continuous growth and impact. Our society is also an esteemed member of the Oyosowapo Group of Cooperatives, headquartered in Ogudu, Lagos State.</p>
                      </div>
                    </div>

                    <a class="button button-icon button-icon-right button-primary big buttons-inset-horizontal-15" href="{{ route('about_us') }}"><span class="icon icon-xs fa-angle-right"></span>Learn More</a>
                  </div>
                </div>
              </div>
            </div>
            <div class="col-sm-10 col-md-6 text-center section-60 section-md-90">
              <div class="inset-left-100">
                <h3>Our Core Values!</h3>
                <div class="box-photo-frame"><img src="{{ asset('public/frontend/images/our core values.png') }}" alt="" width="510" height="268"/>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <section class="bg-whisperapprox">
        <div class="container">
          <div class="row justify-content-sm-center justify-content-md-start">
            <div class="col-sm-10 col-md-6 text-left section-60 section-md-90">
              <div class="inset-right-30">
                <h3>Our Mission</h3>
                <!-- <h6>Our Investment Principles</h6> -->
                <p style="text-align: justify;">To actively empower our members through collaborative efforts, providing accessible and affordable services, promoting democratic decision-making, and prioritizing the collective well-being over individual profit.</p>
                <h3>Our Vission</h3>
                <p></p>
                <!-- <h6>Our Investment Principles</h6> -->
                <p style="text-align: justify;">To create a thriving community where our members collectively own and Manage Economic resources, Fostering social equity, sustainable development, and improved quality of life for all our members .</p>
                 <div class="br-space" ></div>
                <p><b> Cooperative with trust and God’s fearing....</b></p>
              </div>
            </div>
            <div class="col-md-6 context-dark section-image-aside section-image-aside-right pos-relative-before-sm section-60 section-md-0 d-md-flex">
              <div class="section-image-aside-img"  style="background-image: url('{{ $bgImage1 }}')">
                <div class="section-bordered-inside"></div>
              </div>
              <div class="row align-items-md-center offset-top-0">
                <div class="col-md-11 col-lg-10 to-front offset-md-1 offset-lg-2">
                  <h3>Why Choose Us?</h3>
                  <h5>At Oreoluwapo Ilaro, we believe in cooperative growth with trust and integrity, ensuring that our members thrive together</h5>
                  <!-- <p class="text-white-05">TAt Oreoluwapo Ilaro, we believe in cooperative growth with trust and integrity, ensuring that our members thrive together.</p> -->
                  <ul class="list-marked">
                    <li class="text-white">Trusted & Established Cooperative </li>
                    <li class="text-white">Expanding Network & Growth Opportunities</li>
                    <li class="text-white">Commitment to Financial Security & Transparency</li>
                    <li class="text-white">Empowering Local Businesses & Communities</li>
                    <li class="text-white">Flexible Savings & Loan Plans</li>
                    <li class="text-white">Strong Leadership & Governance</li>
                    <li class="text-white">Faith-Based & Ethical Values</li>
                  </ul><a class="button button-icon button-icon-right button-primary big" href=""><span class="icon icon-xs fa-angle-right"></span>Contact us Now!</a>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>


      <section class="section-50 section-md-100">
        <div class="container">
          <h3 class="text-center">Meet Our Founders</h3>
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
      <section class="section parallax-container bg-black" data-parallax-img="{{ asset('public/frontend/images/background/IMG_1709-Background.jpeg') }}">
        <div class="parallax-content">
          <div class="section-60 section-md-100 overlay-9">
            <div class="container">
              <h3 class="text-center">Facts and Numbers</h3>
              <div class="row row-40 row-offset-1 align-items-sm-end">
                <div class="col-sm-6 col-md-3">
                  <div class="box-counter"><span class="icon icon-13"></span>
                    <div class="text-large counter">135+</div>
                    <h5 class="box-header">Cooperative Society</h5>
                  </div>
                </div>
                <div class="col-sm-6 col-md-3">
                  <div class="box-counter"><span class="icon icon-12"></span>
                    <div class="text-large counter">18+</div>
                    <h5 class="box-header">Awards</h5>
                  </div>
                </div>
                <div class="col-sm-6 col-md-3">
                  <div class="box-counter"><span class="icon icon-11"></span>
                    <div class="text-large counter">7+</div>
                    <h5 class="box-header">Years of Experience</h5>
                  </div>
                </div>
                <div class="col-sm-6 col-md-3">
                  <div class="box-counter"><span class="icon icon-14"></span>
                    <div class="text-large counter counter">27</div>
                    <h5 class="box-header">Staffs</h5>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- <section class="section parallax-container bg-black" data-parallax-img="images/bg-image-6.jpg">
        <div class="parallax-content">
          <div class="section-66 section-md-90 section-xl-bottom-120 context-dark overlay-9">
            <div class="container">
              <h3 class="text-center">Testimonials</h3>
              <div class="owl-carousel owl-quote-bordered owl-nav-style-1" data-items="1" data-autoplay="true" data-lg-items="2" data-stage-padding="0" data-loop="true" data-margin="30" data-mouse-drag="true" data-nav="true" data-dots="true" data-dots-each="1">
                <div class="owl-item">
                  <div class="inset-xl-left-60 inset-xl-right-40">
                    <blockquote class="quote-bordered">
                      <div class="quote-body">
                        <div class="quote-open">
                          <svg version="1.1" baseprofile="tiny" xmlns="https://www.w3.org/2000/svg" xmlns:xlink="https://www.w3.org/1999/xlink" width="37px" height="27px" viewbox="0 0 21 15" preserveAspectRatio="none">
                            <path d="M9.597,10.412c0,1.306-0.473,2.399-1.418,3.277c-0.944,0.876-2.06,1.316-3.349,1.316                    c-1.287,0-2.414-0.44-3.382-1.316C0.482,12.811,0,11.758,0,10.535c0-1.226,0.58-2.716,1.739-4.473L5.603,0H9.34L6.956,6.37                    C8.716,7.145,9.597,8.493,9.597,10.412z M20.987,10.412c0,1.306-0.473,2.399-1.418,3.277c-0.944,0.876-2.06,1.316-3.35,1.316                    c-1.288,0-2.415-0.44-3.381-1.316c-0.966-0.879-1.45-1.931-1.45-3.154c0-1.226,0.582-2.716,1.74-4.473L16.994,0h3.734l-2.382,6.37                    C20.106,7.145,20.987,8.493,20.987,10.412z"></path>
                          </svg>
                        </div>
                        <div class="quote-body-inner">
                          <h6>Highly Professional Team</h6>
                          <p>
                            <q>Working with Investment Smart has been a great experience. An exceptional group of people who are well versed in all legal, accounting, and compliance aspects of fund administration for onshore or offshore funds. I highly recommend them.</q>
                          </p>
                        </div>
                      </div>
                      <div class="quote-footer">
                        <div class="unit flex-row unit-spacing-sm align-items-center">
                          <div class="unit-left"><img class="img-circle" src="https://livedemo00.template-help.com/wt_68347/images/clients-testimonials-1-68x68.jpg" width="68" height="68" alt=""/>
                          </div>
                          <div class="unit-body">
                            <cite class="text-white">Emily Wilson</cite>
                            <p class="text-primary">Top Manager</p>
                          </div>
                        </div>
                      </div>
                    </blockquote>
                  </div>
                </div>
                <div class="owl-item">
                  <div class="inset-xl-left-60 inset-xl-right-40">
                    <blockquote class="quote-bordered">
                      <div class="quote-body">
                        <div class="quote-open">
                          <svg version="1.1" baseprofile="tiny" xmlns="https://www.w3.org/2000/svg" xmlns:xlink="https://www.w3.org/1999/xlink" width="37px" height="27px" viewbox="0 0 21 15" preserveAspectRatio="none">
                            <path d="M9.597,10.412c0,1.306-0.473,2.399-1.418,3.277c-0.944,0.876-2.06,1.316-3.349,1.316                    c-1.287,0-2.414-0.44-3.382-1.316C0.482,12.811,0,11.758,0,10.535c0-1.226,0.58-2.716,1.739-4.473L5.603,0H9.34L6.956,6.37                    C8.716,7.145,9.597,8.493,9.597,10.412z M20.987,10.412c0,1.306-0.473,2.399-1.418,3.277c-0.944,0.876-2.06,1.316-3.35,1.316                    c-1.288,0-2.415-0.44-3.381-1.316c-0.966-0.879-1.45-1.931-1.45-3.154c0-1.226,0.582-2.716,1.74-4.473L16.994,0h3.734l-2.382,6.37                    C20.106,7.145,20.987,8.493,20.987,10.412z"></path>
                          </svg>
                        </div>
                        <div class="quote-body-inner">
                          <h6>Real Experts in Investment Management</h6>
                          <p>
                            <q>These guys are efficient! From the first moment that I dealt with Investment Smart I knew that they were real pros. They are asking the right questions, and when getting the answers they are on ball non-stop, providing an excellent service!</q>
                          </p>
                        </div>
                      </div>
                      <div class="quote-footer">
                        <div class="unit flex-row unit-spacing-sm align-items-center">
                          <div class="unit-left"><img class="img-circle" src="https://livedemo00.template-help.com/wt_68347/images/clients-testimonials-2-68x68.jpg" width="68" height="68" alt=""/>
                          </div>
                          <div class="unit-body">
                            <cite class="text-white">Dennis Lewis</cite>
                            <p class="text-primary">Civil Servant</p>
                          </div>
                        </div>
                      </div>
                    </blockquote>
                  </div>
                </div>
                <div class="owl-item">
                  <div class="inset-xl-left-60 inset-xl-right-40">
                    <blockquote class="quote-bordered">
                      <div class="quote-body">
                        <div class="quote-open">
                          <svg version="1.1" baseprofile="tiny" xmlns="https://www.w3.org/2000/svg" xmlns:xlink="https://www.w3.org/1999/xlink" width="37px" height="27px" viewbox="0 0 21 15" preserveAspectRatio="none">
                            <path d="M9.597,10.412c0,1.306-0.473,2.399-1.418,3.277c-0.944,0.876-2.06,1.316-3.349,1.316                    c-1.287,0-2.414-0.44-3.382-1.316C0.482,12.811,0,11.758,0,10.535c0-1.226,0.58-2.716,1.739-4.473L5.603,0H9.34L6.956,6.37                    C8.716,7.145,9.597,8.493,9.597,10.412z M20.987,10.412c0,1.306-0.473,2.399-1.418,3.277c-0.944,0.876-2.06,1.316-3.35,1.316                    c-1.288,0-2.415-0.44-3.381-1.316c-0.966-0.879-1.45-1.931-1.45-3.154c0-1.226,0.582-2.716,1.74-4.473L16.994,0h3.734l-2.382,6.37                    C20.106,7.145,20.987,8.493,20.987,10.412z"></path>
                          </svg>
                        </div>
                        <div class="quote-body-inner">
                          <h6>A Team of Dedicated Investment Management Specialists</h6>
                          <p>
                            <q>It took me a while to find someone who could accomplish what I needed for my company. I have to say it was well worth the wait. Investment Smart is a team of real professionals who showed their hard work, patience, and professionalism, and they far exceeded my expectations.</q>
                          </p>
                        </div>
                      </div>
                      <div class="quote-footer">
                        <div class="unit flex-row unit-spacing-sm align-items-center">
                          <div class="unit-left"><img class="img-circle" src="https://livedemo00.template-help.com/wt_68347/images/clients-testimonials-9-68x68.jpg" width="68" height="68" alt=""/>
                          </div>
                          <div class="unit-body">
                            <cite class="text-white">Jack McGee</cite>
                            <p class="text-primary">Founder, The Therapy</p>
                          </div>
                        </div>
                      </div>
                    </blockquote>
                  </div>
                </div>
                <div class="owl-item">
                  <div class="inset-xl-left-60 inset-xl-right-40">
                    <blockquote class="quote-bordered">
                      <div class="quote-body">
                        <div class="quote-open">
                          <svg version="1.1" baseprofile="tiny" xmlns="https://www.w3.org/2000/svg" xmlns:xlink="https://www.w3.org/1999/xlink" width="37px" height="27px" viewbox="0 0 21 15" preserveAspectRatio="none">
                            <path d="M9.597,10.412c0,1.306-0.473,2.399-1.418,3.277c-0.944,0.876-2.06,1.316-3.349,1.316                    c-1.287,0-2.414-0.44-3.382-1.316C0.482,12.811,0,11.758,0,10.535c0-1.226,0.58-2.716,1.739-4.473L5.603,0H9.34L6.956,6.37                    C8.716,7.145,9.597,8.493,9.597,10.412z M20.987,10.412c0,1.306-0.473,2.399-1.418,3.277c-0.944,0.876-2.06,1.316-3.35,1.316                    c-1.288,0-2.415-0.44-3.381-1.316c-0.966-0.879-1.45-1.931-1.45-3.154c0-1.226,0.582-2.716,1.74-4.473L16.994,0h3.734l-2.382,6.37                    C20.106,7.145,20.987,8.493,20.987,10.412z"></path>
                          </svg>
                        </div>
                        <div class="quote-body-inner">
                          <h6>The Best Investment Management Company</h6>
                          <p>
                            <q>It has been a pleasure to work with Investment Smart and their team of investment experts. They offered me a quick and easy solution to my business investment strategy that will surely help me in handling my future spendings. I’m glad to cooperate with this qualified team.</q>
                          </p>
                        </div>
                      </div>
                      <div class="quote-footer">
                        <div class="unit flex-row unit-spacing-sm align-items-center">
                          <div class="unit-left"><img class="img-circle" src="https://livedemo00.template-help.com/wt_68347/images/clients-testimonials-11-113x113.jpg" width="68" height="68" alt=""/>
                          </div>
                          <div class="unit-body">
                            <cite class="text-white">Jill Miller</cite>
                            <p class="text-primary">Regional Manager</p>
                          </div>
                        </div>
                      </div>
                    </blockquote>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section> -->


      <section class="section-50 section-md-90 section-md-bottom-100">
        <div class="container text-center">
          <h3>Latest News</h3>
          <div class="row row-40 row-offset-6 justify-content-sm-center">
            <div class="col-sm-8 col-md-5 col-xl-3">
              <div class="post-boxed d-xl-inline-block">
                <div class="post-boxed-img-wrap"><a href="{{ route('blog_five') }}"><img src="{{ asset('public/frontend/images/blogs/blog_six.jpg') }}" alt="" style="width: 268px; height: 162px;"/></a></div>
                <div class="post-boxed-caption">
                  <div class="post-boxed-title font-weight-bold"><a href="{{ route('blog_five') }}">Giving Hope, Sharing Love: Widows &amp; Elderly Outreach 2026</a></div>
                  <ul class="list-inline list-inline-dashed text-uppercase font-accent">
                    <li>APR 04, 2026</li>
                    <li><span>by <a class="text-primary" href="{{ route('index') }}#">ADMIN</a></span></li>
                  </ul>
                </div>
              </div>
            </div>

            <div class="col-sm-8 col-md-5 col-xl-3">
              <div class="post-boxed d-xl-inline-block">
                <div class="post-boxed-img-wrap"><a href="{{ route('blog_one') }}"><img src="{{ asset('public/frontend/images/blogs/IMG_1852.jpeg') }}" alt="" style="width: 268px; height: 162px;" /></a></div>
                <div class="post-boxed-caption">
                  <div class="post-boxed-title font-weight-bold"><a href="{{ route('blog_one') }}"> The Power of Investment in Cooperatives: Strength in Unity</a></div>
                  <div class="offset-top-5">
                    <ul class="list-inline list-inline-dashed text-uppercase font-accent">
                      <li>FEB 10, 2025</li>
                      <li><span>by <a class="text-primary" href="{{ route('index') }}">ADMIN</a></span></li>
                    </ul>
                  </div>
                </div>
              </div>
            </div>
            <div class="col-sm-8 col-md-5 col-xl-3">
              <div class="post-boxed d-xl-inline-block">
                <div class="post-boxed-img-wrap"><a href="{{ route('blog_two') }}"><img src="{{ asset('public/frontend/images/blogs/IMG_1853.jpeg') }}" alt="" style="width: 268px; height: 162px;" /></a></div>
                <div class="post-boxed-caption">
                  <div class="post-boxed-title font-weight-bold"><a href="{{ route('blog_two') }}">Cooperative Investments: Empowering Members for a Brighter Future...</a></div>
                  <ul class="list-inline list-inline-dashed text-uppercase font-accent">
                    <li>JAN 20, 2025</li>
                    <li><span>by <a class="text-primary" href="{{ route('blog_two') }}">ADMIN</a></span></li>
                  </ul>
                </div>
              </div>
            </div>
            <div class="col-sm-8 col-md-5 col-xl-3">
              <div class="post-boxed d-xl-inline-block">
                <div class="post-boxed-img-wrap"><a href="{{ route('blog_three') }}"><img src="{{ asset('public/frontend/images/blogs/agro-farmer.jpeg') }}" alt="" style="width: 268px; height: 162px;" /></a></div>
                <div class="post-boxed-caption">
                  <div class="post-boxed-title font-weight-bold"><a href="{{ route('blog_three') }}">Supporting Agricultural Produce – Empowering Farmers in Cooperatives</a></div>
                  <ul class="list-inline list-inline-dashed text-uppercase font-accent">
                    <li>DEC 23, 2024</li>
                    <li><span>by <a class="text-primary" href="{{ route('index') }}#">ADMIN</a></span></li>
                  </ul>
                </div>
              </div>
            </div>

          </div>
        </div>
      </section>
      @include('frontend.footer')
