<?php
/**
 *	@author Alexander Kostynenko <kostinenko@gmail.com>
 *	@package Tenebras\Utils\Html
 *	@version 0.9
 */
namespace Tenebras\Utils\Html;

class Document
{
	protected $raw;
	protected $url;
	protected $doctype;
	protected $isFetched = false;
	protected $ids = array();
	protected $classes = array();
	protected $selfClosing = array(
		'br', 'meta', 'hr', 'img', 'input', 'link', 'base', 'embed', 'spacer'
	);

	/**
	 * @var Node
	 */
	protected $root;

	public function __construct( $url = null )
	{
		// Flip array to use isset( $this->selfClosing[<TagName>] ) wich is mach faster than in_array()
		$this->selfClosing = array_flip( $this->selfClosing );

		if( $url )
		{
			$this->setUrl( $url );
		}
	}

	public function setRaw( $string )
	{
		$this->raw = $string;
		$this->url = null;
		$this->isFetched = true;
		$this->process();

		return $this;
	}

	public function setUrl( $url )
	{
		$this->url = $url;
		$this->raw = null;
		$this->isFetched = false;
		$this->fetch();

		return $this;
	}
	
	public function getUrl()
	{
		return $this->url;
	}

	public function fetch()
	{
		if( $this->isFetched )
		{
			return true;
		}

		if( !$this->url )
		{
			throw new \Exception("Url is empty");
		}

		$this->raw = file_get_contents( $this->url );
		$this->isFetched = true;

		$this->process();
	}

	public function process()
	{
		if( !$this->raw )
		{
			throw new \Exception('Empty data');
		}

		// Strip useless elements
		$this->raw = preg_replace( array(
			'/<!--.*?-->/', '/<script.*?>.*?<\/script>/', '/<style.*?>.*?<\/style>/'), 
			'', 
			str_replace( array("\t", "\n", "\r"), array(' '), trim( $this->raw ) ) 
		);

		if( $this->raw[0] == '<' && $this->raw[1] == '!' ) 
		{
			$this->doctype = substr( $this->raw, 0, strpos( $this->raw, '>')+1 );
			$this->raw = substr( $this->raw, strlen( $this->doctype ) );
		}

		// Read root tag
		$this->root = new TagNode( $this->raw, $this );
	}

	/**
	 *	@return Node
	 */
	public function &getRoot()
	{
		return $this->root;
	}

	public function getText()
	{
		return $this->root? $this->root->getText(): null;
	}

	public function isSelfClosing( $name )
	{
		return isset( $this->selfClosing[ $name ] );
	}

	public function registerId( $id, Node &$node )
	{
		$this->ids[ $id ] = &$node;
		return $this;
	}

	public function registerClasses( $classDef, Node &$node )
	{
		$classes = $classDef;
		
		if( !is_array( $classDef ) ) 
		{
			$classes = explode(' ', $classDef);
		}
		
		if( is_array( $classes ) && count( $classes ) )
		{
			foreach( $classes as $name )
			{
				if( !isset( $this->classes[ $name ] ) )
				{
					$this->classes[ $name ] = array();
				}

				$this->classes[ $name ][] = &$node;
			}
		}

		return $this;
	}

	/**
	 *	@return Node
	 */
	public function getElementById( $id )
	{
		return isset( $this->ids[ $id ] )? $this->ids[ $id ]: null;
	}

	public function getElementsByTagName( $name )
	{
		return $this->root? $this->root->getElementsByTagName( $name ): null;
	}

	public function getElementsByClass( $class )
	{
		return isset( $this->classes[ $class ] )? $this->classes[ $class ]: null;
	}

	public function getIds()
	{
		return array_keys( $this->ids );
	}

	public function getClasses()
	{
		return array_keys( $this->classes );
	}

	/**
	 * Find element by selector
	 * Selector can be single .className, #elementId, tag name or grouped '#elementId .class a.link-class'
	 * You can pass multiple selectors separating them with comma
	 * 
	 * @param $selector string
	 * 
	 * @return array
	 */
	public function find( $selector )
	{
		$selectors = explode(',', trim( $selector) );
		$current = $found = array();
		
		foreach( $selectors as $strPath )
		{
			$path = explode(' ', trim( $strPath ) );
			
			$elements = array();
			
			if( $path[0][0] == '#' )
			{
				$elements = array( $this->getElementById( substr( $path[0], 1) ) );
			}
			elseif( $path[0][0] == '.' )
			{
				$elements = $this->getElementsByClass( substr( $path[0], 1 ) );
			}
			else
			{
				$elements = $this->getElementsByTagName( $path[0] );
			}
			
			array_shift( $path );
			
			if( count( $path ) && count( $elements ) )
			{
				foreach( $elements as $element )
				{
					$found = $element->findBySelector( $path );
					
					if( count( $found ) )
					{
						$current = array_merge( $current, $found );
					}
				}
			}
			else
			{
				$current = $elements;
			}
		}

		return $current;
	}
	
	public function dump()
	{
		return $this->root? '<ul style="list-style:none;margin:0;padding:0">'.$this->root->dump().'</ul>': '';
	}
}