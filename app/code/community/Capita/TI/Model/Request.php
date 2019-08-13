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

/**
 * @method int getProductCount()
 * @method int[] getProductIds()
 * @method int getCategoryCount()
 * @method int[] getCategoryIds()
 * @method int getBlockCount()
 * @method int[] getBlockIds()
 * @method int getPageCount()
 * @method int[] getPageIds()
 * @method int getAttributeCount()
 * @method int[] getAttributeIds()
 * @method string getDestLanguage()
 * @method string getCategoryAttributes()
 * @method string getProductAttributes()
 * @method string getSourceLanguage()
 * @method string getStatus()
 * @method Capita_TI_Model_Request_Document[] getDocuments()
 * @method Capita_TI_Model_Request setProductIds(int[])
 * @method Capita_TI_Model_Request setAttributeIds(int[])
 * @method Capita_TI_Model_Request setSourceLanguage(string)
 */
class Capita_TI_Model_Request extends Mage_Core_Model_Abstract
{

    protected function _construct()
    {
        $this->_init('capita_ti/request');
    }

    protected function _initOldFieldsMap()
    {
        $this->_oldFieldsMap = array(
            'RequestId' => 'remote_id',
            'RequestNo' => 'remote_no',
            'RequestStatus' => 'status',
            'Documents' => 'documents'
        );
        return $this;
    }

    public function getSourceLanguageName()
    {
        $languages = Mage::getSingleton('capita_ti/api_languages')->getLanguages();
        return @$languages[$this->getSourceLanguage()];
    }

    public function getDestLanguageName()
    {
        $languages = Mage::getSingleton('capita_ti/api_languages')->getLanguages();
        // $dests can be string or array of strings
        $dests = $this->getDestLanguage();
        $names = str_replace(
            array_keys($languages),
            array_values($languages),
            $dests);
        if (is_array($names)) {
            $names = implode(', ', $names);
        }
        else {
            $names = preg_replace('/,(?!=\w)/', ', ', $names);
        }
        return $names;
    }

    /**
     * Checks an ISO language code against this request's target languages
     * 
     * @param string $language
     * @return boolean
     */
    public function hasDestLanguage($language = null)
    {
        if (is_null($language)) {
            return parent::hasDestLanguage();
        }
        $destLanguage = $this->getDestLanguage();
        if (is_string($destLanguage)) {
            $destLanguage = explode(',', $destLanguage);
        }
        return in_array($language, $destLanguage);
    }

    /**
     * Converts from internal string to array of strings
     * 
     * @return string[]
     */
    public function getProductAttributesArray()
    {
        return explode(',', $this->getProductAttributes());
    }

    /**
     * Converts attribute codes to frontend labels, separated by commas
     * 
     * @return string
     */
    public function getProductAttributeNames()
    {
        if ($this->hasProductAttributeNames()) {
            return parent::getProductAttributeNames();
        }

        /* @var $attributes Mage_Catalog_Model_Resource_Product_Attribute_Collection */
        $attributes = Mage::getResourceModel('catalog/product_attribute_collection');
        $attributes->addFieldToFilter('attribute_code', array('in' => $this->getProductAttributesArray()));
        $names = implode(', ', $attributes->getColumnValues('frontend_label'));
        $this->setProductAttributeNames($names);
        return $names;
    }

    /**
     * Either a comma-separated string or array of strings
     * 
     * @param mixed $attributeCodes
     * @return Capita_TI_Model_Request
     */
    public function setProductAttributes($attributeCodes)
    {
        parent::setProductAttributes(
            is_array($attributeCodes) ?
            implode(',', $attributeCodes) :
            (string) $attributeCodes);
        $this->unsProductAttributeNames();
        return $this;
    }

    /**
     * Converts from internal string to array of strings
     * 
     * @return string[]
     */
    public function getCategoryAttributesArray()
    {
        return explode(',', $this->getCategoryAttributes());
    }

