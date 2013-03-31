<?php
interface Uploadeble
{

    /**
     * Сохраняет все изображения объявленные в модели и переданные в модель
     */
    public function _prepareImages($isNewRecord);

    /**
     * Возвращает путь до изображения
     *
     * @param $property свойство с именем изоражения
     *
     *
     * @return string
     */
    public function getImagesPaths($property);


    /**
     * Возвращает специальную директорию для изображения первые две буквы имени/вторые две буквы имени
     *
     * @param $imageName имя изображения
     *
     * @return string
     */
    public function getImageSpecifiedDir($imageName);

    /**
     * Возвращает информацию об изображении
     *
     * @param $imageFilename имя изображение
     *
     * @return array
     */
    public function imageInfo($imageFilename);
}