<?php

/* @var $this yii\web\View */

use yii\helpers\Url;
use yii\widgets\ActiveForm;

$baseUrl = Url::base() . '/academic';
$this->title = 'Email Confirmation | iTrust';
?>

<div class="site-section ftco-subscribe-1 site-blocks-cover pb-4" style="background-image: url('<?= $baseUrl ?>/images/bg_1.jpg')">
    <div class="container">
        <div class="row align-items-end justify-content-center text-center">
            <div class="col-lg-7">
                <h2 class="mb-0">Re-Send Email Confirmation</h2>
            </div>
        </div>
    </div>
</div>


<div class="custom-breadcrumns border-bottom">
    <div class="container">
        <a href="index.html">Home</a>
        <span class="mx-3 icon-keyboard_arrow_right"></span>
        <span class="current">Re-Send Email Confirmation</span>
    </div>
</div>

<div class="site-section">
    <div class="container">
        <div class="row justify-content-center">
            <?php if ($status == 1) { ?>
                <div class="col-md-5 justify-content-center">
                    <p>Email Confirmation  have been sent, please check your inbox to activate your account.</p>
                </div>
            <?php }else if($status == 2) { ?>
                <div class="col-md-5 justify-content-center">
                    <p>Your email is <strong class="text-success">ALREADY ACTIVATED</strong></p>
                    <p><a href="<?= Url::to(['site/login']) ?>" target="_blank" >Click here</a> to Login</p>
                </div>    
            <?php }else if($status == 0) { ?>
                <div class="col-md-5 justify-content-center">
                    <p><strong class="text-danger">ERROR FOUND</strong> on email <?=$email?> </p>
                    <p><a href="<?= Url::to(['site/register']) ?>" target="_blank" >Click here</a> to Register</p>
                </div>
            <?php }else if($status == 3) { ?>
                <div class="col-md-5 justify-content-center">
                    <p>email <?=$email?> <strong class="text-danger">NOT FOUND</strong></p>
                    <p><a href="<?= Url::to(['site/register']) ?>" target="_blank" >Click here</a> to Register</p>
                </div>
            <?php } ?>
        </div>



    </div>
</div>