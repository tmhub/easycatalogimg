<?php

class TM_EasyCatalogImg_Helper_Image extends Mage_Core_Helper_Abstract
{
    protected $_backgroundColor = null;

    public function resize($imageUrl, $width, $height)
    {
        if (!file_exists(Mage::getBaseDir('media').DS."catalog".DS."category".DS."resized")) {
            mkdir(Mage::getBaseDir('media').DS."catalog".DS."category".DS."resized", 0777, true);
        }

        $imageName = substr(strrchr($imageUrl, "/"), 1);
        if ('255,255,255' !== $this->getBackgroundColor(true)) {
            $imageName = $width . 'x' . $height . '/' . $this->getBackgroundColor(true) . '/' . $imageName;
        } else {
            $imageName = $width . 'x' . $height . '/' . $imageName;
        }

        $imageResized = Mage::getBaseDir('media').DS."catalog".DS."category".DS."resized".DS.$imageName;

        $imagePath = str_replace(Mage::getBaseUrl('media'), 'media/', $imageUrl);
        $imagePath = Mage::getBaseDir() . DS . str_replace("/", DS, $imagePath);

        if (!file_exists($imageResized) && file_exists($imagePath)) {
            $imageObj = new Varien_Image($imagePath);
            $imageObj->constrainOnly(true);
            $imageObj->keepAspectRatio(true);
            $imageObj->keepFrame(true);
            $imageObj->keepTransparency(true);
            $imageObj->backgroundColor($this->getBackgroundColor());
            $imageObj->resize($width, $height);
            $imageObj->save($imageResized);
        }

        $imageUrl = Mage::getBaseUrl('media')."catalog/category/resized/".$imageName;

        return $imageUrl;
    }

    public function setBackgroundColor($rgb)
    {
        if (!is_array($rgb)) {
            $rgb = explode(',', $rgb);
            foreach ($rgb as $i => $color) {
                $rgb[$i] = (int) $color;
            }
        }
        $this->_backgroundColor = $rgb;

        return $this;
    }

    public function getBackgroundColor($toString = false)
    {
        if (null === $this->_backgroundColor) {
            $rgb = Mage::getStoreConfig('easycatalogimg/general/background');
            $this->setBackgroundColor($rgb);
        }
        if ($toString) {
            return implode(',', $this->_backgroundColor);
        }
        return $this->_backgroundColor;
    }
}
