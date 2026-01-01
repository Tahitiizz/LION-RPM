<?php

// Issu de : http://code.google.com/p/sxe/
// SXE is a class extending SimpleXML's SimpleXMLElement.
// It provides the most commonly used DOM functions while preserving SimpleXML's ease of use.

/*

Copyright 2007 The SXE Working Group Initiative

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.

*/
class SXE extends SimpleXMLElement
{
	/**
	* Add a new child at the end of the children
	*
	* @see http://php.net/manual/function.dom-domnode-appendchild.php
	*
	* @param	SimpleXMLElement	$new	New node
	* @return	SXE							The inserted node
	*/
	public function appendChild(SimpleXMLElement $new)
	{
		$tmp = dom_import_simplexml($this);
		$new = $tmp->ownerDocument->importNode(dom_import_simplexml($new), true);

		$node = $tmp->appendChild($new);
		return simplexml_import_dom($node, get_class($this));
	}

	/**
	* Add a new child before a reference node
	*
	* @see http://php.net/manual/function.dom-domnode-insertbefore.php
	*
	* @param	SimpleXMLElement	$new	New node
	* @param	SimpleXMLElement	$ref	Reference node
	* @return	SXE							The inserted node
	*/
	public function insertBefore(SimpleXMLElement $new, SimpleXMLElement $ref = null)
	{
		$tmp = dom_import_simplexml($this);
		$new = $tmp->ownerDocument->importNode(dom_import_simplexml($new), true);

		if (isset($ref))
		{
			$ref = dom_import_simplexml($ref);

			if ($ref->ownerDocument !== $tmp->ownerDocument)
			{
				throw new DOMException('The reference node does not come from the same document as the context node', DOM_WRONG_DOCUMENT_ERR);
			}

			$node = $tmp->insertBefore($new, $ref);
		}
		else
		{
			$node = $tmp->insertBefore($new);
		}

		return simplexml_import_dom($node, get_class($this));
	}

	/**
	* Replace a child
	*
	* @see http://php.net/manual/function.dom-domnode-replacechild.php
	*
	* @param	SimpleXMLElement	$new	New node
	* @param	SimpleXMLElement	$old	Old node
	* @return	SXE							The replaced node
	*/
	public function replaceChild(SimpleXMLElement $new, SimpleXMLElement $old)
	{
		$tmp = dom_import_simplexml($this);
		$old = dom_import_simplexml($old);

		if ($old->ownerDocument !== $tmp->ownerDocument)
		{
			throw new DOMException('The reference node does not come from the same document as the context node', DOM_WRONG_DOCUMENT_ERR);
		}

		$new = $tmp->ownerDocument->importNode(dom_import_simplexml($new), true);
		$node = $tmp->replaceChild($new, $old);

		return simplexml_import_dom($node, get_class($this));
	}

	/**
	* Remove child from list of children
	*
	* @see http://php.net/manual/function.dom-domnode-removechild.php
	*
	* @param	SimpleXMLElement	$old	Old node
	* @return	SXE							The removed node
	*/
	public function removeChild(SimpleXMLElement $old)
	{
		$tmp = dom_import_simplexml($this);
		$old = dom_import_simplexml($old);

		if ($old->ownerDocument !== $tmp->ownerDocument)
		{
			throw new DOMException('The reference node does not come from the same document as the context node', DOM_WRONG_DOCUMENT_ERR);
		}

		$node = $tmp->removeChild($old);
		return simplexml_import_dom($node, get_class($this));
	}

