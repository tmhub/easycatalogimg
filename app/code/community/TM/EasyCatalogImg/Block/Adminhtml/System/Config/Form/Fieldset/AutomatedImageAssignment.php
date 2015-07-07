<?php

class TM_EasyCatalogImg_Block_Adminhtml_System_Config_Form_Fieldset_AutomatedImageAssignment
    extends Mage_Adminhtml_Block_System_Config_Form_Fieldset
{
    public function render(Varien_Data_Form_Element_Abstract $element)
    {
        $html = $this->_getHeaderHtml($element);
        $url  = $this->getUrl('adminhtml/easycatalogimg_category/assignImage');
        $html .= <<<HTML
<tr>
    <td colspan="100">
        {$this->__("You can fill empty category thumbnails with image of first product of corresponding category. <br/>If category has attached thumbnail already, it will be skipped.")}
        <ul>
            <li><input style="margin: 0 3px 0 0;" type="checkbox" id="easycatalogimg_thumbnail"/><label for="easycatalogimg_thumbnail">{$this->__("Fill category thumbnails")}</label></li>
            <li><input style="margin: 0 3px 0 0;" type="checkbox" id="easycatalogimg_search_in_child_categories"/><label for="easycatalogimg_search_in_child_categories">{$this->__("Search in child categories")}</label></li>
        </ul>
        <button onclick="assignCategoryImages(0, 0);" class="scalable save" type="button"><span><span><span>{$this->__("Run")}</span></span></span></button>
        <script type="text/javascript">
            function assignCategoryImages(last_processed, processed) {
                if (!$('easycatalogimg_thumbnail').checked) {
                    alert('{$this->__("Please select the checkbox above")}');
                    return;
                }
                if (!$('loading_mask_processed')) {
                    $('loading_mask_loader').insert({
                        bottom: '<span id="loading_mask_processed" style="display: block;">0</span>'
                    });
                }
                new Ajax.Request("$url", {
                    parameters: {
                        last_processed: last_processed,
                        processed: processed,
                        thumbnail: $('easycatalogimg_thumbnail').checked ? 1 : 0,
                        search_in_child_categories: $('easycatalogimg_search_in_child_categories').checked ? 1 : 0
                    },
                    onSuccess: function(response) {
                        var response = response.responseText;
                        try {
                            response = response.evalJSON();
                        } catch (e) {
                            alert('{$this->__("An error occured.")}' + response);
                            return;
                        }

                        if (response.error) {
                            alert(response.error);
                            return;
                        }

                        if (!response.finished) {
                            assignCategoryImages(response.last_processed, response.processed);
                            $('loading_mask_processed').update(response.processed);
                        } else {
                            $('loading_mask_processed').remove();
                            var message = '{$this->__("Completed. {count} items was processed. Please reindex catalog_category_flat data.")}';
                            alert(message.replace('{count}', response.processed));
                        }
                    },
                    onFailure: function(response) {
                        alert('{$this->__("An error occured.")}' + response.responseText);
                    }
                });
            }
        </script>
    </td>
</tr>
HTML;
        $html .= $this->_getFooterHtml($element);
        return $html;
    }
}
