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

class Capita_TI_Model_Tracker
{

    /**
     * Direct access to DB adapter
     * 
     * @return Mage_Core_Model_Resource
     */
    protected function getResource()
    {
        return Mage::getSingleton('core/resource');
    }

    /**
     * Direct access to DB adapter
     * 
     * @return Varien_Db_Adapter_Interface
     */
    protected function getConnection()
    {
        return $this->getResource()->getConnection('capita_ti_setup');
    }

    /**
     * A safe form of INSERT IGNORE with multiple rows
     * 
     * @param string $tableEntity
     * @param array $data 2D array of column => values
     */
    protected function insertRetire($tableEntity, $data)
    {
        if (!$data) {
            return;
        }

        $tableName = $this->getResource()->getTableName($tableEntity);
        $top = reset($data);
        unset($top['old_value'], $top['new_value']);
        $top['old_md5'] = true;
        $cols = array_keys($top);

        $inserts = array();
        $deletes = array();
        foreach ($data as $row) {
            $oldValue = @$row['old_value'];
            $newValue = @$row['new_value'];
            $row = array_intersect_key($row, $top);
            $row = array_merge($top, $row);

            $row['old_md5'] = md5($oldValue);
            $inserts[] = '('.implode(',', array_map(array($this->getConnection(), 'quote'), $row)).')';

            $row['old_md5'] = md5($newValue);
            $delete = array();
            foreach ($row as $col => $val) {
                $col = $this->getConnection()->quoteIdentifier($col);
                $val = $this->getConnection()->quote($val);
                $delete[] = '('.$col.'='.$val.')';
            }
            $deletes[] = '('.implode(' AND ', $delete).')';
        }

        $cols = array_map(array($this->getConnection(), 'quoteIdentifier'), $cols);
        $id = reset($cols);
        // actual INSERT IGNORE is dangerous because it ignores all errors
        // ON DUPLICATE KEY in this way is better because it only ignores key collisions
        $sql = 'INSERT INTO '.$this->getConnection()->quoteIdentifier($tableName, true);
        $sql .= ' ('.implode(',', $cols).') VALUES ';
        $sql .= implode(',', $inserts);
        $sql .= ' ON DUPLICATE KEY UPDATE '.$id.'='.$id;
        $this->getConnection()->query($sql);

        $where = implode(' OR ', $deletes);
        $this->getConnection()->delete($tableName, $where);
    }

    protected function deleteRecords($tableEntity, $condition)
    {
        $connection = $this->getConnection();
        $tableName = $this->getResource()->getTableName($tableEntity);
        $where = array();
        foreach ($condition as $col => $val) {
            $col = $connection->quoteIdentifier($col);
            if (is_array($val)) {
                $where[] = sprintf('%s IN (%s)', $col, $connection->quote($val));
            }
            else {
                $where[] = sprintf('%s LIKE %s', $col, $connection->quote($val));
            }
        }
        if ($where) {
            $where = implode(Zend_Db_Select::SQL_AND, $where);
            return $connection->delete($tableName, $where);
        }
        return false;
    }

    protected function watchEntity($tableEntity, Varien_Object $object, $attributes)
    {
        $values = array();
        $languages = Mage::helper('capita_ti')->getNonDefaultLocales();
        foreach ($attributes as $attribute) {
            if ($object->dataHasChangedFor($attribute)) {
                foreach ($languages as $language) {
                    $values[] = array(
                        $object->getIdFieldName() => $object->getId(),
                        'language' => $language,
                        'attribute' => $attribute,
                        'old_value' => $object->getOrigData($attribute),
                        'new_value' => $object->getData($attribute)
                    );
                }
            }
        }
        $this->insertRetire($tableEntity, $values);
    }

    public function blockSaveAfter(Varien_Event_Observer $observer)
    {
        /* @var $block Mage_Cms_Model_Block */
        $block = $observer->getObject();
        $stores = array(
            Mage_Core_Model_App::ADMIN_STORE_ID,
            Mage::app()->getDefaultStoreView()->getId()
        );
        if ($block && array_intersect($block->getStores(), $stores)) {
            $this->watchEntity(
                'capita_ti/block_diff',
                $block,
                array('title', 'content'));
            return $this;
        }
    }

    public function categorySaveAfter(Varien_Event_Observer $observer)
    {
        $category = $observer->getCategory();
        if ($category && !$category->getStoreId()) {
            $attributes = Mage::getSingleton('capita_ti/source_category_attributes')->getBestAttributes();
            $this->watchEntity(
                'capita_ti/category_diff',
                $observer->getCategory(),
                $attributes->getColumnValues('attribute_code'));
        }
    }

