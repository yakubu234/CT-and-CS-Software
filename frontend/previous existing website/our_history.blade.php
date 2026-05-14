@php
  $bgImage1 = asset('public/frontend/images/background/IMG_1709-Background.jpeg');
  $bgImage2 = asset('public/frontend/images/background/bg-image-9.jpg');
@endphp
@include('frontend.header')

      <section class="section-40 section-md-60 section-lg-90 section-xl-120 bg-gray-dark page-title-wrap overlay-5" style="background-image: url('{{ $bgImage1 }}');">
        <div class="container">
          <div class="page-title text-center">
            <h2>Our History</h2>
          </div>
        </div>
      </section>

      <section class="section-35 section-md-50">
        <div class="container">
          <h3>HISTORY OF OREOLUWAPO ILARO C.T.C.U  </h3>
          <div class="row row-30 flex-row-md-reverse justify-content-lg-between">
            <div class="col-md-6">
                <figure><img src="{{ asset('public/frontend/images/slider/oreoluwapo-staffs.png') }}" alt="" width="570" style="height: 350px;" />
                </figure>

            </div>
            <div class="col-md-6">
              <div class="inset-lg-right-40 inset-xl-right-85 text-gray-darker">
                <p style="text-align: justify;">Oreoluwapo Ilaro Cooperative Thrift and Credit Union (C.T.C.U) was established in 2018 with two pioneer societies, driven by a shared vision to promote savings culture, financial discipline, and economic empowerment among members. In its early years, the union operated under the supervision of Oreoluwapo Ibafo Cooperative Union, where it received guidance and structural support to strengthen its operations and governance.</p>
                <p style="text-align: justify;">
                On the 18th of October, 2021, Oreoluwapo Ilaro C.T.C.U was officially granted union status with eighteen (18) affiliated societies. This milestone marked the beginning of a new phase of growth and independence for the union. <br><br>
                Today, Oreoluwapo Ilaro C.T.C.U has grown remarkably to over one hundred and Seventy-Five (175) registered societies, with membership steadily expanding across various local government areas in Ogun State and beyond. The union remains committed to promoting cooperative principles, encouraging financial discipline, and improving the economic and social well-being of its members.
                </p>
              </div>
            </div>
            <div class="col-md-12">
              <div class="inset-lg-right-40 inset-xl-right-85 text-gray-darker">

                <p style="text-align: justify;">Oreoluwapo Ilaro C.T.C.U is officially registered under the Ministry of Community Development and Cooperatives, Ogun State, with Registration Number 14043, which affirms its legal recognition and commitment to operating in accordance with cooperative regulations.
                <br>
                The union is also a proud member of the Oyosowapo Group of Cooperatives, headquartered in Ogudu, Lagos State, strengthening its network and collaboration within the cooperative movement.
                <br>
                Oreoluwapo Ilaro C.T.C.U derives its name from Oreoluwapo Ibafọ, where Mr. Aderemi Ganiu Faoki serves as the President. The President of Oreoluwapo Ilaro C.T.C.U is Mr. Adegbite Adewale Azeem, who leads the union with dedication, integrity, and a strong commitment to cooperative growth and member welfare
                </p>
                <p> <b> <i>Our motto: </i> Cooperative with Truth and God’s Fearing.<i></i></b></p>
              </div>
            </div>
          </div>
        </div>
      </section>
      @include('frontend.footer')
