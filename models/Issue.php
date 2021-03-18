<?php

namespace app\models;

use Yii;
use yii\db\Expression;

/**
 * This is the model class for table "issue".
 *
 * @property integer $id
 * @property string $ticket_id
 * @property Ticket $ticket
 */
class Issue extends Base
{

    /* issue constants */
    const CLIENT_OFFLINE = 0;
    const LONG_TIME_NO_BACKUP = 1;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'issue';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            ['id', 'unique', 'targetAttribute' => ['id', 'ticket_id']],
            ['key', 'integer', 'min' => 0, 'max' => 999],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'key' => \Yii::t('issues', 'Issue'),
            'occuredAt' => \Yii::t('issues', 'Since'),
            'solvedAt' => \Yii::t('issues', 'Solved At'),
            'ticket.token' => \Yii::t('issues', 'Ticket'),
        ];
    }

    /**
     * @inheritdoc
     */
    public function joinTables()
    {
        return [
            Ticket::tableName(),
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTicket()
    {
        return $this->hasOne(Ticket::className(), ['id' => 'ticket_id']);
    }


    /**
     * Mark the issue as solved.
     * @return bool
     */
    public function resolved()
    {
        $this->solvedAt = new Expression('NOW()');
        return $this->save();
    }
}
