<?php
/**
 * SmartMATE Magento Connect Terms of Use
 * 
 * 1. Agreement to these Terms of use
 * These Terms govern your use of the SmartMATE Magento Connect. These Terms do 
 * not relate to any other Capita Translation and Interpreting Limited 
 * (“Capita”) website or service or your use of any other Capita website or 
 * service and these Terms do not govern your use of the language services you may 
 * wish to receive from Capita.
 * By accessing or using the SmartMATE Magento Connect you agree with and consent 
 * to these Terms and you represent that you have the legal authority to accept 
 * the Terms on behalf of yourself and any party you represent.
 * 
 * 2. Intellectual property Rights
 * All Intellectual Property Rights (including but not limited to trademarks, 
 * copyright (including rights in computer software), trade secrets, trade or 
 * business names, domain names and other similar rights or obligations whether 
 * registerable or not in any country and applications for any of the foregoing) 
 * in the SmartMATE Magento Connect belong to Capita and no permission whatsoever 
 * is given to you for the SmartMATE Magento Connect to be (without limitation) 
 * sold, distributed or edited. 
 * 
 * 3. NO WARRANTY
 * THE SMARTMATE MAGENTO CONNECT IS PROVIDED TO YOU ON AN “AS-IS” BASIS, 
 * WITHOUT REPRESENTATIONS, WARRANTIES OR CONDITIONS OF ANY KIND, EITHER EXPRESS 
 * OR IMPLIED INCLUDING, WITHOUT LIMITATION, ANY WARRANTIES, REPRESENTATIONS OR 
 * CONDITIONS OF TITLE, NON-INFRINGEMENT, MERCHANTABILITY OR FITNESS FOR A 
 * PARTICULAR PURPOSE.
 * CAPITA DOES NOT WARRANT THAT THE FUNCTIONS OR CONTENT CONTAINED ON THE 
 * SMARTMATE MAGENTO CONNECT WILL BE ERROR-FREE, THAT DEFECTS WILL BE CORRECTED, 
 * OR THAT CAPITA OR ITS THIRD PARTIES SERVERS ARE FREE OF VIRUSES OR OTHER 
 * HARMFUL COMPONENTS. CAPITA DOES NOT WARRANT OR MAKE ANY REPRESENTATION 
 * REGARDING USE OF THE SMARTMATE MAGENTO CONNECT IN TERMS OF ACCURACY, 
 * RELIABILITY, OR OTHERWISE.
 * You are solely responsible for determining the appropriateness of using the 
 * SmartMATE Magento Connect and you assume all risks associated with this, 
 * including but not limited compliance with applicable laws, damage to or loss of 
 * data, programs or equipment, and unavailability or interruption of operations.
 * YOUR USE OF THE SMARTMATE MAGENTO CONNECT IS SOLEY AT YOUR RISK.
 * 
 * 4. LIMITATION OF LIABILITY
 * IN NO EVENT SHALL CAPITA BE LIABLE TO YOU FOR ANY INCIDENTAL, DIRECT, INDIRECT, 
 * PUNITIVE, ACTUAL, CONSEQUENTIAL, SPECIAL, EXEMPLARY OR OTHER DAMAGES, INCLUDING 
 * WITHOUT LIMITATION, LOSS OF REVENUE OR INCOME, LOST PROFITS, OR SIMILAR DAMAGES 
 * SUFFERED OR INCURRED BY YOU OR ANY THIRD PARTY HOWEVER CAUSED AND ON ANY THEORY 
 * OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING 
 * NEGLIGENCE OR OTHERWISE) OR OTHERWISE THAT ARISE IN CONNECTION WITH YOUR USE OF 
 * THE SMARTMATE MAGENTO CONNECT (OR THE TERMINATION THEREOF FOR ANY REASON), EVEN 
 * IF CAPITA HAS BEEN ADVISED OF THE POSSIBILITY OF SUCH DAMAGES.
 * 
 * 5. Indemnification for breach of the Terms
 * You agree to indemnify and hold harmless Capita from and against any and all 
 * loss, expenses, damages, and costs, including without limitation reasonable 
 * attorney fees, resulting, whether directly or indirectly, from your violation 
 * of the Terms.
 * 
 * 6. General
 * These Terms are governed by and shall be construed in accordance with English 
 * law and the parties submit to the exclusive jurisdiction of the English courts.
 * If any provision of these Terms is invalid or unenforceable under applicable 
 * law, it shall not affect the validity or enforceability of the remainder of the 
 * terms of these Terms and such provision shall be reformed to the minimum extent 
 * necessary to make such provision valid and enforceable.
 * 
 * @author Daniel Deady <daniel@5ms.uk.com>
 * @copyright Copyright (c) 2017 5M Solutions Ltd. (http://www.5ms.co.uk/)
 */

