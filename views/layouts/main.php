<?php

/* @var $this \yii\web\View */
/* @var $content string */

use yii\helpers\Html;
use yii\helpers\Url;
use app\assets\AppAsset;

AppAsset::register($this);

$baseUrl = Url::base() . '/able';

$c = Yii::$app->controller->id;
$a = Yii::$app->controller->action->id;
?>

<?php $this->beginPage() ?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="<?= Yii::$app->charset ?>">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="icon" type="image/x-icon" href="<?= $baseUrl ?>/assets/images/logo_100.png">

    <?php $this->registerCsrfMetaTags() ?>

    <!-- <link href="https://fonts.googleapis.com/css?family=Muli:300,400,700,900" rel="stylesheet"> -->
    <!-- [Page specific CSS] start -->
    <link href="<?= $baseUrl ?>/assets/css/plugins/animate.min.css" rel="stylesheet" type="text/css" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/assets/owl.carousel.min.css" rel="stylesheet" />
    <!-- [Page specific CSS] end -->
    
    <!-- [Font] Family -->
    <link rel="stylesheet" href="<?= $baseUrl ?>/assets/fonts/inter/inter.css" id="main-font-link" />

    <!-- [Tabler Icons] https://tablericons.com -->
    <link rel="stylesheet" href="<?= $baseUrl ?>/assets/fonts/tabler-icons.min.css" />
    <!-- [Feather Icons] https://feathericons.com -->
    <link rel="stylesheet" href="<?= $baseUrl ?>/assets/fonts/feather.css" />
    <!-- [Font Awesome Icons] https://fontawesome.com/icons -->
    <link rel="stylesheet" href="<?= $baseUrl ?>/assets/fonts/fontawesome.css" />
    <!-- [Material Icons] https://fonts.google.com/icons -->
    <link rel="stylesheet" href="<?= $baseUrl ?>/assets/fonts/material.css" />
    <!-- [Template CSS Files] -->
    <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/style.css" id="main-style-link" />
    <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/style-preset.css" />

    <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/landing.css" />

    <link href="<?= $baseUrl ?>/plugins/lightbox/css/lightbox.css" rel="stylesheet" />

    <!-- Gradient Text Animation CSS -->
    <style>
      .btn-carousel {
        background-color: #5B6B79 !important;
      }
    .circle {
        border-radius: 50%;
    }
    .hero-text-gradient {
        --bg-size: 400%;
        --color-one: rgb(37, 161, 244);
        --color-two: rgb(249, 31, 169);

        background: linear-gradient(90deg, var(--color-one), var(--color-two), var(--color-one)) 0 0 / var(--bg-size) 100%;
        color: transparent;
        -webkit-background-clip: text;
        background-clip: text;
        animation: move-bg 24s infinite linear;
    }
    @keyframes move-bg {
        to {
            background-position: var(--bg-size) 0;
        }
    }

    /* lightbox */
    </style>
    <title><?= Html::encode($this->title) ?></title>
    <?php $this->head() ?>

</head>

