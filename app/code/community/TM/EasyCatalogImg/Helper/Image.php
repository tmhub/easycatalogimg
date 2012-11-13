<?php

class TM_EasyCatalogImg_Helper_Image extends Mage_Core_Helper_Abstract
{
    public function resize($imageUrl, $width, $height)
    {
        if (!file_exists(Mage::getBaseDir('media').DS."catalog".DS."category".DS."resized")) {
            mkdir(Mage::getBaseDir('media').DS."catalog".DS."category".DS."resized",0777);
        };

        $imageName = substr(strrchr($imageUrl,"/"),1);
        $imageName = $width . '_' . $height . '_' . $imageName;

        $imageResized = Mage::getBaseDir('media').DS."catalog".DS."category".DS."resized".DS.$imageName;

        $dirImg = Mage::getBaseDir().str_replace("/",DS,strstr($imageUrl,'/media'));

        if (!file_exists($imageResized) && file_exists($dirImg)) {
            $imageObj = new Varien_Image($dirImg);
            $imageObj->constrainOnly(true);
            $imageObj->keepAspectRatio(true);
            $imageObj->keepFrame(true);
            // $imageObj->keepTransparency(true);
            $imageObj->backgroundColor($this->getBackgroundColor());
            $imageObj->resize($width, $height);
            $imageObj->save($imageResized);
        }

        $imageUrl = Mage::getBaseUrl('media')."catalog/category/resized/".$imageName;

        return $imageUrl;
    }

    public function getBackgroundColor()
    {
        $rgb = Mage::getStoreConfig('easycatalogimg/general/background');
        $rgb = explode(',', $rgb);
        foreach ($rgb as $i => $color) {
            $rgb[$i] = (int) $color;
        }
        return $rgb;
    }
}
