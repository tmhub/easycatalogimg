<?php

class TM_EasyCatalogImg_Adminhtml_Easycatalogimg_CategoryController extends Mage_Adminhtml_Controller_Action
{
    public function assignImageAction()
    {
        $fillThumbnails = $this->getRequest()->getParam('thumbnail');
        if (!$fillThumbnails) {
            return $this->getResponse()->setBody(Mage::helper('core')->jsonEncode(array(
                'error' => $this->__('Please select the checkbox above')
            )));
        }

        $media       = Mage::getBaseDir('media');
        $categoryDir = $media . DS . 'catalog' . DS . 'category';
        if (!is_writable($categoryDir)) {
            return $this->getResponse()->setBody(Mage::helper('core')->jsonEncode(array(
                'error' => $this->__('%s is not writable', $categoryDir)
            )));
        }

        $lastProcessed = $this->getRequest()->getParam('last_processed', 0);
        $pageSize      = $this->getRequest()->getParam('page_size', 20);
        $categories    = Mage::getResourceModel('catalog/category_collection')
            ->setItemObjectClass('easycatalogimg/category')
            ->addAttributeToSelect('thumbnail', true)
            ->addAttributeToFilter('entity_id', array('gt' => $lastProcessed))
            ->addAttributeToFilter('level', array('gt' => 0))
            ->addAttributeToFilter('thumbnail', array(array('null' => 1), array('eq' => '')))
            ->setOrder('entity_id')
            ->setPageSize($pageSize)
            ->setCurPage(1);

        $storeGroups = Mage::app()->getGroups(true);
        $searchInChildCategoriesFlag = $this->getRequest()->getParam('search_in_child_categories');
        $helper = Mage::helper('catalog/image');
        foreach ($categories as $category) {
            $storeGroup = false;

            if ($searchInChildCategoriesFlag) {
                $category->load($category->getId());
                $pathIds = $category->getPathIds();
                if (count($pathIds) > 1) {
                    foreach ($storeGroups as $group) {
                        if ($group->getRootCategoryId() != $pathIds[1]) { // 0 element - is global root
                            continue;
                        }
                        $storeGroup = $group;
                        break;
                    }
                }
            }

            if ($storeGroup) {
                $products = Mage::getResourceModel('catalog/product_collection')
                    ->setStoreId($storeGroup->getDefaultStoreId())
                    ->addCategoryFilter($category);
            } else {
                $products = $category->getProductCollection();
            }

            $products->addAttributeToSelect('image')
                ->addAttributeToFilter('image', array('notnull' => 1))
                ->addAttributeToFilter('image', array('neq' => ''))
                ->addAttributeToFilter('image', array('neq' => 'no_selection'))
                ->setOrder('entity_id', 'asc')
                ->setPage(1, 1);

            $product = $products->getFirstItem();
            if (!$product || !$product->getId()) {
                continue;
            }

            $image       = trim($product->getImage(), '/');
            $source      = $media . DS . 'catalog' . DS . 'product' . DS . $image;
            $destination = $categoryDir . DS . $image;

            if (file_exists($source) && !file_exists($destination)) {
                $pathinfo = pathinfo($destination);
                if (!is_writable($pathinfo['dirname']) && !mkdir($pathinfo['dirname'], 0777, true)) {
                    continue;
                }
                copy($source, $destination);
            }

            if (file_exists($destination)) {
                $category->setThumbnail($image)->save();
            }
        }

        $processed = $this->getRequest()->getParam('processed', 0) + count($categories);
        $finished  = (int)(count($categories) < $pageSize);
        if ($finished) {
            Mage::app()->getCacheInstance()->cleanType('block_html');
        }
        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode(array(
            'finished'  => $finished,
            'processed' => $processed,
            'last_processed' => $categories->getLastItem()->getId()
        )));
    }

    protected function _isAllowed()
    {
        $action = strtolower($this->getRequest()->getActionName());
        switch ($action) {
            case 'assignimage':
                return Mage::getSingleton('admin/session')->isAllowed('templates_master/easycatalogimg/' . $action);
            default:
                return Mage::getSingleton('admin/session')->isAllowed('templates_master/easycatalogimg');
        }
    }
}
