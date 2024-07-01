<?php

/* @var $this yii\web\View */
use yii\web\View;
use yii\helpers\Url;
use yii\widgets\ActiveForm;
$baseUrl = Url::base() . '/able';
$this->title = 'Login | iTrust';

$this->registerJsFile(Url::base() . '/js/jquery.particleground.js', [
    'depends' => [\yii\web\JqueryAsset::className()],
    'position' => View::POS_END
]);
?>

<div style="margin-top:-100px;" class="site-section ftco-subscribe-1 site-blocks-cover pb-4" style="background-image: url('images/bg_1.jpg')">
    <div class="container">
        <div class="row align-items-end justify-content-center text-center">
            <div class="col-lg-7">
                <h2 class="mb-0">Login</h2>
                <p>Login to get more access and detail about iTrust.</p>
            </div>
        </div>
    </div>
</div>


<div class="site-section" id="particles" >
    <div class="container text-center">

    <div class='card' style="width: 50%;">
            <div class='overlay-content' >
                <div class="row justify-content-center">
                    <div class="col-md-5">
                    <img style="padding-top: 40px;" src="<?= $baseUrl ?>/assets/images/logo_100.png" alt="Image" class="img-fluid">
                        <?php
                        $form = ActiveForm::begin([
                            'id' => 'login-form',
                            'options' => ['class' => 'form-horizontal'],
                        ]) ?>

                        <div class="row">
                            <?= $form->field($model, 'user_name', ['options' => ['class' => 'col-md-12 form-group']])->textInput(['class' => 'form-control form-control-lg']) ?>
                        </div>
                        <div class="row">    
                            <?= $form->field($model, 'user_pass', ['options' => ['class' => 'col-md-12 form-group']])->passwordInput(['class' => 'form-control form-control-lg']) ?>
                        </div>
                        <div class="row">
                            <div class="col-12">
                                <input type="submit" value="Log In" class="btn btn-primary btn-lg px-5">
                            </div>
                        </div>

                        <?php ActiveForm::end() ?>

                    </div>
                </div>
            </div>
            <div class='bg-wrapper'>
                    
            </div>
        </div>
        
    </div>
</div>

<?php
$this->registerCss("

#particles {
    width: 100%;
    min-height: 500px;
    height: 100%;
    overflow: hidden;
    z-index: 1;
}

.card{
    position:absolute;
    top: 260px;
    bottom: 100px;
    left: 50%;
    right: 50%;
    width:450px;
    height:420px;
    overflow:hidden;
    border-radius:5px;
    box-shadow: rgba(0, 0, 0, 0.24) 0px 3px 8px;
    transform:translate(-50%,0);
    z-index: 10;

  }


  
");

$js = <<<ENROLL_JS
document.addEventListener('DOMContentLoaded', function () {
    particleground(document.getElementById('particles'), {
      dotColor: '#5cbdaa',
      lineColor: '#5cbdaa'
    });

  }, false);
ENROLL_JS;

$this->registerJs($js, View::POS_END);


?>