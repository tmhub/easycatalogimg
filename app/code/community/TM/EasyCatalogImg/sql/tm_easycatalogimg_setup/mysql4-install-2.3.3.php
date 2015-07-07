<?php

/** @var $installer TM_EasyCatalogImg_Model_Resource_Setup */
$installer = $this;
$installer->startSetup();

$installer->addAttribute('catalog_category', 'thumbnail', array(
    'type'       => 'varchar',
    'label'      => 'Thumbnail Image',
    'input'      => 'image',
    'backend'    => 'catalog/category_attribute_backend_image',
    'required'   => false,
    'sort_order' => 4,
    'global'     => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,
    'group'      => 'General Information'
));

$installer->endSetup();
