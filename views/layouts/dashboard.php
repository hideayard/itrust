<?php

/* @var $this \yii\web\View */
/* @var $content string */

use yii\helpers\Html;
use yii\helpers\Url;
use app\assets\AppAsset;
use app\helpers\ImageHelper;

//AppAsset::register($this);

$baseUrl = Url::base() . '/adminlte';

$c = Yii::$app->controller->id;
$a = Yii::$app->controller->action->id;
?>

<?php $this->beginPage() ?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="<?= Yii::$app->request->csrfParam; ?>" id="_csrf" content="<?= Yii::$app->request->csrfToken; ?>">
    <meta name="robots" content="noindex, nofollow">

    <link rel="stylesheet" href="<?= $baseUrl ?>/plugins/fontawesome-free-6.1.1-web/css/all.min.css">
    <link rel="stylesheet" href="<?= $baseUrl ?>/plugins/sweetalert2-theme-bootstrap-4/bootstrap-4.min.css">
    <link rel="stylesheet" href="<?= $baseUrl ?>/plugins/daterangepicker/daterangepicker.css">
    <link rel="stylesheet" href="<?= $baseUrl ?>/plugins/tempusdominus-bootstrap-4/css/tempusdominus-bootstrap-4.min.css">
    <link rel="stylesheet" href="https://code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css">
    <link rel="stylesheet" href="<?= $baseUrl ?>/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700">
    <link rel="stylesheet" href="<?= $baseUrl ?>/dist/css/dashboard.css">
    <link rel="stylesheet" href="<?= $baseUrl ?>/plugins/sweetalert2/sweetalert2.min.css">

    <script src="<?= $baseUrl ?>/dist/js/ml.js"></script>

    <title><?= Html::encode($this->title) ?> | iTrust Trading Platform</title>
    <link rel="icon" type="image/x-icon" href="<?= $baseUrl ?>/dist/img/itrust_50.png">

    <?php $this->head() ?>
</head>