	/**
	* Append raw XML data
	*
	* NOTE: if you append a text node, then its closest ancestor that isn't a text node
	*       will be returned instead
	*
	* @see http://php.net/manual/function.dom-domdocumentfragment-appendxml.php
	*
	* @param	string	$xml	XML to append
	* @return	SXE				The appended node
	*/
	public function appendXML($xml)
	{
		if (!is_string($xml))
		{
			throw new InvalidArgumentException('Argument 1 passed to appendXML() must be a string, ' . gettype($xml) . ' given');
		}

		$tmp = dom_import_simplexml($this);
		$fragment = $tmp->ownerDocument->createDocumentFragment();

		/**
		* Disable error reporting
		*/
		$error_reporting = error_reporting();
		error_reporting(0);

		if (!$fragment->appendXML($xml))
		{
			/**
			* Could not append that XML... but why? We are going to check whether
			* the XML is valid.
			*/
			try
			{
				new SimpleXMLElement($xml);
				$exception = new UnexpectedValueException('DOM could not append XML (reason unknown)');
			}
			catch (Exception $e)
			{
				$exception = new InvalidArgumentException($e->getMessage());
			}

			error_reporting($error_reporting);
			throw $exception;
		}

		$node = $tmp->appendChild($fragment);

		/**
		* Restore error reporting
		*/
		error_reporting($error_reporting);

		/**
		* SimpleXML can't handle text nodes, therefore we return the closest parent
		* that isn't a text node
		*/
		while ($node instanceof DOMText)
		{
			$node = $node->parentNode;
		}

		return simplexml_import_dom($node, get_class($this));
	}

	/**
	* Append text data at the end of the children
	*
	* @param	string	$xml	Text to append
	* @return	SXE				The context node
	*/
	public function appendText($text)
	{
		if (!is_string($text))
		{
			throw new InvalidArgumentException('Argument 1 passed to appendText() must be a string, ' . gettype($xml) . ' given');
		}

		$tmp = dom_import_simplexml($this);
		$doc = $tmp->ownerDocument;
		$node = $tmp->appendChild($doc->importNode($doc->createTextNode($text)));

		return $this;
	}

	/**
	* Search for an element with a certain ID
	*
	* NOTE: in case of multiple elements having the same ID, only the first one is returned.
	* Also, this method does NOT check whether the given ID would be valid value.
	*
	* @param	string	$id		Element ID
	* @return	SXE				The node if found, FALSE otherwise
	*/
	public function getElementById($id)
	{
		if (!is_string($id))
		{
			throw new InvalidArgumentException('Argument 1 passed to getElementsByTagName() must be a string, ' . gettype($id) . ' given');
		}

		$nodes = $this->xpath('//*[@id="' . htmlspecialchars($id) . '"]');

		if (empty($nodes))
		{
			return false;
		}

		return $nodes[0];
	}

	/**
	* Search for all elements with given tag name
	*
	* @param	string	$tag	Tag name
	* @return	array			Array of SXE nodes
	*/
	public function getElementsByTagName($tag)
	{
		if (!is_string($tag))
		{
			throw new InvalidArgumentException('Argument 1 passed to getElementsByTagName() must be a string, ' . gettype($tag) . ' given');
		}

		if (!preg_match('#^(?:[a-z_0-9]+:)?[a-z0-9]+$#iD', $tag))
		{
			throw new InvalidArgumentException('Invalid tag name passed to getElementsByTagName()');
		}

		return $this->xpath('//' . $tag);
	}

	/**
	* Return this node's parent
	*
	* @return	SXE				Parent node if applicable, or this node otherwise
	*/
	public function parentNode()
	{
		$tmp = dom_import_simplexml($this);
		return simplexml_import_dom($tmp->parentNode, get_class($this));
	}

	/**
	* Return the first child of this node
	*
	* @return	SXE				SXE node if applicable, NULL otherwise
	*/
	public function firstChild()
	{
		$tmp = dom_import_simplexml($this);

		if (isset($tmp->firstChild))
		{
			return simplexml_import_dom($tmp->firstChild, get_class($this));
		}
		else
		{
			return null;
		}
	}

	/**
	* Return the last child of this node
	*
	* @return	SXE				SXE node if applicable, NULL otherwise
	*/
	public function lastChild()
	{
		$tmp = dom_import_simplexml($this);

		if (isset($tmp->lastChild))
		{
			return simplexml_import_dom($tmp->lastChild, get_class($this));
		}
		else
		{
			return null;
		}
	}

