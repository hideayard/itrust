<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\View;

$this->title = "Sections";

$this->registerCssFile(Url::base() . '/css/section.css', []);

$this->registerJsFile(Url::base() . '/js/jquery.sortable.min.js', [
    'depends' => [\yii\web\JqueryAsset::className()],
    'position' => View::POS_END
]);

$this->registerJsFile(Url::base() . '/js/jsrender.min.js', [
    'depends' => [\yii\web\JqueryAsset::className()],
    'position' => View::POS_END
]);

$this->registerJsFile(Url::base() . '/js/section.js', [
    'depends' => [\yii\web\JqueryAsset::className()],
    'position' => View::POS_END
]);
?>

<div class="row">
    <div class="col-lg-6 col-md-6">

        <div style="margin: 15px 0">
            <button type="button" class="btn btn-primary" id="btn-save-all"><span class="fa fa-save"></span> &nbsp; Save All</button>
        </div>

        <input type="hidden" name="course_id" id="course_id" value="<?= $model->course_id ?>">

        <div class="row thumbnail-sortable" style="position: relative">

            <div id="left-loader">
                <img src="<?= Url::base() ?>/images/loader.svg">
            </div>

            <ul class="list-group list-group-sortable" style="width: 98%">
                <?php foreach ($sections as $section) : ?>
                    <li class="list-group-item data-<?= $section['id'] ?>" draggable="true" style="cursor: all-scroll;">
                        <?php foreach ($section as $key => $value) : ?>
                            <input type="hidden" name="<?= $key ?>" class="<?= $key ?>" value="<?= Html::encode($value) ?>">
                        <?php endforeach; ?>

                        <div class="row">
                            <div class="col-lg-9 col-md-9">
                                <h6><?= "<strong class=\"section-title\">$section->section_title</strong> - <span class=\"subsection-title\">" . $section->subsection_title . "</span>" ?></h6>
                            </div>
                            <div class="col-lg-3 col-md-3 text-right">
                                <button class="btn btn-info btn-edit"><span class="fa fa-edit"></span></button>
                                <?php if (Yii::$app->user->identity->user_tipe == 'ADMIN') : ?>
                                    <button class="btn btn-danger btn-delete"><span class="fa fa-times"></span></button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>

    <div class="col-lg-6 col-md-6">

        <div style="margin: 10px 0;">
            <button class="btn btn-primary" onclick="addItem()"><span class="fa fa-plus"></span> Add</button>
        </div>

        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title">Sections</h3>
            </div>

            <div class="card-body">

                <form class="form" id="form-editor">

                    <input type="hidden" name="id" value="">

                    <div class="form-group">
                        <label class="control-label">Section</label>
                        <input type="text" class="form-control" name="section_title" disabled>
                    </div>

                    <div class="form-group">
                        <label class="control-label">Subsection</label>
                        <input type="text" class="form-control" name="subsection_title" disabled>
                    </div>

                    <div class="form-group">
                        <label class="control-label">Type</label>
                        <select class="form-control" name="type" id="input-type" disabled>
                            <option value="html" selected>HTML</option>
                            <option value="youtube">Youtube</option>
                            <option value="quiz">Quiz</option>
                        </select>
                    </div>

                    <div class="form-group" name="video_url" id="form-video-url">
                        <label class="control-label">Video URL</label>
                        <input type="text" class="form-control" name="video_url" disabled id="input-video-url">
                    </div>

                    <div class="form-group" name="video_url" id="form-video-duration">
                        <label class="control-label">Video Duration</label>
                        <input type="text" class="form-control" name="video_duration" disabled id="input-video-duration">
                    </div>

                    <div class="form-group" id="form-resource-url">
                        <label class="control-label">Resource URL</label>
                        <input type="text" class="form-control" name="resource_url" disabled id="input-resource-url">
                    </div>

                    <div class="form-group" id="form-content">
                        <label class="control-label">Content</label>
                        <textarea rows="6" class="form-control" name="content" disabled id="input-content"></textarea>
                    </div>

                    <div class="form-actions">
                        <button type="button" class="btn btn-primary" id="btn-save" disabled><span class="fa fa-save"></span> Apply</button>
                        <button type="button" class="btn btn-warning" id="btn-cancel" disabled><span class="fa fa-times"></span> Cancel</button>
                    </div>

                </form>

            </div>

        </div>
    </div>
</div>

<script id="myTemplate" type="text/x-jsrender">
    <li class="list-group-item data-{{>element_id}}" draggable="true" style="cursor: all-scroll;">
        <input type="hidden" name="id" class="id" value="{{>id}}">
        <input type="hidden" name="course_id" class="course_id" value="{{>course_id}}">
        <input type="hidden" name="section_order" class="section_order" value="{{>section_order}}">
        <input type="hidden" name="subsection_order" class="subsection_order" value="{{>subsection_order}}">
        <input type="hidden" name="section_prev" class="section_prev" value="{{>section_prev}}">
        <input type="hidden" name="section_next" class="section_next" value="{{>section_next}}">
        <input type="hidden" name="section_title" class="section_title" value="{{>section_title}}">
        <input type="hidden" name="subsection_title" class="subsection_title" value="{{>subsection_title}}">
        <input type="hidden" name="type" class="type" value="{{>type}}">
        <input type="hidden" name="content" class="content" value="{{>content}}">
        <input type="hidden" name="video_url" class="video_url" value="{{>video_url}}">
        <input type="hidden" name="video_duration" class="video_duration" value="{{>video_duration}}">
        <input type="hidden" name="is_deleted" class="is_deleted" value="{{>is_deleted}}">
        <input type="hidden" name="created_at" class="created_at" value="{{>created_at}}">
        <input type="hidden" name="created_by" class="created_by" value="{{>created_by}}">
        <input type="hidden" name="modified_at" class="modified_at" value="{{>modified_at}}">
        <input type="hidden" name="modified_by" class="modified_by" value="{{>modified_by}}">
        <input type="hidden" name="resource_url" class="resource_url" value="{{>resource_url}}">
        
        <div class="row">
            <div class="col-lg-9 col-md-9">
                <h6><strong class="section-title">{{>label_section_title}}</strong> - <span class="subsection-title">{{>label_subsection_title}}</span></h6>
            </div>
            <div class="col-lg-3 col-md-3 text-right">
                <button class="btn btn-info btn-edit"><span class="fa fa-edit"></span></button>
                <button class="btn btn-danger btn-delete"><span class="fa fa-times"></span></button>
            </div>
        </div>
    </li>
</script>