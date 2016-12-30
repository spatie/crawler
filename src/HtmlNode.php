<?php

namespace Spatie\Crawler;

use DOMElement;

class HtmlNode
{

    /** @var \DOMElement */
    protected $node;

    /**
     * @param \DOMElement $node
     *
     * @return static
     */
    public static function create(DOMElement $node)
    {
        return new static($node);
    }

    public function __construct(DOMElement $node)
    {
        $this->node = $node;
    }

    /**
     * @param void
     *
     * @return \DOMElement
     */
    public function getNode(): DOMElement
    {
        return $this->node;
    }

    /**
     * @param void
     *
     * @return string
     */
    public function getHtml(): string
    {
        return $this->node->ownerDocument->saveHTML($this->node);
    }

    /**
     * @param void
     *
     * @return string
     */
    public function getHtmlAndUpdateHref(string $href): string
    {
        return $this->node->setAttribute('href', $href)->ownerDocument->saveHTML($this->node);
    }

}
