@php
  $bgImage1 = asset('public/frontend/images/background/IMG_1709-Background.jpeg');
  $bgImage2 = asset('public/frontend/images/background/bg-image-9.jpg');
@endphp
@include('frontend.header')

      <section class="section-40 section-md-60 section-lg-90 section-xl-120 bg-gray-dark page-title-wrap overlay-5" style="background-image: url('{{ $bgImage1 }}');">
        <div class="container">
          <div class="page-title text-center">
            <h2>Blog</h2>
          </div>
        </div>
      </section>

      <section class="section-60 section-md-75 section-xl-90">
        <div class="container">
          <div class="row row-50">
            <div class="col-lg-8 col-xl-9">
              <article class="post post-single">
                <div class="post-image">
                  <figure><img src="{{ asset('public/frontend/images/blogs/agro-farmer.jpeg') }}" alt=""  style="height: 400px;" width="870"/>
                  </figure>
                </div>
                <div class="post-header">
                  <h4>Supporting Agricultural Produce – Empowering Farmers in Cooperatives</h4>
                </div>
                <div class="post-meta">
                  <ul class="list-bordered-horizontal">
                    <li>
                      <dl class="list-terms-inline">
                        <dt>Date</dt>
                        <dd>
                          <time datetime="2018-01-22">DEC 23, 2024</time>
                        </dd>
                      </dl>
                    </li>
                    <li>
                      <dl class="list-terms-inline">
                        <dt>Posted by</dt>
                        <dd>Adminn</dd>
                      </dl>
                    </li>
                    <li>
                      <dl class="list-terms-inline">
                        <dt>Category</dt>
                        <dd>Loans Insurance</dd>
                      </dl>
                    </li>
                  </ul>
                </div>
                <div class="divider-fullwidth bg-gray-light"></div>
                <div class="post-body">
                  <p>Agriculture remains the backbone of many economies, providing food, employment, and economic stability. However, small-scale farmers often face challenges such as lack of resources, limited access to markets, and financial constraints. Agricultural cooperatives play a crucial role in empowering farmers by providing collective strength, access to better resources, and financial support.
                  </p>
                  <p>
                  The image above highlights the dedication of farmers working together in the field, demonstrating the power of cooperative farming. When farmers unite, they can access improved seeds, farming equipment, and financial aid, increasing productivity and enhancing food security. Cooperatives also help in negotiating fair prices, ensuring that farmers receive just compensation for their hard work.</p>
                
                  <p>By supporting agricultural produce through cooperatives, communities benefit from increased food production, economic growth, and job creation. Governments and organizations must continue investing in these initiatives, ensuring that farmers receive the necessary tools and knowledge to sustain their livelihoods and contribute to national development.<br>
                  
                  Empowering farmers through cooperatives is not just about improving yields—it’s about building sustainable futures, strengthening rural communities, and securing global food supply chains.</p>
                  <div class="quote-wrap-1">
                    <blockquote class="quote-minimal-bordered">
                      <p>
                        <q>Invest in Unity. Boost your Farming Bussiness.</q>
                      </p>
                    </blockquote>
                  </div>
                </div>
                <div class="post-footer">
                  <h5>Share this post:</h5>
                  <ul class="list-inline list-inline-xs">
                    <li><a class="icon icon-xxs-small link-tundora fa-facebook" href=""></a></li>
                    <li><a class="icon icon-xxs-small link-tundora fa-twitter" href=""></a></li>
                    <li><a class="icon icon-xxs-small link-tundora fa-google-plus" href=""></a></li>
                    <li><a class="icon icon-xxs-small link-tundora fa-pinterest-p" href=""></a></li>
                  </ul>
                </div>
              </article>
              <div class="divider-fullwidth bg-gray-lighter"></div>
             
            </div>
            <div class="col-lg-4 col-xl-3">
              <div class="blog-aside">
                <div class="blog-aside-item">
                  <form class="rd-search rd-search-classic" action="" method="GET">
                    <div class="form-wrap">
                      <label class="form-label" for="rd-search-form-input-1">Search...</label>
                      <input class="form-input" id="rd-search-form-input-1" type="text" name="s" autocomplete="off">
                    </div>
                    <button class="rd-search-submit" type="submit"></button>
                  </form>
                </div>
                <div class="blog-aside-item">
                  <h6 class="text-uppercase">Categories</h6>
                  <ul class="list-marked-bordered">
                    <li><a href="#"><span>Wealth Management</span><span class="text-dusty-gray">(3)</span></a></li>
                    <li><a href="#"><span>Business Owners</span><span class="text-dusty-gray">(4)</span></a></li>
                    <li><a href=""><span>Cash & Credit</span><span class="text-dusty-gray">(4)</span></a></li>
                  </ul>
                </div>
                <div class="blog-aside-item">
                  <h6 class="text-uppercase">Popular posts</h6>
                  <article class="post post-preview"><a href="">
                      <div class="unit flex-row unit-spacing-sm">
                        <div class="unit-left">
                          <figure class="post-image"><img src="{{ asset('public/frontend/images/blogs/agro-farmer.jpeg') }}" alt="" style="width: 65px; height: 65px;" />
                          </figure>
                        </div>
                        <div class="unit-body">
                          <div class="post-header">
                            <p>Supporting Agricultural Produce – Emp...</p>
                          </div>
                          <div class="post-meta">
                            <ul class="list-meta">
                              <li>
                                <time datetime="2018-02-04">DEC 23, 2024</time>
                              </li>
                            </ul>
                          </div>
                        </div>
                      </div></a></article>
                  <article class="post post-preview"><a href="blog-post.html">
                      <div class="unit flex-row unit-spacing-sm">
                        <div class="unit-left">
                          <figure class="post-image"><img src="https://livedemo00.template-help.com/wt_68347/images/post-preview-2-70x70.jpg" alt="" width="70" height="70"/>
                          </figure>
                        </div>
                        <div class="unit-body">
                          <div class="post-header">
                            <p>Over 130 Societies Trust Us – Be Par....</p>
                          </div>
                          <div class="post-meta">
                            <ul class="list-meta">
                              <li>
                                <time datetime="2018-02-04">JAN 30, 2025</time>
                              </li>
                            </ul>
                          </div>
                        </div>
                      </div></a></article>
                  <article class="post post-preview"><a href="blog-post.html">
                      <div class="unit flex-row unit-spacing-sm">
                        <div class="unit-left">
                          <figure class="post-image"><img src="{{ asset('public/frontend/images/blogs/to-the-most-rural-areas.png') }}" alt="" style="width: 65px; height: 65px;" />
                          </figure>
                        </div>
                        <div class="unit-body">
                          <div class="post-header">
                            <p>How Our Savings Model Works – Bringing Fina...</p>
                          </div>
                          <div class="post-meta">
                            <ul class="list-meta">
                              <li>
                                <time datetime="2018-02-04">JAN 20, 2025</time>
                              </li>
                            </ul>
                          </div>
                        </div>
                      </div></a></article>
                </div>
                <div class="blog-aside-item">
                  <h6 class="text-uppercase">Gallery</h6>
                  <div class="row row-xs row-10 max-330" data-lightgallery="group" data-lg-thumbnail="false">
                    <div class="col-4 col-xs-4">
                      <!-- Thumbnail Classic-->
                      <article class="thumbnail-classic thumbnail-classic-xs"><a class="thumbnail-classic-figure" href="{{ asset('public/frontend/images/blogs/IMG_1855.jpeg') }}" data-lightgallery="item"><img src="{{ asset('public/frontend/images/blogs/IMG_1855.jpeg') }}" alt="" width="92" style="height: 81px;" /></a>
                      </article>
                    </div>
                    <div class="col-4 col-xs-4">
                      <!-- Thumbnail Classic-->  
                      <article class="thumbnail-classic thumbnail-classic-xs"><a class="thumbnail-classic-figure" href="{{ asset('public/frontend/images/blogs/IMG_1852.jpeg') }}" data-lightgallery="item"><img src="{{ asset('public/frontend/images/blogs/IMG_1852.jpeg') }}" alt="" width="92" style="height: 81px;"  /></a>
                      </article>
                    </div>
                    <div class="col-4 col-xs-4">
                      <!-- Thumbnail Classic-->
                      <article class="thumbnail-classic thumbnail-classic-xs"><a class="thumbnail-classic-figure" href="{{ asset('public/frontend/images/blogs/IMG_1853.jpeg') }}" data-lightgallery="item"><img src="{{ asset('public/frontend/images/blogs/IMG_1853.jpeg') }}" alt="" width="92" style="height: 81px;" /></a>
                      </article>
                    </div>
                    <div class="col-4 col-xs-4">
                      <!-- Thumbnail Classic-->
                      <article class="thumbnail-classic thumbnail-classic-xs"><a class="thumbnail-classic-figure" href="{{ asset('public/frontend/images/blogs/agro-woman.png') }}" data-lightgallery="item"><img src="{{ asset('public/frontend/images/blogs/agro-woman.png') }}" alt="" width="92"  style="height: 81px;"/></a>
                      </article>
                    </div>
                    <div class="col-4 col-xs-4">
                      <!-- Thumbnail Classic-->
                      <article class="thumbnail-classic thumbnail-classic-xs"><a class="thumbnail-classic-figure" href="{{ asset('public/frontend/images/blogs/Screenshot from 2025-02-26 16-55-31.png') }}" data-lightgallery="item"><img src="{{ asset('public/frontend/images/blogs/Screenshot from 2025-02-26 16-55-31.png') }}" alt="" width="92"style="height: 81px;"/></a>
                      </article>
                    </div>
                    <div class="col-4 col-xs-4">
                      <!-- Thumbnail Classic-->
                      <article class="thumbnail-classic thumbnail-classic-xs"><a class="thumbnail-classic-figure" href="{{ asset('public/frontend/images/blogs/IMG_1854.jpeg') }}" data-lightgallery="item"><img src="{{ asset('public/frontend/images/blogs/IMG_1854.jpeg') }}" alt="" width="92"  style="height: 81px;"/></a>
                      </article>
                    </div>
                  </div>
                </div>
                <div class="blog-aside-item">
                  <h6 class="text-uppercase">Tags</h6>
                  <ul class="list-tag-blocks">
                    <li><a href="">Legacy</a></li>
                    <li><a href="">Plan</a></li>
                    <li><a href="">Finance</a></li>
                    <li><a href="">Money</a></li>
                  </ul>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>
      @include('frontend.footer')