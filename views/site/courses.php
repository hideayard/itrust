<?php

/* @var $this yii\web\View */

use yii\helpers\Url;
use yii\helpers\Html;
use yii\widgets\LinkPager;

$baseUrl = Url::base() . '/academic';
$this->title = 'Course | PMRO';
?>

<div class="site-section ftco-subscribe-1 site-blocks-cover pb-4" style="background-image: url('<?= $baseUrl ?>/images/bg_1.jpg')">
    <div class="container">
        <div class="row align-items-end">
            <div class="col-lg-7">
                <h2 class="mb-0">COURSES</h2>
                <p>Listing of all courses in Digital TVET Academy</p>
            </div>
        </div>
    </div>
</div>


<div class="custom-breadcrumns border-bottom">
    <div class="container">
        <a href="index.php">Home</a>
        <span class="mx-3 icon-keyboard_arrow_right"></span>
        <span class="current">Courses</span>
    </div>
</div>

<div class="site-section">

    <div class="container">
        <div class="row center">
            <div class="col-md-2"></div>
            <div class="col-md-8 form-group">
                <form action="" class="d-flex">
                    <input type="text" class="rounded form-control mr-2 py-4" name="q" placeholder="Search for a course.." value="<?= Html::encode($q) ?>">
                    <button class="btn btn-navbar rounded btn-primary" type="submit">
                        <i class="fas fa-search"></i>
                    </button>
                </form>
            </div>
            <div class="col-md-2"></div>
        </div><br>
        <div class="row">
        
            <?php foreach ($courses as $course) : ?>

                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="course-1-item">
                       
                        <figure class="thumnail">
                            <a href="<?= Url::to(['site/detailcourse', 'id' => $course['course_id']]) ?>"><img src="<?php if(strpos($course["course_photo"], '/images/') === false ) { echo Url::base() . $course["course_photo"]; }else{ echo $baseUrl . $course["course_photo"]; } ?>" alt="Image" class="img-fluid"></a>
                            <div class="price"><?= $course['course_price'] ?></div>
                            <div class="category">
                                <h3><?= $course['course_type'] ?></h3>
                            </div>
                        </figure>

                        <div class="course-1-content pb-4">
                            <h2><?= $course["course_title"].' - by <span class="badge badge-pill badge-success">'.$course["user_nama"]."</span>" ?> <?php if($course["course_is_online"]){echo '<span class="badge badge-pill badge-info">Online</span>';}else{echo '<span class="badge badge-pill badge-primary">Offline</span>';} ?></h2>
                            <div class="rating text-center mb-3">
                                <?php if ($course['course_star'] >= 1) : ?> <span class="icon-star2 text-warning"></span> <?php endif; ?>
                                <?php if ($course['course_star'] >= 2) : ?> <span class="icon-star2 text-warning"></span> <?php endif; ?>
                                <?php if ($course['course_star'] >= 3) : ?> <span class="icon-star2 text-warning"></span> <?php endif; ?>
                                <?php if ($course['course_star'] >= 4) : ?> <span class="icon-star2 text-warning"></span> <?php endif; ?>
                                <?php if ($course['course_star'] >= 5) : ?> <span class="icon-star2 text-warning"></span> <?php endif; ?>
                            </div>
                            <p class="desc mb-4"><?= $course['course_desc'] ?></p>
                            <p><a href="<?= Url::to(['site/detailcourse', 'id' => $course['course_id']]) ?>" class="btn btn-primary rounded-0 px-4">Enroll In This Course</a></p>
                        </div>
                    </div>
                </div>

            <?php endforeach; ?>

            <div class="col-lg-12 col-md-12 mb-12 center">
                <hr>
                <!-- <button class="btn btn-primary rounded py-3 px-4" type="submit">Load More Courses</button> -->

                <?php
                echo LinkPager::widget([
                    'pagination' => $pagination,
                    'activePageCssClass' => 'active',
                    'pageCssClass' => 'page-item',
                    'disabledPageCssClass' => 'page-item disabled',
                    'disabledListItemSubTagOptions' =>  [
                        'class' => 'page-link',
                    ],
                    'linkOptions' => [
                        'class' => 'page-link',
                    ],
                    'options' => [
                        'class' => 'pagination justify-content-center m-0'
                    ],
                    'firstPageLabel' => '<span class="fa fa-fast-backward"></span>',
                    'lastPageLabel' => '<span class="fa fa-fast-forward"></span>',
                    'nextPageLabel' => '<span class="fa fa-step-forward"></span>',
                    'prevPageLabel' => '<span class="fa fa-step-backward"></span>',
                ]);
                ?>

            </div>

        </div>
    </div>
</div>