<body data-pc-preset="preset-1" data-pc-sidebar-caption="true" data-pc-direction="ltr" data-pc-theme_contrast="" data-pc-theme="light" class="landing-page">

    <!-- [ Main Content ] start -->
    <!-- [ Pre-loader ] start -->
    <div class="loader-bg">
      <div class="loader-track">
        <div class="loader-fill"></div>
      </div>
    </div>
    <!-- [ Pre-loader ] End -->

    <?php $this->beginBody() ?>

    <div class="">

        <!-- <div class="site-mobile-menu site-navbar-target">
            <div class="site-mobile-menu-header">
                <div class="site-mobile-menu-close mt-3">
                    <span class="icon-close2 js-menu-toggle"></span>
                </div>
            </div>
            <div class="site-mobile-menu-body"></div>
        </div>


        <div class="py-2 bg-light">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-lg-9 d-none d-lg-block">
                        <a href="#" class="small mr-3"><span class="icon-question-circle-o mr-2"></span> Have a questions?</a>
                        <a href="#" class="small mr-3"><span class="icon-phone2 mr-2"></span> +60 19-282 1389</a>
                    </div>
                    <div class="col-lg-3 text-right">
                        <?php if (Yii::$app->user->isGuest) : ?>

                            <a href="<?= Url::to(['site/login']) ?>" class="small mr-3"><span class="icon-unlock-alt"></span> Log In</a>
                            <a href="<?= Url::to(['site/register']) ?>" class="small btn btn-primary px-4 py-2 rounded-0"><span class="icon-users"></span> Register</a>
                        <?php else : ?>
                            <a href="<?= Url::to(['dashboard/index']) ?>" class="small btn btn-primary px-4 py-2 rounded-0"><span class="fa fa-hdd"></span> Dashboard</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div> -->
        
        <!-- [ Header ] start -->
        <!-- <header id="home" style="background-image: url(assets/images/landing/img-headerbg.jpg)"> -->
        <header>
        <!-- [ Nav ] start -->
        <nav class="navbar navbar-expand-md navbar-light default">
            <div class="container">
            <a class="navbar-brand" href="index.html">
                <img src="<?= $baseUrl ?>/assets/images/logo_50.png" class="circle" alt="logo" />
            </a>
            <button
                class="navbar-toggler rounded"
                type="button"
                data-bs-toggle="collapse"
                data-bs-target="#navbarTogglerDemo01"
                aria-controls="navbarTogglerDemo01"
                aria-expanded="false"
                aria-label="Toggle navigation"
            >
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarTogglerDemo01">
                <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-start">
                <li class="nav-item px-1">
                    <a class="nav-link" href="#" target="_blank">Dapatkan Penawaran</a>
                </li>
                <li class="nav-item px-1">
                    <a class="nav-link" href="#">Buat Janji Temu</a>
                </li>
                <!-- <li class="nav-item px-1 me-2 mb-2 mb-md-0">
                    <a
                    class="btn btn-icon btn-light-dark"
                    target="_blank"
                    href="#"
                    ><i class="ti ti-brand-github"></i
                    ></a>
                </li> -->
                <li class="nav-item">
                    <a
                    class="btn btn btn-success"
                    target="_blank"
                    href="#"
                    >Telepon Sekarang <i class="ti ti-phone-call"></i
                    ></a>
                </li>
                </ul>
            </div>
            </div>
        </nav>
        <!-- [ Nav ] end -->

        </header>
        <!-- [ Header ] End -->


        <?= $content ?>


      <!-- [ footer apps ] start -->
    <footer class="footer">
      <section class="bg-primary overflow-hidden">

        <div class="container title mb-0">
          <div class="row align-items-center wow fadeInUp " data-wow-delay="0.2s">
            <div class="col-md-8">
              <h2 class="mb-3 text-white">Gratis langganan dengan kami</h2>
              <p class="mb-4 mb-md-0 text-white"
                >Cukup dengan input alamat email anda, dan kami akan memberitahukan info seputar promo menarik.</p
              >
            </div>
            <div class="col-md-4">
              <div class="row">
                <div class="col"><input type="email" class="form-control" placeholder="Enter your email" /></div>
                <div class="col-auto"><button class="btn btn-success">Subscribe</button></div>
              </div>
            </div>
          </div>
        </div>

      </section>
      <div class="border-top border-bottom footer-center">
        <div class="container">
          <div class="row">
            <div class="col-md-4 wow fadeInUp" data-wow-delay="0.2s">
            <h5 class="mb-4">Kontak</h5>
                  <ul class="list-unstyled footer-link">
                    <li>
                      <a href="https://wa.me/62882009057770" target="_blank">0882-0090-57770</a>
                    </li>
                  </ul>
            </div>
            <div class="col-md-8">
              <div class="row">
                <div class="col-sm-6 wow fadeInUp" data-wow-delay="0.6s">
                  <h5 class="mb-4">Alamat</h5>
                  <ul class="list-unstyled footer-link">
                    <li>
                      <a href="https://wa.me/62882009057770" target="_blank">0882-0090-57770</a>
                    </li>
                  </ul>
                </div>
                <div class="col-sm-6 wow fadeInUp" data-wow-delay="0.8s">
                  <h5 class="mb-4">Jam buka</h5>
                  <ul class="list-unstyled footer-link">
                    <li>Senin : 09:00 AM - 05:00 PM</li>
                    <li>Selasa : 09:00 AM - 05:00 PM</li>
                    <li>Rabu : 09:00 AM - 05:00 PM</li>
                    <li>Kamis : 09:00 AM - 05:00 PM</li>
                    <li>Jumat : 09:00 AM - 05:00 PM</li>
                    <li>Sabtu : 09:00 AM - 05:00 PM</li>
                    <li>Minggu : 09:00 AM - 05:00 PM</li>
                    
                  </ul>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="container">
        <div class="row align-items-center">
          <div class="col my-1 wow fadeInUp" data-wow-delay="0.4s">
            <p class="mb-0">© Handcrafted by Team <a href="#" target="_blank">iTrust</a></p>
          </div>
          <!-- <div class="col-auto my-1">
            <ul class="list-inline footer-sos-link mb-0">
              <li class="list-inline-item wow fadeInUp" data-wow-delay="0.4s"
                ><a href="https://fb.com/phoenixcoded">
                  <svg class="pc-icon">
                    <use xlink:href="#custom-facebook"></use>
                  </svg> </a
              ></li>
            </ul>
          </div> -->
        </div>
      </div>
    </footer>
    <!-- [ footer apps ] End -->


    </div>
    <!-- .site-wrap -->


    <script src="<?= $baseUrl ?>/plugins/jquery/jquery.min.js"></script>

    
    <!-- [ Main Content ] end -->
    <!-- Required Js -->
    <script src="<?= $baseUrl ?>/assets/js/plugins/popper.min.js"></script>
    <script src="<?= $baseUrl ?>/assets/js/plugins/simplebar.min.js"></script>
    <script src="<?= $baseUrl ?>/assets/js/plugins/bootstrap.min.js"></script>
    <script src="<?= $baseUrl ?>/assets/js/fonts/custom-font.js"></script>
    <script src="<?= $baseUrl ?>/assets/js/pcoded.js"></script>
    <script src="<?= $baseUrl ?>/assets/js/plugins/feather.min.js"></script>
    <!-- lightbox -->
    <script src="<?= $baseUrl ?>/plugins/lightbox/js/lightbox.js"></script>

    <!-- [Page Specific JS] start -->
    <script src="<?= $baseUrl ?>/assets/js/plugins/wow.min.js"></script>
    <script src="https://cdn.jsdelivr.net/jquery.marquee/1.4.0/jquery.marquee.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/owl.carousel.min.js"></script>
    <script src="<?= $baseUrl ?>/assets/js/plugins/Jarallax.js"></script>

    <script>
      // Start [ Menu hide/show on scroll ]
      let ost = 0;
      document.addEventListener('scroll', function () {
        let cOst = document.documentElement.scrollTop;
        if (cOst == 0) {
          document.querySelector('.navbar').classList.add('top-nav-collapse');
        } else if (cOst > ost) {
          document.querySelector('.navbar').classList.add('top-nav-collapse');
          document.querySelector('.navbar').classList.remove('default');
        } else {
          document.querySelector('.navbar').classList.add('default');
          document.querySelector('.navbar').classList.remove('top-nav-collapse');
        }
        ost = cOst;
      });
      // End [ Menu hide/show on scroll ]
      var wow = new WOW({
        animateClass: 'animated'
      });
      wow.init();

      // slider start
      $('.screen-slide').owlCarousel({
        loop: true,
        margin: 30,
        center: true,
        nav: false,
        dotsContainer: '.app_dotsContainer',
        URLhashListener: true,
        items: 1
      });
      $('.workspace-slider').owlCarousel({
        loop: true,
        margin: 30,
        center: true,
        nav: false,
        dotsContainer: '.workspace-card-block',
        URLhashListener: true,
        items: 1.5
      });
      // slider end
      // marquee start
      $('.marquee').marquee({
        duration: 500000,
        pauseOnHover: true,
        startVisible: true,
        duplicated: true
      });
      $('.marquee-1').marquee({
        duration: 500000,
        pauseOnHover: true,
        startVisible: true,
        duplicated: true,
        direction: 'right'
      });
      // marquee end
    </script>

