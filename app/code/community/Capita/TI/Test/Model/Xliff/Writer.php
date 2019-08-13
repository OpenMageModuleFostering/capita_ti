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

class Capita_TI_Test_Model_Xliff_Writer extends EcomDev_PHPUnit_Test_Case
{

    /**
     * @var string
     */
    protected $filename;

    /**
     * @var Capita_TI_Model_Xliff_Writer
     */
    protected $writer;

    /**
     * @var DomDocument
     */
    protected $document;

    protected function setUp()
    {
        $this->filename = tempnam(sys_get_temp_dir(), 'mgxliff');
        touch($this->filename);
        $this->writer = Mage::getModel('capita_ti/xliff_writer');
    }

    protected function tearDown()
    {
        unlink($this->filename);
        unset($this->document);
    }

    protected function assertXPathMatches($expected, $path, $message = null)
    {
        if (!$this->document) {
            $this->document = new DOMDocument();
            $this->document->load($this->filename);
        }
        $xpath = new DOMXPath($this->document);
        $xpath->registerNamespace('x', Capita_TI_Model_Xliff_Writer::XML_NAMESPACE);
        $xpath->registerNamespace('h', Capita_TI_Model_Xliff_Writer::HTM_NAMESPACE);
        $xpath->registerNamespace('c', Capita_TI_Model_Xliff_Writer::CMS_NAMESPACE);
        $this->assertEquals($expected, $xpath->evaluate($path), $message);
    }

    /**
     * @test
     */
    public function xmlHasBasicStructure()
    {
        $collection = new Varien_Data_Collection();
        $collection->addItem(new Varien_Object());
        $this->writer->addCollection('foo', $collection, array());
        $this->writer->output($this->filename);
        $this->assertXPathMatches(1, 'count(/x:xliff)', 'Document starts with "xliff" element');
        $this->assertXPathMatches(1, 'count(/x:xliff/x:file[@original="foo/0"])', 'Document has one "file" element');
        $this->assertXPathMatches(1, 'count(/x:xliff/x:file/x:body)', 'Document has one "body" element');
    }

    /**
     * @test
     */
    public function attributesAreIncluded()
    {
        $cupboard = new Varien_Data_Collection();
        $cupboard->addItem(new Varien_Object(array(
                'type'=>'A cup',
            )));
        $this->writer->addCollection('cupboard', $cupboard, array('type'));
        $this->writer->output($this->filename);
        $this->assertXPathMatches('A cup', 'string(//x:trans-unit[@id="type"]/x:source)', 'Trans unit IDs are attribute key');
    }

    /**
     * @test
     */
    public function attributesAreExcluded()
    {
        $cupboard = new Varien_Data_Collection();
        $cupboard->addItem(new Varien_Object(array(
                'name'=>'World\'s best dad mug',
                'type'=>'A cup',
                'description'=>'A modern classic'
            )));
        $this->writer->addCollection('cupboard', $cupboard, array('type', 'description', 'imaginary'));
        $this->writer->output($this->filename);
        $this->assertXPathMatches(2, 'count(//x:source)', 'Attributes are filterable');
        $this->assertXPathMatches('A cup', 'string(//x:trans-unit[@id="type"]/x:source)');
        $this->assertXPathMatches('A modern classic', 'string(//x:trans-unit[@id="description"]/x:source)');
    }

    /**
     * @test
     */
    public function htmlIsEscaped()
    {
        $cupboard = new Varien_Data_Collection();
        $cupboard->addItem(new Varien_Object(array(
                'type'=>'A <em>big</em> cup',
            )));
        $this->writer->addCollection('cupboard', $cupboard, array('type'));
        $this->writer->output($this->filename);
        $this->assertXPathMatches('A big cup', 'string(//x:source)', 'HTML tags are encoded');
        $this->assertXPathMatches('big', 'string(//x:source/x:g[@ctype="italic"])', 'HTML tags are namespaced');
    }

    /**
     * @test
     */
    public function entitiesAreHtmlEncoded()
    {
        $cupboard = new Varien_Data_Collection();
        $cupboard->addItem(new Varien_Object(array(
                'type'=>'Knife & Fork',
            )));
        $this->writer->addCollection('cupboard', $cupboard, array('type'));
        $this->writer->output($this->filename);
        // if ampersand isn't encoded then an exception is thrown
        $this->assertXPathMatches('Knife & Fork', 'string(//x:source)', 'HTML entities are encoded');
    }
}
