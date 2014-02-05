<?php

/**
 * This class is used during automated image assignment.
 * All callbacks and events are disabled to speedup the thumbnail save process.
 */
class TM_EasyCatalogImg_Model_Category extends Mage_Catalog_Model_Category
{
    const CACHE_TAG             = 'catalog_category_easycatalogimg_disable';
    protected $_eventPrefix     = 'catalog_category_easycatalogimg_disable';

    protected function _construct()
    {
        $this->_init('catalog/category');
    }

    public function validate()
    {
        return true;
    }

    public function afterCommitCallback()
    {
        return $this;
    }

    protected function _afterSaveCommit()
    {
        return $this;
    }

    protected function _beforeSave()
    {
        return $this;
    }

    protected function _afterSave()
    {
        return $this;
    }
}
