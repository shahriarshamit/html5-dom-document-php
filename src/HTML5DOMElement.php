<?php

/*
 * HTML5 DOMDocument PHP library (extends DOMDocument)
 * https://github.com/ivopetkov/html5-dom-document-php
 * Copyright 2016, Ivo Petkov
 * Free to use under the MIT license.
 */

namespace IvoPetkov;

/**
 * @property-read string $innerHTML The HTML code inside the element
 * @property-read string $outerHTML The HTML code for the element including the code inside
 */
class HTML5DOMElement extends \DOMElement
{

    use \IvoPetkov\HTML5DOMDocument\Internal\QuerySelectors;

    /**
     *
     * @var array
     */
    static private $foundEntitiesCache = [[], []];

    /**
     *
     * @var array
     */
    static private $newObjectsCache = [];

	/*
	 * @var DOMTokenList
	 */
	private $classList = null;

    /**
     * Returns the value for the property specified
     *
     * @param string $name The name of the property
     * @return string The value of the property specified
     * @throws \Exception
     */
    public function __get(string $name)
    {
        if ($name === 'innerHTML') {
            if ($this->firstChild === null) {
                return '';
            }
            $html = $this->ownerDocument->saveHTML($this);
            $nodeName = $this->nodeName;
            return preg_replace('@^<' . $nodeName . '[^>]*>|</' . $nodeName . '>$@', '', $html);
        } elseif ($name === 'outerHTML') {
            if ($this->firstChild === null) {
                $nodeName = $this->nodeName;
                $attributes = $this->getAttributes();
                $result = '<' . $nodeName . '';
                foreach ($attributes as $name => $value) {
                    $result .= ' ' . $name . '="' . htmlentities($value) . '"';
                }
                if (array_search($nodeName, ['area', 'base', 'br', 'col', 'command', 'embed', 'hr', 'img', 'input', 'keygen', 'link', 'meta', 'param', 'source', 'track', 'wbr']) === false) {
                    $result .= '></' . $nodeName . '>';
                } else {
                    $result .= '/>';
                }
                return $result;
            }
            return $this->ownerDocument->saveHTML($this);
        }
        throw new \Exception('Undefined property: HTML5DOMElement::$' . $name);
    }

    /**
     * Sets the value for the property specified
     *
     * @param string $name
     * @param string $value
     * @throws \Exception
     */
    public function __set(string $name, $value)
    {
        if ($name === 'innerHTML') {
            while ($this->hasChildNodes()) {
                $this->removeChild($this->firstChild);
            }
            if (!isset(self::$newObjectsCache['html5domdocument'])) {
                self::$newObjectsCache['html5domdocument'] = new \IvoPetkov\HTML5DOMDocument();
            }
            $tmpDoc = clone(self::$newObjectsCache['html5domdocument']);
            $tmpDoc->loadHTML('<body>' . $value . '</body>');
            foreach ($tmpDoc->getElementsByTagName('body')->item(0)->childNodes as $node) {
                $node = $this->ownerDocument->importNode($node, true);
                $this->appendChild($node);
            }
            return;
        } elseif ($name === 'outerHTML') {
            if (!isset(self::$newObjectsCache['html5domdocument'])) {
                self::$newObjectsCache['html5domdocument'] = new \IvoPetkov\HTML5DOMDocument();
            }
            $tmpDoc = clone(self::$newObjectsCache['html5domdocument']);
            $tmpDoc->loadHTML('<body>' . $value . '</body>');
            foreach ($tmpDoc->getElementsByTagName('body')->item(0)->childNodes as $node) {
                $node = $this->ownerDocument->importNode($node, true);
                $this->parentNode->insertBefore($node, $this);
            }
            $this->parentNode->removeChild($this);
            return;
        }
        throw new \Exception('Undefined property: HTML5DOMElement::$' . $name);
    }

    /**
     * Updates the result value before returning it
     *
     * @param string $value
     * @return string The updated value
     */
    private function updateResult(string $value): string
    {

        $value = str_replace(self::$foundEntitiesCache[0], self::$foundEntitiesCache[1], $value);
        if (strstr($value, 'html5-dom-document-internal-entity') !== false) {
            $search = [];
            $replace = [];
            $matches = [];
            preg_match_all('/html5-dom-document-internal-entity([12])-(.*?)-end/', $value, $matches);
            $matches[0] = array_unique($matches[0]);
            foreach ($matches[0] as $i => $match) {
                $search[] = $match;
                $replace[] = html_entity_decode(($matches[1][$i] === '1' ? '&' : '&#') . $matches[2][$i] . ';');
            }
            $value = str_replace($search, $replace, $value);
            self::$foundEntitiesCache[0] = array_merge(self::$foundEntitiesCache[0], $search);
            self::$foundEntitiesCache[1] = array_merge(self::$foundEntitiesCache[1], $replace);
            unset($search);
            unset($replace);
            unset($matches);
        }
        return $value;
    }

    /**
     * Returns the value for the attribute name specified
     *
     * @param string $name The attribute name
     * @return string
     * @throws \InvalidArgumentException
     */
    public function getAttribute($name): string
    {
        if ($this->attributes->length === 0) { // Performance optimization
            return '';
        }
        $value = parent::getAttribute($name);
        return $value !== '' ? (strstr($value, 'html5-dom-document-internal-entity') !== false ? $this->updateResult($value) : $value) : '';
    }

    /**
     * Returns an array containing all attributes
     *
     * @return array An associative array containing all attributes
     */
    public function getAttributes(): array
    {
        $attributes = [];
        foreach ($this->attributes as $attributeName => $attribute) {
            $value = $attribute->value;
            $attributes[$attributeName] = $value !== '' ? (strstr($value, 'html5-dom-document-internal-entity') !== false ? $this->updateResult($value) : $value) : '';
        }
        return $attributes;
    }

    /**
     * Returns the element outerHTML
     *
     * @return string The element outerHTML
     */
    public function __toString(): string
    {
        return $this->outerHTML;
    }

    /**
     * Returns the first child element matching the selector
     *
     * @param string $selector CSS query selector
     * @return \DOMElement|null The result DOMElement or null if not found
     * @throws \InvalidArgumentException
     */
    public function querySelector(string $selector)
    {
        return $this->internalQuerySelector($selector);
    }

    /**
     * Returns a list of children elements matching the selector
     *
     * @param string $selector CSS query selector
     * @return \DOMNodeList Returns a list of DOMElements matching the criteria
     * @throws \InvalidArgumentException
     */
    public function querySelectorAll(string $selector)
    {
        return $this->internalQuerySelectorAll($selector);
    }

	/**
	 * Return a list of class names.
	 * 
	 * @return DOMTokenList Returns a list of class names.
	 */
	public function classList() : DOMTokenList
	{
		if (is_null($this->classList)) {
			$this->classList = new DOMTokenList($this, 'class');
		}
		return $this->classList;
	}
}
