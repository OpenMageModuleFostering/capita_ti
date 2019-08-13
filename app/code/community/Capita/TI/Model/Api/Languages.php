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

class Capita_TI_Model_Api_Languages extends Capita_TI_Model_Api_Abstract
{

    protected $languages;

    public function __construct($config = null)
    {
        parent::__construct($this->getEndpoint('languages'), $config);
    }

    /**
     * Get available languages for this TI account
     * 
     * @return array
     */
    public function getLanguages()
    {
        if (isset($this->languages)) {
            return $this->languages;
        }

        // check recent cache
        $cacheId = 'capita_ti_languages_'.$this->getUsername();
        try {
            $data = Zend_Json::decode(Mage::app()->loadCache($cacheId));
        } catch (Zend_Json_Exception $e) {
            // cache was empty or corrupted
            $data = null;
        }

        // fallback to remote source
        if (!is_array($data)) {
            $response = $this->request();
            $data = $this->decode($response);
            // if exception throws here then cache is not written
            $cacheTags = array(
                // clear on "Flush Magento Cache"
                Mage_Core_Model_APP::CACHE_TAG,
                // clear with "Collection Data"
                Mage_Core_Model_Resource_Db_Collection_Abstract::CACHE_TAG
            );
            Mage::app()->saveCache($response->getBody(), $cacheId, $cacheTags, 3600);
        }

        // convert to Magento/Zend convention
        if (is_array($data)) {
            $this->languages = array();
            foreach ($data as $language) {
                $code = strtr(@$language['IsoCode'], '-', '_');
                $name = @$language['LanguageName'];
                $this->languages[$code] = $name;
            }
            return $this->languages;
        }

        // worst case scenario, no content but still traversable
        return array();
    }

    /**
     * Override list of languages with local fixed list. Useful for fallbacks.
     * 
     * @return Capita_TI_Model_Api_Languages
     */
    public function setLocalLanguages()
    {
        $this->languages = array();
        foreach (Mage::app()->getLocale()->getOptionLocales() as $locale) {
            $this->languages[@$locale['value']] = @$locale['label'];
        }
        return $this;
    }

    public function getLanguagesInUse($includeGlobal = true)
    {
        $codes = array();
        /* @var $store Mage_Core_Model_Store */
        foreach (Mage::app()->getStores() as $store) {
            $code = (string) $store->getConfig('general/locale/code');
            $codes[$code] = true;
        }
        if (!$includeGlobal) {
            unset($codes[Mage::getStoreConfig('general/locale/code')]);
        }
        $languages = $this->getLanguages();
        return array_intersect_key($languages, $codes);
    }

    /**
     * Successful response is cached transparently, this explicitly clears it.
     * 
     * Currently only languages are cacheable.
     * If this changes then consider moving this function to parent class.
     * 
     * @return Capita_TI_Model_Api_Languages
     */
    public function clearCache()
    {
        $cacheId = 'capita_ti_languages_'.$this->getUsername();
        Mage::app()->removeCache($cacheId);
        return $this;
    }
}
