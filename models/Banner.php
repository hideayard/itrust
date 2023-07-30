<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "banner".
 *
 * @property int $b_id
 * @property string|null $b_title
 * @property string|null $b_desc
 * @property string|null $b_link
 * @property string|null $b_foto
 * @property int|null $b_created_by
 * @property string $b_created_at
 * @property int|null $b_modified_by
 * @property string $b_modified_at
 * @property int|null $b_status
 */
class Banner extends \yii\db\ActiveRecord
{

    public $imageFile;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'banner';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['b_desc', 'b_link'], 'string'],
            [['b_created_by', 'b_modified_by', 'b_status'], 'integer'],
            [['b_created_at', 'b_modified_at'], 'safe'],
            [['b_title', 'b_foto'], 'string', 'max' => 255],
            [['imageFile'], 'file', 'skipOnEmpty' => true, 'extensions' => ['png', 'jpg'], 'maxFiles' => 1],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'b_id' => 'ID',
            'b_title' => 'Title',
            'b_desc' => 'Description',
            'b_link' => 'Link',
            'b_foto' => 'Photo',
            'b_created_by' => 'B Created By',
            'b_created_at' => 'B Created At',
            'b_modified_by' => 'B Modified By',
            'b_modified_at' => 'B Modified At',
            'b_status' => 'B Status',
        ];
    }

    public function upload()
    {
        if ($this->validate()) {

            if (!file_exists("uploads")){
                mkdir("uploads", 777, true);
            }
            
            $this->imageFile->saveAs('uploads/' . $this->imageFile->baseName . '.' . $this->imageFile->extension);
            $this->b_foto = 'uploads/' . $this->imageFile->baseName . '.' . $this->imageFile->extension;
            return $this->save();
        } else {
            return false;
        }
    }
}
