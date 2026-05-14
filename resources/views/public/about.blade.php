@extends('public.layout')

@section('title', 'About Us | Oreoluwapo Ilaro Cooperative Thrift & Credit Union Ltd.')
@section('meta_description', 'Learn more about Oreoluwapo Ilaro Cooperative Thrift and Credit Union Ltd., our growth, mission, location, and member-focused cooperative impact.')

@section('content')
    <section class="oreo-page-hero oreo-page-hero--about">
        <div class="container">
            <div class="witr_section_title white">
                <div class="witr_section_title_inner text-center">
                    <h2>About Us</h2>
                    <h3>About Oreoluwapo Ilaro C.T.C.U.</h3>
                    <p class="oreo-page-hero-copy">A member-focused cooperative built on trust, savings culture, affordable credit, and lasting community progress.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="oreo-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <div class="witr_section_title">
                        <div class="witr_section_title_inner text-left">
                            <h2>Who We Are</h2>
                            <h3>About Oreoluwapo Ilaro Cooperative Thrift and Credit Union</h3>
                        </div>
                    </div>
                    <div class="oreo-article-body oreo-article-body--plain">
                        <p>Oreoluwapo Ilaro Cooperative Thrift and Credit Union (C.T.C.U) is a member-focused financial cooperative established to promote savings, provide affordable credit, and improve the economic well-being of its members.</p>
                        <p>Founded in 2018, the union has grown from two pioneer societies to over 175 registered societies across Ogun State and beyond. The union is officially registered under the Ministry of Community Development and Cooperatives, Ogun State, with Registration Number 14043, ensuring that it operates in line with approved cooperative standards and regulations.</p>
                        <p>We are committed to transparency, trust, and sustainable development, empowering individuals and communities through financial inclusion and collective progress. As a member of the Oyosowapo Group of Cooperatives, we continue to strengthen our network and provide reliable financial services to our members.</p>
                        <p><strong>Our motto:</strong> Cooperative with Truth and God's Fearing.</p>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="oreo-article-card">
                        <img class="oreo-article-image oreo-article-image--about" src="{{ asset('frontend/images/slider/IMG_1709.jpeg') }}" alt="Oreoluwapo Ilaro cooperative members">
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="oreo-section oreo-surface">
        <div class="container">
            <div class="oreo-about-wide">
                <div class="oreo-article-body oreo-article-body--plain">
                    <p>Oreoluwapo Ilaro is being managed, controlled, and directed under nine board of trustees, twenty-five staff members, and one hundred and thirty-five presidents, and the number continues to increase.</p>
                </div>
                <div class="oreo-about-team-photo">
                    <img src="{{ asset('frontend/images/slider/oreoluwapo-staffs.png') }}" alt="Oreoluwapo Ilaro staffs and leadership">
                </div>
                <div class="oreo-article-body oreo-article-body--plain">
                    <p>Oreoluwapo Ilaro is located in the ancient city area of Ogun State, about 50km from Abeokuta, the Ogun State capital, and about 100km from Ikeja, the capital of Lagos State. It is strategically located at the geographical and cultural confluence of western Ogun State. Our building is located at Udoji Road along Odo-Aje Road, opposite Okobo Joint, Ilaro, Yewa South, Ogun State.</p>
                </div>
            </div>
        </div>
    </section>
@endsection
