EAjaxUpload
===========


installation



configuration

add to protected/config/main.php

autoloading model and component classes
'import'=>array(
    'application.models.*',
    'application.components.*',
),
'aliases' => array('FileUpload' => 'protected/vendor/ViktorGolodyaev/EAjaxUpload/ViktorGolodyaev/EAjaxUpload'),


....



// application-level parameters that can be accessed
// using Yii::app()->params['paramName']
'params'=>array(
    // this is used in contact page
    'adminEmail'=>'webmaster@example.com',
    'images' => array(
        'tmpUploadFolder' => '/uploads/images/tmp/',
        'uploadFolder' => '/uploads/images/',
        'allowedExtension' => array('png', 'PNG', 'jpeg', 'jpg', 'JPG', 'gif', 'GIF'),
        'sizeLimit' => 10 * 1024 * 1024,
    ),
),




use

create uploader controller and add route

                          

class UploadController extends IndexController
{
	/**
	 * This is the default 'index' action that is invoked
	 * when an action is not explicitly requested by users.
	 */
	public function actionIndex()
	{
        Yii::import("FileUpload.qqFileUploader");

        $folder=Yii::app()->getBasePath() . '/..' . Yii::app()->params['images']['tmpUploadFolder'];// folder for uploaded files

        $allowedExtensions = Yii::app()->params['images']['allowedExtension'];//array("jpg","jpeg","gif","exe","mov" and etc...
        $sizeLimit = Yii::app()->params['images']['sizeLimit'];// maximum file size in bytes
        $uploader = new qqFileUploader($allowedExtensions, $sizeLimit);
        $result = $uploader->handleUpload($folder);
        $return = htmlspecialchars(json_encode($result), ENT_NOQUOTES);

        //$fileSize=filesize($folder.$result['filename']);//GETTING FILE SIZE
        //$fileName=$result['filename'];//GETTING FILE NAME

        echo $return;// it's array
	}


}



in model:


!!! use text for field multiupload in db


/**
 * This is the model class for table "{{users}}".
 *
 * The followings are the available columns in table '{{users}}':
 * @property integer $id
 * @property string $avatar
 *
 */

Yii::import('FileUpload.ImageManager');
class User extends ActiveRecord implements Uploadable
{
    use ImageManager;

    protected $_images = array(
        'avatar' => array(
            'formats' => array(
                'adminpreview' => array (
                    'width' => 100,
                    'height' => 100,
                    'quality' => 50,
                ),
                'normal' => array(
                    'width' => 240,
                    'height' => 120,
                    'quality' => 95,
                )
            ),
            'multi' => false,
            'folder' => 'users/avatar/'
        )
//        'avatar' => array(
//                    'formats' => array(
//                        'adminpreview' => array (
//                            'width' => 100,
//                            'height' => 100,
//                            'quality' => 50,
//                        ),
//                        'normal' => array(
//                            'width' => 240,
//                            'height' => 120,
//                            'quality' => 95,
//                        )
//                    ),
//                    'multi' => true,
//                    'folder' => 'users/avatar2/'
//                )
    );



        /**
         * befor save callback
         *
         * @return bool
         */
        public function beforeSave()
        {

            if ($this instanceof Uploadable) {
                $this->_prepareImages($this->getIsNewRecord());
            }

           return true;
        }

/*you code*/
}




in view:


<? $this->widget('FileUpload.EAjaxUpload',
array(
    'id'=>'uploadAvatar',
    'config'=>array(
    'action'=>Yii::app()->createUrl('/upload'),
    'allowedExtensions'=>array("jpg", "gif", "jpeg"),//array("jpg","jpeg","gif","exe","mov" and etc...
    'sizeLimit'=>10*1024*1024,// maximum file size in bytes
    //'minSizeLimit'=>10*1024*1024,// minimum file size in bytes
    'multi' => false,
    'model' => $model,
    'property' => 'avatar',
    //'messages'=>array(
    //                  'typeError'=>"{file} has invalid extension. Only {extensions} are allowed.",
    //                  'sizeError'=>"{file} is too large, maximum file size is {sizeLimit}.",
    //                  'minSizeError'=>"{file} is too small, minimum file size is {minSizeLimit}.",
    //                  'emptyError'=>"{file} is empty, please select files again without it.",
    //                  'onLeave'=>"The files are being uploaded, if you leave now the upload will be cancelled."
    //                 ),
    //'showMessage'=>"js:function(message){ alert(message); }"
))); ?>


