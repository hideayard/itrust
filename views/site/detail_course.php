<?php

/* @var $this yii\web\View */

use yii\helpers\Url;
use yii\helpers\Html;

$baseUrl = Url::base() . '/academic';
$this->title = 'Course | PMRO';
//for debugging, delete when finished
// var_dump($enroll);
// echo "<hr>";
// var_dump($course_detail);die;
?>
<div class="custom-breadcrumns border-bottom">
    <div class="container">
        <a href="<?= $baseUrl ?>/">Home</a>
        <span class="mx-3 icon-keyboard_arrow_right"></span>
        <a href="<?= $baseUrl ?>/courses">Courses</a>
        <span class="mx-3 icon-keyboard_arrow_right"></span>
        <span class="current">Courses</span>
    </div>
</div>

<div class="site-section">
    <div class="container">
        <div class="row shadow p-3 mb-5 bg-white rounded">
            <div class="col-md-6 mb-4">
                <p>
                    <img src="<?php if(strpos($course_detail[0]["course_photo"], '/images/') === false ) { echo Url::base() . $course_detail[0]["course_photo"]; }else{ echo $baseUrl . $course_detail[0]["course_photo"]; } ?>" alt="Image" class="img-fluid">
                </p>
            </div>
            <div class="col-lg-5 ml-auto align-self-center">
                <h2 class="section-title-underline mb-5">
                    <span><?= $course_detail[0]["course_title"] ?></span>
                </h2>

                <p><strong class="text-black d-block">Teacher:</strong> <?= $course_detail[0]["user_nama"] ?> <?php // var_dump($course_detail) ?></p>
                <p class="mb-5"><strong class="text-black d-block">Hours:</strong> <?=date("h:i A", strtotime($course_detail[0]["cs_hour_start"]))?> &mdash; <?=date("h:i A", strtotime($course_detail[0]["cs_hour_end"]))?></p>


            </div>
        </div>
        <div class="row shadow p-3 mb-5 bg-white rounded">
            <div class="col-md-3">
                <table>
                    <tr>
                        <td class="align-middle" rowspan="2"><button type="button" class="btn btn-lg btn-primary" disabled><i class="fas fa-play-circle"></i></button></td>
                        <td style="padding-left:5px;"> <?php if($course_detail[0]["course_is_online"]){echo '<span class="badge badge-pill badge-info">Online</span>';}else{echo '<span class="badge badge-pill badge-primary">Offline</span>';} ?></td>
                    </tr>
                    <tr>
                        <td style="padding-left:5px;"> <?=date("d", strtotime($course_detail[0]["cs_date_start"]))?>-<?=date("d F Y", strtotime($course_detail[0]["cs_date_end"]))?></td>
                    </tr>
                    <tr>
                        <td colspan="2">
                            <hr>
                        </td>
                    </tr>
                    <tr>
                        <td></td>
                        <td style="padding-left:5px;"> Next course</td>
                    </tr>
                    <tr>
                        <td></td>
                        <td style="padding-left:5px;"> <?= $course_detail[0]["cs_next_course"] ?> </td>
                    </tr>
                </table>

            </div>

            <div class="col-md-3">
                <table>
                    <tr>
                        <td class="align-middle" rowspan="2"><button type="button" class="btn btn-lg btn-primary" disabled>
                                <i class="fas fa-calendar-check"></i></button></td>
                        <td style="padding-left:5px;"> Application dateline</td>
                    </tr>
                    <tr>
                        <td style="padding-left:5px;"> <?=date("d F Y", strtotime($course_detail[0]["cs_dateline"]))?></td>
                    </tr>
                    <tr>
                        <td colspan="2">
                            <hr>
                        </td>
                    </tr>
                    <tr>
                        <td class="align-middle" rowspan="2"><button type="button" class="btn btn-lg btn-primary" disabled>
                                <i class="fas fa-dollar-sign"></i></button></td>
                        <td style="padding-left:5px;"> Price</td>
                    </tr>
                    <tr>
                        <td style="padding-left:5px;"> <?= $course_detail[0]["course_price"] ?></td>
                    </tr>
                </table>

            </div>

            <div class="col-md-3">
                <table>
                    <tr>
                        <td class="align-middle" rowspan="2"><button type="button" class="btn btn-lg btn-primary" disabled>
                                <i class="fas fa-info-circle"></i></button></td>
                        <td style="padding-left:5px;"> Email :</td>
                    </tr>
                    <tr>
                        <td style="padding-left:5px;"> <?= $course_detail[0]["user_email"] ?> </td>
                    </tr>
                    <tr>
                        <td colspan="2">
                            <hr>
                        </td>
                    </tr>
                    <tr>
                        <td class="align-middle" rowspan="2"><button type="button" class="btn btn-lg btn-primary" disabled>
                                <i class="fas fa-qrcode"></i></button></td>
                        <td style="padding-left:5px;"> Code</td>
                    </tr>
                    <tr>
                        <td style="padding-left:5px;">  <?= $course_detail[0]["cs_code"] ?></td>
                    </tr>
                </table>

            </div>

            <div class="col-md-3 center">
                <table class="center">
                    <tr>
                        <td class="">
                            <p> <button type="button" class="border border-info"> Download Information Note </button> </p>
                        </td>
                    </tr>
                    <tr>
                        <td>
                        <?php
                         if($course_detail[0]["course_price"] == "FREE") 
                         {
                            //  echo '<p><button type="button" class="btn btn-md btn-primary">Enroll Now</button></p>';
                         }
                         else
                         {
                            echo '<p><button type="button" class="btn btn-md btn-primary">Paid Now</button></p>';
                         }
                         ?>
                            
                        </td>
                    </tr>

                </table>

            </div>

        </div>

        <div class="row shadow p-3 mb-5 bg-white rounded">
            <div class="col-md-12 ">
                <?= ($course_detail[0]["course_content"]) ?>
            </div>

            <div class="col-md-12 ">
            <?php if (!$penuh) : ?>
                <?php if (!Yii::$app->user->isGuest) : ?>
                    <div>
                        <?php if ($enroll->isNewRecord) : ?>
                            <form method="post">
                            <p>This training will run with identical content. The proposed that is as follow:</p>
                                <p>&nbsp;</p>
                                <div class="col-md-6 form-group">
                                    <label for="tdate">Pick a Date :</label>
                                    <select name="tdate" id="tdate" class="form-control form-control-lg">
                                    <option value="1">Session 1 : 11-12 January 2021</option>
                                    <option value="2">Session 2 : 18-19 January 2021</option>
                                    <option value="3">Session 3 : 25-26 January 2021</option>
                                    </select>
                                </div>
                                <input type="hidden" name="<?= Yii::$app->request->csrfParam ?>" value="<?= Yii::$app->request->csrfToken ?>">
                                <input type="hidden" name="Enroll[enroll_userid]" value="<?= Yii::$app->user->identity->user_id ?>">
                                <input type="hidden" name="Enroll[enroll_courseid]" value="<?= $course_detail[0]['course_id'] ?>">
                                <input type="hidden" name="Enroll[enroll_cs]" value="">
                                <button class="btn btn-primary rounded-0 btn-lg px-5">Enroll Now</button>
                            </form>
                        <?php else : ?>
                            <a class="btn btn-primary rounded-0 btn-lg px-5" href="<?= Url::to(['site/enroll', 'id' => $enroll->enroll_courseid , 'id_section' => 0]) ?>">View Course</a>
                        <?php endif; ?>
                    </div>
                <?php else : ?>
                <p>This training will run with identical content. The proposed that is as follow:</p>
                <p>&nbsp;</p>
                <div class="col-md-6 form-group">
                    <label for="tdate">Pick a Date :</label>
                    <select name="tdate" id="tdate" class="form-control form-control-lg">
                    <option value="1">Session 1 : 11-12 January 2021</option>
                    <option value="2">Session 2 : 18-19 January 2021</option>
                    <option value="3">Session 3 : 25-26 January 2021</option>
                    </select>
                </div>
                <a href="<?= Url::to(['site/login']) ?>" class="btn btn-primary rounded-0 btn-lg px-5">Enroll Now</a>
                
                <?php endif; ?>
            <?php else : ?>
                <p>This training will run with identical content. The proposed that is as follow:</p>
                <p>&nbsp;</p>
                <div class="col-md-6 form-group">
                    <label for="tdate">Pick a Date :</label>
                    <select name="tdate" id="tdate" class="form-control form-control-lg">
                    <option value="1">Session 1 : 11-12 January 2021</option>
                    <option value="2">Session 2 : 18-19 January 2021</option>
                    <option value="3">Session 3 : 25-26 January 2021</option>
                    </select>
                </div>
                <a href="#" class="btn btn-primary rounded-0 btn-lg px-5">Participant is Full</a>
            <?php endif; ?>
            <p>&nbsp;</p>
            <p><strong>Contact Information</strong></p>
            <p>&nbsp;</p>
            <p>For more information, please contact:</p>
            <p>Ts. Dr. Affero Ismail</p>
            <p><a href="mailto:affero@uthm.edu.my">affero@uthm.edu.my</a></p>
            <p>+6019-7789843</p>
            </div>
            

        </div>

    </div>
</div>