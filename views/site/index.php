<?php

/* @var $this yii\web\View */

use yii\helpers\Url;

$baseUrl = Url::base() . '/academic';
$this->title = 'iTRUST | SERVICE IPHONE | SURABAYA';

$i=1;
?>

<!-- [ Container ] start -->
<div class="container">
            <div class="row justify-content-center">
            <div class="col-md-10 text-center">
                <h1 class="mb-4 wow fadeInUp" data-wow-delay="0.2s"
                >  <span class="hero-text-gradient">Apple Service | Surabaya</span> </h1
                >
                <div class="row justify-content-center wow fadeInUp" data-wow-delay="0.3s">
                <div class="col-md-8">
                    <p class="text-muted f-16 mb-0"
                    >Jasa Perbaikan Iphone di Surabaya</p
                    >
                </div>
                </div>
                <div class="my-4 my-sm-5 wow fadeInUp" data-wow-delay="0.4s">
                <a target="_blank" href="https://www.google.com/search?hl=id-ID&gl=id&q=iTRUST+%7C+SERVICE+IPHONE+%7C+SURABAYA,+Jl.+Tambak+Windu+I+No.12,+RT.003/RW.08,+Tambakrejo,+Kec.+Simokerto,+Surabaya,+Jawa+Timur+60142&ludocid=4895260041900631275&lsig=AB86z5WXctJevflxRqe5XAUhnVNZ&mat=CVbPY43clVbnElcBeenfiBD6Y4dqHousvIiBR8pWw-oPLeeKw2GLAIwUIXl59vWqyqIvIVxuicRtI6NaOn5Tkk7U5YzsaiEjHQu38vpiuF2ghuqvmc6fjUeejsuSqGYW5Yo" class="btn btn-outline-secondary me-2">Kunjungi sekarang</a>
                <a target="_blank" href="https://wa.me/62882009057770" class="btn btn-primary">Hubungi Kami</a>
                </div>
                <div class="row g-5 justify-content-center text-center wow fadeInUp" data-wow-delay="0.5s">
                <div class="col-auto head-rating-block">
                    <div class="star mb-2">
                      <?php // $rating = 5; ?>
                      <?php for($j=1;$j<=$rating;$j++) {?>
                        <i class="fas fa-star text-warning"></i>
                      <?php }
                        $number = $rating;
                        $decimalPart = fmod($number, 1);
                        // Multiply by 10 and round to get the first decimal digit
                        $firstDecimal = round($decimalPart * 10);
                        if($firstDecimal>=5) {
                          echo '<i class="fas fa-star-half-alt text-warning"></i>';
                        }
                      ?>
                      
                    </div>
                    <h4 class="mb-0"><a target="_blank" href="https://www.google.com/search?hl=id-ID&gl=id&q=iTRUST+%7C+SERVICE+IPHONE+%7C+SURABAYA,+Jl.+Tambak+Windu+I+No.12,+RT.003/RW.08,+Tambakrejo,+Kec.+Simokerto,+Surabaya,+Jawa+Timur+60142&ludocid=4895260041900631275&lsig=AB86z5WXctJevflxRqe5XAUhnVNZ&mat=CVbPY43clVbnElcBeenfiBD6Y4dqHousvIiBR8pWw-oPLeeKw2GLAIwUIXl59vWqyqIvIVxuicRtI6NaOn5Tkk7U5YzsaiEjHQu38vpiuF2ghuqvmc6fjUeejsuSqGYW5Yo"> <?=$rating?>/5 <small class="text-muted f-w-400"> Google Ratings</small></a></h4>
                </div>
                <!-- <div class="col-auto">
                    <h5 class="mb-2"><small class="text-muted f-w-400"> Sales</small></h5>
                    <h4 class="mb-0">2.5K+</h4>
                </div> -->
                </div>
                <div class="row g-5 mt-1 justify-content-center text-center wow fadeInUp" data-wow-delay="1s">
                <!-- <div class="col-auto">
                    <p class="mb-4 text-muted">- Click Below Icon to Preview Each Tech Demos -</p>
                </div> -->
                </div>
            </div>
            </div>
        </div>

        <br>
<!-- <div class="hero-slide owl-carousel site-blocks-cover">

  <?php foreach ($banner_list as $key => $value) : ?>
    <div class="intro-section" style="background-image: url('<?= Url::base() ?>/<?= $value["b_foto"] ?>');">
      <div class="container">
        <div class="row align-items-center">
          <div class="col-lg-12 mx-auto text-center" data-aos="fade-up">
            <h1><a class="text-black" href="<?= $value["b_link"] ?>"><?= $value["b_title"] ?></a></h1>
          </div>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div> -->

<div class="page-body gallery-page">
    <div class="row">
        <!-- image grid -->
        <div class="col-sm-12">
            <!-- Image grid card start -->
            <div class="card" style="padding-bottom: 50px;">
                <div class="card-header text-center">
                    <h2 class="section-title-underline mb-2">
                        <span>Galleri</span>
                    </h2>
                </div>
                <div class="card-block">
                    <div class="row">
                    <?php foreach ($gallery_list as $key => $value) : ?>

                      <div class="col-lg-4 col-sm-6">
                            <div class="thumbnail">
                                <div class="thumb">
                                  <!-- <a href="<?= Url::base() ?>/<?= $value["g_foto"] ?>" data-lightbox="galleri"><?= $value["g_title"] ?></a> -->
                                    <a 
                                      target="_blank" 
                                      href="<?= Url::base() ?>/<?= $value["g_foto"] ?>" 
                                      data-lightbox="gallery" 
                                      data-title="<?= $value["g_title"] ?>">
                                        <img src="<?= Url::base() ?>/<?= $value["g_foto"] ?>" alt="" class="img-fluid img-thumbnail">
                                    </a>
                                </div>
                            </div>
                        </div>


                    <?php endforeach; ?>
                        
                        
                    </div>
                </div>
            </div>
            <!-- Image grid card end -->
        </div>

    </div>
