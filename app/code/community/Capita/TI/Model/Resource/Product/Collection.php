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

class Capita_TI_Model_Resource_Product_Collection extends Mage_Catalog_Model_Resource_Product_Collection
{

    protected function _getTranslatableAttributeIds()
    {
        $attrCodes = explode(',', Mage::getStoreConfig('capita_ti/products/attributes'));
        $attrIds = array();
        $config = Mage::getSingleton('eav/config');
        foreach ($attrCodes as $attrCode) {
            $attrIds[] = (int) $config->getAttribute('catalog_product', $attrCode)->getId();
        }
        return $attrIds;
    }

    protected function _initSelect()
    {
        parent::_initSelect();
        $entityTable = $this->getEntity()->getEntityTable();
        $configTable = $this->getTable('core/config_data');
        $attributes = $this->_getTranslatableAttributeIds();
        $diffTable = $this->getTable('capita_ti/product_diff');

        // each subquery maps entity IDs to locale codes
        // TODO select media_gallery and media_gallery_value
        $textSelect = $this->getConnection()->select()
            ->distinct()
            ->from(array('values' => $entityTable.'_text'), 'entity_id')
            ->join(array('config' => $configTable), '(scope_id=store_id) AND (path="general/locale/code")', 'value')
            ->joinLeft(array('diff' => $diffTable), '(diff.entity_id=values.entity_id) AND (diff.language=config.value)', '')
            ->where('store_id > 0')
            ->where('attribute_id IN (?)', $attributes)
            ->where('old_md5 IS NULL');
        $varcharSelect = $this->getConnection()->select()
            ->distinct()
            ->from(array('values' => $entityTable.'_varchar'), 'entity_id')
            ->join(array('config' => $configTable), '(scope_id=store_id) AND (path="general/locale/code")', 'value')
            ->joinLeft(array('diff' => $diffTable), '(diff.entity_id=values.entity_id) AND (diff.language=config.value)', '')
            ->where('store_id > 0')
            ->where('attribute_id IN (?)', $attributes)
            ->where('old_md5 IS NULL');
        // subqueries have the same columns so can be unioned
        // UNION ALL is fastest option
        $unionSelect = $this->getConnection()->select()
            ->union(array($textSelect, $varcharSelect), Zend_Db_Select::SQL_UNION_ALL);
        // too many subqueries?.. nah
        $groupSelect = $this->getConnection()->select()
            ->from($unionSelect, array('entity_id', 'translated' => 'GROUP_CONCAT(DISTINCT value)'))
            ->group('entity_id');

        $this->getSelect()->joinLeft(
            array('locales' => $groupSelect),
            '(e.entity_id = locales.entity_id)',
            array('translated'));
        $this->_joinFields['translated'] = array(
            'table' => 'locales',
            'field' => 'translated'
        );
        return $this;
    }

    public function getSelectCountSql()
    {
        $countSelect = parent::getSelectCountSql();
        $countSelect->resetJoinLeft();
        return $countSelect;
    }
}
