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

class Capita_TI_Model_Resource_Request_Collection extends Mage_Core_Model_Resource_Db_Collection_Abstract
{

    protected function _construct()
    {
        $this->_init('capita_ti/request');
    }

    protected function _initSelect()
    {
        parent::_initSelect();
        $this->getSelect()
            ->joinLeft(
                array('productlist' => $this->getConnection()->select()
                    ->from($this->getTable('capita_ti/product'),
                        array('request_id', 'product_ids' => 'GROUP_CONCAT(DISTINCT product_id)'))
                    ->group('request_id')),
                'main_table.request_id=productlist.request_id',
                'product_ids')
            ->joinLeft(
                array('categorylist' => $this->getConnection()->select()
                    ->from($this->getTable('capita_ti/category'),
                        array('request_id', 'category_ids' => 'GROUP_CONCAT(DISTINCT category_id)'))
                    ->group('request_id')),
                'main_table.request_id=categorylist.request_id',
                'category_ids')
            ->joinLeft(
                array('blocklist' => $this->getConnection()->select()
                    ->from($this->getTable('capita_ti/block'),
                        array('request_id', 'block_ids' => 'GROUP_CONCAT(DISTINCT block_id)'))
                    ->group('request_id')),
                'main_table.request_id=blocklist.request_id',
                'block_ids')
            ->joinLeft(
                array('pagelist' => $this->getConnection()->select()
                    ->from($this->getTable('capita_ti/page'),
                        array('request_id', 'page_ids' => 'GROUP_CONCAT(DISTINCT page_id)'))
                    ->group('request_id')),
                'main_table.request_id=pagelist.request_id',
                'page_ids')
            ->joinLeft(
                array('attributelist' => $this->getConnection()->select()
                    ->from($this->getTable('capita_ti/attribute'),
                        array('request_id', 'attribute_ids' => 'GROUP_CONCAT(DISTINCT attribute_id)'))
                    ->group('request_id')),
                'main_table.request_id=attributelist.request_id',
                'attribute_ids');
            return $this;
    }

    public function getSelectCountSql()
    {
        // undo effects of _initSelect() above
        $select = parent::getSelectCountSql();
        $select->reset(Zend_Db_Select::GROUP);
        $select->resetJoinLeft();
        return $select;
    }

    protected function _afterLoad()
    {
        if ($this->count()) {
            /* @var $documents Capita_TI_Model_Resource_Request_Document_Collection */
            $documents = Mage::getResourceModel('capita_ti/request_document_collection');
            $documents->addFieldToFilter('request_id', array('in' => array_keys($this->_items)));

            foreach ($this as $request) {
                if ($request->hasDestLanguage()) {
                    $request->setDestLanguage(
                        explode(',', $request->getDestLanguage())
                    );
                }

                $reqdocs = array();
                foreach ($documents as $document) {
                    if ($document->getRequestId() == $request->getId()) {
                        $reqdocs[$document->getId()] = $document;
                    }
                }
                $request->setDocuments($reqdocs);

                $request->setProductIds(array_filter(explode(',', $request->getProductIds())));
                $request->setCategoryIds(array_filter(explode(',', $request->getCategoryIds())));
                $request->setBlockIds(array_filter(explode(',', $request->getBlockIds())));
                $request->setPageIds(array_filter(explode(',', $request->getPageIds())));
                $request->setAttributeIds(array_filter(explode(',', $request->getAttributeIds())));
            }
        }

        return parent::_afterLoad();
    }

    /**
     * Allows convenient searching of comma separated lists
     * 
     * Because destination language is one text field it can be searched with
     * a simple LIKE clause.
     * Used by adminhtml grid with a select control so input is limited.
     * Input is still escaped properly.
     * 
     * @param string $language
     * @return Capita_TI_Model_Resource_Request_Collection
     */
    public function addFilterLikeLanguage($language)
    {
        // addFieldToFilter escapes values for us
        $this->addFieldToFilter(
            'dest_language',
            array('like' => '%'.$language.'%'));
        return $this;
    }

    /**
     * Restrict to records with a status indicating a remote job
     * 
     * @return Capita_TI_Model_Resource_Request_Collection
     */
    public function addRemoteFilter()
    {
        $this->addFieldToFilter('status', array('in' => array('onHold', 'inProgress')));
        return $this;
    }

    /**
     * Restrict to records with a status == 'importing'
     * 
     * @return Capita_TI_Model_Resource_Request_Collection
     */
    public function addImportingFilter()
    {
        $this->addFieldToFilter('status', 'importing');
        return $this;
    }

    /**
     * Restrict to records no longer required and at least 1 day old
     * 
     * @return Capita_TI_Model_Resource_Request_Collection
     */
    public function addExpiredFilter()
    {
        $this->addFieldToFilter('status', array('in' => array('completed', 'error')));
        $date = Zend_Date::now();
        $date->subDay(1);
        $this->addFieldToFilter('updated_at', array('lt' => $this->formatDate($date)));
        return $this;
    }

    public function addProductFilter($productId)
    {
        if ($productId instanceof Varien_Object) {
            $productId = $productId->getId();
        }
        $this->join(
            array('products' => 'capita_ti/product'),
            'main_table.request_id=products.request_id',
            '');
        $this->addFieldToFilter(
            'products.product_id',
            is_array($productId) ? array('in' => $productId) : $productId);
        return $this;
    }

    public function addCategoryFilter($categoryId)
    {
        if ($categoryId instanceof Varien_Object) {
            $categoryId = $categoryId->getId();
        }
        $this->join(
            array('categories' => 'capita_ti/category'),
            'main_table.request_id=categories.request_id',
            '');
        $this->addFieldToFilter('categories.category_id', $categoryId);
        return $this;
    }

    public function addBlockFilter($blockId)
    {
        if ($blockId instanceof Varien_Object) {
            $blockId = $blockId->getId();
        }
        $this->join(
            array('blocks' => 'capita_ti/block'),
            'main_table.request_id=blocks.request_id',
            '');
        $this->addFieldToFilter(
            'blocks.block_id',
            is_array($blockId) ? array('in' => $blockId) : $blockId);
        return $this;
    }

    public function addPageFilter($pageId)
    {
        if ($pageId instanceof Varien_Object) {
            $pageId = $pageId->getId();
        }
        $this->join(
            array('pages' => 'capita_ti/page'),
            'main_table.request_id=pages.request_id',
            '');
        $this->addFieldToFilter(
            'pages.page_id',
            is_array($pageId) ? array('in' => $pageId) : $pageId);
        return $this;
    }

    public function isTargettingStore($storeIds)
    {
        // if no stores specified then we don't care and any store is valid
        // assume any request matches
        if (!$storeIds) {
            return $this->getSize() > 0;
        }

        if (is_string($storeIds)) {
            $storeIds = explode(',', $storeIds);
        }
        else {
            $storeIds = (array) $storeIds;
        }

        $languages = array();
        foreach ($storeIds as $storeId) {
            $languages[] = Mage::getStoreConfig('general/locale/code', $storeId);
        }
        $languages = array_unique($languages);

        foreach ($this as $request) {
            foreach ($languages as $language) {
                if ($request->hasDestLanguage($language)) {
                    // only need one match
                    return true;
                }
            }
        }

        return false;
    }
}
