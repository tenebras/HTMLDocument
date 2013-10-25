<?php
/**
 *	@author Alexander Kostynenko <kostinenko@gmail.com>
 *	@package Tenebras\Utils\Html
 *	@version 0.9
 */
namespace Tenebras\Utils\Html;

abstract class Node
{
	protected $raw;
	/**
	 *	@var Document
	 */
	protected $document;
	/**
	 *	@var Node
	 */
	protected $parent;
	/**
	 *	@var array
	 */
	protected $childs = array();
	/**
	 *	@var array
	 */
	protected $attributes = array();

	public function __construct( &$raw, Document &$document, Node &$parent = null )
	{
		$this->raw = &$raw;
		$this->document = &$document;
		$this->parent = &$parent;
		$this->process();
	}

	public function getChild( $index )
	{
		return isset( $this->childs[ $index ] )? $this->childs[ $index ]: null;
	}

	public function getChilds()
	{
		return $this->childs;
	}

	public function getElementsByTagName( $name )
	{
		$list = array();
		
		if( $this instanceof TagNode )
		{
			if( $this->name == $name )
			{
				$list[] = $this;
			}

			if( is_array( $this->childs ) && count( $this->childs ) )
			{
				foreach( $this->childs as &$child )
				{
					if( $child instanceof TagNode && $child->getName() )
					{
						$list = array_merge( $list, $child->getElementsByTagName( $name ) );
					}
				}
			}
		}

		return $list;
	}

	public function attr( $name )
	{
		return is_array( $this->attributes ) && isset( $this->attributes[ $name ] )
			? $this->attributes[ $name ]
			: null;
	}

	abstract function dump();
	abstract function getText();
	abstract function process();
}