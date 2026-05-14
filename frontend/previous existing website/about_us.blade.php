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

      <section class="section-35 section-md-50">
        <div class="container">
          <h3>About  OREOLUWAPO ILARO C.T.C.U  </h3>
          <div class="row row-30 flex-row-md-reverse justify-content-lg-between">
            <div class="col-md-6">
              <figure><img src="{{ asset('public/frontend/images/slider/IMG_1709.jpeg') }}" alt="" width="570" style="height: 350px;" />
              </figure>
            </div>
            <div class="col-md-6">
              <div class="inset-lg-right-40 inset-xl-right-85 text-gray-darker">
                <p style="text-align: justify;">Oreoluwapo Ilaro Cooperative Thrift and Credit Union (C.T.C.U) is a member-focused financial cooperative established to promote savings, provide affordable credit, and improve the economic well-being of its members.</p>
                <p style="text-align: justify;">
                 Founded in 2018, the union has grown from two pioneer societies to over 17 5 registered societies across Ogun State and beyond. The union is officially registered under the Ministry of Community Development and Cooperatives, Ogun State, with Registration Number 14043, ensuring that it operates in line with approved cooperative standards and regulations.
                </p>
                <p style="text-align: justify;"> We are committed to transparency, trust, and sustainable development, empowering individuals and communities through financial inclusion and collective progress. As a member of the Oyosowapo Group of Cooperatives, we continue to strengthen our network and provide reliable financial services to our members.
                </p>
                <p> <b> <i>Our motto: </i> Cooperative with Truth and God’s Fearing.<i></i></b></p>
              </div>
            </div>
          </div>
        </div>
      </section>

      <section class="section-35 section-bottom-60 section-md-bottom-100">
        <div class="container">
          <p class="text-gray-darker" style="text-align: justify;">
            Oreoluwapo Ilaro is being Manage/Control/Direct under Nine board of trustee’s, twenty-five Staff’s and one hundred thirty-five president and still increasing.
          </p>
          <p></p>
          <figure><img src="{{ asset('public/frontend/images/slider/oreoluwapo-staffs.png') }}" alt="" width="1169" height="410"/>
          </figure>
          <p class="text-gray-darker" style="text-align: justify;">
            Oreoluwapo Ilaro is Located in the ancient city of Ogun state, about 50km from Abeokuta, the Ogun state Capital, and about 100km from Ikeja, the capital of Lagos state, it strategically located at the geographical and cultural confluence of the western ogun state, our Building is located at udoji road along Odo-Aje road opposite okobo joint Ilaro yewa south ogun state.

          </p>
        </div>
      </section>

      @include('frontend.footer')
