<?php

use yii\helpers\Url;

$baseUrl = Url::base() . '/';
// $discussionUrl = Url::to(['site/get-discussions']);
// $moreDiscussionUrl = Url::to(['site/get-more-discussions']);
$submitQuizUrl = Url::to(['site/submit-quiz']);
$courseId = $enroll->enrollCourse->course_id;
$csrfParam = Yii::$app->request->csrfParam;
$csrfToken = Yii::$app->request->csrfToken;

$commentJs = <<<COMMENT_JS

function isEmpty(obj) {
    for(var prop in obj) {
        if(obj.hasOwnProperty(prop))
            return false;
    }
      return true;
  }

$('#submit-quiz').click(function(){
    if($status == 0)
    {
        console.log('triggered');
        $('#quiz-loader').show();
        // Get form
        var form = $('#quizform')[0];

        // Create an FormData object
        var datajawaban = new FormData(form);
        datajawaban.append("$csrfParam",'$csrfToken') ;
        datajawaban.append("course_id",$courseId) ;
        datajawaban.append("id_enroll",$enroll->enroll_id) ;
        datajawaban.append("id_section",$id_section) ;

        $.ajax({
            type: "POST",
            url: '$submitQuizUrl',
            data: datajawaban,
            processData: false,
            contentType: false,
            cache: false,
            timeout: 600000,
            success: function (data) {
                console.log("succes : ",data);
                $('#quiz-loader').hide();
                try {
                    rv = JSON.parse(data);
                    if(isEmpty(rv))
                    {
                            Swal.fire(
                            'Info!',
                            'No Data!',
                            'info'
                            );
                        console.log("NO DATA : ", data);
                    }
                    else if(rv.status)
                    {
                        Swal.fire(
                            'Success!',
                            rv.message,
                            'success'
                            );
                        document.getElementById("divNext").style.display = "none";
                        document.getElementById("divSubmit").style.display = "inline";
                    }
                } catch (e) {
                    //error data not json
                    Swal.fire(
                            'error!',
                            'Error Input Data, '+e,
                            'error'
                            );
                        console.log("ERROR : ", data);
                } 
            },
            error: function (e) {
                console.log("ERROR : ", e);
                $('#quiz-loader').hide();

            }
        });
    }
    else
    {
        Swal.fire(
            'error!',
            'Data Already Submitted <br> Please go to the next session',
            'error'
            );
    }
    
});
COMMENT_JS;

// var_dump($data_exam);
// echo "<hr>";
$this->registerJs($commentJs);
$disabled = " ";
if($status == 1)
{
    $disabled = " disabled ";
}
?>
<div class="video-container">

    <!-- <div class="offset-lg-2 offset-md-2 offset-sm-2 offset-xs-2 col-lg-8 col-md-8 col-sm-8 xol-xs-8"> -->
    <hr>
    <h3>Assessment</h3>
    <hr>
    <form id="quizform" method="post">
    <?php
    foreach($data_exam as $key => &$value)
    {
    ?>
    <input type="hidden" name="soal<?=$key+1?>" value="<?=$data_exam[$key]['id']?>" />
    <hr>
    <p class="MsoNormal1"><span lang="EN-US"><?=$data_exam[$key]['title']?></p>
    <!-- <br> --><hr>
    <div class="form-group">
        <div class="radio">
            <?php if($value["soal_a"]!=null){
                $checked = "";
                if($status == 1 && array_key_exists($key, $data_jawaban) )
                {if($data_jawaban[$key]->jawaban == "A"){  $checked = " checked"; }}
                
                ?>
            <label>
            <input type="radio" name="jawaban<?=$key+1?>" id="soal<?=$key+1?>A" value="A" <?=$disabled . $checked ?>>
                <span class="text"> 
                <?= $value["soal_a"] ?>
                </span>
            </label> <br>
            <?php } ?>

            <?php if($value["soal_b"]!=null){
                 $checked = "";
                 if($status == 1 && array_key_exists($key, $data_jawaban) )
                 {if($data_jawaban[$key]->jawaban == "B"){  $checked = " checked"; } }
                ?>
            <label>
            <input type="radio" name="jawaban<?=$key+1?>" id="soal<?=$key+1?>B" value="B" <?=$disabled . $checked ?>>
                <span class="text"> 
                <?= $value["soal_b"] ?>
                </span>
            </label> <br>
            <?php } ?>

            <?php if($value["soal_c"]!=null){
                 $checked = "";
                 if($status == 1 && array_key_exists($key, $data_jawaban) )
                 {if($data_jawaban[$key]->jawaban == "C"){  $checked = " checked"; } }
                ?>
            <label>
            <input type="radio" name="jawaban<?=$key+1?>" id="soal<?=$key+1?>C" value="C" <?=$disabled . $checked ?>>
                <span class="text"> 
                <?= $value["soal_c"] ?>
                </span>
            </label> <br>
            <?php } ?>

            <?php if($value["soal_d"]!=null){
                 $checked = "";
                 if($status == 1 && array_key_exists($key, $data_jawaban) )
                 {if($data_jawaban[$key]->jawaban == "D"){  $checked = " checked"; } }
                ?>
            <label>
            <input type="radio" name="jawaban<?=$key+1?>" id="soal<?=$key+1?>D" value="D" <?=$disabled . $checked ?>>
                <span class="text"> 
                <?= $value["soal_d"] ?>
                </span>
            </label> <br>
            <?php } ?>

            <?php if($value["soal_e"]!=null){
                 $checked = "";
                 if($status == 1 && array_key_exists($key, $data_jawaban) )
                 {if($data_jawaban[$key]->jawaban == "E"){  $checked = " checked"; } }
                ?>
            <label>
            <input type="radio" name="jawaban<?=$key+1?>" id="soal<?=$key+1?>E" value="E" <?=$disabled . $checked ?>>
                <span class="text"> 
                <?= $value["soal_e"] ?>
                </span>
            </label> <br>
            <?php } ?>

        </div>
    </div>
    <?php   }   ?>
    <input type="hidden" name="id_exam" value="<?=$value["id_exam"]?>" />
    <input type="hidden" name="jumlahsoal" value="<?=count($data_exam)?>" />

    </form>
    <div id="quiz-loader" class="text-center" style="display: none">
        <img src="<?= Url::base() ?>/academic/images/loader.svg" height="100" width="100">
    </div>
    <button type="button" class="btn btn-primary btn-block" id="submit-quiz">Submit Quiz</button>

</div>
<!-- </div> -->

