<?php

use app\helpers\ImageHelper;
use yii\base\View;
use yii\helpers\Url;

$this->title = "Course Details"
?>
<div class="container-fluid">
    <div class="row">
        <div class="col-md-3">

            <!-- Profile Image -->
            <div class="card card-primary card-outline">
                <div class="card-body box-profile">
                    <!-- <div class="text-center">
                        <img class="profile-user-img img-fluid img-circle" src="<?= ImageHelper::viewImage("") ?>" alt="User profile picture">
                    </div> -->

                    <h3 class="profile-username text-center"><?= $model->course_title ?></h3>

                    <p class="text-muted text-center"><?= $model->course_type ?></p>

                    <ul class="list-group list-group-unbordered mb-3">
                        <li class="list-group-item">
                            <b>Price</b> <a class="float-right"><?= $model->course_price ?></a>
                        </li>
                        <li class="list-group-item">
                            <b>Rating</b> <a class="float-right"><?= $model->course_star ?></a>
                        </li>
                        <li class="list-group-item">
                            <b>Online</b> <a class="float-right"><?= $model->course_is_online == 1 ? 'Yes' : 'No' ?></a>
                        </li>
                        <li class="list-group-item">
                            <b>Trainer</b> <a class="float-right"><?= $model->courseTrainer ? $model->courseTrainer->user_nama : 'N/A' ?></a>
                        </li>
                    </ul>
                </div>
                <!-- /.card-body -->
            </div>
            <!-- /.card -->

            <!-- About Me Box -->
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">Description</h3>
                </div>
                <!-- /.card-header -->
                <div class="card-body">

                    <?= $model->course_desc ?>

                </div>
                <!-- /.card-body -->
            </div>
            <!-- /.card -->
        </div>
        <!-- /.col -->
        <div class="col-md-9">
            <div class="card">
                <div class="card-header p-2">
                    <ul class="nav nav-pills">
                        <li class="nav-item"><a class="nav-link active" href="#activity" data-toggle="tab">Content</a></li>
                        <li class="nav-item"><a class="nav-link" href="#sections" data-toggle="tab">Sections</a></li>
                    </ul>
                </div><!-- /.card-header -->
                <div class="card-body">
                    <div class="tab-content">
                        <div class="active tab-pane" id="activity">
                            <?= $model->course_content ?>
                        </div>
                        <div class="tab-pane" id="sections">
                            <ul>
                                <?php foreach ($sections as $key => $section) : ?>
                                    <li><?= $key ?>
                                        <ul>
                                            <?php foreach ($section as $subsection) : ?>
                                                <li><?= $subsection['subsection_title'] ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <!-- /.tab-pane -->
                    </div>
                    <!-- /.tab-content -->
                </div><!-- /.card-body -->
            </div>
            <!-- /.card -->
        </div>
        <!-- /.col -->
    </div>
    <!-- /.row -->
</div>