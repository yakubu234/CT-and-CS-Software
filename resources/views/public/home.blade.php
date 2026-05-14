@extends('public.layout')

@section('title', 'Oreoluwapo Ilaro Cooperative Thrift & Credit Union Ltd.')

@section('content')
    <div class="witr_swiper_area" id="home">
        <div class="swiper main_slider_active">
            <div class="swiper-wrapper">
                <div class="swiper-slide txbdslider" style="background-image: url('{{ asset('frontend/images/slider/IMG_1636.jpeg') }}');">
                    <div class="txbdsinner text-left">
                        <div class="witr_swiper_content">
                            <h2>Over 135 Thriving <span>Cooperative</span></h2>
                            <h3>Societies Growing Together</h3>
                            <p>Empowering communities through cooperative growth, member trust, and practical financial support across Ogun State and beyond.</p>
                            <div class="slider_btn">
                                <div class="witr_btn_style">
                                    <div class="witr_btn_sinner">
                                        <a class="witr_btn" href="#services">Join Us Today</a>
                                        <a class="witr_btn active2" href="#about">Learn More</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="swiper-slide txbdslider" style="background-image: url('{{ asset('frontend/images/slider/IMG_1709.jpeg') }}');">
                    <div class="txbdsinner text-left">
                        <div class="witr_swiper_content">
                            <h2>Need Financial <span>Growth</span></h2>
                            <h3>And Security You Can Trust?</h3>
                            <p>Oreoluwapo Ilaro provides transparent cooperative financial solutions built on discipline, integrity, and long-term community value.</p>
                            <div class="slider_btn">
                                    <div class="witr_btn_style">
                                        <div class="witr_btn_sinner">
                                            <a class="witr_btn" href="{{ route('history') }}">Our History</a>
                                            <a class="witr_btn active2" href="#contact">Contact Us</a>
                                        </div>
                                    </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="swiper-slide txbdslider" style="background-image: url('{{ asset('frontend/images/slider/IMG_1711.jpeg') }}');">
                    <div class="txbdsinner text-left">
                        <div class="witr_swiper_content">
                            <h2>A Cooperative <span>Built on Trust</span></h2>
                            <h3>Creating Wealth and Opportunity</h3>
                            <p>We help members build savings culture, expand opportunity, and strengthen their families, societies, and communities together.</p>
                            <div class="slider_btn">
                                <div class="witr_btn_style">
                                    <div class="witr_btn_sinner">
                                        <a class="witr_btn" href="#leaders">Meet Leadership</a>
                                        <a class="witr_btn active2" href="#news">Latest News</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="swiper-scrollbar"></div>
            <div class="swiper-pagination"></div>
            <div class="swiper-button-next"></div>
            <div class="swiper-button-prev"></div>
        </div>
    </div>

    <div class="temkuri_features_area" id="services">
        <div class="container">
            <div class="row features_bg">
                <div class="col-lg-4 col-md-6 col-sm-12 pdlf">
                    <div class="em-service2 sleft all_color_service">
                        <div class="em_service_content">
                            <div class="em_single_service_text width_height_link_0">
                                <div class="text_box witr_s_flex">
                                    <div class="service_top_text all_icon_color">
                                        <div class="em-service-icon">
                                            <img src="{{ asset('frontend/template/assets/images/feature1.png') }}" alt="Weekly contributions icon">
                                        </div>
                                    </div>
                                    <div class="em-service-inner">
                                        <div class="em-service-title">
                                            <h3><a href="#services">Weekly Contributions</a></h3>
                                        </div>
                                        <div class="em-service-desc">
                                            <p>These societies operate once in a week depending on the preferred contribution day.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4 col-md-6 col-sm-12 pdlf">
                    <div class="em-service2 sleft all_color_service">
                        <div class="em_service_content">
                            <div class="em_single_service_text width_height_link_0">
                                <div class="text_box witr_s_flex">
                                    <div class="service_top_text all_icon_color">
                                        <div class="em-service-icon">
                                            <img src="{{ asset('frontend/template/assets/images/feature2.png') }}" alt="15 days contributions icon">
                                        </div>
                                    </div>
                                    <div class="em-service-inner">
                                        <div class="em-service-title">
                                            <h3><a href="#services">15/15 Days Contributions</a></h3>
                                        </div>
                                        <div class="em-service-desc">
                                            <p>These societies operate every two weeks for members who prefer a bi-weekly savings rhythm.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4 col-md-6 col-sm-12 pdlf">
                    <div class="em-service2 sleft all_color_service border_lf">
                        <div class="em_service_content">
                            <div class="em_single_service_text width_height_link_0">
                                <div class="text_box witr_s_flex">
                                    <div class="service_top_text all_icon_color">
                                        <div class="em-service-icon">
                                            <img src="{{ asset('frontend/template/assets/images/feature3.png') }}" alt="Monthly contributions icon">
                                        </div>
                                    </div>
                                    <div class="em-service-inner">
                                        <div class="em-service-title">
                                            <h3><a href="#services">Monthly Contributions</a></h3>
                                        </div>
                                        <div class="em-service-desc">
                                            <p>These societies operate once in a month, typically on the last Saturday of the month.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <section class="oreo-section" id="about">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <div class="oreo-dark-card">
                        <div class="witr_section_title white">
                            <div class="witr_section_title_inner text-left">
                                <h2>Who We Are</h2>
                                <h3>Ore-Oluwapo Ilaro Cooperative Society</h3>
                            </div>
                        </div>
                        <p>
                            We are a dynamic and growing cooperative society dedicated to fostering financial growth,
                            trust, and community development. Established in 2018 with just two societies under the
                            umbrella of Oreoluwapo Ibafo, we officially became a recognized union on October 18,
                            2021, with eighteen societies.
                        </p>
                        <p>
                            Today, we have expanded significantly, boasting at least 135 cooperative societies
                            across various local government areas in Ogun State and beyond, with continuous growth
                            and impact. Our society is also an esteemed member of the Oyosowapo Group of
                            Cooperatives, headquartered in Ogudu, Lagos State.
                        </p>
                            <div class="slider_btn">
                                <div class="witr_btn_style">
                                    <div class="witr_btn_sinner">
                                        <a class="witr_btn" href="{{ route('about') }}">Read More</a>
                                    </div>
                                </div>
                            </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="oreo-core-card">
                        <div class="witr_section_title">
                            <div class="witr_section_title_inner text-center">
                                <h2>Our Core Values</h2>
                                <h3>Values that shape our cooperative culture</h3>
                            </div>
                        </div>
                        <img src="{{ asset('frontend/images/our core values.png') }}" alt="Oreoluwapo core values">
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="oreo-section oreo-surface" id="history">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <div class="oreo-mission-copy">
                        <div class="witr_section_title">
                            <div class="witr_section_title_inner text-left">
                                <h2>Our History</h2>
                                <h3>Mission, vision, and our cooperative journey</h3>
                            </div>
                        </div>
                        <h4>Our Mission</h4>
                        <p>To actively empower our members through collaborative efforts, providing accessible and affordable services, promoting democratic decision-making, and prioritizing the collective well-being over individual profit.</p>
                        <h4>Our Vision</h4>
                        <p>To create a thriving community where our members collectively own and manage economic resources, fostering social equity, sustainable development, and improved quality of life for all our members.</p>
                        <p><strong>Cooperative with trust and God's fearing....</strong></p>
                        <div class="slider_btn">
                            <div class="witr_btn_style">
                                <div class="witr_btn_sinner">
                                    <a class="witr_btn" href="{{ route('history') }}">View Full History</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="oreo-contact-panel oreo-why-card">
                        <div class="witr_section_title white">
                            <div class="witr_section_title_inner text-left">
                                <h2>Why Choose Us</h2>
                                <h3>Cooperative growth with trust and integrity</h3>
                            </div>
                        </div>
                        <ul>
                            <li>Trusted and established cooperative.</li>
                            <li>Expanding network and growth opportunities.</li>
                            <li>Commitment to financial security and transparency.</li>
                            <li>Empowering local businesses and communities.</li>
                            <li>Flexible savings and loan plans.</li>
                            <li>Strong leadership and governance.</li>
                            <li>Faith-based and ethical values.</li>
                        </ul>
                        <div class="slider_btn">
                            <div class="witr_btn_style">
                                <div class="witr_btn_sinner">
                                    <a class="witr_btn" href="#contact">Contact Us Now</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="oreo-section" id="leaders">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <div class="witr_section_title">
                        <div class="witr_section_title_inner text-center">
                            <h2>Our Leadership</h2>
                            <h3>Meet our founders</h3>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row oreo-founders">
                <div class="col-lg-4 col-md-6 col-sm-12">
                    <div class="oreo-founder-card">
                        <img src="{{ asset('frontend/images/potraits/President-Oreoluwapo-Ibafo.jpeg') }}" alt="President Oreoluwapo Ibafo">
                        <div class="oreo-founder-content">
                            <h4>Alhaji Fatoki Aderemi Ganiu</h4>
                            <p class="oreo-founder-role">President Oreoluwapo Ibafo</p>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4 col-md-6 col-sm-12">
                    <div class="oreo-founder-card">
                        <img src="{{ asset('frontend/images/potraits/President-Oreoluwapo-Ilaro.jpeg') }}" alt="President Oreoluwapo Ilaro">
                        <div class="oreo-founder-content">
                            <h4>Adegbite Adewale Abiodun</h4>
                            <p class="oreo-founder-role">President Oreoluwapo Ilaro</p>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4 col-md-6 col-sm-12">
                    <div class="oreo-founder-card">
                        <img src="{{ asset('frontend/images/potraits/director-general-oyosowapo.jpeg') }}" alt="Director General Oyosowapo">
                        <div class="oreo-founder-content">
                            <h4>Alhaji Dr Abdul-Lateef Akinola</h4>
                            <p class="oreo-founder-role">DG, Oyosowapo Group of Cooperative</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="oreo-section oreo-counter-wrap">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <div class="witr_section_title white">
                        <div class="witr_section_title_inner text-center">
                            <h2>Facts and Numbers</h2>
                            <h3>Growth you can measure</h3>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-lg-3 col-md-6 col-sm-12">
                    <div class="oreo-counter-card">
                        <img src="{{ asset('frontend/images/icon-13.webp') }}" alt="Cooperative societies icon">
                        <h3>135</h3>
                        <h4>Cooperative Society</h4>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 col-sm-12">
                    <div class="oreo-counter-card">
                        <img src="{{ asset('frontend/images/icon-12.webp') }}" alt="Awards icon">
                        <h3>18</h3>
                        <h4>Awards</h4>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 col-sm-12">
                    <div class="oreo-counter-card">
                        <img src="{{ asset('frontend/images/icon-11.webp') }}" alt="Experience icon">
                        <h3>07</h3>
                        <h4>Years of Experience</h4>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 col-sm-12">
                    <div class="oreo-counter-card">
                        <img src="{{ asset('frontend/images/icon-14.webp') }}" alt="Staff icon">
                        <h3>27</h3>
                        <h4>Staffs</h4>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="oreo-section oreo-surface" id="news">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <div class="witr_section_title">
                        <div class="witr_section_title_inner text-center">
                            <h2>Our News and Media</h2>
                            <h3>Latest News</h3>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row oreo-founders">
                @forelse ($latestPosts as $post)
                    <div class="col-lg-3 col-md-6 col-sm-12">
                        <div class="oreo-news-card">
                            <a href="{{ route('blogs.show', $post->slug) }}">
                                <img src="{{ $post->image_url }}" alt="{{ $post->title }}">
                            </a>
                            <div class="oreo-news-content">
                                <div class="oreo-news-meta">{{ strtoupper($post->published_label) }} | {{ strtoupper($post->createdBy?->name ?? 'ADMIN') }}</div>
                                <h4><a href="{{ route('blogs.show', $post->slug) }}">{{ $post->title }}</a></h4>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-lg-12">
                        <div class="oreo-empty-state">
                            <h4>No published blog posts yet</h4>
                            <p>Create and publish posts from the admin panel to make this section live.</p>
                        </div>
                    </div>
                @endforelse
            </div>

            <div class="text-center mt-4">
                <a class="witr_btn" href="{{ route('blogs.index') }}">View All News</a>
            </div>
        </div>
    </section>

    <section class="oreo-section" id="contact">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <div class="witr_section_title">
                        <div class="witr_section_title_inner text-left">
                            <h2>Get In Touch</h2>
                            <h3>Ready to grow with Oreoluwapo Ilaro?</h3>
                        </div>
                    </div>
                    <p>
                        Whether you want to make enquiries, understand how a society can join, or connect with our
                        leadership, we are always ready to help.
                    </p>
                    <img src="{{ asset('frontend/images/slider/oreoluwapo-staffs.png') }}" alt="Oreoluwapo staffs">
                </div>
                <div class="col-lg-6">
                    <div class="oreo-contact-panel">
                        <div class="witr_section_title white">
                            <div class="witr_section_title_inner text-left">
                                <h2>Contact Us</h2>
                                <h3>Reach out to our cooperative office</h3>
                            </div>
                        </div>
                        <p>Mon - Sat: 9:00am - 06:00pm. Sunday CLOSED</p>
                        <ul class="oreo-contact-list">
                            <li><strong>Phone:</strong> +234 815 127 3635, +234 806 095 7070</li>
                            <li><strong>Email:</strong> info@oreoluwapo.org.ng</li>
                            <li><strong>Address:</strong> Udoji Road, along Odo-Aje Road, opposite Okobo Joint, Ilaro, Ogun State.</li>
                            <li><strong>RCN:</strong> 14043</li>
                        </ul>
                        <div class="slider_btn">
                            <div class="witr_btn_style">
                                <div class="witr_btn_sinner">
                                    <a class="witr_btn" href="mailto:info@oreoluwapo.org.ng">Send Email</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
