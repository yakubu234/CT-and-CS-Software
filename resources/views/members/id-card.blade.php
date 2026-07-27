<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $member->name }} - ID Card</title>
    <link rel="stylesheet" href="{{ asset('id-card/style.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/adminlte/plugins/fontawesome-free/css/all.min.css') }}">
    <script src="{{ asset('id-card/vendor/html2canvas.min.js') }}"></script>
    <style>
        body {
            margin: 0;
            padding: 24px;
            background: #eef2f7;
            font-family: Montserrat, Arial, sans-serif;
        }

        .id-card-toolbar {
            position: sticky;
            top: 0;
            z-index: 20;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto 24px;
            padding: 14px;
            border-radius: 12px;
            background: #fff;
            box-shadow: 0 8px 24px rgba(15, 23, 42, 0.12);
        }

        .id-card-toolbar button,
        .id-card-toolbar a {
            border: 0;
            border-radius: 7px;
            padding: 10px 14px;
            background: #004aad;
            color: #fff;
            cursor: pointer;
            font: inherit;
            font-weight: 700;
            text-decoration: none;
        }

        .id-card-toolbar a {
            background: #64748b;
        }

        .id-card-status {
            margin-left: auto;
            color: #475569;
            font-size: 14px;
        }

        .id-card-stage {
            display: flex;
            flex-wrap: wrap;
            gap: 24px;
            justify-content: center;
            align-items: flex-start;
        }

        .front-card-wrapper,
        .back-card-wrapper {
            flex: 0 0 582px;
            box-shadow: 0 14px 35px rgba(15, 23, 42, 0.18);
        }

        @media (max-width: 650px) {
            body {
                padding: 10px;
            }

            .id-card-toolbar {
                position: static;
            }

            .id-card-status {
                width: 100%;
                margin-left: 0;
            }

            .id-card-stage {
                display: block;
                overflow-x: auto;
            }

            .front-card-wrapper,
            .back-card-wrapper {
                margin: 0 auto 20px;
            }
        }
    </style>
