<?php

trait ImageManager
{
    /**
     * Сохраняет все изображения объявленные в модели и переданные в модель
     */
    protected function _prepareImages()
    {
        $primaryKey = $this->getPrimaryKey();
        $oldModel = null;
        if ($primaryKey != null) {
            $oldModel = $this->findByPk($primaryKey);
        }
        if (count($this->_images) > 0) {
            foreach ($this->_images as $property => $imageProperties) {
                $bResave = $oldModel !== null && $oldModel->$property != $this->$property;

                if ($bResave) {

                    $arrImageName = $this->$property;
                    if (!is_array($arrImageName)) {
                        $arrImageName = array($arrImageName);
                    }

                    $arrOdImageFilenames = explode(';', (string) $oldModel->$property);
                    if (!is_array($arrOdImageFilenames)) {
                        $arrOdImageFilenames = array($arrOdImageFilenames);
                    }


                    // сохраняем новые
                    $values = array();
                    foreach ($arrImageName as  $imageFilename) {
                        if (in_array($imageFilename, $arrOdImageFilenames)) {
                            $imageID = array_search($imageFilename, $arrOdImageFilenames);
                            $values[] = $arrOdImageFilenames[$imageID];
                            unset($arrOdImageFilenames[$imageID]);
                        } else {
                            $values[] = $this->_saveImage($imageFilename, $imageProperties);
                        }
                    }

                    $values = array_filter(
                        $values,
                        function($element) {
                            return !empty($element);
                        }
                    );

                    $this->$property = implode(";", $values);


                    //var_dump($arrOdImageFilenames);exit;
                    //удаляем старые которые удалил пользователь
                    if (count($arrOdImageFilenames) > 0) {
                        foreach ($arrOdImageFilenames as $oldFilename) {
                            $this->_deleteImage($oldFilename, $imageProperties);
                        }
                    }
                }

            }
        }
    }


    /**
     * Удаляем изображение и его форматы
     *
     * @param $ImageFilename имя изображения
     * @param $imageProperties параметры для удаления
     */
    protected function _deleteImage($ImageFilename,$imageProperties)
    {
        if ($ImageFilename != null) {
            list($filename, $extension) = $this->imageInfo($ImageFilename);
            $ImagePath = Yii::app()->getBasePath() . '/..' . Yii::app()->params['images']['uploadFolder'] . $imageProperties['folder'];
            $ImagePath .= $this->getImageSpecifiedDir($ImageFilename) . '/';

            foreach ($imageProperties['formats'] as $postfix => $imageInfo) {
                if ($ImageFilename != null) {
                    // удаляем старые
                    if (file_exists($ImagePath . $filename . '_' . $postfix . '.' . $extension)) {
                        unlink($ImagePath . $filename . '_' . $postfix . '.' . $extension);
                    }

                    $files =  glob($ImagePath . '/*');
                    list($firstDir, $secondDir) = explode('/', $this->getImageSpecifiedDir($filename));
                    if(count($files) == 0) {

                        @rmdir(Yii::app()->getBasePath() . '/..' . Yii::app()->params['images']['uploadFolder'] . $imageProperties['folder'] . $firstDir . '/' . $secondDir);
                        $files =  glob(Yii::app()->getBasePath() . '/..' . Yii::app()->params['images']['uploadFolder'] . $imageProperties['folder'] . '/' . $firstDir . '/*');
                        if(count($files) == 0) {
                            @rmdir(Yii::app()->getBasePath() . '/..' . Yii::app()->params['images']['uploadFolder'] . $imageProperties['folder'] . '/' . $firstDir);
                        }
                    }
                }

            }
        }


    }

    /**
     * Сохраняет оддну фотографию в разных форматах
     * Возвращает преобразованное имя исходной фотографии
     *
     * @param $uploadImageFilename имя изоражения
     * @param $imageProperties параметры для сохранения
     *
     * @return null|string
     */
    protected function _saveImage($uploadImageFilename, $imageProperties)
    {

        $result = null;
        if ($uploadImageFilename != null) {
            list($filename, $extension) = $this->imageInfo($uploadImageFilename);
            $uploadImagePath = Yii::app()->getBasePath() . '/..' . Yii::app()->params['images']['tmpUploadFolder'];

            $newImageName = md5($filename);
            $newImagePath = Yii::app()->getBasePath() . '/..' . Yii::app()->params['images']['uploadFolder'] . $imageProperties['folder'];
            $newImagePath .= $this->getImageSpecifiedDir($newImageName) . '/';

            foreach ($imageProperties['formats'] as $postfix => $imageInfo) {
                // сохраняем новые
                $newImageFilename = $newImageName . '_' . $postfix . '.' . $extension;
                if (!is_dir($newImagePath)) {
                    mkdir($newImagePath, 0777, true);
                }
                \Gregwar\Image\Image::open($uploadImagePath . $uploadImageFilename)
                    ->resize($imageInfo['width'], $imageInfo['height'])
                    ->save($newImagePath . $newImageFilename);
            }

            $result = $newImageName . '.' . $extension;

        }

        return $result;

    }


    /**
     * Возвращает путь до изображения
     *
     * @param $property свойство с именем изоражения
     *
     *
     * @return string
     */
    public function getImagesPaths($property)
    {
        $arrFilesResult = array();
        if (count($this->_images) > 0 && isset($this->_images[$property])) {

            $imageFilenames = $this->$property;

            $arrImageFilenamse = explode(';', $imageFilenames);
            $arrImageFilenamse = array_filter(
                $arrImageFilenamse,
                function($element) {
                    return !empty($element);
                }
            );

            if (count($arrImageFilenamse) > 0) {
                foreach ($arrImageFilenamse as $key => $filename) {
                    $imageSpecifiedDir = $this->getImageSpecifiedDir($filename);

                    list($imageName, $extension) = $this->imageInfo($filename);


                    $arrFilesResult[$key] = array();
                    $arrFilesResult[$key]['filename'] = $imageName . '.' . $extension;
                    foreach ($this->_images[$property]['formats'] as $postfix => $imageInfo) {
                        $arrFilesResult[$key][$postfix] = Yii::app()->getBaseUrl() . Yii::app()->params['images']['uploadFolder'] . $this->_images[$property]['folder'] . $imageSpecifiedDir . '/' .
                            $imageName . '_' . $postfix . '.' . $extension;
                    }


                }
            }

        }

        return $arrFilesResult;

    }

    /**
     * Возвращает специальную директорию для изображения первые две буквы имени/вторые две буквы имени
     *
     * @param $imageName имя изображения
     *
     * @return string
     */
    public function getImageSpecifiedDir($imageName)
    {
        return substr($imageName, 0, 2) . '/' . substr($imageName, 2, 2);
    }

    /**
     * Возвращает информацию об изображении
     *
     * @param $imageFilename имя изображение
     *
     * @return array
     */
    public function imageInfo($imageFilename)
    {
        $imageArrFileName = explode('.', $imageFilename);
        $extension = array_pop($imageArrFileName);
        $filename = implode('.', $imageArrFileName);

        return array($filename, $extension);
    }
}