<body class="hold-transition sidebar-mini">
    <?php $this->beginBody() ?>
    <!-- Site wrapper -->
    <div class="wrapper">
        <!-- Navbar -->
        <nav class="main-header navbar navbar-expand navbar-teal navbar-light text-white">
            <!-- Left navbar links -->
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a style="color: #FFFFFF" class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
                </li>
                <li class="nav-item d-none d-sm-inline-block">
                    <a style="color: #FFFFFF" href="/" class="nav-link">Home</a>
                </li>
                <li class="nav-item d-none d-sm-inline-block">
                    <a style="color: #FFFFFF" href="<?= Url::to(['site/contact']) ?>" class="nav-link">Contact</a>
                </li>
            </ul>

            <!-- SEARCH FORM -->
            <form class="form-inline ml-3">
                <div class="input-group input-group-sm">
                    <input class="form-control form-control-navbar" type="search" placeholder="Search" aria-label="Search">
                    <div class="input-group-append">
                        <button class="btn btn-navbar" type="submit">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
            </form>

        </nav>
        <!-- /.navbar -->

        <!-- Main Sidebar Container -->
        <aside class="main-sidebar sidebar-dark-primary elevation-4">
            <!-- Brand Logo -->
            <a href="<?= Url::to(['index']) ?>" class="brand-link">
                <img src="<?= $baseUrl ?>/dist/img/itrust_50.png" alt="PMRO Logo" class="brand-image img-circle elevation-3" style="opacity: .8">
                <span class="brand-text font-weight-light">iTrust</span>
            </a>

            <!-- Sidebar -->
            <div class="sidebar">
                <!-- Sidebar user (optional) -->
                <div class="user-panel mt-3 pb-3 mb-3 d-flex">
                    <div class="image">
                        <img src="<?= ImageHelper::viewImage(Yii::$app->user->identity->user_foto) ?>" class="img-circle elevation-2" alt="User Image">
                    </div>
                    <div class="info">
                        <a href="<?= Url::to(['users/view', 'id' => Yii::$app->user->identity->user_id]) ?>" class="d-block"><?= Yii::$app->user->identity->user_nama ?></a>
                        <a href="<?= Url::to(['dashboard/update-profile']) ?>" class="btn btn-primary btn-sm" style="margin-top: 5px; font-size: 10px">Edit Profile</a>
                    </div>

                </div>

                <!-- Sidebar Menu -->
                <nav class="mt-2">
                    <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">

                        <li class="nav-header">MAIN MENU</li>
                        <li class="nav-item">
                            <a href="<?= Url::to(['dashboard/index']) ?>" class="nav-link <?= ($c == "dashboard") ? "active" : "" ?>">
                                <i class="nav-icon far fa-hdd"></i>
                                <p>Dashboard</p>
                            </a>
                        </li>

                        <!-- <li class="nav-item">
                                <a href="<?= Url::to(['log/index']) ?>" class="nav-link <?= ($c == "log") ? "active" : "" ?>">
                                    <i class="nav-icon fa fa-chart-line"></i>
                                    <p>Data Logs</p>
                                </a>
                        </li> 
                        <li class="nav-item">
                            <a href="<?= Url::to(['node/index']) ?>" class="nav-link <?= ($c == "node") ? "active" : "" ?>">
                                <i class="nav-icon far fa-hdd"></i>
                                <p>Device</p>
                            </a>
                        </li>  -->

                        <?php if (Yii::$app->user->identity->user_tipe == "ADMIN" || Yii::$app->user->identity->user_tipe == 'SUPERADMIN') : ?>

                            <li class="nav-item">
                                <a href="<?= Url::to(['users/index']) ?>" class="nav-link <?= ($c == "users") ? "active" : "" ?>">
                                    <i class="nav-icon far fa-user"></i>
                                    <p>Users</p>
                                </a>
                            </li>
                        <?php endif; ?>

                        <?php if (Yii::$app->user->identity->user_tipe == "ADMIN" || Yii::$app->user->identity->user_tipe == "SUPERADMIN") : ?>

                            <li class="nav-item">
                                <a href="<?= Url::to(['banner/index']) ?>" class="nav-link <?= ($c == "banner") ? "active" : "" ?>">
                                    <i class="nav-icon fas fa-newspaper"></i>
                                    <p>Banners</p>
                                </a>
                            </li>

                            
                            <li class="nav-item">
                                <a href="<?= Url::to(['notif/index']) ?>" class="nav-link <?= ($c == "notif") ? "active" : "" ?>">
                                    <i class="nav-icon far fa-bell"></i>
                                    <p>Notifications</p>
                                </a>
                            </li>
                            <!-- <li class="nav-item">
                                <a href="<?= Url::to(['subscriber/index']) ?>" class="nav-link <?= ($c == "subscriber") ? "active" : "" ?>">
                                    <i class="nav-icon far fa-star"></i>
                                    <p>Subscribers</p>
                                </a>
                            </li> -->
                            <!-- <li class="nav-item">
                                <a href="<?= Url::to(['video/index']) ?>" class="nav-link <?= ($c == "video") ? "active" : "" ?>">
                                    <i class="nav-icon far fa-play-circle"></i>
                                    <p>Videos</p>
                                </a>
                            </li> -->

                        <?php endif; ?>

                        <li class="nav-item">
                            <a href="#" id="logout-button" class="nav-link">
                                <i class="nav-icon far fa-times-circle"></i>
                                <p>Logout</p>
                            </a>
                            <form method="post" name="logout-form" action="<?= Url::to(['site/logout']) ?>">
                                <input type="hidden" name="<?= Yii::$app->request->csrfParam; ?>" value="<?= Yii::$app->request->csrfToken; ?>" />
                            </form>
                        </li>
                    </ul>
                </nav>
                <!-- /.sidebar-menu -->
            </div>
            <!-- /.sidebar -->
        </aside>

        <!-- Content Wrapper. Contains page content -->
        <div class="content-wrapper">
            <!-- Content Header (Page header) -->
            <section class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-6">
                            <h1><?= Html::encode($this->title) ?></h1>
                        </div>
                        <div class="col-sm-6">
                            <ol class="breadcrumb float-sm-right">
                                <li class="breadcrumb-item"><a href="/">Home</a></li>
                                <li class="breadcrumb-item active"><?= Html::encode($this->title) ?></li>
                            </ol>
                        </div>
                    </div>
                </div><!-- /.container-fluid -->
            </section>

            <!-- Main content -->
            <section class="content" style="padding: 20px">

                <?= $content ?>

            </section>
            <!-- /.content -->
        </div>
        <!-- /.content-wrapper -->

        <footer class="main-footer">
            <div class="float-right d-none d-sm-block">
                <b>Version</b> 3.0.5
            </div>
            <strong>Copyright &copy; 2014-2019 <a href="http://adminlte.io">AdminLTE.io</a>.</strong> All rights
            reserved.
        </footer>

    </div>
    <!-- ./wrapper -->

    <script src="<?= $baseUrl ?>/plugins/jquery/jquery.min.js"></script>
    <!-- <script src="<?= $baseUrl ?>/js/plugins.js"></script> -->

    <script src="<?= $baseUrl ?>/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="<?= $baseUrl ?>/plugins/moment/moment.min.js"></script>
    <script src="<?= $baseUrl ?>/plugins/inputmask/jquery.inputmask.min.js"></script>
    <script src="<?= $baseUrl ?>/plugins/daterangepicker/daterangepicker.js"></script>
    <script src="<?= $baseUrl ?>/plugins/tempusdominus-bootstrap-4/js/tempusdominus-bootstrap-4.min.js"></script>\
    <script src="<?= $baseUrl ?>/plugins/sweetalert2/sweetalert2.min.js"></script>
    <script src="<?= $baseUrl ?>/dist/js/adminlte.min.js"></script>

    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

    <script>
        function createElementFromHTML(htmlString) {
            var div = document.createElement('div');
            div.innerHTML = htmlString.trim();

            // Change this to div.childNodes to support multiple top-level nodes
            return div.firstChild;
        }

        $(document).ready(function() {
            $('#logout-button').click(function(e) {
                e.preventDefault();
                if (confirm('Are you sure?')) {
                    document.forms['logout-form'].submit();
                }
            });

            $('body').on('click', '.delete-button', function(e) {
                e.preventDefault();

                if (confirm('Are you sure?')) {
                    $(this).closest('.delete-form').find('form').submit();
                }
            });

        });
    </script>

    <?php $this->endBody() ?>
</body>

</html>
<?php $this->endPage() ?>