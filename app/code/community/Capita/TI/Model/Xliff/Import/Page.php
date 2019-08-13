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

class Capita_TI_Model_Xliff_Import_Page extends Capita_TI_Model_Xliff_Import_Abstract
{

    public function getEntityType()
    {
        return Mage_Cms_Model_Page::CACHE_TAG;
    }

    public function import($id, $sourceLanguage, $destLanguage, $sourceData, $destData)
    {
        if ($this->getRequest()) {
            if (!in_array($id, $this->getRequest()->getPageIds())) {
                // prevent accidentally importing data which shouldn't be
                // perhaps it wasn't requested or the page was deleted afterwards
                return;
            }
            if (!in_array($destLanguage, $this->getRequest()->getDestLanguage())) {
                // was not expecting this language
                return;
            }
        }

        /* @var $origPage Mage_Cms_Model_Page */
        $origPage = Mage::getModel('cms/page')->load($id);
        if ($identifier = $origPage->getIdentifier()) {
            // do not change original page
            // create new page only for targetted stores and retire old one from those stores

            $destStores = Mage::helper('capita_ti')->getStoreIdsByLanguage($destLanguage);
            $newStores = $destStores;

            /* @var $transaction Mage_Core_Model_Resource_Transaction */
            $transaction = Mage::getResourceModel('core/transaction');

            /* @var $page Mage_Cms_Model_Page */
            /* @var $pages Mage_Cms_Model_Resource_Page_Collection */
            $pages = Mage::getResourceModel('cms/page_collection');
            $pages->addFieldToFilter('identifier', $identifier);
            foreach ($pages as $page) {
                // lookupStoreIds() is normally called in afterLoad but collection does not do it
                $pageStores = $page->getResource()->lookupStoreIds($page->getId());
                if ($pageStores == array(0)) {
                    // equivalent to "All Store Views"
                    $pageStores = array_keys(Mage::app()->getStores());
                }

                $exStores = array_diff($pageStores, $destStores);
                if ($exStores) {
                    // page cannot be translated without interfering with other locales
                    if ($pageStores != $exStores) {
                        // page must also be removed from targets
                        $page->setStores($exStores);
                        $transaction->addObject($page);
                    }
                    continue;
                }

                $inStores = array_diff($destStores, $pageStores);
                if ($inStores) {
                    // page covers at least one target
                    $page->setTitle(@$destData['title'])
                        ->setContent(@$destData['content'])
                        ->setContentHeading(@$destData['content_heading'])
                        ->setMetaDescription(@$destData['meta_description'])
                        ->setMetaKeywords(@$destData['meta_keywords']);
                    $transaction->addObject($page);
                }

                $newStores = array_diff($newStores, $inStores);
            }
            if ($newStores) {
                /* @var $newPage Mage_Cms_Model_Page */
                $newPage = Mage::getModel('cms/page');
                $newPage->setData($origPage->getData())
                    ->unsetData($newPage->getIdFieldName())
                    ->unsetData('creation_time')
                    ->unsetData('update_time')
                    ->setTitle(@$destData['title'])
                    ->setContent(@$destData['content'])
                    ->setContentHeading(@$destData['content_heading'])
                    ->setMetaDescription(@$destData['meta_description'])
                    ->setMetaKeywords(@$destData['meta_keywords'])
                    ->setStores($newStores);
                $transaction->addObject($newPage);
            }
            $transaction->save();
        }
    }
}
