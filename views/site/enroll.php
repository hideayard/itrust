<?php

use yii\helpers\Url;
use yii\web\View;

use aryelds\sweetalert\SweetAlert;

$this->registerJsFile('https://www.youtube.com/player_api');
$this->registerCssFile(Url::base() . '/css/enroll.css');
$this->registerjsFile(Url::base() . '/js/enroll.js');
// sweetalert
$this->registerCssFile('https://cdn.jsdelivr.net/npm/sweetalert2@10/dist/sweetalert2.min.css');
$this->registerjsFile('https://cdn.jsdelivr.net/npm/sweetalert2@10/dist/sweetalert2.min.js');


Yii::$app->session->setFlash('success', 'This is the message');

$js = <<<ENROLL_JS

var video_duration = 0;
var watch_counter = 0;
var skip_counter = 0;
var complete_status = 0;
var alert_status=0;
var didInit = false;
var complete_text = 'You have COMPLETED this sub section!';

if("$data_section->type" == "html")
{
    document.getElementById("divNext").style.display = "none";
    document.getElementById("divSubmit").style.display = "inline";
}


if(("$data_section->type" != "youtube") && ($enroll_progress->ep_status == 0) && ($enroll->enroll_status == 0) )
{
    //video_duration = $data_section->video_duration;
    //start();
}
    function initMunchkin() {
      if(didInit === false) {
        didInit = true;
        Munchkin.init('410-XOR-673', {domainLevel : 2});
      }
    }
    var s = document.createElement('script');
    s.type = 'text/javascript';
    s.async = true;
    s.src = '//munchkin.marketo.net/munchkin-beta.js';
    s.onreadystatechange = function() {
      if (this.readyState == 'complete' || this.readyState == 'loaded') {
        initMunchkin();
      }
    };
    s.onload = initMunchkin;
    document.getElementsByTagName('head')[0].appendChild(s);
  
    var interestingTimeSS = 10, // elapsed time in seconds that you find interesting
      interestingPercentPosition = 33, // elapsed percent that you find interesting
      synthUrlBase = "/munchkinVideoTracker/video/"; // base path of synthetic Visit Web Page hits (video ID is appended)
  
    // extended player states beyond native constants
    var YTMunchkin = {
        PlayerState: {
          PASSED_INTERESTING_MOMENT: 10
        }
      },
      friendlyStates = {};
    
    var players = [],
      arrayify = getSelection.call.bind([].slice);
  
    window.onYouTubeIframeAPIReady = function() {
      
      // friendly state values to log to Marketo in query string
      friendlyStates[YT.PlayerState.PLAYING] = "play";
      friendlyStates[YT.PlayerState.PAUSED] = "pause";
      friendlyStates[YT.PlayerState.ENDED] = "end";
      friendlyStates[YTMunchkin.PlayerState.PASSED_INTERESTING_MOMENT] = "skip";
      
      /* NO NEED TO TOUCH BELOW THIS LINE! */
  
      arrayify(document.querySelectorAll(".youtube-wrapper"))
        .forEach(function(wrapper) {
          players.push({
            player: new YT.Player(wrapper, {
              width: wrapper.getAttribute("data-video-width"),
              height: wrapper.getAttribute("data-video-height"),
              videoId: wrapper.getAttribute("data-video-id"),
              events: {
                onStateChange: onPlayerStateChange
              }
            })
          });
      });
    };
  
    function getTimeFormat(totalSeconds)
    {
        let hours = Math.floor(totalSeconds / 3600);
        totalSeconds %= 3600;
        let minutes = Math.floor(totalSeconds / 60);
        let seconds = totalSeconds % 60;
        let hasil = "";
        if(hours>0){ hasil += hours+ " Hour ";}
        if(minutes>0){ hasil += minutes+ " Min ";}
        if(seconds>0){ hasil += seconds+ " Sec ";}
        return hasil;
    }

    // The API calls this function when the player's state changes.
    function onPlayerStateChange(event) {
      var player = event.target,
        states = [event.data],
        videoId = player.getVideoData().video_id,
        synthUrl = synthUrlBase + videoId,
        timePositionSS = player.getCurrentTime(),
        durationSS = player.getDuration(),
        percentPosition = (timePositionSS / durationSS).toFixed(2) * 100;
        video_duration = Math.floor(player.getDuration()-1);
        ///testing
        //video_duration = 5;
        //90% of video duration must be watched
        //console.log('durasi?=',player.getDuration(),'timeformat', getTimeFormat(player.getDuration()));

        if (
        (timePositionSS >= interestingTimeSS ||
          percentPosition >= interestingPercentPosition) &&
         !player.loggedInterestingMoment
      ) {
        states.push(YTMunchkin.PlayerState.PASSED_INTERESTING_MOMENT);
      }
  
      states.forEach(function(state) {
        switch (state) {
          case YT.PlayerState.PLAYING:{console.log("action: " + friendlyStates[state]);start();}break;
          case YT.PlayerState.PAUSED:{console.log("action: " + friendlyStates[state]);stop();}break;
          case YT.PlayerState.ENDED:{
                                        stop();
                                        if(watch_counter<(video_duration) )
                                        {
                                            console.log("=== NOT COMPLETED===");
                                            reset();
                                            Swal.fire({
                                                icon: 'error',
                                                title: 'Not Complete',
                                                text: 'Look like you have skipped some video, please re-watch the video or refresh the page.',
                                              });
                                              
                                        }
                                        else if(watch_counter>=(video_duration) && alert_status == 0 )
                                        {
                                            if($next==0)
                                            {
                                                complete_text = 'You have COMPLETED the COURSE!';
                                            }
                                            complete_status = 1;
                                            console.log("===COMPLETED===");
                                            // document.getElementById("btnNext").href =  document.getElementById("urlNext").value;
                                            document.getElementById("divNext").style.display = "none";
                                            document.getElementById("divSubmit").style.display = "inline";
                                            Swal.fire(
                                                'Good job!',
                                                complete_text,
                                                'success'
                                            );
                                            alert_status = 1;
                                        }
                                    }break;
          case YTMunchkin.PlayerState.PASSED_INTERESTING_MOMENT:
            skip_counter++;
            console.log("action: " + friendlyStates[state]);
            Munchkin.munchkinFunction("visitWebPage", {
              url: synthUrl,
              params:
                "movie-action=" +
                friendlyStates[state] +
                "&" +
                "movie-percent-position=" +
                percentPosition
            });
            break;
        }
  
        if (state == YTMunchkin.PlayerState.PASSED_INTERESTING_MOMENT) {
          player.loggedInterestingMoment = true;
        }
      });
    }
  
    ///================= timer function ===================
    var x;
    var startstop = 0;
    
    function startStop() { /* Toggle StartStop */
    
      startstop = startstop + 1;
    
      if (startstop === 1) {
        start();
        // document.getElementById("start").innerHTML = "Stop";
      } else if (startstop === 2) {
        // document.getElementById("start").innerHTML = "Start";
        startstop = 0;
        stop();
      }
    
    }
    
    
    function start() {
      x = setInterval(timer, 10);
    } /* Start */
    
    function stop() {
      clearInterval(x);
    } /* Stop */
    
    var milisec = 0;
    var sec = 0; /* holds incrementing value */
    var min = 0;
    var hour = 0;
    
    /* Contains and outputs returned value of  function checkTime */
    
    var miliSecOut = 0;
    var secOut = 0;
    var minOut = 0;
    var hourOut = 0;
    var seconds = 0; 
    
    /* Output variable End */
    
    
    function timer() {
      /* Main Timer */
    
      miliSecOut = checkTime(milisec);
      secOut = checkTime(sec);
      minOut = checkTime(min);
      hourOut = checkTime(hour);
      seconds = checkTime(milisec);
    
      milisec = ++milisec;
    
      if (milisec === 100) {
        milisec = 0;
        sec = ++sec;
        watch_counter = ++watch_counter; //Math.floor( (hourOut * 3600)+(minOut*60)+ secOut ); 
        console.log("count=",watch_counter," >= ",video_duration ," ? ");
        
        if("$data_section->type" != "youtube")
        {
            if($next==0)
            {
                complete_text = 'You have COMPLETED the COURSE!';
            }
            if(watch_counter>=(video_duration) && alert_status == 0 )
            {
                complete_status = 1;
                console.log("===COMPLETED===");
                document.getElementById("divNext").style.display = "none";
                document.getElementById("divSubmit").style.display = "inline";
                Swal.fire(
                    'Good job!',
                    complete_text,
                    'success'
                );
                alert_status = 1;
                stop();
            }
        }
      }
    
      if (sec == 60) {
        min = ++min;
        sec = 0;
      }
    
      if (min == 60) {
        min = 0;
        hour = ++hour;
    
      }
    
    }
    
    
    /* Adds 0 when value is <10 */
    
    
    function checkTime(i) {
      if (i < 10) {
        i = "0" + i;
      }
      return i;
    }
    
    function reset() {
    
        video_duration = 0;
        watch_counter = 0;
        skip_counter = 0;
        complete_status = 0;
        alert_status=0;
      
        console.log("reset variable");
        /*Reset*/
        
        milisec = 0;
        sec = 0;
        min = 0
        hour = 0;
    
    }

    function alert_not_complete()
    {
        Swal.fire('Please Complete this section first', '', 'info')
    }
    
   
    if("$enroll_progress->ep_status" == 1) 
    {
        document.getElementById("divNext").style.display = "none";
        document.getElementById("divSubmit").style.display = "inline";
    }
    