class Capita_TI_Model_Observer
{

    /**
     * Handle adminhtml_catalog_product_grid_prepare_massaction
     * 
     * @param Varien_Event_Observer $observer
     */
    public function addMassActionToBlock(Varien_Event_Observer $observer)
    {
        /* @var $block Mage_Adminhtml_Block_Catalog_Product_Grid */
        $block = $observer->getBlock();
        $block->getMassactionBlock()->addItem('capita_translate', array(
                'label' => Mage::helper('capita_ti')->__('Translate'),
                'url'   => Mage::getUrl('*/catalog_product/translate')
            ));
    }

    /**
     * Handler for controller_action_layout_render_before_adminhtml_catalog_product_edit
     * 
     * @param Varien_Event_Observer $observer
     */
    public function warnProductInProgress(Varien_Event_Observer $observer)
    {
        /* @var $product Mage_Catalog_Model_Product */
        $product = Mage::registry('product');
        if ($product && !$product->isObjectNew()) {
            /* @var $requests Capita_TI_Model_Resource_Request_Collection */
            $requests = Mage::getResourceModel('capita_ti/request_collection');
            $requests->addProductFilter($product);
            $requests->addRemoteFilter();
            if ($requests->isTargettingStore($product->getStoreId())) {
                Mage::app()->getLayout()->getMessagesBlock()->addWarning(
                    Mage::helper('capita_ti')->__('This product is currently being translated.'));
            }
        }
    }

    /**
     * Handler for controller_action_layout_render_before_adminhtml_catalog_product_action_attribute_edit
     * 
     * @param Varien_Event_Observer $observer
     */
    public function warnProductsInProgress(Varien_Event_Observer $observer)
    {
        $productIds = Mage::getSingleton('adminhtml/session')->getProductIds();
        $storeId = Mage::app()->getRequest()->getParam('store', Mage_Core_Model_App::ADMIN_STORE_ID);
        if ($productIds) {
            /* @var $requests Capita_TI_Model_Resource_Request_Collection */
            $requests = Mage::getResourceModel('capita_ti/request_collection');
            $requests->addProductFilter($productIds);
            $requests->addRemoteFilter();
            if ($requests->isTargettingStore($storeId)) {
                Mage::app()->getLayout()->getMessagesBlock()->addWarning(
                    Mage::helper('capita_ti')->__('Some of these products are currently being translated.'));
            }
        }
    }

    /**
     * Handler for adminhtml_catalog_category_tabs
     * 
     * @param Varien_Event_Observer $observer
     */
    public function warnCategoryInProgress(Varien_Event_Observer $observer)
    {
        /* @var $category Mage_Catalog_Model_Category */
        $category = Mage::registry('category');
        if ($category && !$category->isObjectNew()) {
            $currentLang = Mage::getStoreConfig('general/locale/code', $category->getStoreId());
            /* @var $requests Capita_TI_Model_Resource_Request_Collection */
            $requests = Mage::getResourceModel('capita_ti/request_collection');
            $requests->addCategoryFilter($category);
            $requests->addRemoteFilter();
            if ($requests->isTargettingStore($category->getStoreId())) {
                Mage::app()->getLayout()->getMessagesBlock()->addWarning(
                    Mage::helper('capita_ti')->__('This category is currently being translated.'));
            }
        }
    }

    /**
     * Handler for category_prepare_ajax_response
     * 
     * If category AJAX messages field is empty then javascript doesn't remove old messages.
     * Instead, set something benign to replace them.
     * This might upset other extensions which expect messages to stay.
     * 
     * @param Varien_Event_Observer $observer
     */
    public function unwarnCategoryInProgress(Varien_Event_Observer $observer)
    {
        $response = $observer->getResponse();
        if ($response->getMessages() === '') {
            $response->setMessages('<i></i>'); // invisible content
        }
    }