</div>  

<section class="">

  <div class="site-section">
    <div class="container">
      <div class="row mb-2 justify-content-center text-center">
        <div class="col-lg-6 mb-5">
          <h2 class="section-title-underline mb-2">
            <span><a href="https://www.google.com/search?hl=id-ID&gl=id&q=iTRUST+%7C+SERVICE+IPHONE+%7C+SURABAYA,+Jl.+Tambak+Windu+I+No.12,+RT.003/RW.08,+Tambakrejo,+Kec.+Simokerto,+Surabaya,+Jawa+Timur+60142&ludocid=4895260041900631275&lsig=AB86z5WXctJevflxRqe5XAUhnVNZ&mat=CVbPY43clVbnElcBeenfiBD6Y4dqHousvIiBR8pWw-oPLeeKw2GLAIwUIXl59vWqyqIvIVxuicRtI6NaOn5Tkk7U5YzsaiEjHQu38vpiuF2ghuqvmc6fjUeejsuSqGYW5Yo#lrd=0x2dd7f9e42b4dbcc9:0x43ef751268a6a4eb,1,,,," target="_blank"> Testimoni </a></span>
          </h2>
        </div>
      </div>
      <div class="row">
        <div class="col-lg-12 col-md-12 mb-4 mb-lg-0">

        <div class="p-3 px-lg-5 text-center">
              <div id="carouselExampleIndicators" class="carousel slide" data-bs-ride="carousel">
                <div class="carousel-inner">
                <?php  $totalObjects = count($reviews); ?>

                <?php for ($i = 0; $i < $totalObjects; $i += 2) {?>

                  <div class="carousel-item <?=($i==0)?'active':''?>">

                    <div class="row">

                      <div class="col-lg-6 col-md-12 col-sm-12 col-xs-12" style="float:none;margin:auto;">
                        <div class="card support-card">
                          <div class="card-body">
                            <div class="d-flex">
                              <div class="flex-shrink-0">
                                <img src="<?=$reviews[$i]['profile_photo_url']?>" alt="user-image" class="rounded-circle wid-60 hei-60">
                                <br>
                                <?php for($j=0;$j<$reviews[$i]['rating'];$j++) {?>
                                  <i class="fas fa-star text-warning"></i>
                                <?php } ?>
                              </div>
                              <div class="flex-grow-1 ms-3">
                                <p class="mb-1">
                                <?=$reviews[$i]['text']?>
                                </p>
                                <small><a target="_blank" href="<?=$reviews[$i]['author_url']?>"><?=$reviews[$i]['author_name']?></a> -
                                  <span class="text-muted"><?=$reviews[$i]['relative_time_description']?></span></small>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>

                      <?php if ($i + 1 < $totalObjects) { ?>
                      <div class="col-lg-6 col-md-12 col-sm-12 col-xs-12">
                        <div class="card support-card">
                          <div class="card-body">
                            <div class="d-flex">
                              <div class="flex-shrink-0">
                                <img src="<?=$reviews[$i + 1]['profile_photo_url']?>" alt="user-image" class="rounded-circle wid-60 hei-60">
                                <br>
                                <?php for($j=0;$j<$reviews[$i + 1]['rating'];$j++) {?>
                                  <i class="fas fa-star text-warning"></i>
                                <?php } ?>
                              </div>
                              <div class="flex-grow-1 ms-3">
                                <p class="mb-1">
                                <?=$reviews[$i + 1]['text']?>
                                </p>
                                <small><a target="_blank" href="<?=$reviews[$i + 1]['author_url']?>"><?=$reviews[$i + 1]['author_name']?></a> -
                                  <span class="text-muted"><?=$reviews[$i + 1]['relative_time_description']?></span></small>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>
                      <?php } ?>



                    </div>
                    
                  </div>

                <?php } ?>

                </div>
                <div class="carousel-indicators position-relative mt-3">
                <?php
                $ii=0; 
                for ($i = 0; $i < $totalObjects; $i += 2) {
                  if(($i==0)) {
                    echo '<button
                            type="button"
                            data-bs-target="#carouselExampleIndicators"
                            data-bs-slide-to="'.$ii.'"
                            class="active btn-carousel"
                            aria-current="true"
                            aria-label="Slide '.($i+1).'"
                          ></button>';
                  }
                  else {
                    echo '<button class="btn-carousel" type="button" data-bs-target="#carouselExampleIndicators" data-bs-slide-to="'.$ii.'" aria-label="Slide '.($i+1).'"></button>';
                  }
                  $ii++;
                } ?>
                <!-- <hr>
                  <button
                    type="button"
                    data-bs-target="#carouselExampleIndicators"
                    data-bs-slide-to="0"
                    class="active btn-carousel"
                    aria-current="true"
                    aria-label="Slide 1"
                  ></button>
                  <button class="btn-carousel" 
                  ype="button" 
                  data-bs-target="#carouselExampleIndicators" 
                  data-bs-slide-to="1" 
                  aria-label="Slide 2"></button>
                  <button class="btn-carousel" type="button" data-bs-target="#carouselExampleIndicators" data-bs-slide-to="2" aria-label="Slide 3"></button>
                  <button class="btn-carousel" type="button" data-bs-target="#carouselExampleIndicators" data-bs-slide-to="3" aria-label="Slide 4"></button>
                  <button class="btn-carousel" type="button" data-bs-target="#carouselExampleIndicators" data-bs-slide-to="4" aria-label="Slide 5"></button> -->

                </div>
              </div>
            </div>
        </div>
      </div>
    </div>
  </div>

</section>