ENROLL_JS;

$this->registerJs($js, View::POS_END);

$this->title = $enroll->enrollCourse->course_title . ' | iTrust';

//php function nanti akan dipakai saat insert new data
function getDuration($videoID){
    $apikey = "AIzaSyCi7J_JZ5EfzFciXD1mzLYC7DG60-hETFk"; // Like this AIcvSyBsLA8znZn-i-aPLWFrsPOlWMkEyVaXAcv
    $dur = file_get_contents("https://www.googleapis.com/youtube/v3/videos?part=contentDetails&id=$videoID&key=$apikey");
    $VidDuration =json_decode($dur, true);
    foreach ($VidDuration['items'] as $vidTime)
    {
        $VidDuration= $vidTime['contentDetails']['duration'];
    }
    preg_match_all('/(\d+)/',$VidDuration,$parts);
    $time = implode(":",$parts[0]);
        $timeArr = array_reverse(explode(":", $time));
        $timeInSeconds = 0;
        foreach ($timeArr as $key => $value)
        {
            if ($key > 2) break;
            $timeInSeconds += pow(60, $key) * $value;
        }
    // return implode(":",$parts[0]);
    return $timeInSeconds;
 }

 function secondsToTime($seconds_time)
{
    if ($seconds_time < 24 * 60 * 60) {
        return gmdate('H:i:s', $seconds_time);
    } else {
        $hours = floor($seconds_time / 3600);
        $minutes = floor(($seconds_time - $hours * 3600) / 60);
        $seconds = floor($seconds_time - ($hours * 3600) - ($minutes * 60));
        return "$hours Hour $minutes Min $seconds Sec";
    }
}