</head>
<body>
    @php
        $headquartersAddress = 'Udoji Road, Along Odo-Aje Road, Opposite, Okobo Joint, Ilaro, Ogun State';
        $downloadBaseName = \Illuminate\Support\Str::slug($member->display_member_no ?: $member->name);
    @endphp

    <div class="id-card-toolbar">
        <a href="{{ url()->previous() }}">&larr; Back</a>
        <button type="button" data-download-side="front">Download Front</button>
        <button type="button" data-download-side="back">Download Back</button>
        <button type="button" id="downloadBoth">Download Both</button>
        <span class="id-card-status" id="downloadStatus">PNG download at 4× resolution</span>
    </div>

    <main class="id-card-stage">
        <div class="front-card-wrapper" id="idCardFront">
            <img src="{{ asset('id-card/bg/image 5.svg') }}" alt="" class="bg-up">
            <img src="{{ $branchLogo }}" alt="{{ $branch->name }} logo" class="logo">
            <p class="rc-number">RC: {{ $branch->registration_number ?: '14043' }}</p>

            <div class="content">
                <h1>{{ strtoupper($branch->name) }}</h1>
                <p class="affiliated-text">Affiliated to</p>
                <h1>OREOLUWAPO ILARO CTCU LTD</h1>
                <div class="address">
                    <p>
                        <span class="icon"><i class="fas fa-map-marker-alt"></i></span>
                        <span class="secretariat-text">Secretariat</span>
                        <span class="inner-address">{{ $headquartersAddress }}</span>
                    </p>
                    <div class="number">
                        <span class="phone-icon"><i class="fas fa-phone-alt"></i></span>
                        <span>+2348151273635</span>
                        <span>+2348060957070</span>
                    </div>
                </div>

                <img class="image-wrapper" src="{{ $memberPhoto }}" alt="{{ $member->name }}">

                <div class="name-wrapper">
                    <h1 class="user-name-id-card">{{ strtoupper($member->name) }}</h1>
                    <h4 class="user-role">{{ strtoupper($member->designation ?: 'MEMBER') }}</h4>
                    <p class="user-number">{{ $member->detail?->mobile ?: $member->email }}</p>
                    <p class="user-unique-name">{{ $member->display_member_no ?: 'N/A' }}</p>
                    <p class="user-address">{{ $member->detail?->address ?: 'Address not provided' }}</p>
                    <img src="{{ $memberSignature }}" alt="{{ $member->name }} signature" class="signature-front">
                </div>
            </div>

            <img src="{{ asset('id-card/bg/vecto_logo.png') }}" alt="Oreoluwapo Ilaro CTCU logo" class="company-logo">
            <div class="info-down">
                <p>www.oreoluwapoilaroctcultd.com</p>
            </div>
        </div>

        <div class="back-card-wrapper" id="idCardBack">
            <img src="{{ asset('id-card/bg/image 4.svg') }}" alt="" class="bg-back-up">

            <div class="content-up">
                <div class="image-left">
                    <img src="{{ asset('id-card/bg/vecto_logo.png') }}" alt="Oreoluwapo Ilaro CTCU logo">
                </div>
                <div class="content-right">
                    <h1>OREOLUWAPO ILARO CTCU LTD</h1>
                    <p>COOPERATIVE THRIFT AND CREDIT UNION LIMITED</p>
                </div>
            </div>

            <div class="certify-text">
                <p>
                    This is to certify that the person whose Name, Passport and Signature
                    appear on this Identity Card is a member of
                </p>
                <h1>{{ strtoupper($branch->name) }}</h1>
                <p class="found-text">
                    If found, kindly return it to the address Overleaf or call the numbers
                </p>

                <div class="line-and-text">
                    <img src="{{ $branchSignature }}" alt="Authorized signature" class="signature">
                    <p>Authorized Signature</p>
                </div>

                <div class="code-and-text">
                    <img src="{{ asset('id-card/bg/barcode.png') }}" alt="Barcode" class="barcode">
                    <p>Motto: Cooperative with trust and God fearing...</p>
                </div>

                <img src="{{ asset('id-card/bg/image 2.svg') }}" alt="" class="bg-back-down">
            </div>
        </div>
    </main>

    <script>
        (() => {
            const status = document.getElementById('downloadStatus');
            const fileBaseName = @json($downloadBaseName ?: 'member');

            const waitForAssets = async (card) => {
                if (document.fonts && document.fonts.ready) {
                    await document.fonts.ready;
                }

                await Promise.all(Array.from(card.querySelectorAll('img')).map((image) => {
                    if (image.complete) {
                        return Promise.resolve();
                    }

                    return new Promise((resolve) => {
                        image.addEventListener('load', resolve, { once: true });
                        image.addEventListener('error', resolve, { once: true });
                    });
                }));
            };

            const downloadSide = async (side) => {
                const card = document.getElementById(side === 'back' ? 'idCardBack' : 'idCardFront');
                status.textContent = `Preparing ${side}...`;
                await waitForAssets(card);

                const canvas = await html2canvas(card, {
                    scale: 4,
                    backgroundColor: '#ffffff',
                    useCORS: true,
                    logging: false,
                });

                const link = document.createElement('a');
                link.href = canvas.toDataURL('image/png');
                link.download = `${fileBaseName}-id-card-${side}.png`;
                link.click();
                status.textContent = `${side.charAt(0).toUpperCase() + side.slice(1)} downloaded`;
            };

            document.querySelectorAll('[data-download-side]').forEach((button) => {
                button.addEventListener('click', () => downloadSide(button.dataset.downloadSide).catch(() => {
                    status.textContent = 'Download failed. Please reload and try again.';
                }));
            });

            document.getElementById('downloadBoth').addEventListener('click', async () => {
                try {
                    await downloadSide('front');
                    await downloadSide('back');
                    status.textContent = 'Front and back downloaded';
                } catch (error) {
                    status.textContent = 'Download failed. Please reload and try again.';
                }
            });
        })();
    </script>
</body>
</html>
