@php
  $bgImage1 = asset('public/frontend/images/background/IMG_1709-Background.jpeg');
  $bgImage2 = asset('public/frontend/images/background/bg-image-9.jpg');
@endphp
@include('frontend.header')

      <section class="section-40 section-md-60 section-lg-90 section-xl-120 bg-gray-dark page-title-wrap overlay-5" style="background-image: url('{{ $bgImage1 }}');">
        <div class="container">
          <div class="page-title text-center">
            <h2>About Us</h2>
          </div>
        </div>
      </section>

      <section class="section-50 section-md-75 section-xl-100 bg-whisperapprox">
        <div class="container">
          <div class="row row-30 justify-content-md-center justify-content-lg-start">
            <div class="col-md-9 col-lg-6">

<article class="post-modern">
                <div class="post-header">
                  <h5><a href="{{ route('blog_five') }}">Giving Hope, Sharing Love: Widows &amp;
Elderly Outreach 2026</a></h5>
                </div>
                <div class="post-body">
                  <figure><img src="{{ asset('public/frontend/images/blogs/blog_six.jpg') }}" alt="" width="570" height="321"/>
                  </figure>
                  <div class="post-inset">
                    <p class="text-gray-05">In every community, there are silent heroes—our widows and elderly—who have given so
much of their lives in service, love, and sacrifice. Yet, many of them now face loneliness,
financial struggles, and a lack of bas.....</p>
                  </div>
                </div>
                <div class="post-footer">
                  <div class="post-meta">
                    <ul class="post-list">
                      <li class="object-inline"><span class="icon icon-xxs icon-black material-icons-query_builder"></span>
                        <time class="text-gray-05" datetime="2026-04-04">APR 04, 2026</time>
                      </li>
                      <li class="object-inline"><span class="icon icon-xxs icon-black material-icons-loyalty"></span>
                        <ul class="list-tags-inline">
                          <li><a href="{{ route('blog_five') }}">Legacy</a></li>
                          <li><a href="{{ route('blog_five') }}">Plan</a></li>
                        </ul>
                      </li>
                    </ul>
                  </div>
                  <!-- <div class="post-type"><span class="icon icon-xxs icon-black material-icons-videocam"></span></div> -->
                </div>
              </article>
              <article class="post-modern">
                <div class="post-header">
                  <h5><a href="{{ route('blog_one') }}">The Power of Investment in Cooperatives: Strength in Unity</a></h5>
                </div>
                <div class="post-body">
                  <figure><img src="{{ asset('public/frontend/images/blogs/IMG_1852.jpeg') }}" alt="" width="570" height="321"/>
                  </figure>
                  <div class="post-inset">
                    <p class="text-gray-05">In the heart of our communities, investment in cooperatives are transforming lives by pooling resources to create sustainable wealth and economic opportunities. The image above captures a ...</p>
                  </div>
                </div>
                <div class="post-footer">
                  <div class="post-meta">
                    <ul class="post-list">
                      <li class="object-inline"><span class="icon icon-xxs icon-black material-icons-query_builder"></span>
                        <time class="text-gray-05" datetime="2025-02-10">FEB 10, 2025</time>
                      </li>
                      <li class="object-inline"><span class="icon icon-xxs icon-black material-icons-loyalty"></span>
                        <ul class="list-tags-inline">
                          <li><a href="{{ route('blog_one') }}">Legacy</a></li>
                          <li><a href="{{ route('blog_one') }}">Plan</a></li>
                        </ul>
                      </li>
                    </ul>
                  </div>
                  <!-- <div class="post-type"><span class="icon icon-xxs icon-black material-icons-videocam"></span></div> -->
                </div>
              </article>
              <article class="post-modern">
                <div class="post-header">
                  <h5><a href="{{ route('blog_three') }}">Supporting Agricultural Produce – Empowering Farmers in Cooperatives</a></h5>
                </div>
                <div class="post-body">
                  <figure><img src="{{ asset('public/frontend/images/blogs/agro-farmer.jpeg') }}" alt="" width="570" height="321"/>
                  </figure>
                  <div class="post-inset">
                    <p class="text-gray-05">Agriculture remains the backbone of many economies, providing food, employment, and economic stability. However, small-scale farmers often face challenges such as lack of resources, limited access to markets, and financial constraints. Agricultural cooperatives play a crucial role in empoweri...</p>
                  </div>
                </div>
                <div class="post-footer">
                  <div class="post-meta">
                    <ul class="post-list">
                      <li class="object-inline"><span class="icon icon-xxs icon-black material-icons-query_builder"></span>
                        <time class="text-gray-05" datetime="2018-01-01">DEC 23, 2024</time>
                      </li>
                      <li class="object-inline"><span class="icon icon-xxs icon-black material-icons-loyalty"></span>
                        <ul class="list-tags-inline">
                          <li><a href="{{ route('blog_three') }}">Loans</a></li>
                          <li><a href="{{ route('blog_three') }}">Insurance</a></li>
                        </ul>
                      </li>
                    </ul>
                  </div>
                  <div class="post-type"><span class="icon icon-xxs icon-black material-icons-local_see"></span></div>
                </div>
              </article>
            </div>
            <div class="col-md-9 col-lg-6">
              <article class="post-modern">
                <div class="post-header">
                  <h5><a href="blog.html">Over 130 Societies Trust Us – Be Part of Our Growing Family!</a></h5>
                </div>
                <div class="post-body">
                  <div class="post-inset">
                    <p class="text-gray-05">Our rapid expansion is a reflection of the trust, transparency, and commitment we bring to cooperative governance. More than just a financial institution, Oreoluwapo Ilaro is a community-driven cooperative where members thrive toge...</p>
                  </div>
                </div>
                <div class="post-footer">
                  <div class="post-meta">
                    <ul class="post-list">
                      <li class="object-inline"><span class="icon icon-xxs icon-black material-icons-query_builder"></span>
                        <time class="text-gray-05" datetime="2018-01-01">JAN 30, 2025</time>
                      </li>
                      <li class="object-inline"><span class="icon icon-xxs icon-black material-icons-loyalty"></span>
                        <ul class="list-tags-inline">
                          <li><a href="#">Legacy</a></li>
                          <li><a href="#">Strong Ethics</a></li>
                        </ul>
                      </li>
                    </ul>
                  </div>
                  <div class="post-type"><span class="icon icon-xxs icon-black material-icons-library_books"></span></div>
                </div>
              </article>

              <article class="post-modern">
                <div class="post-header">
                  <h5><a href="{{ route('blog_two') }}">Cooperative Investments: Empowering Members for a Brighter Future</a></h5>
                </div>
                <div class="post-body">
                  <figure><img src="{{ asset('public/frontend/images/blogs/IMG_1853.jpeg') }}" alt="" width="570" height="321"/>
                  </figure>
                  <div class="post-inset">
                    <p class="text-gray-05">The image above beautifully captures the impact of cooperative investments in the lives of our members. A man sits joyfully on a newly acquired motorcycle, surrounded by fellow cooperative members dressed in matching traditional attire, symbolizing unity and shared prosperity. This moment reflec...</p>
                  </div>
                </div>
                <div class="post-footer">
                  <div class="post-meta">
                    <ul class="post-list">
                      <li class="object-inline"><span class="icon icon-xxs icon-black material-icons-query_builder"></span>
                        <time class="text-gray-05" datetime="2025-02-10">JAN 20, 2025</time>
                      </li>
                      <li class="object-inline"><span class="icon icon-xxs icon-black material-icons-loyalty"></span>
                        <ul class="list-tags-inline">
                          <li><a href="{{ route('blog_two') }}">Outreaches</a></li>
                          <li><a href="{{ route('blog_two') }}">Plan</a></li>
                        </ul>
                      </li>
                    </ul>
                  </div>
                  <!-- <div class="post-type"><span class="icon icon-xxs icon-black material-icons-videocam"></span></div> -->
                </div>
              </article>


              <article class="post-modern">
                <div class="post-header">
                  <h5><a href="{{ route('blog_four') }}">Empowering Market Women and Men Through Cooperative Credit</a></h5>
                </div>
                <div class="post-body">
                  <figure><img src="{{ asset('public/frontend/images/blogs/farming-tomato.jpg') }}" alt="" width="570" height="321"/>
                  </figure>
                  <div class="post-inset">
                    <p class="text-gray-05">Market traders, both women and men, play a crucial role in sustaining local economies by ensuring the availability of essential goods. However, many of them face financial barriers that limit their ability to expand their businesses, purchase goods in bulk, or invest in better infrastructure. Providing credit through cooperatives is a powerful way to empower these traders, enabling them to grow and thri...</p>
                  </div>
                </div>
                <div class="post-footer">
                  <div class="post-meta">
                    <ul class="post-list">
                      <li class="object-inline"><span class="icon icon-xxs icon-black material-icons-query_builder"></span>
                        <time class="text-gray-05" datetime="2018-01-01">NOV 12, 2024</time>
                      </li>
                      <li class="object-inline"><span class="icon icon-xxs icon-black material-icons-loyalty"></span>
                        <ul class="list-tags-inline">
                          <li><a href="{{ route('blog_four') }}">Support</a></li>
                          <li><a href="{{ route('blog_four') }}">Credit Worthiness</a></li>
                        </ul>
                      </li>
                    </ul>
                  </div>
                </div>
              </article>
            </div>
          </div>
          <div class="pagination-custom-wrap text-center">
            <ul class="pagination-custom">
              <li><a href="blog-masonry.html#">Prev</a></li>
              <li><a href="blog-masonry.html#">1</a></li>
              <li><a href="blog-masonry.html#">2</a></li>
              <li class="active"><a href="blog-masonry.html#">3</a></li>
              <li><a href="blog-masonry.html#">4</a></li>
              <li><a href="blog-masonry.html#">Next</a></li>
            </ul>
          </div>
        </div>
      </section>

      @include('frontend.footer')