    /**
     * Handler for controller_action_layout_render_before_adminhtml_cms_block_edit
     *
     * @param Varien_Event_Observer $observer
     */
    public function warnBlockInProgress(Varien_Event_Observer $observer)
    {
        /* @var $block Mage_Cms_Model_Block */
        $block = Mage::registry('cms_block');
        if ($block && !$block->isObjectNew()) {
            /* @var $blocks Mage_Cms_Model_Resource_Block_Collection */
            $blocks = $block->getCollection();
            $blocks->addFieldToFilter('identifier', $block->getIdentifier());

            /* @var $requests Capita_TI_Model_Resource_Request_Collection */
            $requests = Mage::getResourceModel('capita_ti/request_collection');
            $requests->addBlockFilter($blocks->getAllIds());
            $requests->addRemoteFilter();
            if ($requests->isTargettingStore($block->getStoreId())) {
                Mage::app()->getLayout()->getMessagesBlock()->addWarning(
                    Mage::helper('capita_ti')->__('This block identifier is currently being translated.'));
            }
        }
    }

    /**
     * Handler for controller_action_layout_render_before_adminhtml_cms_page_edit
     *
     * @param Varien_Event_Observer $observer
     */
    public function warnPageInProgress(Varien_Event_Observer $observer)
    {
        /* @var $page Mage_Cms_Model_Page */
        $page = Mage::registry('cms_page');
        if ($page && !$page->isObjectNew()) {
            /* @var $pages Mage_Cms_Model_Resource_Page_Collection */
            $pages = $page->getCollection();
            $pages->addFieldToFilter('identifier', $page->getIdentifier());

            /* @var $requests Capita_TI_Model_Resource_Request_Collection */
            $requests = Mage::getResourceModel('capita_ti/request_collection');
            $requests->addPageFilter($pages->getAllIds());
            $requests->addRemoteFilter();
            if ($requests->isTargettingStore($page->getStoreId())) {
                Mage::app()->getLayout()->getMessagesBlock()->addWarning(
                    Mage::helper('capita_ti')->__('This page identifier is currently being translated.'));
            }
        }
    }

    public function cronRefresh(Mage_Cron_Model_Schedule $schedule)
    {
        /* @var $client Capita_TI_Model_Api_Requests */
        $client = Mage::getModel('capita_ti/api_requests', array(
            'keepalive' => true
        ));
        /* @var $requests Capita_TI_Model_Resource_Request_Collection */
        $requests = Mage::getResourceModel('capita_ti/request_collection');
        $requests->addRemoteFilter();
        foreach ($requests as $request) {
            if ($request->canUpdate()) {
                $client->updateRequest($request);
            }
        }
    }

    public function cronImport(Mage_Cron_Model_Schedule $schedule)
    {
        /* @var $reader Capita_TI_Model_Xliff_Reader */
        $reader = Mage::getSingleton('capita_ti/xliff_reader');
        $reader->addType(Mage::getSingleton('capita_ti/xliff_import_product'));
        $reader->addType(Mage::getSingleton('capita_ti/xliff_import_category'));
        $reader->addType(Mage::getSingleton('capita_ti/xliff_import_block'));
        $reader->addType(Mage::getSingleton('capita_ti/xliff_import_page'));
        $reader->addType(Mage::getSingleton('capita_ti/xliff_import_attribute'));
        $reader->addType(Mage::getSingleton('capita_ti/xliff_import_attributeoption'));
        $varDir = Mage::getConfig()->getVarDir() . DS;

        /* @var $requests Capita_TI_Model_Resource_Request_Collection */
        $requests = Mage::getModel('capita_ti/request')->getCollection();
        $requests->addImportingFilter();

        /* @var $request Capita_TI_Model_Request */
        foreach ($requests as $request) {
            $reader->setRequest($request);
            try {
                /* @var $document Capita_TI_Model_Request_Document */
                foreach ($request->getDocuments() as $document) {
                    if ($document->getStatus() == 'importing') {
                        $filename = $varDir . $document->getLocalName();
                        $reader->import($filename, $document->getLanguage());
                        $document->setStatus('completed')->save();
                    }
                }
                $request->setStatus('completed')->save();
            }
            catch (Exception $e) {
                $request->setStatus('error')->save();
                // Mage_Cron already has a nice exception logging ability, let it handle this
                throw $e;
            }
        }
    }

    public function cronPurge(Mage_Cron_Model_Schedule $schedule)
    {
        /* @var $requests Capita_TI_Model_Resource_Request_Collection */
        $requests = Mage::getResourceModel('capita_ti/request_collection');
        $requests->addExpiredFilter();
        foreach ($requests as $request) {
            if ($request->canDelete()) {
                $request->delete();
            }
        }
    }
}
