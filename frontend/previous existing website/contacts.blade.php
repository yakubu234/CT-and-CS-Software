@php
  $bgImage1 = asset('public/frontend/images/banner-contactus.png');
  $bgImage2 = asset('public/frontend/images/background/bg-image-9.jpg');
@endphp
@include('frontend.header')

      <section class="section-40 section-md-60 section-lg-90 section-xl-120 bg-gray-dark page-title-wrap overlay-5" style="background-image: url('{{ $bgImage1 }}');">
        <div class="container">
          <div class="page-title text-center">
            <h2>Contacts</h2>
          </div>
        </div>
      </section>

      
      <section class="section-60 section-top-60 section-bottom-60 section-md-bottom-100">
        <div class="container">
          <div class="row row-40 row-offset-3">
            <div class="col-md-6 col-lg-4 height-fill">
              <article class="icon-box">
                <div class="box-top">
                  <div class="box-icon"><span class="icon icon-primary icon-lg-bigger material-icons-location_on material-icons-device_hub"></span></div>
                  <div class="box-header">
                    <h5><a href="icon-lists.html#">Office Address</a></h5>
                  </div>
                </div>
                <div class="divider"></div>
                <div class="box-body">
                  <p class="text-gray-05">Udoji Road, along Odo-Aje Road, opposite Okobo Joint, Ilaro, Ogun State.</p>
                </div>
              </article>
            </div>
            <div class="col-md-6 col-lg-4 height-fill">
              <article class="icon-box">
                <div class="box-top">
                  <div class="box-icon"><span class="icon icon-primary icon-lg-bigger  material-icons-phone_android  material-icons-device_hub"></span></div>
                  <div class="box-header">
                    <h5><a href="icon-lists.html#">Office Phone</a></h5>
                  </div>
                </div>
                <div class="divider"></div>
                <div class="box-body">
                  <p class="text-gray-05">
                  +234 815 127 3635,<br> +234 806 095 7070</p>
                </div>
              </article>
            </div>
            <div class="col-md-6 col-lg-4 height-fill">
              <article class="icon-box">
                <div class="box-top">
                  <div class="box-icon"><span class="icon icon-primary icon-lg-bigger material-icons-email"></span></div>
                  <div class="box-header">
                    <h5><a href="icon-lists.html#">Office Email</a></h5>
                  </div>
                </div>
                <div class="divider"></div>
                <div class="box-body">
                  <p class="text-gray-05">info@oreoluwapo.org.ng</p>
                </div>
              </article>
            </div>
          </div>
        </div>
      </section>

      <section class="section">
        
        <div class="google-map-container" data-zoom="15"  data-styles="[{&quot;featureType&quot;:&quot;administrative.locality&quot;,&quot;elementType&quot;:&quot;all&quot;,&quot;stylers&quot;:[{&quot;hue&quot;:&quot;#2c2e33&quot;},{&quot;saturation&quot;:7},{&quot;lightness&quot;:19},{&quot;visibility&quot;:&quot;on&quot;}]},{&quot;featureType&quot;:&quot;landscape&quot;,&quot;elementType&quot;:&quot;all&quot;,&quot;stylers&quot;:[{&quot;hue&quot;:&quot;#ffffff&quot;},{&quot;saturation&quot;:-100},{&quot;lightness&quot;:100},{&quot;visibility&quot;:&quot;simplified&quot;}]},{&quot;featureType&quot;:&quot;poi&quot;,&quot;elementType&quot;:&quot;all&quot;,&quot;stylers&quot;:[{&quot;hue&quot;:&quot;#ffffff&quot;},{&quot;saturation&quot;:-100},{&quot;lightness&quot;:100},{&quot;visibility&quot;:&quot;off&quot;}]},{&quot;featureType&quot;:&quot;road&quot;,&quot;elementType&quot;:&quot;geometry&quot;,&quot;stylers&quot;:[{&quot;hue&quot;:&quot;#bbc0c4&quot;},{&quot;saturation&quot;:-93},{&quot;lightness&quot;:31},{&quot;visibility&quot;:&quot;simplified&quot;}]},{&quot;featureType&quot;:&quot;road&quot;,&quot;elementType&quot;:&quot;labels&quot;,&quot;stylers&quot;:[{&quot;hue&quot;:&quot;#bbc0c4&quot;},{&quot;saturation&quot;:-93},{&quot;lightness&quot;:31},{&quot;visibility&quot;:&quot;on&quot;}]},{&quot;featureType&quot;:&quot;road.arterial&quot;,&quot;elementType&quot;:&quot;labels&quot;,&quot;stylers&quot;:[{&quot;hue&quot;:&quot;#bbc0c4&quot;},{&quot;saturation&quot;:-93},{&quot;lightness&quot;:-2},{&quot;visibility&quot;:&quot;simplified&quot;}]},{&quot;featureType&quot;:&quot;road.local&quot;,&quot;elementType&quot;:&quot;geometry&quot;,&quot;stylers&quot;:[{&quot;hue&quot;:&quot;#e9ebed&quot;},{&quot;saturation&quot;:-90},{&quot;lightness&quot;:-8},{&quot;visibility&quot;:&quot;simplified&quot;}]},{&quot;featureType&quot;:&quot;transit&quot;,&quot;elementType&quot;:&quot;all&quot;,&quot;stylers&quot;:[{&quot;hue&quot;:&quot;#e9ebed&quot;},{&quot;saturation&quot;:10},{&quot;lightness&quot;:69},{&quot;visibility&quot;:&quot;on&quot;}]},{&quot;featureType&quot;:&quot;water&quot;,&quot;elementType&quot;:&quot;all&quot;,&quot;stylers&quot;:[{&quot;hue&quot;:&quot;#e9ebed&quot;},{&quot;saturation&quot;:-78},{&quot;lightness&quot;:67},{&quot;visibility&quot;:&quot;simplified&quot;}]}]">
          <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d253505.79170138884!2d2.682618994531238!3d6.8872501000000055!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x103b0f130995e199%3A0x84cea4dce7cefb57!2sFederal%20Polytechnic%2C%20Ilaro!5e0!3m2!1sen!2sng!4v1740697520033!5m2!1sen!2sng"  data-zoom="15" style="border:0;" width="100%" height="400px" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
          <ul class="google-map-markers">
            <li data-location="9870 St Vincent Place, Glasgow, DC 45 Fr 45." data-description="9870 St Vincent Place, Glasgow" data-icon="images/gmap_marker.png" data-icon-active="images/gmap_marker_active.png"></li>
          </ul>
        </div>
      </section>

      @include('frontend.footer')