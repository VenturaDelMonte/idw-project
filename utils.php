<?php

class stdObject {
	public function __construct(array $arguments = array()) {
		if (!empty($arguments)) {
			foreach ($arguments as $property => $argument) {
				$this->{$property} = $argument;
			}
		}
	}

	public function __call($method, $arguments) {
		$arguments = array_merge(array("stdObject" => $this), $arguments); // Note: method argument 0 will always referred to the main class ($this).
		if (isset($this->{$method}) && is_callable($this->{$method})) {
			return call_user_func_array($this->{$method}, $arguments);
		} else {
			throw new Exception("Fatal error: Call to undefined method stdObject::{$method}()");
		}
	}
}

function nodeContent($n, $outer=false) 
{
    $d = new DOMDocument('1.0');
    $b = $d->importNode($n->cloneNode(true),true);
    $d->appendChild($b); $h = $d->saveHTML();
    // remove outter tags
    if (!$outer) $h = substr($h,strpos($h,'>')+1,-(strlen($n->nodeName)+4));
    return $h;
}

function scrapePage($source)
{
	$curl = curl_init();
	$doc = new DOMDocument();
	$tidy = new tidy();

	curl_setopt($curl, CURLOPT_URL, $source);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl, CURLOPT_USERAGENT, "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/40.0.2214.91 Safari/537.36");
	curl_setopt($curl, CURLOPT_FAILONERROR, true);
	curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($curl, CURLOPT_AUTOREFERER, true);
	curl_setopt($curl, CURLOPT_HEADER, false);
	curl_setopt($curl, CURLOPT_TIMEOUT, 10);
	$result = curl_exec($curl);
	
	curl_close($curl);

	$clean = $tidy->repairString($result);
	$doc->strictErrorChecking = false;
	$doc->recover = true;
	$doc->loadHTML($clean);

	return new DOMXPath($doc);
}


class RecursiveDOMIterator implements RecursiveIterator
{
    /**
     * Current Position in DOMNodeList
     * @var Integer
     */
    protected $_position;

    /**
     * The DOMNodeList with all children to iterate over
     * @var DOMNodeList
     */
    protected $_nodeList;

    /**
     * @param DOMNode $domNode
     * @return void
     */
    public function __construct(DOMNode $domNode)
    {
        $this->_position = 0;
        $this->_nodeList = $domNode->childNodes;
    }

    /**
     * Returns the current DOMNode
     * @return DOMNode
     */
    public function current()
    {
        return $this->_nodeList->item($this->_position);
    }

    /**
     * Returns an iterator for the current iterator entry
     * @return RecursiveDOMIterator
     */
    public function getChildren()
    {
        return new self($this->current());
    }

    /**
     * Returns if an iterator can be created for the current entry.
     * @return Boolean
     */
    public function hasChildren()
    {
        return $this->current()->hasChildNodes();
    }

    /**
     * Returns the current position
     * @return Integer
     */
    public function key()
    {
        return $this->_position;
    }

    /**
     * Moves the current position to the next element.
     * @return void
     */
    public function next()
    {
        $this->_position++;
    }

    /**
     * Rewind the Iterator to the first element
     * @return void
     */
    public function rewind()
    {
        $this->_position = 0;
    }

    /**
     * Checks if current position is valid
     * @return Boolean
     */
    public function valid()
    {
        return $this->_position < $this->_nodeList->length;
    }
}

?>