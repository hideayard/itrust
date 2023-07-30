<?php

/* @var $this yii\web\View */

use yii\helpers\Url;
use yii\widgets\ActiveForm;

$baseUrl = Url::base() . '/academic';
$this->title = 'Register | PMRO';
?>

<div class="site-section ftco-subscribe-1 site-blocks-cover pb-4" style="background-image: url('<?= $baseUrl ?>/images/bg_1.jpg')">
    <div class="container">
        <div class="row align-items-end justify-content-center text-center">
            <div class="col-lg-7">
                <h2 class="mb-0">Register</h2>
                <p>Register to Digital PMRO Academy.</p>
            </div>
        </div>
    </div>
</div>


<div class="custom-breadcrumns border-bottom">
    <div class="container">
        <a href="index.html">Home</a>
        <span class="mx-3 icon-keyboard_arrow_right"></span>
        <span class="current">Register</span>
    </div>
</div>

<div class="site-section">
    <div class="container">


        <div class="row justify-content-center">
            <?php if (!$success) : ?>
                <div class="col-md-5">

                    <?php
                    $form = ActiveForm::begin([
                        'id' => 'register-form',
                        'options' => ['class' => 'form-horizontal'],
                    ]) ?>

                    <div class="row">

                        <?= $form->field($model, 'user_name', ['options' => ['class' => 'col-md-12 form-group']])->textInput(['class' => 'form-control form-control-lg']) ?>

                        <?= $form->field($model, 'user_pass', ['options' => ['class' => 'col-md-12 form-group']])->passwordInput(['class' => 'form-control form-control-lg']) ?>

                        <?= $form->field($model, 'user_pass2', ['options' => ['class' => 'col-md-12 form-group']])->passwordInput(['class' => 'form-control form-control-lg']) ?>

                        <?= $form->field($model, 'user_nama', ['options' => ['class' => 'col-md-12 form-group']])->textInput(['class' => 'form-control form-control-lg']) ?>

                        <?= $form->field($model, 'user_hp', ['options' => ['class' => 'col-md-12 form-group']])->textInput(['class' => 'form-control form-control-lg']) ?>

                        <?= $form->field($model, 'user_email', ['options' => ['class' => 'col-md-12 form-group']])->textInput(['class' => 'form-control form-control-lg']) ?>
                    </div>
                    <div class="row">
                        <div class="col-12">
                            <input type="submit" value="Register" class="btn btn-primary btn-lg px-5">
                        </div>
                    </div>

                    <?php ActiveForm::end() ?>

                </div>
            <?php else : ?>
                <div class="col-md-5">
                    <p>You have been registered, please check your inbox to activate your account.
                </div>
            <?php endif; ?>
        </div>



    </div>
</div>