<?php

use yii\helpers\Url;
use yii\helpers\Html;
use yii\widgets\DetailView;
use app\components\VideoJsWidget;
use yii\bootstrap\Modal;
use yii\widgets\ListView;
use yii\widgets\Pjax;
use yii\data\ArrayDataProvider;

/* @var $this yii\web\View */
/* @var $model app\models\Ticket */

if ($model->screencapture !== null) {
    $js = <<< SCRIPT
var player = videojs('video-container');
$(".js-playlist__item-button").on('click', function () {
    player.src({type: 'application/x-mpegURL', src: $(this).data("src")});
});

player.on('loadstart', function() {
    $("a.js-playlist__item-button span").addClass('hidden');
});

player.on('loadeddata', function() {
    $("a.js-playlist__item-button[data-src='"+player.currentSrc()+"'] span").removeClass('hidden');
});

// TODO: put in a plugin
document.addEventListener('keydown', function(event) {
    if(event.keyCode == 37) {
        if (event.ctrlKey) {
            player.currentTime(player.currentTime() - 60);
        } else {
            player.currentTime(player.currentTime() - 10);
        }
    } else if(event.keyCode == 39) {
        if (event.ctrlKey) {
            player.currentTime(player.currentTime() + 60);
        } else {
            player.currentTime(player.currentTime() + 10);
        }
    }
});
SCRIPT;

}

?>

<?= DetailView::widget([
    'model' => $model,
    'attributes' => [
        'sc_size:shortSize',
    ],
]) ?>

    <div class="panel panel-default">
        <div class="panel-heading">
            <span><?= \Yii::t('ticket', 'Screen Capture'); ?></span>
            <div class="pull-right">
                <div class="btn-group">
                    <a href="#" class="dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <span class="glyphicon glyphicon-list-alt"></span> <?= \Yii::t('ticket', 'Actions'); ?><span class="caret"></span>
                    </a>            
                    <ul class="dropdown-menu dropdown-menu-right">
                        <li>
                            <?= Html::a(
                                '<span class="glyphicon glyphicon-paperclip"></span> '. \Yii::t('ticket', 'Show Log File'),
                                Url::to([
                                    'screencapture/log',
                                    'ticket_id' => $model->id,
                                ]),
                                [
                                    'id' => 'screencapture-log-show',
                                    'title' => \Yii::t('ticket', 'Show screencapture log')
                                ]
                            ); ?>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="row">

            <?php if ($model->screencapture !== null) {

                $ScreenCapturesDataProvider = new ArrayDataProvider([
                    'allModels' => $model->screencapture->screencaptures,
                ]);
                $ScreenCapturesDataProvider->pagination->pageSize = -1;

                ?>

                <div class="col-md-9">

                <?= VideoJsWidget::widget([
                    'jsOptions' => [
                        'fluid' => true,
                        'controls' => true,
                        'liveui' => true,
                        'html5' => [
                            'nativeTextTracks' => false,
                        ],
                        'plugins' => [
                            'karaokeSubtitles' => [],
                            'playlist' => [
                                'playlist' => $model->screencapture->screencaptures,
                            ],
                        ],
                    ],
                    'options' => [
                        'id' => 'video-container',
                    ],
                    'tags' => [
                        'source' => [
                            [
                                'type' => 'application/x-mpegURL',
                                'src' => Url::to([
                                    'screencapture/view',
                                    'id' => $model->id,
                                    'file' => $model->state == app\models\Ticket::STATE_RUNNING
                                        ? end($model->screencapture->masters)
                                        : $model->screencapture->masters[0]
                                ]),
                                'data-id' => '0',
                            ],
                        ],
                    ],
                ]); ?>
                </div>
                <div class="col-md-3">
                    <h4><?= Yii::t('ticket', 'Available screen captures:') ?></h4>

                    <?php Pjax::begin([
                        'id' => 'w102',
                        'options' => [
                            'tag' => 'ul',
                            'class' => 'list-unstyled timeline widget video-playlist',
                        ],
                    ]); ?>

                    <?= ListView::widget([
                        'dataProvider' => $ScreenCapturesDataProvider,
                        'options' => [
                            'tag' => false,
                        ],
                        'itemOptions' => [
                            'tag' => 'li',
                        ],
                        'viewParams' => ['ticket' => $model],
                        'itemView' => function ($sc, $key, $index, $widget) use ($model) {
                            return '<div class="block"><div class="block-content"><h2 class="title">' . Html::a(
                                '<span class="glyphicon glyphicon-play hidden" aria-hidden="true"></span>&nbsp;' . 
                                \Yii::t('ticket', 'Capture #{key}', [
                                    'key' => $key+1,
                                ]),
                                null,
                                [
                                    'class' => 'js-playlist__item-button',
                                    'data-id' => $key+1,
                                    'data-src' => Url::to([
                                        'screencapture/view',
                                        'id' => $model->id,
                                        'file' => $sc['master']
                                    ])
                                ]
                            ) . '</h2><div class="byline">' . \Yii::t('ticket', 'Length: {length}', [
                                'length' => yii::$app->formatter->format($sc['length'], 'duration')
                            ]) . '</div></div></div>';
                        },
                        'emptyText' => \Yii::t('ticket', 'No video file(s) found.'),
                        'emptyTextOptions' => [
                            'tag' => 'li',
                        ],
                        'layout' => '{items} {summary} {pager}',
                        'pager' => [
                            'maxButtonCount' => 3,
                            'options' => [
                                'class' => 'pagination pagination-sm',
                                'style' => 'padding: 3px 20px;'
                            ]
                        ],
                    ]); ?>
                    <?php $this->registerJs($js, \yii\web\View::POS_READY); ?>
                    <?php Pjax::end() ?>

                </div>
                <div class="col-md-3 js-keylogger__log">
                </div>

            <?php } else { ?>
                <br>
                <div class="row">
                    <div class="col-sm-12 text-center">
                        <i class="glyphicon glyphicon-warning-sign"></i>&nbsp;<span><?= Yii::t('ticket', 'No video file(s) found.') ?></span>
                        <br><br>
                        <a class="btn btn-default" onClick='$.pjax.reload({container:"#screencapture"});'><i class="glyphicon glyphicon-refresh"></i>&nbsp;<?= Yii::t('app', 'Reload') ?></a>
                    </div>
                </div>
                <br>
            <?php } ?>

        </div>
    </div>

<?php

$screencaptureLogButton = "
    $('#screencapture-log-show').click(function(event) {
        event.preventDefault();
        $('#screencaptureLogModal').modal('show');
        $.pjax({url: this.href, container: '#screencaptureLogModalContent', push: false, async:false})
    });
";
$this->registerJs($screencaptureLogButton);

Modal::begin([
    'id' => 'screencaptureLogModal',
    'header' => '<h4>' . \Yii::t('ticket', 'Screencapture Log') . '</h4>',
    'footer' => Html::Button(\Yii::t('ticket', 'Close'), ['data-dismiss' => 'modal', 'class' => 'btn btn-default']),
    'size' => \yii\bootstrap\Modal::SIZE_LARGE
]);

    Pjax::begin([
        'id' => 'screencaptureLogModalContent',
    ]);
    Pjax::end();

Modal::end();

?>
