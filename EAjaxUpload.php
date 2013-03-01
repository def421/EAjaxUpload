<?php
/**
 * EAjaxUpload class file.
 * This extension is a wrapper of http://valums.com/ajax-upload/
 *
 * @author Vladimir Papaev <kosenka@gmail.com>
 * @version 0.1
 * @license http://www.opensource.org/licenses/bsd-license.php
 */

/**
How to use:

view:
$this->widget('ext.EAjaxUpload.EAjaxUpload',
array(
'id'=>'uploadFile',
'config'=>array(
'action'=>'/controller/upload',
'allowedExtensions'=>array("jpg"),//array("jpg","jpeg","gif","exe","mov" and etc...
'sizeLimit'=>10*1024*1024,// maximum file size in bytes
'minSizeLimit'=>10*1024*1024,// minimum file size in bytes
'onComplete'=>"js:function(id, fileName, responseJSON){ alert(fileName); }",
//'messages'=>array(
//                  'typeError'=>"{file} has invalid extension. Only {extensions} are allowed.",
//                  'sizeError'=>"{file} is too large, maximum file size is {sizeLimit}.",
//                  'minSizeError'=>"{file} is too small, minimum file size is {minSizeLimit}.",
//                  'emptyError'=>"{file} is empty, please select files again without it.",
//                  'onLeave'=>"The files are being uploaded, if you leave now the upload will be cancelled."
//                 ),
//'showMessage'=>"js:function(message){ alert(message); }"
)
));

controller:

public function actionUpload()
{
Yii::import("ext.EAjaxUpload.qqFileUploader");

$folder='upload/';// folder for uploaded files
$allowedExtensions = array("jpg"),//array("jpg","jpeg","gif","exe","mov" and etc...
$sizeLimit = 10 * 1024 * 1024;// maximum file size in bytes
$uploader = new qqFileUploader($allowedExtensions, $sizeLimit);
$result = $uploader->handleUpload($folder);
$result=htmlspecialchars(json_encode($result), ENT_NOQUOTES);
echo $result;// it's array
}

 */
class EAjaxUpload extends CWidget
{
    /**
     * @var string
     */
    public $id = "fileUploader";

    /**
     * @var array
     */
    public $postParams = array();

    /**
     * @var array
     */
    public $config = array();

    /**
     * @var null
     */
    public $css = null;

    /**
     * @throws CException
     *
     * @return void
     */
    public function run()
    {
        if (empty($this->config['action'])) {
            throw new CException('EAjaxUpload: param "action" cannot be empty.');
        }

        if (empty($this->config['allowedExtensions'])) {
            throw new CException('EAjaxUpload: param "allowedExtensions" cannot be empty.');
        }

        if (empty($this->config['sizeLimit'])) {
            throw new CException('EAjaxUpload: param "sizeLimit" cannot be empty.');
        }

        $bMultiUpload = isset($this->config['multi']) && $this->config['multi'];
        unset($this->config['multi']);

        $html= '<div id="' . $this->id . '">
                    <noscript><p>Please enable JavaScript to use file uploader.</p></noscript>';
        $html .= '</div>';

        if (!empty($this->config['model'])) {
            $model = $this->config['model'];
            unset($this->config['model']);
            if ($model != null && empty($this->config['property'])) {
                throw new CException('EAjaxUpload: param "property" cannot be empty if param "model" exist.');
            }

            $property = $this->config['property'];
            unset($this->config['property']);

            $onCompleteFunction = 'var completeCallback = function(){};';
            if (!empty($this->config['onComplete'])) {
                $onCompleteFunction = 'var completeCallback = ' . $this->config['onComplete'];
            }

            $fieldID = get_class($model) . '_' . $property;
            if ($bMultiUpload) {
                $fieldName = get_class($model) . '[' . $property . '][]';
                $this->config['onComplete'] = "js:function(id, fileName, responseJSON){
                        var countImages = $(\"input[id^='" . $fieldID . "_']\").length;
                        if ($('#" . $fieldID . "_' + (countImages + id)).length == 0) {
                            var newField = '<input type=\"hidden\" id=\"" . $fieldID . "_' + countImages +  '\" name=\"" . $fieldName . "\" value=\"\">';
                            $('#" . $this->id . "').append($(newField));
                        }
                        $('#" . $fieldID . "_' + countImages).val(responseJSON.filename);
                        " . $onCompleteFunction . ";
                        completeCallback();
                    }";
            } else {
                $fieldName = get_class($model) . '[' . $property . ']';
                $this->config['onComplete'] = "js:function(id, fileName, responseJSON){

                        if ($('#" . $fieldID . "_' + 0).length == 0) {
                            var newField = '<input type=\"hidden\" id=\"" . $fieldID . "_' + 0 +  '\" name=\"" . $fieldName . "\" value=\"\">';
                            $('#" . $this->id . "').append($(newField));
                        }
                        if ($('#" . $this->id  . "').find('.qq-upload-list li').length > 1) {
                             $('#" . $this->id  . "').find('.qq-upload-list li:first').remove();
                        }
                        $('#" . $fieldID . "_' + 0).val(responseJSON.filename);
                        " . $onCompleteFunction . ";
                        completeCallback();
                    }";
            }

            $arrFiles = $model->getImagesPaths($property);

            if (count($arrFiles) > 0) {
                foreach ($arrFiles as $key => $image) {
                    $html .= "<input type=\"hidden\" id=\"" . $fieldID . "_" . $key . "\" name=\"" . $fieldName . "\" value=\"" . $image['filename'] . "\">";
                    $html .= '<img id="'. $fieldID . "_" . $key .'_image" src="' . $image["adminpreview"] . '"> <a href="#" id="jsDelAvatar_' . $key . '" onClick="$(\'#' . $fieldID . "_" . $key . '\').val(\'\'); $(\'#'. $fieldID . "_" . $key .'_image\').attr(\'src\', \'\'); $(\'#jsDelAvatar_' . $key . '\').remove();  return false;">Удалить</a>';
                }
            }

        }

        echo $html;
        unset($this->config['element']);


        $assets = dirname(__FILE__) . '/assets';
        $baseUrl = Yii::app()->assetManager->publish($assets);

        Yii::app()->clientScript->registerCoreScript('jquery');
        Yii::app()->clientScript->registerScriptFile($baseUrl . '/fileuploader.js', CClientScript::POS_HEAD);
        $this->css = (!empty($this->css)) ? $this->css : $baseUrl . '/fileuploader.css';
        Yii::app()->clientScript->registerCssFile($this->css);

        $postParams = array('PHPSESSID' => session_id(), 'YII_CSRF_TOKEN' => Yii::app()->request->csrfToken);
        if (isset($this->postParams)) {
            $postParams = array_merge($postParams, $this->postParams);
        }

        $config = array(
            'element' => 'js:document.getElementById("' . $this->id . '")',
            'debug' => false,
            'multiple' => false
        );
        $config = array_merge($config, $this->config);
        $config['params'] = $postParams;
        $config = CJavaScript::encode($config);

        Yii::app()->getClientScript()->registerScript("FileUploader_" . $this->id, "var FileUploader_" . $this->id . " = new qq.FileUploader($config); ", CClientScript::POS_LOAD);
    }


}