<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "banner".
 *
 * @property int $g_id
 * @property string|null $g_title
 * @property string|null $g_desc
 * @property string|null $g_link
 * @property string|null $g_foto
 * @property int|null $g_created_by
 * @property string $g_created_at
 * @property int|null $g_modified_by
 * @property string $g_modified_at
 * @property int|null $g_status
 */
class Gallery extends \yii\db\ActiveRecord
{

    public $imageFile;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'gallery';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['g_desc', 'g_link'], 'string'],
            [['g_created_by', 'g_modified_by', 'g_status'], 'integer'],
            [['g_created_at', 'g_modified_at'], 'safe'],
            [['g_title', 'g_foto'], 'string', 'max' => 255],
            [['imageFile'], 'file', 'skipOnEmpty' => true, 'extensions' => ['png', 'jpg'], 'maxFiles' => 1],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'g_id' => 'ID',
            'g_title' => 'Title',
            'g_desc' => 'Description',
            'g_link' => 'Link',
            'g_foto' => 'Photo',
            'g_created_by' => 'B Created By',
            'g_created_at' => 'B Created At',
            'g_modified_by' => 'B Modified By',
            'g_modified_at' => 'B Modified At',
            'g_status' => 'B Status',
        ];
    }

    public function upload()
    {
        if ($this->validate()) {

            if (!file_exists("uploads/gallery")){
                mkdir("uploads/gallery", 777, true);
            }
            
            $this->imageFile->saveAs('uploads/gallery/' . $this->imageFile->baseName . '.' . $this->imageFile->extension);
            $this->g_foto = 'uploads/gallery/' . $this->imageFile->baseName . '.' . $this->imageFile->extension;
            return $this->save();
        } else {
            return false;
        }
    }
}
