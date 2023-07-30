<?php

use yii\helpers\Url;

$baseUrl = Url::base() . '/';
$discussionUrl = Url::to(['site/get-discussions']);
$moreDiscussionUrl = Url::to(['site/get-more-discussions']);
$createQuestionUrl = Url::to(['site/create-question']);
$courseId = $enroll->enrollCourse->course_id;
$csrfParam = Yii::$app->request->csrfParam;
$csrfToken = Yii::$app->request->csrfToken;

$commentJs = <<<COMMENT_JS
window.commentsLoaded = 0;

window.getDiscussions = function(){

    $('#comment-items').html('');
    $('#comment-loader').show();

    $.post('$discussionUrl', {
        $csrfParam: '$csrfToken',

    }, function(data){
        if (typeof(data) != 'object'){
            data = $.parseJSON(data);
        }

        window.commentsLoaded = data.length;

        for (let i=0; i<data.length; i++){

            const d = new Date(data[i].date);
            const ye = new Intl.DateTimeFormat('id', { year: 'numeric' }).format(d);
            const mo = new Intl.DateTimeFormat('id', { month: 'short' }).format(d);
            const da = new Intl.DateTimeFormat('id', { day: '2-digit' }).format(d);
            const ho = new Intl.DateTimeFormat('id', { hour: '2-digit' }).format(d);
            const mi = new Intl.DateTimeFormat('id', { minute: '2-digit' }).format(d);
            const finalDate = `\${da}-\${mo}-\${ye} \${ho}:\${mi}`;

            let html = `<div class="comment-item row">
                <div class="col-lg-2 col-md-2">
                    <img src="$baseUrl\${data[i].user_foto}" title="\${data[i].name}" class="img-fluid comment-photo">
                </div>
                <div class="col-lg-10 col-md-10">
                    <div class="comment-title">\${data[i].title}</div>
                    <div class="comment-message">\${data[i].message}</div>
                    <div class="comment-date">\${finalDate}</div>
                </div>
            </div>`;

            $('#comment-items').append(html);
        }
        $('#comment-loader').hide();
    });
};

$('#tab-discussion').click(getDiscussions);

$('#see-more').click(function(e){

    $('#comment-loader').show();

    $.post('$moreDiscussionUrl', {
        $csrfParam: '$csrfToken',
        offset: commentsLoaded

    }, function(data){
        if (typeof(data) != 'object'){
            data = $.parseJSON(data);
        }

        for (let i=0; i<data.length; i++){

            const d = new Date(data[i].date);
            const ye = new Intl.DateTimeFormat('id', { year: 'numeric' }).format(d);
            const mo = new Intl.DateTimeFormat('id', { month: 'short' }).format(d);
            const da = new Intl.DateTimeFormat('id', { day: '2-digit' }).format(d);
            const ho = new Intl.DateTimeFormat('id', { hour: '2-digit' }).format(d);
            const mi = new Intl.DateTimeFormat('id', { minute: '2-digit' }).format(d);
            const finalDate = `\${da}-\${mo}-\${ye} \${ho}:\${mi}`;

            let html = `<div class="comment-item row">
                <div class="col-lg-2 col-md-2">
                    <img src="$baseUrl\${data[i].user_foto}" title="\${data[i].name}" class="img-fluid comment-photo">
                </div>
                <div class="col-lg-10 col-md-10">
                    <div class="comment-title">\${data[i].title}</div>
                    <div class="comment-message">\${data[i].message}</div>
                    <div class="comment-date">\${finalDate}</div>
                </div>
            </div>`;

            $('#comment-items').append(html);
        }
        $('#comment-loader').hide();
    });
});

$('#question-modal').on('shown.bs.modal', function (e) {
    $('#myInput').trigger('focus');
});

$('#post-question').click(function(){
    console.log('triggered');
    $('#question-title, #question-message').prop('disabled', true).addClass('disabled');

    $.post('$createQuestionUrl', {
        $csrfParam: '$csrfToken',
        course_id : $courseId,
        title: $('#question-title').val(),
        message: $('#question-message').val()
    }, function(data){

        if (data.status){
            getDiscussions();
        } else {
            alert(data.message);
        }

        $('#question-title, #question-message').prop('disabled', false).removeClass('disabled');
        $('#close-button').click();
    });
});
COMMENT_JS;

$this->registerJs($commentJs);
?>
<div class="row">

    <div class="offset-lg-2 offset-md-2 offset-sm-2 offset-xs-2 col-lg-8 col-md-8 col-sm-8 xol-xs-8">

        <div class="input-group mb-3" id="course-search">
            <input type="text" class="form-control" placeholder="Search all course cuestions" aria-label="Search all course cuestions" aria-describedby="basic-addon2">
            <div class="input-group-append">
                <span class="input-group-text" id="basic-addon2"><span class="fa fa-search"></span></span>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-6 col-md-6">
                <select class="form-control" id="comment-lecture">
                    <option value="all">All lectures</option>
                    <option value="current">Current lecture</option>
                </select>
            </div>
            <div class="col-lg-6 col-md-6">
                <select class="form-control" id="comment-sort">
                    <option value="asc">Sort ascending</option>
                    <option value="desc">Sort descending</option>
                </select>
            </div>
        </div>

        <div id="discussion-metadata" class="row">
            <div class="col-lg-6 col-md-6">
                1 comment on this course
            </div>
            <div class="col-lg-6 col-md-6 text-right">
                <button type="button" id="new-question" class="btn btn-primary" data-toggle="modal" data-target="#question-modal">Ask a new question</a>
            </div>
        </div>

        <div id="comment-loader" class="text-center" style="display: none">
            <img src="<?= Url::base() ?>/academic/images/loader.svg" height="100" width="100">
        </div>

        <div class="comment-items" id="comment-items">

            <!-- <div class="comment-item row">
                <div class="col-lg-2 col-md-2">
                    <img src="http://localhost:8080/academic/images/logo_tvet_h150.png" class="img-fluid comment-photo">
                </div>
                <div class="col-lg-10 col-md-10">
                    <div class="comment-title">Awesome</div>
                    <div class="comment-message">Nice course, keep on track</div>
                    <div class="comment-date">5 hours ago</div>
                </div>
            </div> -->

        </div>

        <button class="btn btn-light btn-block" id="see-more">See More</button>

    </div>

</div>


<div id="question-modal" class="modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">New Question</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <input type="text" id="question-title" class="form-control" placeholder="Title...">
                </div>
                <div class="form-group">
                    <textarea id="question-message" class="form-control" rows="10" placeholder="Message..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" id="post-question">Post</button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal" id="close-button">Close</button>
            </div>
        </div>
    </div>
</div>