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

class Capita_TI_Model_Resource_Request extends Mage_Core_Model_Resource_Db_Abstract
{

    protected function _construct()
    {
        $this->_init('capita_ti/request', 'request_id');
    }

    protected function _afterLoad(Mage_Core_Model_Abstract $request)
    {
        /* @var $documents Capita_TI_Model_Resource_Request_Document_Collection */
        $documents = Mage::getResourceModel('capita_ti/request_document_collection');
        $documents->addFieldToFilter($request->getIdFieldName(), $request->getId());
        $request->setDocuments($documents->getItems());

        // product IDs don't have their own model class
        $adapter = $this->_getReadAdapter();
        $select = $adapter->select();
        $select->from($this->getTable('capita_ti/product'), 'product_id')
            ->where('request_id=?', $request->getId());
        $request->setProductIds($adapter->fetchCol($select));

        // do the same for categories
        $select = $adapter->select();
        $select->from($this->getTable('capita_ti/category'), 'category_id')
            ->where('request_id=?', $request->getId());
        $request->setCategoryIds($adapter->fetchCol($select));

        $select = $adapter->select();
        $select->from($this->getTable('capita_ti/block'), 'block_id')
            ->where('request_id=?', $request->getId());
        $request->setBlockIds($adapter->fetchCol($select));

        $select = $adapter->select();
        $select->from($this->getTable('capita_ti/page'), 'page_id')
            ->where('request_id=?', $request->getId());
        $request->setPageIds($adapter->fetchCol($select));

        $select = $adapter->select();
        $select->from($this->getTable('capita_ti/attribute'), 'attribute_id')
            ->where('request_id=?', $request->getId());
        $request->setAttributeIds($adapter->fetchCol($select));

        return parent::_afterLoad($request);
    }

    protected function _beforeSave(Mage_Core_Model_Abstract $request)
    {
        if (is_array($request->getDestLanguage())) {
            $request->setDestLanguage(
                implode(',', $request->getDestLanguage())
            );
        }

        if (!$request->hasProductCount()) {
            $request->setProductCount(
                is_string($request->getProductIds()) ?
                substr_count($request->getProductIds(), ',') + 1 :
                count($request->getProductIds())
            );
        }

        if (!$request->hasCategoryCount()) {
            $request->setCategoryCount(
                is_string($request->getCategoryIds()) ?
                substr_count($request->getCategoryIds(), ',') + 1 :
                count($request->getCategoryIds())
            );
        }

        if (!$request->hasBlockCount()) {
            $request->setBlockCount(
                is_string($request->getBlockIds()) ?
                substr_count($request->getBlockIds(), ',') + 1 :
                count($request->getBlockIds())
            );
        }

        if (!$request->hasPageCount()) {
            $request->setPageCount(
                is_string($request->getPageIds()) ?
                substr_count($request->getPageIds(), ',') + 1 :
                count($request->getPageIds())
            );
        }

        if (!$request->hasAttributeCount()) {
            $request->setAttributeCount(
                is_string($request->getAttributeIds()) ?
                substr_count($request->getAttributeIds(), ',') + 1 :
                count($request->getAttributeIds())
            );
        }

        $request->setUpdatedAt($this->formatDate(true));

        return parent::_beforeSave($request);
    }

    protected function _afterSave(Mage_Core_Model_Abstract $request)
    {
        if ($request->dataHasChangedFor('documents')) {
            $this->_saveDocuments($request);
        }

        if ($request->dataHasChangedFor('product_ids')) {
            $this->_saveLinks($request, 'capita_ti/product', 'product_id');
        }

        if ($request->dataHasChangedFor('category_ids')) {
            $this->_saveLinks($request, 'capita_ti/category', 'category_id');
        }

        if ($request->dataHasChangedFor('block_ids')) {
            $this->_saveLinks($request, 'capita_ti/block', 'block_id');
        }

        if ($request->dataHasChangedFor('page_ids')) {
            $this->_saveLinks($request, 'capita_ti/page', 'page_id');
        }

        if ($request->dataHasChangedFor('attribute_ids')) {
            $this->_saveLinks($request, 'capita_ti/attribute', 'attribute_id');
        }

        return parent::_afterSave($request);
    }

    protected function _afterDelete(Mage_Core_Model_Abstract $request)
    {
        foreach ($request->getDocuments() as $document) {
            if (is_array($document)) {
                $document = Mage::getModel('capita_ti/request_document')
                    ->setData($document);
            }
            $document->delete();
        }

        return parent::_afterDelete($request);
    }

    protected function _saveDocuments(Capita_TI_Model_Request $request)
    {
        $documents = $request->getDocuments();
        /* @var $document Capita_TI_Model_Request_Document */
        foreach ($documents as &$document) {
            if (is_array($document)) {
                $document = Mage::getModel('capita_ti/request_document')
                    ->setData($document)
                    ->setRequestId($request->getId())
                    ->setStatus($request->getStatus());
            }
            $document->save();
        }
        $request->setData('documents', $documents)
            ->setOrigData('documents', $documents);
    }

    protected function _saveLinks(Capita_TI_Model_Request $request, $tableEntity, $idFieldName)
    {
        $linkTable = $this->getTable($tableEntity);
        $idsFieldName = $idFieldName . 's';
        $entityIds = $request->getData($idsFieldName);
        if (!is_array($entityIds)) {
            $entityIds = explode(',', (string) $entityIds);
        }
        
        $adapter = $this->_getWriteAdapter();
        $condition = sprintf(
            '(%s) AND (%s)',
            $adapter->prepareSqlCondition('request_id', $request->getId()),
            $adapter->prepareSqlCondition($idFieldName, array('nin' => $entityIds)));
        $adapter->delete($linkTable, $condition);
        
        $insertData = array();
        foreach ($entityIds as $entityId) {
            $insertData[] = array(
                'request_id' => $request->getId(),
                $idFieldName => $entityId
            );
        }
        $adapter->insertOnDuplicate($linkTable, $insertData);
        $request->setOrigData($idsFieldName, $entityIds);
    }
}
