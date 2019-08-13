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
 * Writes an XML file without breaking memory limits (usually)
 * 
 * @method string getDatatype()
 * @method Capita_TI_Model_Xliff_Writer setDatatype(string $datatype)
 * @method Capita_TI_Model_Xliff_Writer setSourceLanguage(string $language)
 */
class Capita_TI_Model_Xliff_Writer
{

    const XML_NAMESPACE = 'urn:oasis:names:tc:xliff:document:1.2';
    const HTM_NAMESPACE = 'urn:magento:html';
    const CMS_NAMESPACE = 'urn:magento:cms';

    protected $_collections = array();
    protected $_attributes = array();
    protected $_datatype = 'database';
    protected $_sourceLanguage = 'en-GB';
    protected $_autoClear = true;

    /**
     * Each collection becomes a <file> section when output.
     * 
     * Collections are loaded and cleared as they are processed.
     * Keys are visible as file origins.
     * 
     * @param string $key
     * @param Varien_Data_Collection $collection
     * @param string[] $attributes
     * @return Capita_TI_Model_Xliff_Writer $this
     */
    public function addCollection($key, Varien_Data_Collection $collection, $attributes)
    {
        $this->_collections[$key] = $collection;
        $this->_attributes[$key] = $attributes;
        return $this;
    }

    /**
     * Controls clearing after writing to save memory
     * 
     * Default is true.
     * Set to false to prevent collections being cleared and possibly losing data.
     * 
     * @param unknown $flag
     */
    public function setAutoClear($flag)
    {
        $this->_autoClear = (bool) $flag;
    }

    public function setDatatype($datatype)
    {
        $this->_datatype = (string) $datatype;
        return $this;
    }

    public function setSourceLanguage($language)
    {
        $this->_sourceLanguage = strtr($language, '_', '-');
        return $this;
    }

    /**
     * Write a collection of objects to $uri as translateable sources
     * 
     * If $uri is an array the keys should be language codes.
     * 
     * @param array|string $uri
     * @param traversable $entities
     * @param string $group
     * @param string[] $attributes
     */
    public function output($uri)
    {
        $uris = is_array($uri) ? $uri : array($this->_sourceLanguage => $uri);
        $writers = array();
        foreach ($uris as $language => $uri) {
            $xml = new XMLWriter();
            $xml->openUri($uri);
            $xml->startDocument();
            $xml->startElement('xliff');
            $xml->writeAttribute('version', '1.2');
            $xml->writeAttribute('xmlns', self::XML_NAMESPACE);
            $xml->writeAttribute('xmlns:htm', self::HTM_NAMESPACE);
            $xml->writeAttribute('xmlns:cms', self::CMS_NAMESPACE);
            $writers[$language] = $xml;
        }

        foreach ($this->_collections as $key => $collection) {
            $this->_writeCollection($writers, $key, $collection, @$this->_attributes[$key]);
        }

        foreach ($writers as $xml) {
            // end all open elements, easier than remembering how many to do
            while ($xml->endElement());
            // only ever one document to end
            $xml->endDocument();
            $xml->flush();
            // force file to close, just in case
            unset($xml);
        }
    }

    /**
     * Uses a collection once, writing it's objects to potentially several files
     * 
     * @param XMLWriter[] $writers
     * @param string $original
     * @param Varien_Data_Collection $collection
     * @param string[] $attributes
     */
    protected function _writeCollection($writers, $original, Varien_Data_Collection $collection, $attributes)
    {
        /* @var $item Varien_Object */
        foreach ($collection as $id => $item) {
            foreach ($writers as $language => $xml) {
                $xml->startElement('file');
                $xml->writeAttribute('original', $original . '/' . ($item->getId() ? $item->getId() : $id));
                $xml->writeAttribute('source-language', $this->_sourceLanguage);
                $xml->writeAttribute('target-language', $language);
                $xml->writeAttribute('datatype', $this->_datatype);
                $xml->startElement('body');
            }

            // tried $item->toArray() but products still fill stock values that weren't asked for
            $data = array_intersect_key(
                $item->getData(),
                array_fill_keys($attributes, true));
            // do not translate empty values
            $data = array_filter($data, 'strlen');
            if ($data) {
                foreach ($data as $id => $source) {
                    $source = $this->_getInlineXml($source);
                    foreach ($writers as $xml) {
                        $xml->startElement('trans-unit');
                        $xml->writeAttribute('id', $id);
                        $xml->startElement('source');
                        $xml->writeRaw($source);
                        $xml->endElement(); // source
                        $xml->startElement('target');
                        $xml->writeRaw($source);
                        $xml->endElement(); // target
                        $xml->endElement(); // trans-unit
                    }
                }
            }

            foreach ($writers as $xml) {
                $xml->endElement(); // body
                $xml->endElement(); // file
            }
        }
        if ($this->_autoClear) {
            $collection->clear();
        }
    }