	/**
	* Return the node immediately preceding this node
	*
	* @return	SXE				SXE node if applicable, NULL otherwise
	*/
	public function previousSibling()
	{
		$tmp = dom_import_simplexml($this);

		if (isset($tmp->previousSibling))
		{
			return simplexml_import_dom($tmp->previousSibling, get_class($this));
		}
		else
		{
			return null;
		}
	}

	/**
	* Return the node immediately following this node
	*
	* @return	SXE				SXE node if applicable, NULL otherwise
	*/
	public function nextSibling()
	{
		$tmp = dom_import_simplexml($this);

		if (isset($tmp->nextSibling))
		{
			return simplexml_import_dom($tmp->nextSibling, get_class($this));
		}
		else
		{
			return null;
		}
	}

	/**
	* Remove all elements matching a XPath expression
	*
	* @param	string	$xpath	XPath expression
	* @return	array			Array of removed nodes on success or FALSE on failure
	*/
	public function removeNodes($xpath)
	{
		if (!is_string($xpath))
		{
			throw new InvalidArgumentException('Argument 1 passed to removeNodes() must be a string, ' . gettype($xpath) . ' given');
		}

		$nodes = $this->_xpath($xpath);

		if (isset($nodes[0]))
		{
			$tmp = dom_import_simplexml($nodes[0]);

			if ($tmp === $tmp->ownerDocument->documentElement)
			{
				unset($nodes[0]);
			}
		}

		$return = array();
		foreach ($nodes as $node)
		{
			$return[] = $node->removeSelf();
		}

		return $return;
	}

	/**
	* Remove all elements matching a XPath expression
	*
	* @param	string				$xpath	XPath expression
	* @param	SimpleXMLElement	$new	Replacement node
	* @return	array						Array of replaced nodes on success or FALSE on failure
	*/
	public function replaceNodes($xpath, SimpleXMLElement $new)
	{
		if (!is_string($xpath))
		{
			throw new InvalidArgumentException('Argument 1 passed to replaceNodes() must be a string, ' . gettype($xpath) . ' given');
		}

		$nodes = array();
		foreach ($this->_xpath($xpath) as $node)
		{
			$nodes[] = $node->replaceSelf($new);
		}

		return $nodes;
	}

	/**
	* Delete all elements matching a XPath expression
	*
	* @param	string	$xpath	XPath expression
	* @return	integer			Number of nodes removed
	*/
	public function deleteNodes($xpath)
	{
		if (!is_string($xpath))
		{
			throw new InvalidArgumentException('Argument 1 passed to deleteNodes() must be a string, ' . gettype($xpath) . ' given');
		}

		$nodes = $this->_xpath($xpath);

		if (isset($nodes[0]))
		{
			$tmp = dom_import_simplexml($nodes[0]);

			if ($tmp === $tmp->ownerDocument->documentElement)
			{
				unset($nodes[0]);
			}
		}

		foreach ($nodes as $node)
		{
			$node->deleteSelf();
		}

		return count($nodes);
	}

	/**
	* Remove this node from document
	*
	* @return	SXE				The removed node
	*/
	public function removeSelf()
	{
		$tmp = dom_import_simplexml($this);

		if ($tmp === $tmp->ownerDocument->documentElement)
		{
			throw new BadMethodCallException('SXE->removeSelf() cannot be used to remove the root node');
		}

		$node = $tmp->parentNode->removeChild($tmp);
		return simplexml_import_dom($node, get_class($this));
	}

	/**
	* Replace this node
	*
	* @param	SimpleXMLElement	$new	New node
	* @return	SXE							Replaced node on success
	*/
	public function replaceSelf(SimpleXMLElement $new)
	{
		$old = dom_import_simplexml($this);
		$new = $old->ownerDocument->importNode(dom_import_simplexml($new), true);

		$node = $old->parentNode->replaceChild($new, $old);
		return simplexml_import_dom($node, get_class($this));
	}