<!-- <div class="pct-c-btn">
  <a href="#" data-bs-toggle="offcanvas" data-bs-target="#offcanvas_pc_layout">
    <svg class="pc-icon">
      <use xlink:href="#custom-setting-2"></use>
    </svg>
  </a>
</div>
<div class="offcanvas border-0 pct-offcanvas offcanvas-end" tabindex="-1" id="offcanvas_pc_layout">
  <div class="offcanvas-header">
    <h5 class="offcanvas-title">Settings</h5>
    <button type="button" class="btn btn-icon btn-link-danger" data-bs-dismiss="offcanvas" aria-label="Close"
      ><i class="ti ti-x"></i
    ></button>
  </div>
  <div class="pct-body" style="height: calc(100% - 85px)">
    <div class="offcanvas-body py-0">
      <ul class="list-group list-group-flush">
        <li class="list-group-item">
          <div class="pc-dark">
            <h6 class="mb-1">Theme Mode</h6>
            <p class="text-muted text-sm">Choose light or dark mode or Auto</p>
            <div class="row theme-layout">
              <div class="col-4">
                <div class="d-grid">
                  <button class="preset-btn btn active" data-value="true" onclick="layout_change('light');">
                    <svg class="pc-icon text-warning">
                      <use xlink:href="#custom-sun-1"></use>
                    </svg>
                  </button>
                </div>
              </div>
              <div class="col-4">
                <div class="d-grid">
                  <button class="preset-btn btn" data-value="false" onclick="layout_change('dark');">
                    <svg class="pc-icon">
                      <use xlink:href="#custom-moon"></use>
                    </svg>
                  </button>
                </div>
              </div>
              <div class="col-4">
                <div class="d-grid">
                  <button class="preset-btn btn" data-value="default" onclick="layout_change_default();">
                    <svg class="pc-icon">
                      <use xlink:href="#custom-setting-2"></use>
                    </svg>
                  </button>
                </div>
              </div>
            </div>
          </div>
        </li>
        <li class="list-group-item">
          <h6 class="mb-1">Theme Contrast</h6>
          <p class="text-muted text-sm">Choose theme contrast</p>
          <div class="row theme-contrast">
            <div class="col-6">
              <div class="d-grid">
                <button class="preset-btn btn" data-value="true" onclick="layout_sidebar_change('true');">
                  <svg class="pc-icon">
                    <use xlink:href="#custom-mask"></use>
                  </svg>
                </button>
              </div>
            </div>
            <div class="col-6">
              <div class="d-grid">
                <button class="preset-btn btn active" data-value="false" onclick="layout_sidebar_change('false');">
                  <svg class="pc-icon">
                    <use xlink:href="#custom-mask-1-outline"></use>
                  </svg>
                </button>
              </div>
            </div>
          </div>
        </li>
        <li class="list-group-item">
          <h6 class="mb-1">Custom Theme</h6>
          <p class="text-muted text-sm">Choose your Primary color</p>
          <div class="theme-color preset-color">
            <a href="#!" class="active" data-value="preset-1"><i class="ti ti-check"></i></a>
            <a href="#!" data-value="preset-2"><i class="ti ti-check"></i></a>
            <a href="#!" data-value="preset-3"><i class="ti ti-check"></i></a>
            <a href="#!" data-value="preset-4"><i class="ti ti-check"></i></a>
            <a href="#!" data-value="preset-5"><i class="ti ti-check"></i></a>
            <a href="#!" data-value="preset-6"><i class="ti ti-check"></i></a>
            <a href="#!" data-value="preset-7"><i class="ti ti-check"></i></a>
            <a href="#!" data-value="preset-8"><i class="ti ti-check"></i></a>
            <a href="#!" data-value="preset-9"><i class="ti ti-check"></i></a>
            <a href="#!" data-value="preset-10"><i class="ti ti-check"></i></a>
          </div>
        </li>
        <li class="list-group-item">
          <h6 class="mb-1">Sidebar Caption</h6>
          <p class="text-muted text-sm">Sidebar Caption Hide/Show</p>
          <div class="row theme-nav-caption">
            <div class="col-6">
              <div class="d-grid">
                <button class="preset-btn btn active" data-value="true" onclick="layout_caption_change('true');">
                  <img src="assets/images/customizer/img-caption-1.svg" alt="img" class="img-fluid" width="70%" />
                </button>
              </div>
            </div>
            <div class="col-6">
              <div class="d-grid">
                <button class="preset-btn btn" data-value="false" onclick="layout_caption_change('false');">
                  <img src="assets/images/customizer/img-caption-2.svg" alt="img" class="img-fluid" width="70%" />
                </button>
              </div>
            </div>
          </div>
        </li>
        <li class="list-group-item">
          <div class="pc-rtl">
            <h6 class="mb-1">Theme Layout</h6>
            <p class="text-muted text-sm">LTR/RTL</p>
            <div class="row theme-direction">
              <div class="col-6">
                <div class="d-grid">
                  <button class="preset-btn btn active" data-value="false" onclick="layout_rtl_change('false');">
                    <img src="assets/images/customizer/img-layout-1.svg" alt="img" class="img-fluid" width="70%" />
                  </button>
                </div>
              </div>
              <div class="col-6">
                <div class="d-grid">
                  <button class="preset-btn btn" data-value="true" onclick="layout_rtl_change('true');">
                    <img src="assets/images/customizer/img-layout-2.svg" alt="img" class="img-fluid" width="70%" />
                  </button>
                </div>
              </div>
            </div>
          </div>
        </li>
        <li class="list-group-item">
          <div class="pc-container-width">
            <h6 class="mb-1">Layout Width</h6>
            <p class="text-muted text-sm">Choose Full or Container Layout</p>
            <div class="row theme-container">
              <div class="col-6">
                <div class="d-grid">
                  <button class="preset-btn btn active" data-value="false" onclick="change_box_container('false')">
                    <img src="assets/images/customizer/img-container-1.svg" alt="img" class="img-fluid" width="70%" />
                  </button>
                </div>
              </div>
              <div class="col-6">
                <div class="d-grid">
                  <button class="preset-btn btn" data-value="true" onclick="change_box_container('true')">
                    <img src="assets/images/customizer/img-container-2.svg" alt="img" class="img-fluid" width="70%" />
                  </button>
                </div>
              </div>
            </div>
          </div>
        </li>
        <li class="list-group-item">
          <div class="d-grid">
            <button class="btn btn-light-danger" id="layoutreset">Reset Layout</button>
          </div>
        </li>
      </ul>
    </div>
  </div>
</div> -->

    <?php $this->endBody() ?>
</body>

</html>
<?php $this->endPage() ?>