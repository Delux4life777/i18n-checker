<?php 
require_once('class.Parser.PHPQuery.php');
require_once('class.Parser.HTML5Lib.php');
require_once('class.Utils.php');

abstract class Parser {
	
	private static $logger;
	protected $markup;
	// HTTP Content-Type Header 
	protected $contentType;
	// TODO: What if no dtd is declared?
	protected $isHTML;
	protected $isHTML5;
	protected $isXHTML;
	protected $isXHTML5;
	// DOMDocument
	protected $document;
	// Meta charset tags
	protected $metaCharsetTags;
	protected $charsetsFromHTML;
	// Meta language tags
	protected $metaLanguageTags;
	protected $langsFromMeta;
	
	public static function init() {
		self::$logger = Logger::getLogger('Parser');
	}
	
	protected function __construct($markup, $contentType) {
		$this->markup = $markup;
		$this->contentType = $contentType;
	}
	
	public static function getParser($markup, $contentType) {
		if (self::is_HTML5($markup)) {
			self::$logger->debug(sprintf("Creating HTML5 parser. Content-type is: %s", $contentType == null ? 'null' : $contentType));
			return new ParserHTML5Lib($markup, $contentType);
		} else
			self::$logger->debug(sprintf("Creating (X)HTML parser. Content-type is: %s", $contentType == null ? 'null' : $contentType));
			return new ParserPHPQuery($markup, $contentType);
	}
	
	private static function is_HTML5($markup) {
		return preg_match("/<!DOCTYPE HTML>/i", substr($markup, '0', Conf::get('perf_head_length'))) == true;
	}
	
	public function isHTML() {
		if ($this->isHTML == null) {
			if ($this->isHTML = preg_match("/<!DOCTYPE [^>]*DTD HTML/i", substr($this->markup, '0', Conf::get('perf_head_length'))) == true) {
				$this->isHTML5 = false;
				$this->isXHTML5 = false;
				$this->isXHTML = false;
			}
		}
		return $this->isHTML;
	}
	
	public function isHTML5() {
		if ($this->isHTML5 == null) {
			//$this->isHTML5 = self::is_HTML5($this->markup);
			// If HTML5 DTD then it can't be HTML or XHTML but still can be XHTML5 (in which case both isHTML5 and isXHTML5 return true) 
			if ($this->isHTML5 = self::is_HTML5($this->markup)) { 
				$this->isHTML = false;
				$this->isXHTML = false;
			}
		}
		return $this->isHTML5;
	}
	
	public function isXHTML() {
		if ($this->isXHTML == null) {
			if ($this->isXHTML = preg_match("/<!DOCTYPE [^>]*DTD XHTML/i", substr($this->markup, '0', Conf::get('perf_head_length'))) == true) {
				$this->isHTML = false;
				$this->isHTML5 = false;
				$this->isXHTML5 = false;
			}
		}
		return $this->isXHTML;
	}
	
	public function isXHTML5() {
		if ($this->isXHTML5 == null) {
		 	if ($this->isHTML5() && Utils::mimeFromContentType($this->contentType) == "application/xhtml+xml") {
		 		$this->isXHTML5 = true;
				$this->isHTML = false;
				$this->isXHTML = false;
		 	} else {
		 		$this->isXHTML5 = false;
		 	}
		}
		return $this->isXHTML5;
	}
	
	public function isXML() {
		return $this->isXHTML() || $this->isXHTML5();
	}
	
	public function mimetypeFromHTTP() {
		return Utils::mimeFromContentType($this->contentType);
	}
	
	public function charsetFromHTTP() {
		return Utils::charsetFromContentType($this->contentType);
	}
	
	public function charsetFromXML() {
		preg_match('@<'.'?xml[^>]+encoding\\s*=\\s*(["|\'])(.*?)\\1@i', substr($this->markup, '0', Conf::get('perf_head_length')), $matches);
		return isset($matches[2]) ? strtoupper($matches[2]) : null;
	}
	
	public function XMLDeclaration() {
		preg_match('/<\?xml[^>]+>/i', substr($this->markup, '0', Conf::get('perf_head_length')), $matches);
		return isset($matches[0]) ? $matches[0] : null;
	}
	
	public function dump($node){
	    return $this->document->saveXML($node);
	}
	
	// Only dumps the opening tag of $node
	public function dumpTag($node){
	    preg_match('/^<[^>]+>/i', $this->document->saveXML($node), $matches);
	    return isset($matches[0]) ? $matches[0] : null;
	}
	
	public function charsetsFromHTML() {
		if ($this->charsetsFromHTML == null)
			$this->parseMeta();
		return array_unique((array) $this->charsetsFromHTML);
	}
	
	public function metaCharsetTags() {
		if ($this->metaCharsetTags == null)
			$this->parseMeta();
		return $this->metaCharsetTags;
	}
	
	protected abstract function parseMeta();
	
	public function langsFromMeta() {
		if ($this->langsFromMeta == null)
			$this->parseMeta();
		return array_unique((array) $this->langsFromMeta);
	}
	
	public function metaLangTags() {
		if ($this->metaLanguageTags == null)
			$this->parseMeta();
		return $this->metaLanguageTags;
	}
	
	public function langFromHTML() {
		// Use getNamedItemNS(null,'lang') so that it does not match xml:lang attributes
		$lang = $this->document->getElementsByTagName('html')->item(0)->attributes->getNamedItemNS(null,'lang');
		return ($lang != null) ? $lang->value : null;
	}
	
	public function xmlLangFromHTML() {
		$lang = $this->document->getElementsByTagName('html')->item(0)->attributes->getNamedItemNS('http://www.w3.org/XML/1998/namespace','lang');
		return ($lang != null) ? $lang->value : null;
	}
	
	public function HTMLTag() {
		return $this->dumpTag($this->document->getElementsByTagName('html')->item(0));
	}
	
	public function dirFromHTML() {
		$dir = $this->document->getElementsByTagName('html')->item(0)->attributes->getNamedItem('dir');
		return ($dir != null) ? strtoupper($dir->value) : null;
	}
	
	public abstract function getNodesWithClass();
	
	public abstract function getNodesWithId();
}

Parser::init();