	/**
	* Delete this node from document
	*
	* @return	bool						TRUE on success, FALSE otherwise
	*/
	public function deleteSelf()
	{
		$tmp = dom_import_simplexml($this);

		if ($tmp === $tmp->ownerDocument->documentElement)
		{
			throw new BadMethodCallException('SXE->deleteSelf() cannot be used to delete the root node');
		}

		return (bool) $tmp->parentNode->removeChild($tmp);
	}

	/**
	* Add a new sibling before this node
	*
	* @param	SimpleXMLElement	$new	New node
	* @return	SXE							The inserted node
	*/
	public function insertBeforeSelf(SimpleXMLElement $new)
	{
		$tmp = dom_import_simplexml($this);
		$new = $tmp->ownerDocument->importNode(dom_import_simplexml($new), true);

		/**
		* We don't want to insert anything before the root node
		*/
		if ($tmp === $tmp->ownerDocument->documentElement)
		{
			throw new BadMethodCallException('SXE->insertBeforeSelf() cannot be used to insert nodes outside of the root node');
		}

		$node = $tmp->parentNode->insertBefore($new, $tmp);
		return simplexml_import_dom($node, get_class($this));
	}

	/**
	* Add a new sibling after this node
	*
	* @param	SimpleXMLElement	$new	New node
	* @return	SXE							The inserted node
	*/
	public function insertAfterSelf(SimpleXMLElement $new)
	{
		$tmp = dom_import_simplexml($this);
		$new = $tmp->ownerDocument->importNode(dom_import_simplexml($new), true);

		/**
		* We don't want to insert anything after the root node
		*/
		if ($tmp === $tmp->ownerDocument->documentElement)
		{
			throw new BadMethodCallException('SXE->insertAfterSelf() cannot be used to insert nodes outside of the root node');
		}

		if (isset($tmp->nextSibling))
		{
			$node = $tmp->parentNode->insertBefore($new, $tmp->nextSibling);
		}
		else
		{
			$node = $tmp->parentNode->appendChild($new);
		}

		return simplexml_import_dom($node, get_class($this));
	}

	/**
	* Add a Processing Instruction at the top of the document
	*
	* Processing Instructions are inserted in order, right before the root node.
	* The content of the PI can be passed either as string or as an associative array.
	*
	* @param	string			$target		Target of the processing instruction
	* @param	string|array	$data		Content of the processing instruction
	* @return	bool						TRUE on success, FALSE on failure
	*/
	public function addProcessingInstruction($target, $data = null)
	{
		if (!is_string($target))
		{
			throw new InvalidArgumentException('Argument 1 passed to addProcessingInstruction() must be a string, ' . gettype($xml) . ' given');
		}

		$tmp = dom_import_simplexml($this);
		$doc = $tmp->ownerDocument;

		if (isset($data))
		{
			if (is_array($data))
			{
				$str = '';
				foreach ($data as $k => $v)
				{
					$str .= $k . '="' . htmlspecialchars($v) . '" ';
				}

				$data = substr($str, 0, -1);
			}
			elseif (!is_string($data))
			{
				throw new InvalidArgumentException('Argument 2 passed to addProcessingInstruction() must be an array or a string, ' . gettype($xml) . ' given');
			}

			$pi = $doc->createProcessingInstruction($target, $data);
		}
		else
		{
			$pi = $doc->createProcessingInstruction($target);
		}

		if ($pi === false)
		{
			return false;
		}

		return (bool) $doc->insertBefore($pi, $doc->lastChild);
	}

	/**
	* 
	*
	* @return	void
	*/
	protected function _xpath($xpath)
	{
		if (!libxml_use_internal_errors())
		{
			$restore = true;
			libxml_use_internal_errors(true);
		}

		$nodes = $this->xpath($xpath);

		if (isset($restore))
		{
			libxml_use_internal_errors(false);
		}

		if ($nodes === false)
		{
			throw new InvalidArgumentException('Invalid XPath expression ' . $xpath);
		}

		return $nodes;
	}
}