    /**
     * Converts attribute codes to frontend labels, separated by commas
     * 
     * @return string
     */
    public function getCategoryAttributeNames()
    {
        if ($this->hasCategoryAttributeNames()) {
            return parent::getCategoryAttributeNames();
        }

        /* @var $attributes Mage_Catalog_Model_Resource_Category_Attribute_Collection */
        $attributes = Mage::getResourceModel('catalog/category_attribute_collection');
        $attributes->addFieldToFilter('attribute_code', array('in' => $this->getCategoryAttributesArray()));
        $names = implode(', ', $attributes->getColumnValues('frontend_label'));
        $this->setCategoryAttributeNames($names);
        return $names;
    }

    /**
     * Either a comma-separated string or array of strings
     * 
     * @param mixed $attributeCodes
     * @return Capita_TI_Model_Request
     */
    public function setCategoryAttributes($attributeCodes)
    {
        parent::setCategoryAttributes(
            is_array($attributeCodes) ?
            implode(',', $attributeCodes) :
            (string) $attributeCodes);
        $this->unsCategoryAttributeNames();
        return $this;
    }

    public function getStatusLabel()
    {
        return Mage::getSingleton('capita_ti/source_status')->getOptionLabel($this->getStatus());
    }

    /**
     * True if request is not waiting on remote action
     * 
     * @return boolean
     */
    public function canDelete()
    {
        return !in_array($this->getStatus(), array('onHold', 'inProgress'));
    }

    /**
     * True if there is more to be learned from remote API
     * 
     * @return boolean
     */
    public function canUpdate()
    {
        // error isn't a remote status but updating can help overcome it
        return $this->getRemoteId() && in_array($this->getStatus(), array('onHold', 'inProgress', 'error'));
    }

    /**
     * Matches local filename to remote filename intelligently
     * 
     * If names are too dissimilar then a consistent order is
     * assumed and next available document is used.
     * 
     * @param string $filename
     */
    public function addLocalDocument($filename)
    {
        $documents = $this->getDocuments();
        foreach ($documents as &$document) {
            if ((basename($filename) == @$document['DocumentName']) || (basename($filename) == @$document['remote_name'])) {
                $document['local_name'] = $filename;
                $this->setDocuments($documents);
                return $this;
            }
        }

        // not found yet
        foreach ($documents as &$document) {
            if (!@$document['local_name']) {
                $document['local_name'] = $filename;
                $this->setDocuments($documents);
                return $this;
            }
        }

        // nothing to change
        return $this;
    }

    /**
     * What to do when a status changes?
     * 
     * It might mean downloading some files and importing them.
     * 
     * @param array $info Response decoded from API
     * @param Capita_TI_Model_Request_Document List of remote documents to download
     */
    public function updateStatus($info)
    {
        $newStatus = @$info['RequestStatus'];
        $documents = $this->getDocuments();
        foreach ($documents as &$document) {
            if ($document instanceof Capita_TI_Model_Request_Document) {
                $document->setStatus($newStatus);
            }
            else {
                $document['status'] = $newStatus;
            }
        }

        $downloads = array();
        if (($this->getStatus != 'completed') && ($newStatus == 'completed')) {
            // only care about nested arrays right now
            $remoteDocuments = call_user_func_array('array_merge_recursive', @$info['Documents']);
            $finalDocuments = (array) @$remoteDocuments['FinalDocuments'];

            foreach ($finalDocuments as $finalDocument) {
                $newdoc = Mage::getModel('capita_ti/request_document', $finalDocument);
                $filename = 'import'.DS.basename($newdoc->getRemoteName());
                // ensure directory exists
                Mage::getConfig()->getVarDir('import');
                $newdoc->setLocalName($filename)
                    ->setRequestId($this->getId());
                $downloads[] = $newdoc;
            }
        }

        $this->setDocuments(array_merge($documents, $downloads));
        $this->setStatus($newStatus);
        return $downloads;
    }
}
