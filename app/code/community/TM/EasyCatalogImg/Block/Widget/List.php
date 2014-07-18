<?php

class TM_EasyCatalogImg_Block_Widget_List extends TM_EasyCatalogImg_Block_List
{
    public function getHideWhenFilterIsUsed()
    {
        return (bool) $this->_getData('hide_when_filter_is_used');
    }

    public function getEnabledForAnchor()
    {
        return true;
    }

    public function getEnabledForDefault()
    {
        return true;
    }
}
