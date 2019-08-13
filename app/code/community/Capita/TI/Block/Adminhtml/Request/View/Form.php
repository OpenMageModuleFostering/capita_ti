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

class Capita_TI_Block_Adminhtml_Request_View_Form extends Mage_Adminhtml_Block_Widget_Form
{

    protected function _prepareForm()
    {
        $form = new Varien_Data_Form();
        /* @var $request Capita_TI_Model_Request */
        $request = Mage::registry('capita_request');

        $this->_addGeneralData($form, $request);

        if ($request->getProductCount()) {
            $this->_addProductData($form, $request);
        }

        if ($request->getCategoryCount()) {
            $this->_addCategoryData($form, $request);
        }

        if ($request->getBlockCount()) {
            $this->_addBlockData($form, $request);
        }

        if ($request->getPageCount()) {
            $this->_addPageData($form, $request);
        }

        if ($request->getAttributeCount()) {
            $this->_addAttributeData($form, $request);
        }

        $this->setForm($form);
        return parent::_prepareForm();
    }

    protected function _addGeneralData(Varien_Data_Form $form, Capita_TI_Model_Request $request)
    {
        $general = $form->addFieldset('general', array(
            'legend' => $this->__('General')
        ));
        $general->addField('status', 'label', array(
            'label' => $this->__('Status'),
            'value' => $request->getStatusLabel()
        ));
        $general->addField('source_language', 'label', array(
            'label' => $this->__('Source Language'),
            'value' => $request->getSourceLanguageName()
        ));
        $general->addField('dest_language', 'label', array(
            'label' => $this->__('Requested Languages'),
            'value' => $request->getDestLanguageName()
        ));
        $general->addField('created_at', 'label', array(
            'label' => $this->__('Submission Date'),
            'value' => Mage::app()->getLocale()->date($request->getCreatedAt(), Varien_Date::DATETIME_INTERNAL_FORMAT)
        ));
        $general->addField('updated_at', 'label', array(
            'label' => $this->__('Last Updated'),
            'value' => Mage::app()->getLocale()->date($request->getUpdatedAt(), Varien_Date::DATETIME_INTERNAL_FORMAT)
        ));
        return $general;
    }

    protected function _addProductData(Varien_Data_Form $form, Capita_TI_Model_Request $request)
    {
        $products = $form->addFieldset('products', array(
            'legend' => $this->__('Products')
        ));
        $products->addField('product_count', 'label', array(
            'label' => $this->__('Number of products selected'),
            'value' => $request->getProductCount()
        ));
        $products->addField('product_attributes', 'label', array(
            'label' => $this->__('Product Attributes'),
            'value' => $request->getProductAttributeNames()
        ));

        return $products;
    }

    protected function _addCategoryData(Varien_Data_Form $form, Capita_TI_Model_Request $request)
    {
        $categories = $form->addFieldset('categories', array(
            'legend' => $this->__('Categories')
        ));
        $categories->addField('category_count', 'label', array(
            'label' => $this->__('Number of categories selected'),
            'value' => $request->getCategoryCount()
        ));
        $categories->addField('category_attributes', 'label', array(
            'label' => $this->__('Category Attributes'),
            'value' => $request->getCategoryAttributeNames()
        ));

        return $categories;
    }

    protected function _addBlockData(Varien_Data_Form $form, Capita_TI_Model_Request $request)
    {
        $blocks = $form->addFieldset('blocks', array(
            'legend' => $this->__('Blocks')
        ));
        $blocks->addField('block_count', 'label', array(
            'label' => $this->__('Number of blocks selected'),
            'value' => $request->getBlockCount()
        ));

        return $categories;
    }

    protected function _addPageData(Varien_Data_Form $form, Capita_TI_Model_Request $request)
    {
        $pages = $form->addFieldset('pages', array(
            'legend' => $this->__('Pages')
        ));
        $pages->addField('page_count', 'label', array(
            'label' => $this->__('Number of pages selected'),
            'value' => $request->getPageCount()
        ));

        return $categories;
    }

    protected function _addAttributeData(Varien_Data_Form $form, Capita_TI_Model_Request $request)
    {
        $attributes = $form->addFieldset('attributes', array(
            'legend' => $this->__('Attributes')
        ));
        $attributes->addField('attribute_count', 'label', array(
            'label' => $this->__('Number of attributes selected'),
            'value' => $request->getAttributeCount()
        ));

        return $categories;
    }
}