    public function pageSaveAfter(Varien_Event_Observer $observer)
    {
        /* @var $page Mage_Cms_Model_Page */
        $page = $observer->getObject();
        $stores = array(
            Mage_Core_Model_App::ADMIN_STORE_ID,
            Mage::app()->getDefaultStoreView()->getId()
        );
        if ($page && array_intersect($page->getStores(), $stores)) {
            $this->watchEntity(
                'capita_ti/page_diff',
                $observer->getObject(),
                array('title', 'meta_keywords', 'meta_description', 'content_heading', 'content'));
        }
    }

    public function productSaveAfter(Varien_Event_Observer $observer)
    {
        $product = $observer->getProduct();
        // only changes in global scope
        if ($product && !$product->getStoreId()) {
            $attributes = Mage::getSingleton('capita_ti/source_product_attributes')->getBestAttributes();
            $this->watchEntity(
                'capita_ti/product_diff',
                $product,
                $attributes->getColumnValues('attribute_code'));
        }
    }

    public function modelSaveAfter(Varien_Event_Observer $observer)
    {
        // CMS Blocks do not set an event_prefix so cannot dispatch a specific event
        // test if generic event contains a block and pass to appropriate handler
        if ($observer->getObject() instanceof Mage_Cms_Model_Block) {
            $this->blockSaveAfter($observer);
        }
    }

    public function attributeSaveAfter(Varien_Event_Observer $observer)
    {
        /* @var $attribute Mage_Catalog_Model_Entity_Attribute */
        $attribute = $observer->getAttribute();
        $languages = Mage::helper('capita_ti')->getNonDefaultLocales();
        $values = array();

        $oldLabel = $attribute->getOrigData('frontend_label');
        $newLabel = $attribute->getData('frontend_label');
        if (is_array($newLabel)) {
            $newLabel = $newLabel[0];
        }
        if ($oldLabel != $newLabel) {
            foreach ($languages as $language) {
                $values[] = array(
                    'attribute_id' => $attribute->getAttributeId(),
                    'language' => $language,
                    'attribute' => 'frontend_label',
                    'old_value' => $oldLabel,
                    'new_value' => $newLabel
                );
            }
        }

        $options = $attribute->getOption();
        $newValues = array();
        foreach ((array) @$options['value'] as $optionId => $option) {
            $newValues[$optionId] = $option[0];
        }
        $oldValues = Mage::getResourceModel('eav/entity_attribute_option_collection')
            ->setAttributeFilter($attribute->getAttributeId())
            ->setStoreFilter()
            ->walk('getValue');
        foreach (array_diff_assoc($newValues, $oldValues) as $optionId => $newValue) {
            foreach ($languages as $language) {
                $values[] = array(
                    'attribute_id' => $attribute->getAttributeId(),
                    'language' => $language,
                    'attribute' => 'option_'.$optionId,
                    'old_value' => $oldValues[$optionId],
                    'new_value' => $newValue
                );
            }
        }

        $this->insertRetire('capita_ti/attribute_diff', $values);
    }

    public function endWatch(Capita_TI_Model_Request $request)
    {
        $languages = explode(',', $request->getDestLanguage());
        if ($request->getProductIds() && $request->getProductAttributes()) {
            $this->deleteRecords(
                'capita_ti/product_diff',
                array(
                    'entity_id' => $request->getProductIds(),
                    'language'  => $languages,
                    'attribute' => $request->getProductAttributesArray()
                ));
        }
        if ($request->getCategoryIds() && $request->getCategoryAttributes()) {
            $this->deleteRecords(
                'capita_ti/category_diff',
                array(
                    'entity_id' => $request->getCategoryIds(),
                    'language'  => $languages,
                    'attribute' => $request->getCategoryAttributesArray()
                ));
        }
        if ($request->getBlockIds()) {
            $this->deleteRecords(
                'capita_ti/block_diff',
                array(
                    'block_id' => $request->getBlockIds(),
                    'language'  => $languages
                ));
        }
        if ($request->getPageIds()) {
            $this->deleteRecords(
                'capita_ti/page_diff',
                array(
                    'page_id' => $request->getPageIds(),
                    'language'  => $languages
                ));
        }
        if ($request->getAttributeIds()) {
            $this->deleteRecords(
                'capita_ti/attribute_diff',
                array(
                    'attribute_id' => $request->getAttributeIds(),
                    'language'  => $languages
                ));
        }
        return $this;
    }
}