function secondsToHumanReadable(int $seconds, int $requiredParts = null)
{
    $from     = new \DateTime('@0');
    $to       = new \DateTime("@$seconds");
    $interval = $from->diff($to);
    $str      = '';

    $parts = [
        'y' => 'year',
        'm' => 'month',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'minute',
        's' => 'second',
    ];

    $includedParts = 0;

    foreach ($parts as $key => $text) {
        if ($requiredParts && $includedParts >= $requiredParts) {
            break;
        }

        $currentPart = $interval->{$key};

        if (empty($currentPart)) {
            continue;
        }

        if (!empty($str)) {
            $str .= ', ';
        }

        $str .= sprintf('%d %s', $currentPart, $text);

        if ($currentPart > 1) {
            // handle plural
            $str .= 's';
        }

        $includedParts++;
    }

    return $str;
}

?>
<?php
if ($next != 0) {
?>
    <input type="hidden" id="urlNext" value="<?= Url::to(['site/enroll', 'id' => $enroll->enroll_courseid, 'id_section' => $next]) ?>"/>
<?php
}
?>
<div class="site-section" style="margin-top: 30px;">

    <div class="row" style="position: relative; margin: 0;">
        <button id="course-nav-toggler2" class="btn d-none" type="button">
            <span class="fa fa-arrow-left"></span>&nbsp;&nbsp;&nbsp;Course content
        </button>

        <div class="col-lg-9 col-md-9 col-sm-9 col-xs-9" style="height:100%" id="course-content">

            <?= '<h3><strong class="text-primary">' . $data_section->section_title . " - " . $data_section->subsection_title . "</strong></h3>" ?>
            
                <?php
                if ($data_section->type == "html") {
                    echo '<div id="video-container">'.$data_section->content.'</div>';
                } else if ($data_section->type == "youtube") {
                    $dvi = explode('/', $data_section->video_url);
                    echo '<div id="video-container"><div id="player" class="youtube-wrapper" data-video-id="' . end($dvi) . '" data-video-width="640" data-video-height="340"></div></div>';
                }
                else if ($data_section->type == "quiz") {
                    // echo $data_section->content;
                    // var_dump($data_exam);
                    // print_r($data_exam);
?>
                <?= $this->render('quiz', [
                    'enroll' => $enroll,
                    'data_exam' => $data_exam,
                    'data_jawaban' => $data_jawaban,
                    'id_section' => $data_section->id,
                    'status' => $enroll_progress->ep_status
                    ]) ?>
                
<?php
                    
            }
             ?>  
            <br>
            <form method="post" name="next-form" action="<?= Url::to(['site/submit-section']) ?>">
                <input type="hidden" name="<?= Yii::$app->request->csrfParam; ?>" value="<?= Yii::$app->request->csrfToken; ?>" />
                <input type="hidden" name="id" value="<?= $enroll->enroll_id ?>" />
                <input type="hidden" name="id_section" value="<?= $data_section->id ?>" />
            <table border="0" class="col-lg-12">
            <tbody>
            <tr>
            <td>
            <?php
            if ($prev != 0) {
            ?>
            
            <div id="divPrev" style="display: inline;">
                <a id="btnPrev" class="btn btn-secondary rounded-0 btn-lg px-5" href="<?= Url::to(['site/enroll', 'id' => $enroll->enroll_id, 'id_section' => $prev]) ?>">Prev</a>
            </div>
            <?php
            }
            ?>

           
            <div id="divSubmit" style="display: none;">
           
                <input type="submit" value="Next Section" class="btn btn-primary rounded-0 btn-lg px-5">
             
            </div>
            <div id="divNext" style="display: inline;">
            <?php if($enroll_progress->ep_status == 1 || $enroll->enroll_status == 1) 
                    {  echo ' <a class="btn btn-primary rounded-0 btn-lg px-5" href="'.$data_section->section_next.'">Next</a>';  }
                    else{
            ?>
                <a id="btnNext" onclick="alert_not_complete();" class="btn btn-primary rounded-0 btn-lg px-5" href="#">Next</a>            
            <?php
            }
            ?>
            </div>
            </td></tr>
            </tbody></table>
            </form>
            <hr>

            <div id="tab-container">
                <div class="tab-navigation">

                    <div class="tab" data-target="tab1"><span class="fa fa-search"></span></div>

                    <div class="tab active" data-target="tab2">Overview</div>

                    <div class="tab" data-target="tab3" id="tab-discussion">Q&A</div>

                    <div class="tab" data-target="tab4">Notes</div>

                    <div class="tab" data-target="tab5">Announcements</div>

                    <div style="clear:both"></div>
                </div>

                <div class="tab-contents">

                    <div id="tab1" class="tab-content">
                        <h1>Tab search - coming soon.</h1>
                    </div>
                    <div id="tab2" class="tab-content active">
                        <h1><?= $enroll->enrollCourse->course_desc ?></h1>
                    </div>
                    <div id="tab3" class="tab-content">
                        <?= $this->render('_qna', [
                            'enroll' => $enroll,
                        ]) ?>
                    </div>
                    <div id="tab4" class="tab-content">
                        <h1>Tab Notes - coming soon.</h1>
                    </div>
                    <div id="tab5" class="tab-content">
                        <h1>Tab Announcement - coming soon.</h1>
                    </div>

                </div>
            </div>

        </div>
        <div class="col-lg-3 col-md-3 col-sm-3 col-xs-12" id="course-nav">

            <div id="course-index">
                <div class="row title">
                    <div class="col-lg-10 col-md-10 col-xs-10 col-sm-10">
                        Course content
                    </div>
                    <div class="col-lg-2 col-md-2 col-sm-2 col-xs-2 text-center">
                        <a href="#" id="course-nav-toggler"><span class="fa fa-times"></span></a>
                    </div>
                </div>
                <div class="course-list">
                    <?php $sectionIndex = 1;
                    foreach ($sections as $key => $val) : ?>
                        <?php
                        if ($key == $data_section->section_title) {
                            echo '<div class="course-section open">';
                        } else {
                            echo '<div class="course-section">';
                        }
                        ?>
                        <div class="course-section-inner">
                            <div class="section-title"><?= $key ?>:</div>
                            <!-- <div>7 / 13 | 35min</div> -->
                            <div class="section-toggler">
                                <?php if ($key == $data_section->section_title): ?>
                                <span class="fa fa-chevron-down"></span>
                                <?php else: ?>
                                <span class="fa fa-chevron-up"></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="course-pages">
                            <?php $subSectionIndex = 1;
                            foreach ($val as $key2 => $val2) : ?>

                                <div class="course-page">

                                    <div class="checkbox">
                                        <label>
                                            <?php
                                            $output = ' <input type="checkbox" disabled> ';
                                            $course_link = "#";
                                            $i=0;
                                            for ($i;$i<count($all_enroll_progress);$i++)
                                            {                                           
                                                if( ($all_enroll_progress[$i]['ep_section_id'] == $val2->id) && ($all_enroll_progress[$i]['ep_status'] == 1) ) {
                                                    $output = ' <input type="checkbox" disabled checked> ';
                                                    $course_link = Url::to(['site/enroll', 'id' => $enroll->enroll_id, 'id_section' => $val2->id]);
                                                } 
                                                else if( ($all_enroll_progress[$i]['ep_section_id'] == $val2->id) && ($all_enroll_progress[$i]['ep_status'] == 0) ) {
                                                    $output = ' <input type="checkbox" disabled> ';
                                                    $course_link = Url::to(['site/enroll', 'id' => $enroll->enroll_id, 'id_section' => $val2->id]);
                                                } 
                                            }   
                                            echo $output;
                                            ?>

                                        </label>
                                    </div>
                                    <div class="course-page-content">
                                        <div class="course-page-title">
                                            <a href="<?= $course_link ?>">
                                                <?php
                                                if ($data_section->id == $val2->id) {
                                                    echo '<h4><strong class="text-warning">' . $subSectionIndex . '. ' . $val2->subsection_title . "</strong></h4>";
                                                } else {
                                                    echo $subSectionIndex . '. ' . $val2->subsection_title;
                                                }
                                                ?>
                                            </a>
                                        </div>
                                        <div class="course-page-duration">
                                             <?php
                                             if($val2->type == "youtube")
                                             {
                                                echo '<span class="fa fa-video"> - </span>'; 
                                                echo secondsToHumanReadable($val2->video_duration); 
                                            }
                                            else
                                            {
                                                if($val2->resource_url != null && $val2->video_duration != '')
                                                {
                                                echo '<a href="'.$val2->resource_url.'" target="_blank" ><span class="fa fa-file"> -  resource file</span></a>'; 
                                                }
                                                else if($val2->type == 'quiz')
                                                {
                                                    echo '<span class="fa fa-question"> - quiz</span>'; 
                                                }
                                                else
                                                {
                                                    echo '<span class="fa fa-file"> - ppt/pdf</span>'; 
                                                }
                                            }

                                            ?>
                                        </div>
                                    </div>

                                </div>
                            <?php $subSectionIndex++;
                            endforeach; ?>

                        </div>
                </div>
            <?php $sectionIndex++;
                    endforeach; ?>
            </div>
        </div>


    </div>
</div>



</div>