    protected function _getInlineXml($source)
    {
        $source = str_replace("\r\n", "\n", $source);
        // use second XMLWriter without a document to produce valid, raw XML
        $xml = new XMLWriter();
        $xml->openMemory();
        // XMLWriter needs an open (albeit temporary) element for text() to work correctly
        $xml->startElement('_');

        // split text into array of basic HTML tags and CMS directives and text
        $parts = preg_split('/(<(?:{{.*?}}|.)*?>|{{.*?}})/', $source, null, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
        // only tag names that were parsed, used for finding closing partners
        $tagStack = array();
        foreach ($parts as $part) {
            if (preg_match('/<(\w+)(.*?)>/', $part, $tag)) {
                list(, $tagName, $attributes) = $tag;
                $attributes = $this->_parseAttributes($attributes);
                switch ($tagName) {
                    case 'area':
                    case 'br':
                    case 'col':
                    case 'hr':
                    case 'img':
                    case 'input':
                    case 'nobr':
                    case 'wbr':
                        // do not push stack since 'x' is an empty type
                        $this->_writeEmptyElement($xml, $tagName, $attributes);
                        break;
                    default:
                        $tagStack[] = $tagName;
                        $this->_writeGroupElement($xml, $tagName, $attributes);
                }
            }
            elseif (preg_match('/<\/(\w+)>/', $part, $tag)) {
                list(, $tagName) = $tag;
                // closing tag without opening tag is ignored
                if (array_search($tagName, $tagStack) === false) continue;
                // pop off as many tags as necessary
                do {
                    $xml->fullEndElement();
                } while ($tagName != array_pop($tagStack));
            }
            elseif (preg_match('/^{{.*}}$/', $part)) {
                // base64 encode all CMS directives whether opening, closing, or empty
                $xml->startElement('ph');
                $xml->writeAttribute('ctype', 'x-cms-directive');
                if (preg_match('/{{var (.*?)}}/', $part, $variable)) {
                    $xml->writeAttribute('equiv-text', $variable[1]);
                }
                $xml->text(base64_encode($part));
                $xml->endElement();
            }
            else {
                $xml->text($part);
            }
        }
        while ($xml->endElement());

        // strip temporary holder element
        return preg_replace('/^<_>(.*)<\/_>$/', '\1', $xml->outputMemory(), 1);
    }

    protected function _parseAttributes($text)
    {
        $attributes = array();
        preg_match_all('/\s*(\w+)\s*=\s*("(?:{{.+?}}|.)*?"|\'(?:{{.+?}}|.)*?\'|\S+?)/', $text, $pairs, PREG_SET_ORDER);
        foreach ($pairs as $pair) {
            list(, $name, $val) = $pair;
            $val = trim($val, '"\'');
            if (preg_match('/{{.*?}}/', $val)) {
                $attributes['cms:'.$name] = base64_encode($val);
            }
            else {
                $attributes['htm:'.$name] = $val;
            }
        }
        // TODO: generate a unique htm:id
        return $attributes;
    }

    protected function _writeEmptyElement(XMLWriter $xml, $tagName, $attributes)
    {
        // translateable attributes
        $subs = array_intersect_key($attributes, array(
            'htm:abbr' => true,
            'htm:alt' => true,
            'htm:content' => true,
            'htm:label' => true,
            'htm:standby' => true,
            'htm:summary' => true,
            'htm:title' => true
        ));
        // ignore empty values
        $subs = array_filter($subs);

        if ($subs) {
            $xml->startElement('ph');
            $xml->writeAttribute('ctype', $this->ctype($tagName));
            foreach (array_diff_key($attributes, $subs) as $name => $value) {
                $xml->writeAttribute($name, $value);
            }
            foreach ($subs as $name => $value) {
                $xml->startElement('sub');
                $xml->writeAttribute('ctype', "x-html-$tagName-".substr($name, 4));
                $xml->text($value);
                $xml->endElement();
            }
            $xml->endElement();
        }
        else {
            $xml->startElement('x');
            $xml->writeAttribute('ctype', $this->ctype($tagName));
            foreach ($attributes as $name => $value) {
                $xml->writeAttribute($name, $value);
            }
            $xml->endElement();
        }
    }

    protected function _writeGroupElement(XMLWriter $xml, $tagName, $attributes)
    {
        $xml->startElement('g');
        $xml->writeAttribute('ctype', $this->ctype($tagName));
        foreach ($attributes as $name => $value) {
            $xml->writeAttribute($name, $value);
        }
        // TODO: use <bpt> when an attribute can be translated with a <sub>
        // this will be hard because it needs a matching <ept> somewhere
    }

    protected function ctype($tagName)
    {
        switch ($tagName) {
            case 'img':
                return 'image';
            case 'hr':
                return 'pb';
            case 'br':
                return'lb';
            case 'b':
            case 'strong':
                return 'bold';
            case 'em':
            case 'i':
                return 'italic';
            case 'u':
                return 'underline';
            case 'a':
                return 'link';
            default:
                return 'x-html-'.$tagName;
        }
    }
}
