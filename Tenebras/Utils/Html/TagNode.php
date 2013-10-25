<?php
/**
 *	@author Alexander Kostynenko <kostinenko@gmail.com>
 *	@package Tenebras\Utils\Html
 *	@version 0.9
 */
namespace Tenebras\Utils\Html;

class TagNode extends Node
{
	protected $name;
	protected $classes = array();

	public function getName()
	{
		return $this->name;
	}
	
	public function getText()
	{
		if( $this->childs )
		{
			$text = '';

			foreach( $this->childs as $child )
			{
				$text .= $child->getText();
			}

			return $text;
		}
	}

	public function hasClass( $class )
	{
		return in_array( $class, $this->classes );
	}
	
	public function getChilds()
	{
		return $this->childs;
	}
	
	public function findBySelector( array $path, $mode = null )
	{
		if( !count( $path ) )
		{
			return array();
		}
		
		$fullPath = $path;
		$step = array_shift( $path );

		if( in_array( $step, array(' ', '>', '+') ) )
		{
			$mode = $step;
			$step = array_shift( $path );
		}
		
		$pathLength = count( $fullPath );
		$checkForId = $step[0] == '#';
		$checkForClass = $step[0] == '.';
		$checkForTag = !$checkForId && !$checkForClass;
		$current = array();
		
		if( $checkForId || $checkForClass )
		{
			$step = substr( $step, 1 );
		}
		
		if( ( $checkForTag && $step == $this->getName() ) 
			|| ( $checkForId && $this->attr('id') == $step ) 
			|| ( $checkForClass && $this->hasClass( $step ) ) )
		{
			if( $pathLength == 1 )
			{
				$current[] = $this;
			}
			else
			{
				$current = array_merge( $current, $this->findBySelector( $path, $mode ) );
			}
		}
		
		foreach( $this->childs as $child )
		{
			if( $child instanceof TagNode )
			{
				if( ( $checkForTag && $step == $child->getName() ) 
					|| ( $checkForId && $child->attr('id') == $step ) 
					|| ( $checkForClass && $child->hasClass( $step ) ) )
				{
					if( $pathLength == 1 )
					{
						$current[] = $child;
					}
					else
					{
						$current = array_merge( $current, $child->findBySelector( $path, $mode ) );
					}
				}
				else
				{
					$current = array_merge( $current, $child->findBySelector( $fullPath, $mode ) );
				}
			}
		}
		
		return $current;
	}

	public function find( $selector )
	{
		$selectors = explode(',', trim( $selector) );
		$current = array();
	
		foreach( $selectors as $strPath )
		{
			$current = array_merge( $current, $this->findBySelector( explode(' ', trim( $strPath ) ) ) );
		}

		return $current;
	}

	public function process()
	{
		$tmp = null;
		$position = 0;
		$isAttributeValue = false;
		$lastAttribute = null;

		while( $this->raw[ $position ] != '>' )
		{
			$position++;

			if( ( $this->raw[ $position ] == ' ' && !$isAttributeValue ) || $this->raw[ $position ] == '>' 
				|| ( !$isAttributeValue && $this->raw[ $position ] == '/' ) )
			{
				if( !$tmp )
				{
					continue;
				}

				if( !$this->name )
				{
					$this->name = $tmp;
					$tmp = null;
				}
			}
			elseif( $this->raw[ $position ] == '=' )
			{
				$lastAttribute = $tmp;
				$tmp = null;
			}
			elseif( $this->raw[ $position ] == '"' || $this->raw[ $position ] == "'" )
			{
				if( $isAttributeValue )
				{
					if( !is_array( $this->attributes ) )
					{
						$this->attributes = array();
					}

					$this->attributes[ $lastAttribute ] = $tmp;
					$tmp = null;
					$isAttributeValue = $lastAttribute =false;
				}
				else
				{
					$isAttributeValue = true;
				}
			}
			else
			{
				$tmp .= $this->raw[ $position ];
			}
		}

		$endsWith = $this->raw[ $position -1 ].$this->raw[ $position ];
		$this->raw = substr( $this->raw, $position+1 );

		if( count( $this->attributes ) )
		{
			if( isset( $this->attributes['id'] ) )
			{
				$this->document->registerId( $this->attributes['id'], $this );
			}

			if( isset( $this->attributes['class'] ) )
			{
				$this->classes = explode( ' ', $this->attributes['class'] );
				$this->document->registerClasses( $this->classes, $this );
			}
		}

		// is tag closed
		if( $endsWith == '/>' || $this->document->isSelfClosing( $this->name ) )
		{	
			return;
		}

		$this->processChilds( $position );
	}

	protected function processChilds( $position )
	{
		// limit childs count to 128
		for( $i=0; $i<1024; $i++ )
		{
			//Skip white spaces between tags
			if( $this->raw[0] == ' ' )
			{
				$this->raw = ltrim( $this->raw );
			}

			if( !$this->raw )
			{
				return;
			}

			// is next tag closed, just skip it
			if( $this->raw[0] == '<' && $this->raw[1] == '/')
			{
				$this->raw = substr( $this->raw, strpos( $this->raw, '>' )+1 );
				return;
			}

			if( !is_array( $this->childs ) )
			{
				$this->childs = array();
			}

			// Look for childs
			if( $this->raw[0] == '<' )
			{
				$this->childs[] = new TagNode( $this->raw, $this->document, $this );
			}
			else
			{
				$this->childs[] = new TextNode( $this->raw, $this->document, $this );
			}
		}
	}

	public function dump()
	{
		$str = '<li><strong>'.$this->name.'</strong>'.
			( isset( $this->attributes['id'] )? '#'.$this->attributes['id']:'' ).
			( isset( $this->attributes['class'] )? '.'.$this->attributes['class']:'' );

		if( $this->childs && count( $this->childs ) )
		{
			$str .= '<ul style="list-style:none;border-left:1px dashed gray;">';
			foreach( $this->childs as $child )
			{
				$str .= $child->dump();
			}
			$str .= '</ul>';
		}

		return $str.'</li>';
	}
}