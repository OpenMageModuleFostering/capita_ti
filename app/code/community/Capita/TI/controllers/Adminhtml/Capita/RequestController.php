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

class Capita_TI_Adminhtml_Capita_RequestController extends Capita_TI_Controller_Action
{

    const MENU_PATH = 'system/capita_request';

    public function preDispatch()
    {
        parent::preDispatch();

        // might be called on any page, even by AJAX
        // can be used to refresh all or just one
        if ($this->getRequest()->getParam('refresh') == 'status') {
            try {
                $id = $this->getRequest()->getParam('id');
                /* @var $client Capita_TI_Model_Api_Requests */
                $client = Mage::getModel('capita_ti/api_requests', array(
                    'keepalive' => true
                ));
                if ($id) {
                    /* @var $request Capita_TI_Model_Request */
                    $request = Mage::getModel('capita_ti/request')->load($id);
                    if ($request->canUpdate()) {
                        $client->updateRequest($request);
                    }
                }
                else {
                    /* @var $requests Capita_TI_Model_Resource_Request_Collection */
                    $requests = Mage::getResourceModel('capita_ti/request_collection');
                    $requests->addRemoteFilter();
                    foreach ($requests as $request) {
                        if ($request->canUpdate()) {
                            $client->updateRequest($request);
                        }
                    }
                }
            }
            catch (Exception $e) {
                $this->_getSession()->addException($e, $this->__('There was a problem connecting to the server: %s', $e->getMessage()));
            }
        }

        return $this;
    }

    public function indexAction()
    {
        $this->loadLayout();
        $this->_checkConnection();
        $this->_title($this->__('Capita Translations'))
            ->_title($this->__('Requests'))
            ->_setActiveMenu(self::MENU_PATH);
        $this->renderLayout();
    }

    public function gridAction()
    {
        $this->loadLayout(array('default', 'adminhtml_capita_request_index'));
        $this->getResponse()->setBody(
            $this->getLayout()->getBlock('adminhtml_request.grid')->toHtml());
    }

    public function newAction()
    {
        $this->loadLayout();
        $this->_checkConnection();
        $this->_title($this->__('Capita Translations'))
            ->_title($this->__('New Request'))
            ->_setActiveMenu(self::MENU_PATH);
        $this->renderLayout();
    }

    public function productsTabAction()
    {
        $this->loadLayout();
        $this->renderLayout();
    }

    public function productsGridAction()
    {
        $this->loadLayout();
        $this->getLayout()->getBlock('request_tab_products_grid')->setEntityIds(
            $this->getRequest()->getParam('product_ids', array()));
        $this->renderLayout();
    }

    public function attributesTabAction()
    {
        $this->loadLayout();
        $this->renderLayout();
    }

    public function attributesGridAction()
    {
        $this->loadLayout();
        $this->getLayout()->getBlock('request_tab_attributes_grid')->setEntityIds(
            $this->getRequest()->getParam('attribute_ids', array()));
        $this->renderLayout();
    }

    public function categoriesTabAction()
    {
        $this->loadLayout();
        $this->renderLayout();
    }

    public function categoriesGridAction()
    {
        $this->loadLayout();
        $this->getLayout()->getBlock('request_tab_categories_grid')->setEntityIds(
            $this->getRequest()->getParam('category_ids', array()));
        $this->renderLayout();
    }

    public function blocksTabAction()
    {
        $this->loadLayout();
        $this->renderLayout();
    }

    public function blocksGridAction()
    {
        $this->loadLayout();
        $this->getLayout()->getBlock('request_tab_blocks_grid')->setEntityIds(
            $this->getRequest()->getParam('block_ids', array()));
        $this->renderLayout();
    }

    public function pagesTabAction()
    {
        $this->loadLayout();
        $this->renderLayout();
    }

    public function pagesGridAction()
    {
        $this->loadLayout();
        $this->getLayout()->getBlock('request_tab_pages_grid')->setEntityIds(
            $this->getRequest()->getParam('page_ids', array()));
        $this->renderLayout();
    }

    public function deleteAction()
    {
        try {
            $requestId = $this->getRequest()->getParam('id');
            /* @var $request Capita_TI_Model_Request */
            $request = Mage::getModel('capita_ti/request')->load($requestId);
            if (!$request->isObjectNew() && $request->canDelete()) {
                $request->delete();
                $this->_getSession()->addSuccess($this->__('Request "%s" has been deleted', $request->getRemoteNo()));
            }
        }
        catch (Exception $e) {
            Mage::logException($e);
            $this->_getSession()->addError($e->getMessage());
        }
        $this->_redirect('*/*');
    }

    public function saveAction()
    {
        try {
            /* @var $requests Capita_TI_Model_Api_Requests */
            $requests = Mage::getModel('capita_ti/api_requests', array(
                // enable following line to test without submitting to real API
//                 'adapter' => Mage::getModel('capita_ti/api_adapter_samplePostRequest')
            ));
            $request = $requests->startNewRequest($this->getRequest());
            $request->save();
            $this->_getSession()->unsCapitaProductIds();
            $this->_getSession()->addSuccess($this->__('Request "%s" has been started', $request->getRemoteNo()));

            Mage::getModel('capita_ti/email')->sendFirstUse();

            $this->_redirect('*/*');
        }
        catch (Exception $e) {
            Mage::logException($e);
            $this->_getSession()->addError($e->getMessage());
            $this->_redirectReferer($this->getUrl('*/*'));
        }
    }

    public function viewAction()
    {
        try {
            $requestId = (int) $this->getRequest()->getParam('id');
            $request = Mage::getModel('capita_ti/request')->load($requestId);
            if ($request->isObjectNew()) {
                throw new Mage_Adminhtml_Exception($this->__('Request "%d" is unavailable', $requestId));
            }
            Mage::register('capita_request', $request);
    
            $this->loadLayout();
            $this->_checkConnection();
            $this->_title($this->__('Capita Translations'))
                ->_title($this->__('Request "%s"', $request->getRemoteNo()))
                ->_setActiveMenu(self::MENU_PATH);
            $this->renderLayout();
        }
        catch (Mage_Core_Exception $e) {
            $this->_getSession()->addError($e->getMessage());
            $this->_redirect('*/*');
        }
    }
}
