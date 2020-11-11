<?php

use yii\helpers\Html;
use yii\helpers\Url;
use app\components\ActiveEventField;

/* @var $model app\models\ticket the data model */
/* @var $key integer mixed, the key value associated with the data item */
/* @var $index integer integer, the zero-based index of the data item in the items array returned by $dataProvider. */
/* @var $widget yii\widgets\ListView this widget instance */

?>

<div class="live-overview-item">
    <?= ActiveEventField::widget([
        'id' => generate_uuid(),
        'options' => [
            'tag' => 'img',
            'class' => 'live-thumbnail',
            'data-time' => 0,
            'data-url' => Url::to(['ticket/live', 'token' => $model->token]),
            'data-toggle' => 'modal',
            'data-target' => '#galleryModal',
            'data-src' => Url::to(['screenshot/snap', 'token' => $model->token]),
        ],
        'event' => 'monitor:ticket/' . $model->id,
        'jsonSelector' => 'live',
        // reload image when a new has arrived
        'jsHandler' => 'function(d, s) {
            fetch("data:image/jpg;base64," + d.base64)
                .then(res => res.blob())
                .then(blob => {
                    var urlCreator = window.URL || window.webkitURL;
                    url = urlCreator.createObjectURL(blob);
                    s.src = url;
                    var now = parseFloat(new Date().getTime()/1000);
                    $(s).attr("data-time", now);
                });
        }',
    ]); ?>
    <div class="live-overview-detail-error alert alert-info" data-toggle="modal" data-target="#galleryModal" style="display:none;"><?= \Yii::t('ticket', 'Please wait, while the live image is being produced...'); ?></div>

    <?= Html::a(
        ActiveEventField::widget([
            'id' => generate_uuid(),
            'options' => [
                'tag' => 'span',
                'class' => 'live-indicator glyphicon glyphicon-circle',
                'title' => \Yii::t('ticket', 'Currently behind live'),
            ],
            'event' => 'monitor:ticket/' . $model->id,
            'jsonSelector' => 'live',
            // change the live indicator
            'jsHandler' => 'function(d, s) {
                $(s).addClass("live");
                $(s).attr("title", "' . \Yii::t('ticket', 'Currently playing live') . '");
            }',
        ]) . (empty($model->test_taker) ? $model->token : $model->test_taker) .
        ' // ' .
        ActiveEventField::widget([
            'id' => generate_uuid(),
            'options' => [
                'tag' => 'img',
                'class' => 'live-overview-item-icon',
                'alt' => ' ',
                'src' => Url::to(['ticket/live', 'token' => $model->token, 'mode' => 'icon']),
            ],
            'event' => 'monitor:ticket/' . $model->id,
            'jsonSelector' => 'live',
            // show the icon of the active window
            'jsHandler' => 'function(d, s) {
                if (typeof d.icon !== "undefined") {
                    s.src = "data:image/jpg;base64," + d.icon;
                }
            }',
        ]) .
        ' ' .
        ActiveEventField::widget([
            'id' => generate_uuid(),
            'options' => [
                'tag' => 'span',
                'class' => 'live-overview-item-info',
                'title' => $model->liveWindowName,
            ],
            'content' => $model->liveWindowName,
            'event' => 'monitor:ticket/' . $model->id,
            'jsonSelector' => 'live',
            // show the active window
            'jsHandler' => 'function(d, s) {
                if (typeof d.window !== "undefined") {
                    $(s).html(d.window);
                    $(s).attr("title", d.window);
                }
            }',
        ]), Url::to(['ticket/view', 'id' => $model->id]),
        [
            'class' => 'live-overview-item-title',
            'data-pjax' => 0,
        ]
    ); ?>

    <span class='live-overview-fullscreen glyphicon glyphicon-fullscreen' data-toggle="modal" data-target="#galleryModal"></span>
</div>