<?php

class TM_EasyCatalogImg_Block_List extends Mage_Core_Block_Template
    implements Mage_Widget_Block_Interface
{
    /**
     * Initialize block's cache
     */
    protected function _construct()
    {
        parent::_construct();

        $this->addData(array(
            'cache_lifetime' => 86400,
            'cache_tags'     => array(Mage_Catalog_Model_Category::CACHE_TAG, Mage_Core_Model_Store_Group::CACHE_TAG)
        ));
    }

    /**
     * Fill the block data with coniguration values
     *
     * @param string $path 'navigationpro/top'
     */
    public function addDataFromConfig($path)
    {
        foreach (Mage::getStoreConfig($path) as $key => $value) {
            $this->setData($key, $value);
        }
        return $this;
    }

    /**
     * Set data using the Magento's configuration
     *
     * @param string $key
     * @param string $path
     * @return TM_NavigationPro_Block_Navigation
     */
    public function setDataFromConfig($key, $path)
    {
        return $this->setData($key, Mage::getStoreConfig($path));
    }

    /**
     * Get Key pieces for caching block content
     *
     * @return array
     */
    public function getCacheKeyInfo()
    {
        $categoryId = false;
        if ($category = $this->getCurrentCategory()) {
            $categoryId = $category->getId();
        }

        return array(
            'TM_EASYCATALOGIMAGES',
            Mage::app()->getStore()->getId(),
            Mage::getDesign()->getPackageName(),
            Mage::getDesign()->getTheme('template'),
            Mage::getSingleton('customer/session')->getCustomerGroupId(),
            $this->getTemplate(),
            $this->getNameInLayout(),
            $categoryId,
            $this->getCategoryCount(),
            $this->getColumnCount(),
            $this->getShowImage(),
            $this->getResizeImage(),
            $this->getImageWidth(),
            $this->getImageHeight(),
            $this->getSubcategoryCount(),
            $this->getUseImageAttribute(),
            $this->getCategoryToShow(),
            $this->getCategoryToHide(),
            (int)Mage::app()->getStore()->isCurrentlySecure()
        );
    }

    /**
     * Opimized method, to get all categories to show
     *
     * @return array
     * <pre>
     * [
     *  Mage_Catalog_Model_Category => {
     *      children => [
     *          Mage_Catalog_Model_Category => {...}
     *      ]
     *  }
     *  Mage_Catalog_Model_Category => {
     *      children => []
     *  }
     *  ...
     * ]
     * </pre>
     */
    public function getCategories()
    {
        if ($category = $this->getCurrentCategory()) {
            $currentLevel = $category->getLevel();
        } else {
            $category     = Mage::getModel('catalog/category');
            $currentLevel = 1;
        }

        $collection = $category->getCollection();

        if ($category->getId()) {
            if (method_exists($collection, 'addParentPathFilter')) {
                $collection->addParentPathFilter($category->getPath());
            } elseif (method_exists($collection, 'addPathsFilter')) {
                $collection->addPathsFilter($category->getPath() . '/');
            }
        }

        if (method_exists($collection, 'addStoreFilter')) {
            $collection->addStoreFilter();
        }

        $collection
            // ->addAttributeToSelect('url_key')
            ->addAttributeToSelect('name')
            ->addAttributeToSelect('image')
            ->addAttributeToSelect('thumbnail')
            ->addAttributeToSelect('is_anchor')
            ->addAttributeToFilter('is_active', 1)
            ->addUrlRewriteToResult()
            ->addFieldToFilter('level', array('lteq' => $currentLevel + 2))
            ->addFieldToFilter('level', array('gt'   => $currentLevel))
            ->setOrder('level', Varien_Db_Select::SQL_ASC)
            ->setOrder('position', Varien_Db_Select::SQL_ASC)
            ->load();

        // the next loops is working for two levels only
        if ($categoriesToShow = $this->getCategoryToShow()) {
            $categoriesToShow = explode(',', $categoriesToShow);
        } else {
            $categoriesToShow = array();
        }
        if ($categoriesToHide = $this->getCategoryToHide()) {
            $categoriesToHide = explode(',', $categoriesToHide);
        } else {
            $categoriesToHide = array();
        }

        $result        = array();
        $subcategories = array();
        foreach ($collection as $category) {
            if (in_array($category->getId(), $categoriesToHide)) {
                continue;
            }
            if ($categoriesToShow
                && !in_array($category->getId(), $categoriesToShow)
                && !in_array($category->getParentId(), $categoriesToShow)) {

                continue;
            }

            if ($category->getLevel() == ($currentLevel + 1)) {
                $result[$category->getId()] = $category;
            } else {
                $subcategories[$category->getParentId()][] = $category;
            }
        }
        foreach ($subcategories as $parentId => $_subcategories) {
            if (!isset($result[$parentId])) { // inactive parent category
                continue;
            }
            $parent = $result[$parentId];
            $parent->setSubcategories($_subcategories);
        }

        return $result;
    }

    public function getImage($category)
    {
        $url = false;
        $prefix = Mage::getBaseUrl('media') . 'catalog/category/';
        if ($image = $category->getThumbnail()) {
            $url = $prefix . $image;
        } elseif ($this->getUseImageAttribute() && $image = $category->getImage()) {
            $url = $prefix . $image;
        } else {
            $url = Mage::getBaseUrl('media') . '/'
                . Mage::getStoreConfig('easycatalogimg/general/placeholder');
        }
        return $url;
    }

    /**
     * Fix for widget instance
     *
     * @return boolean
     */
    public function getResizeImage()
    {
        return (bool) $this->_getDataFromConfigByKey('resize_image', 'general');
    }

    /**
     * Should we use image attribute, when thumbnail is not available
     *
     * @return boolean
     */
    public function getUseImageAttribute()
    {
        return (bool) $this->_getDataFromConfigByKey('use_image_attribute', 'general');
    }

    public function getHideWhenFilterIsUsed()
    {
        return (bool) $this->_getDataFromConfigByKey('hide_when_filter_is_used', 'category');
    }

    public function getEnabledForAnchor()
    {
        return (bool) $this->_getDataFromConfigByKey('enabled_for_anchor', 'category');
    }

    public function getEnabledForDefault()
    {
        return (bool) $this->_getDataFromConfigByKey('enabled_for_default', 'category');
    }

    protected function _getDataFromConfigByKey($key, $configSection)
    {
        $data = $this->_getData($key);
        if (null === $data) {
            $this->setData($key, Mage::getStoreConfig("easycatalogimg/{$configSection}/{$key}"));
        }
        return $this->_getData($key);
    }

    /**
     * @return Mage_Catalog_Model_Category
     */
    public function getCurrentCategory()
    {
        if ($categoryId = $this->getCategoryId()) {
            return Mage::getModel('catalog/category')->load($categoryId);
        }
        if (Mage::getSingleton('catalog/layer')) {
            return Mage::getSingleton('catalog/layer')->getCurrentCategory();
        }
        return false;
    }

    /**
     * @return int
     */
    public function getCategoryId()
    {
        $id = $this->_getData('category_id');
        if (null !== $id && strstr($id, 'category/')) { // category id from widget
            $id = str_replace('category/', '', $id);
        }
        return $id;
    }

    /**
     * Get relevant path to template
     *
     * @return string
     */
    public function getTemplate()
    {
        if (!Mage::getStoreConfig('easycatalogimg/general/enabled')) {
            return '';
        }

        /**
         * don't show the block:
         *  if pagination is used
         *  if filter is applied
         */
        $page = (int) $this->getRequest()->getParam('p', 1);
        if ($this->getHideWhenFilterIsUsed()
            && ($page > 1
                || Mage::getSingleton('catalog/layer')->getState()->getFilters())
        ) {
            return '';
        }

        $category = $this->getCurrentCategory();
        if ($category && $category->getLevel() > 1) {
            $isAnchor          = $category->getIsAnchor();
            $enabledForAnchor  = $this->getEnabledForAnchor();
            $enabledForDefault = $this->getEnabledForDefault();

            if (($isAnchor && !$enabledForAnchor)
                || (!$isAnchor && !$enabledForDefault)) {

                return '';
            }
        }

        $template = parent::getTemplate();
        if (!$template) {
            $template = $this->_getData('template');
        }
        return $template;
    }
}
