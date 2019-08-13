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

class Capita_TI_Model_Api_Requests extends Capita_TI_Model_Api_Abstract
{

    protected function getCustomerName()
    {
        return Mage::getStoreConfig('capita_ti/authentication/customer_name');
    }

    protected function getContactName()
    {
        return Mage::getStoreConfig('capita_ti/authentication/contact_name');
    }

    public function __construct($config = null)
    {
        if (!@$config['adapter']) {
            // libcurl is faster but breaks on streaming large downloads
            $config['adapter'] = 'Zend_Http_Client_Adapter_Socket';
        }
        if (!@$config['timeout']) {
            $config['timeout'] = 120;
        }

        parent::__construct($this->getEndpoint('requests'), $config);
    }

    /**
     * Writes entities to a file, uploads it to API, and returns an object which describes it.
     * 
     * @param Zend_Controller_Request_Abstract $input
     * @throws Mage_Adminhtml_Exception
     * @throws Zend_Http_Exception
     * @return Capita_TI_Model_Request
     */
    public function startNewRequest(Zend_Controller_Request_Abstract $input)
    {
        $sourceLanguage = $input->getParam('source_language', Mage::getStoreConfig('general/locale/code'));
        $destLanguage = implode(',', $input->getParam('dest_language'));
        $this->setParameterPost('CustomerName', $this->getCustomerName());
        $this->setParameterPost('ContactName', $this->getContactName());
        $this->setParameterPost('SourceLanguageCode', $sourceLanguage);
        $this->setParameterPost('TargetLanguageCodes', $destLanguage);

        // any future date will probably do
        // API demands a date but doesn't use it
        $nextWeek = new Zend_Date();
        $nextWeek->addWeek(1);
        $this->setParameterPost('DeliveryDate', $nextWeek->toString('y-MM-d HH:mm:ss'));

        // now for the main content
        $productIds = $input->getParam('product_ids', '');
        $productIds = array_filter(array_unique(explode('&', $productIds)));
        $productAttributes = $input->getParam('product_attributes', array());
        /* @var $products Mage_Catalog_Model_Resource_Product_Collection */
        $products = Mage::getResourceModel('catalog/product_collection');
        $products->addIdFilter($productIds);
        $products->addAttributeToSelect($productAttributes);

        $attributeIds = $input->getParam('attribute_ids', '');
        $attributeIds = array_filter(array_unique(explode('&', $attributeIds)));
        /* @var $attributes Mage_Eav_Model_Resource_Entity_Attribute_Collection */
        $attributes = Mage::getResourceModel('eav/entity_attribute_collection');
        $attributes->addFieldToFilter('attribute_id', array('in' => $attributeIds));
        /* @var $attributeOptions Mage_Eav_Model_Resource_Entity_Attribute_Option_Collection */
        $attributeOptions = Mage::getResourceModel('eav/entity_attribute_option_collection');
        $attributeOptions->setAttributeFilter(array('in' => $attributeIds));
        // joins the values for global store
        $attributeOptions->setStoreFilter();

        $categoryIds = $input->getParam('category_ids', '');
        $categoryIds = array_filter(array_unique(explode('&', $categoryIds)));
        $categoryAttributes = $input->getParam('category_attributes', array());
        /* @var $categories Mage_Catalog_Model_Resource_Category_Collection */
        $categories = Mage::getResourceModel('catalog/category_collection');
        $categories->addIdFilter($categoryIds);
        $categories->addAttributeToSelect($categoryAttributes);

        $blockIds = $input->getParam('block_ids', '');
        $blockIds = array_filter(array_unique(explode('&', $blockIds)));
        /* @var $blocks Mage_Cms_Model_Resource_Block_Collection */
        $blocks = Mage::getResourceModel('cms/block_collection');
        $blocks->addFieldToFilter('block_id', array('in' => $blockIds));

        $pageIds = $input->getParam('page_ids', '');
        $pageIds = array_filter(array_unique(explode('&', $pageIds)));
        /* @var $pages Mage_Cms_Model_Resource_Page_Collection */
        $pages = Mage::getResourceModel('cms/page_collection');
        $pages->addFieldToFilter('page_id', array('in' => $pageIds));

        if (!$productIds && !$attributeIds && !$categoryIds && !$blockIds && !$pageIds) {
            throw new InvalidArgumentException(
                Mage::helper('capita_ti')->__('Must specify at least one product, category, block or page'));
        }

        /* @var $newRequest Capita_TI_Model_Request */
        $newRequest = Mage::getModel('capita_ti/request');
        $newRequest
            ->setSourceLanguage($sourceLanguage)
            ->setDestLanguage($destLanguage)
            ->setProductAttributes($productAttributes)
            ->setProductIds($productIds)
            ->setAttributeIds($attributeIds)
            ->setCategoryIds($categoryIds)
            ->setCategoryAttributes($categoryAttributes)
            ->setBlockIds($blockIds)
            ->setPageIds($pageIds);

        $varDir = Mage::getConfig()->getVarDir('export') . DS;
        if (!$varDir) {
            throw new Mage_Adminhtml_Exception(Mage::helper('capita_ti')->__('Cannot write to "%s"', $varDir));
        }
        $filenames = array();
        $absFilenames = array();
        foreach (explode(',', $destLanguage) as $language) {
            $language = strtr($language, '_', '-');
            $filenames[$language] = sprintf(
                'capita-ti-%d-%s.mgxliff',
                time(),
                $language);
            $absFilenames[$language] = $varDir.$filenames[$language];
        }

        /* @var $output Capita_TI_Model_Xliff_Writer */
        $output = Mage::getModel('capita_ti/xliff_writer');
        $output->addCollection(Mage_Catalog_Model_Product::ENTITY, $products, $newRequest->getProductAttributesArray());
        $output->addCollection('eav_attribute', $attributes, array('frontend_label'));
        $output->addCollection('eav_attribute_option', $attributeOptions, array('value'));
        $output->addCollection(Mage_Catalog_Model_Category::ENTITY, $categories, $newRequest->getCategoryAttributesArray());
        $output->addCollection(Mage_Cms_Model_Block::CACHE_TAG, $blocks, array('title', 'content'));
        $output->addCollection(Mage_Cms_Model_Page::CACHE_TAG, $pages, array('title', 'content', 'content_heading', 'meta_keywords', 'meta_description'));
        $output->setSourceLanguage($sourceLanguage);
        $output->output($absFilenames);
        foreach ($absFilenames as $absFilename) {
            $this->setFileUpload($absFilename, 'files');
        }
        $response = $this->decode($this->request(self::POST));

        $newRequest->addData($response);
        foreach ($filenames as $filename) {
            $newRequest->addLocalDocument('export'.DS.$filename);
        }
        // remove notice of changed fields now that they're being translated
        Mage::getSingleton('capita_ti/tracker')->endWatch($newRequest);
        return $newRequest;
    }

    /**
     * Retrieve latest request info from remote
     * 
     * If there are several updates to process it helps to set keepalive on this client.
     * 
     * @param Capita_TI_Model_Request $request
     */
    public function updateRequest(Capita_TI_Model_Request $request)
    {
        $path = 'request/'.urlencode($request->getRemoteId());
        $this->setUri($this->getEndpoint($path));
        try {
            $response = $this->decode($this->request(self::GET));

            // downloads might be empty
            $downloads = $request->updateStatus($response);
            $varDir = Mage::getConfig()->getVarDir() . DS;
            /* @var $document Capita_TI_Model_Request_Document */
            foreach ($downloads as $document) {
                $uri = $this->getEndpoint('document/'.$document->getRemoteId());
                $this->setUri($uri)
                    ->setStream($varDir . $document->getLocalName())
                    ->request(self::GET);
                $document->setStatus('importing');
                $request->setStatus('importing');
            }
    
            // also saves all documents
            $request->save();
        }
        catch (Zend_Http_Exception $e) {
            // 404 means probably cancelled, delete our record of it
            if ($e->getCode() == 404) {
                $request->delete();
            }
            else {
                throw $e;
            }
        }
    }
}
