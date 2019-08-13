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

/* @var $this Mage_Core_Model_Resource_Setup */

$this->startSetup();

$tableRName = $this->getTable('capita_ti/request');
$this->getConnection()->addColumn($tableRName, 'page_count', array(
    'type' => Varien_Db_Ddl_Table::TYPE_INTEGER,
    'nullable' => false,
    'default' => 0,
    'after' => 'block_count',
    'comment' => 'Number of CMS pages before translation'
));

$tableP = new Varien_Db_Ddl_Table();
$tablePName = $this->getTable('capita_ti/page');
$tableP->setName($tablePName);
$tableP->addColumn('request_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
    'unsigned' => true,
    'nullable' => false
), 'Request entity ID');
$tableP->addColumn('page_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
    'unsigned' => true,
    'nullable' => false
), 'Page entity ID');
$tableP->addForeignKey(
    $this->getFkName($tablePName, 'request_id', $tableRName, 'request_id'),
    'request_id',
    $tableRName,
    'request_id',
    Varien_Db_Ddl_Table::ACTION_CASCADE,
    Varien_Db_Ddl_Table::ACTION_CASCADE);
$tableP->addForeignKey(
    $this->getFkName($tablePName, 'page_id', $this->getTable('cms/page'), 'page_id'),
    'page_id',
    $this->getTable('cms/page'),
    'page_id',
    Varien_Db_Ddl_Table::ACTION_CASCADE,
    Varien_Db_Ddl_Table::ACTION_CASCADE);
$tableP->setComment('CMS pages referenced for each request');
$tableP->setOption('type', 'InnoDB');
$tableP->setOption('charset', 'utf8');
$this->getConnection()->createTable($tableP);

$this->endSetup();
